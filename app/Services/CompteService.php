<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Client;
use App\Models\Compte;
use App\Exceptions\ApiException;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CompteService
{
    /**
     * Récupère la liste paginée des comptes avec filtrage
     */
    public function getComptes(Request $request): LengthAwarePaginator
    {
        $query = Compte::with('client');

        // Appliquer les filtres
        $this->applyFilters($query, $request);

        // Appliquer le tri
        $this->applySorting($query, $request);

        // Pagination
        $perPage = $request->get('limit', 10);
        $perPage = min($perPage, 100); // Limite maximale

        return $query->paginate($perPage);
    }

    /**
     * Applique les filtres à la requête
     */
    private function applyFilters($query, Request $request): void
    {
        // Filtre par type
        if ($request->has('type') && in_array($request->type, ['cheque', 'epargne', 'courant'])) {
            $query->where('type', $request->type);
        }

        // Filtre par statut
        if ($request->has('statut') && in_array($request->statut, ['actif', 'inactif', 'bloque', 'ferme'])) {
            $query->where('statut', $request->statut);
        }

        // Recherche par titulaire ou numéro de compte
        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('numero_compte', 'LIKE', "%{$search}%")
                  ->orWhereHas('client', function ($clientQuery) use ($search) {
                      $clientQuery->where('titulaire', 'LIKE', "%{$search}%");
                  });
            });
        }
    }

    /**
     * Applique le tri à la requête
     */
    private function applySorting($query, Request $request): void
    {
        $sortBy = $request->get('sort', 'created_at');
        $sortOrder = $request->get('order', 'desc');

        // Champs de tri autorisés
        $allowedSortFields = [
            'dateCreation' => 'created_at',
            'solde' => 'solde_initial', // Tri par solde initial car solde est calculé
            'titulaire' => 'client.titulaire',
            'numero_compte' => 'numero_compte',
            'type' => 'type',
            'statut' => 'statut',
        ];

        if (array_key_exists($sortBy, $allowedSortFields)) {
            $actualField = $allowedSortFields[$sortBy];

            if (str_contains($actualField, '.')) {
                // Tri sur relation (client.titulaire)
                [$relation, $field] = explode('.', $actualField);
                $query->join('clients', 'comptes.client_id', '=', 'clients.id')
                      ->orderBy("clients.{$field}", $sortOrder)
                      ->select('comptes.*');
            } else {
                $query->orderBy($actualField, $sortOrder);
            }
        } else {
            // Tri par défaut
            $query->orderBy('created_at', 'desc');
        }
    }

    /**
     * Récupère un compte par son ID
     */
    public function getCompteById(string $id): Compte
    {
        $compte = Compte::with('client')->find($id);

        if (!$compte) {
            throw new ApiException('Compte non trouvé', 404);
        }

        return $compte;
    }

    /**
     * Récupère un compte par son numéro
     */
    public function getCompteByNumero(string $numero): Compte
    {
        $compte = Compte::numero($numero)->first();

        if (!$compte) {
            throw new ApiException('Compte non trouvé', 404);
        }

        return $compte->load('client');
    }

    /**
     * Récupère les comptes d'un client
     */
    public function getComptesByClient(string $telephone): Collection
    {
        return Compte::client($telephone)
            ->with('client')
            ->get();
    }

    /**
     * Calcule le solde d'un compte
     */
    public function calculateSolde(Compte $compte): float
    {
        return $compte->getSoldeAttribute();
    }

    /**
     * Vérifie si un compte peut effectuer une transaction
     */
    public function canPerformTransaction(Compte $compte, float $montant, string $type): bool
    {
        if (!$compte->isActif()) {
            return false;
        }

        // Pour les retraits, vérifier le solde disponible
        if (in_array($type, ['retrait', 'virement'])) {
            $soldeActuel = $this->calculateSolde($compte);
            return $soldeActuel >= $montant;
        }

        return true;
    }

    /**
     * Crée un nouveau compte bancaire avec gestion transactionnelle
     *
     * @throws ApiException
     */
    public function createCompte(array $data): Compte
    {
        return DB::transaction(function () use ($data) {
            try {
                // Étape 1: Vérifier/créer le client
                $client = $this->findOrCreateClient($data['client']);

                // Étape 2: Créer le compte
                $compte = Compte::create([
                    'numero_compte' => $this->generateNumeroCompte(),
                    'type' => $data['type'],
                    'solde_initial' => $data['soldeInitial'],
                    'devise' => $data['devise'],
                    'statut' => 'actif',
                    'client_id' => $client->id,
                ]);

                // Étape 3: Log de l'opération
                \Log::info('Compte créé avec succès', [
                    'compte_id' => $compte->id,
                    'numero_compte' => $compte->numero_compte,
                    'client_id' => $client->id,
                    'client_email' => $client->email,
                    'montant_initial' => $data['soldeInitial'],
                    'devise' => $data['devise']
                ]);

                return $compte->load('client');

            } catch (\Exception $e) {
                // En cas d'erreur, la transaction sera automatiquement rollbackée
                \Log::error('Erreur lors de la création du compte', [
                    'error' => $e->getMessage(),
                    'data' => $data,
                    'trace' => $e->getTraceAsString()
                ]);

                throw new ApiException(
                    'Erreur lors de la création du compte: ' . $e->getMessage(),
                    500
                );
            }
        });
    }

    /**
     * Trouve un client existant ou en crée un nouveau avec gestion transactionnelle
     *
     * @throws ApiException
     */
    private function findOrCreateClient(array $clientData): Client
    {
        // Étape 1: Si un ID de client est fourni, vérifier qu'il existe
        if (isset($clientData['id']) && !empty($clientData['id'])) {
            $client = Client::find($clientData['id']);
            if (!$client) {
                throw new ApiException('Client avec ID ' . $clientData['id'] . ' non trouvé', 404);
            }

            \Log::info('Client existant trouvé par ID', [
                'client_id' => $client->id,
                'client_email' => $client->email
            ]);

            return $client;
        }

        // Étape 2: Chercher un client existant par email ou téléphone
        $existingClient = Client::where('email', $clientData['email'])
            ->orWhere('telephone', $clientData['telephone'])
            ->first();

        if ($existingClient) {
            \Log::info('Client existant trouvé par email/téléphone', [
                'client_id' => $existingClient->id,
                'client_email' => $existingClient->email,
                'client_telephone' => $existingClient->telephone
            ]);

            return $existingClient;
        }

        // Étape 3: Créer un nouveau client
        \Log::info('Création d\'un nouveau client', [
            'email' => $clientData['email'],
            'telephone' => $clientData['telephone']
        ]);

        return $this->createNewClient($clientData);
    }

    /**
     * Crée un nouveau client avec génération de mot de passe et code
     *
     * @throws ApiException
     */
    private function createNewClient(array $clientData): Client
    {
        try {
            // Générer un mot de passe temporaire sécurisé
            $password = $this->generateSecurePassword();

            // Générer un code unique pour la première connexion
            $code = $this->generateUniqueCode();

            $client = Client::create([
                'titulaire' => $clientData['titulaire'],
                'email' => $clientData['email'],
                'telephone' => $clientData['telephone'],
                'adresse' => $clientData['adresse'],
                'nci' => $clientData['nci'] ?? null,
                'password' => bcrypt($password), // Hash du mot de passe
                'code' => $code, // Code pour première connexion
                'statut' => 'actif',
                'email_verified_at' => null, // À vérifier lors de la première connexion
            ]);

            // Log détaillé de la création du client
            \Log::info('Nouveau client créé avec succès', [
                'client_id' => $client->id,
                'client_email' => $client->email,
                'client_telephone' => $client->telephone,
                'temporary_password_generated' => true,
                'activation_code_generated' => true,
                'code_length' => strlen($code)
            ]);

            // TODO: Implémenter l'envoi réel d'email et SMS
            // Pour l'instant, on log les informations sensibles (à supprimer en production)
            \Log::warning('Informations temporaires du client (à supprimer en production)', [
                'client_email' => $client->email,
                'temporary_password' => $password,
                'activation_code' => $code
            ]);

            return $client;

        } catch (\Exception $e) {
            \Log::error('Erreur lors de la création du client', [
                'error' => $e->getMessage(),
                'client_data' => $clientData,
                'trace' => $e->getTraceAsString()
            ]);

            throw new ApiException(
                'Erreur lors de la création du client: ' . $e->getMessage(),
                500
            );
        }
    }

    /**
     * Génère un numéro de compte unique
     */
    private function generateNumeroCompte(): string
    {
        do {
            // Format: CB + AnnéeMois (YYMM) + 8 chiffres aléatoires
            $numero = 'CB' . now()->format('ym') . str_pad((string) random_int(10000000, 99999999), 8, '0', STR_PAD_LEFT);
        } while (Compte::where('numero_compte', $numero)->exists());

        return $numero;
    }

    /**
     * Génère un mot de passe sécurisé
     */
    private function generateSecurePassword(): string
    {
        // Combinaison de lettres majuscules, minuscules, chiffres et caractères spéciaux
        $uppercase = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $lowercase = 'abcdefghijklmnopqrstuvwxyz';
        $numbers = '0123456789';
        $specialChars = '!@#$%^&*';

        $password = '';
        $password .= $uppercase[random_int(0, strlen($uppercase) - 1)];
        $password .= $lowercase[random_int(0, strlen($lowercase) - 1)];
        $password .= $numbers[random_int(0, strlen($numbers) - 1)];
        $password .= $specialChars[random_int(0, strlen($specialChars) - 1)];

        // Compléter avec des caractères aléatoires jusqu'à 12 caractères
        $allChars = $uppercase . $lowercase . $numbers . $specialChars;
        for ($i = 4; $i < 12; $i++) {
            $password .= $allChars[random_int(0, strlen($allChars) - 1)];
        }

        return str_shuffle($password);
    }

    /**
     * Génère un code unique pour la première connexion
     */
    private function generateUniqueCode(): string
    {
        do {
            $code = strtoupper(Str::random(8)); // Code alphanumérique de 8 caractères
        } while (Client::where('code', $code)->exists());

        return $code;
    }

    /**
     * Met à jour les informations d'un compte bancaire
     *
     * @throws ApiException
     */
    public function updateCompte(string $compteId, array $data): Compte
    {
        return DB::transaction(function () use ($compteId, $data) {
            try {
                // Récupérer le compte avec le client
                $compte = Compte::with('client')->find($compteId);
                if (!$compte) {
                    throw new ApiException('Compte non trouvé', 404);
                }

                $originalData = [
                    'compte' => $compte->toArray(),
                    'client' => $compte->client->toArray()
                ];

                $updatedFields = [];

                // Mise à jour du titulaire du compte (si fourni)
                if (isset($data['titulaire'])) {
                    $compte->client->titulaire = $data['titulaire'];
                    $compte->client->save();
                    $updatedFields[] = 'titulaire';
                }

                // Mise à jour des informations client (si fournies)
                if (isset($data['informationsClient']) && is_array($data['informationsClient'])) {
                    $clientData = $data['informationsClient'];

                    foreach ($clientData as $field => $value) {
                        if ($compte->client->$field !== $value) {
                            $compte->client->$field = $value;
                            $updatedFields[] = "client.{$field}";
                        }
                    }

                    $compte->client->save();
                }

                // Recharger les relations pour obtenir les données mises à jour
                $compte->load('client');

                // Log de l'opération de mise à jour
                \Log::info('Compte mis à jour avec succès', [
                    'compte_id' => $compte->id,
                    'numero_compte' => $compte->numero_compte,
                    'client_id' => $compte->client->id,
                    'champs_modifies' => $updatedFields,
                    'ancienne_valeur' => $originalData,
                    'nouvelle_valeur' => [
                        'compte' => $compte->toArray(),
                        'client' => $compte->client->toArray()
                    ]
                ]);

                return $compte;

            } catch (\Exception $e) {
                \Log::error('Erreur lors de la mise à jour du compte', [
                    'compte_id' => $compteId,
                    'data' => $data,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);

                throw new ApiException(
                    'Erreur lors de la mise à jour du compte: ' . $e->getMessage(),
                    500
                );
            }
        });
    }
    /**
     * Supprime un compte bancaire (soft delete)
     * Seuls les comptes actifs peuvent être supprimés
     *
     * @throws ApiException
     */
    public function deleteCompte(string $compteId): Compte
    {
        return DB::transaction(function () use ($compteId) {
            try {
                // Récupérer le compte avec le client
                $compte = Compte::with('client')->find($compteId);
                if (!$compte) {
                    throw new ApiException('Compte non trouvé', 404);
                }

                // Vérifier que le compte est actif
                if (!$compte->isActif()) {
                    throw new ApiException(
                        'Seuls les comptes actifs peuvent être supprimés. Statut actuel: ' . $compte->statut,
                        400
                    );
                }

                // Sauvegarder les données avant suppression pour le logging
                $originalData = [
                    'compte' => $compte->toArray(),
                    'client' => $compte->client->toArray()
                ];

                // Effectuer le soft delete
                $compte->delete();

                // Recharger le compte pour obtenir les données mises à jour (avec deleted_at)
                $compte->refresh();

                // Log de l'opération de suppression
                \Log::info('Compte supprimé avec succès (soft delete)', [
                    'compte_id' => $compte->id,
                    'numero_compte' => $compte->numero_compte,
                    'client_id' => $compte->client->id,
                    'statut_final' => $compte->statut,
                    'deleted_at' => $compte->deleted_at,
                    'ancienne_valeur' => $originalData
                ]);

                return $compte;

            } catch (\Exception $e) {
                \Log::error('Erreur lors de la suppression du compte', [
                    'compte_id' => $compteId,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);

                throw new ApiException(
                    'Erreur lors de la suppression du compte: ' . $e->getMessage(),
                    500
                );
            }
        });
    }
}