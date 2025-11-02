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

        // Appliquer les permissions selon le rôle de l'utilisateur
        $this->applyRoleBasedFilters($query, $request);

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
     * Applique les filtres basés sur les rôles utilisateur
     */
    private function applyRoleBasedFilters($query, Request $request): void
    {
        $user = $request->auth_user; // Récupéré depuis le middleware

        if (!$user) {
            // Si pas d'utilisateur authentifié, ne rien retourner
            $query->whereRaw('1 = 0');
            return;
        }

        // Admin peut voir tous les comptes
        if ($user instanceof \App\Models\Admin) {
            // Pas de restriction pour l'admin
            return;
        }

        // Client ne peut voir que ses propres comptes
        if ($user instanceof \App\Models\Client) {
            $query->where('client_id', $user->id);
        }
    }

    /**
     * Applique les filtres à la requête
     */
    private function applyFilters($query, Request $request): void
    {
        // Filtre par type - uniquement épargne et chèque
        if ($request->has('type') && in_array($request->type, ['cheque', 'epargne'])) {
            $query->where('type', $request->type);
        } else {
            // Par défaut, exclure les comptes courants
            $query->whereIn('type', ['cheque', 'epargne']);
        }

        // Filtre par statut - exclure automatiquement les comptes bloqués et fermés
        if ($request->has('statut') && in_array($request->statut, ['actif', 'inactif'])) {
            $query->where('statut', $request->statut);
        } else {
            // Par défaut, exclure les comptes bloqués et fermés
            $query->whereNotIn('statut', ['bloque', 'ferme']);
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
     * Récupère un compte par son ID avec vérification des permissions
     * Pour les comptes épargne archivés, récupère depuis la base Neon
     */
    public function getCompteById(string $id, $user = null): Compte
    {
        $compte = Compte::with('client')->find($id);

        // Si le compte n'est pas trouvé dans la base principale et que c'est un compte épargne,
        // essayer de le récupérer depuis la base Neon
        if (!$compte) {
            $compte = $this->getArchivedCompteFromNeon($id);
        }

        if (!$compte) {
            throw new ApiException('Compte non trouvé', 404);
        }

        // Vérifier les permissions
        if ($user) {
            $this->checkAccountAccessPermission($compte, $user);
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
     * Vérifie les permissions d'accès à un compte
     */
    private function checkAccountAccessPermission(Compte $compte, $user): void
    {
        // Admin peut accéder à tous les comptes
        if ($user instanceof \App\Models\Admin) {
            return;
        }

        // Client ne peut accéder qu'à ses propres comptes
        if ($user instanceof \App\Models\Client) {
            if ($compte->client_id !== $user->id) {
                throw new ApiException('Accès refusé. Vous ne pouvez accéder qu\'à vos propres comptes.', 403);
            }
            return;
        }

        // Utilisateur inconnu
        throw new ApiException('Type d\'utilisateur non autorisé.', 403);
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
                $client = $this->findOrCreateClient($data);

                // Étape 2: Créer le compte
                $compte = Compte::create([
                    'numero_compte' => $data['numero_compte'] ?? $this->generateNumeroCompte(),
                    'type' => $data['type_compte'],
                    'solde_initial' => $data['solde'],
                    'devise' => 'FCFA', // Devise par défaut
                    'statut' => 'actif',
                    'client_id' => $client->id,
                ]);

                // Étape 3: Log de l'opération
                \Log::info('Compte créé avec succès', [
                    'compte_id' => $compte->id,
                    'numero_compte' => $compte->numero_compte,
                    'client_id' => $client->id,
                    'client_email' => $client->email,
                    'montant_initial' => $data['solde'],
                    'type_compte' => $data['type_compte']
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
    private function findOrCreateClient(array $data): Client
    {
        // Étape 1: Si un client_id est fourni, vérifier qu'il existe
        if (isset($data['client_id']) && !empty($data['client_id'])) {
            $client = Client::find($data['client_id']);
            if (!$client) {
                throw new ApiException('Client avec ID ' . $data['client_id'] . ' non trouvé', 404);
            }

            \Log::info('Client existant trouvé par ID', [
                'client_id' => $client->id,
                'client_email' => $client->email
            ]);

            return $client;
        }

        // Étape 2: Si des données client sont fournies, créer ou trouver le client
        if (isset($data['client']) && is_array($data['client'])) {
            $clientData = $data['client'];

            // Chercher un client existant par email ou téléphone
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

            // Créer un nouveau client
            \Log::info('Création d\'un nouveau client', [
                'email' => $clientData['email'],
                'telephone' => $clientData['telephone']
            ]);

            return $this->createNewClient($clientData);
        }

        // Étape 3: Aucun client fourni - créer un client automatiquement
        \Log::info('Aucun client fourni, création automatique d\'un client');
        return $this->createAutoGeneratedClient();
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
     * Crée un client avec des données auto-générées
     *
     * @throws ApiException
     */
    private function createAutoGeneratedClient(): Client
    {
        try {
            // Générer des données uniques pour le client
            $uniqueId = strtoupper(Str::random(6));
            $clientData = [
                'titulaire' => 'Client Auto-' . $uniqueId,
                'email' => 'auto.client.' . strtolower($uniqueId) . '@banktt.local',
                'telephone' => '+221' . random_int(700000000, 799999999),
                'adresse' => 'Adresse auto-générée - ' . now()->format('Y-m-d H:i:s'),
                'nci' => $this->generateUniqueNCI(),
            ];

            // Générer un mot de passe temporaire sécurisé
            $password = $this->generateSecurePassword();

            // Générer un code unique pour la première connexion
            $code = $this->generateUniqueCode();

            $client = Client::create([
                'titulaire' => $clientData['titulaire'],
                'email' => $clientData['email'],
                'telephone' => $clientData['telephone'],
                'adresse' => $clientData['adresse'],
                'nci' => $clientData['nci'],
                'password' => bcrypt($password), // Hash du mot de passe
                'code' => $code, // Code pour première connexion
                'statut' => 'actif',
                'email_verified_at' => null, // À vérifier lors de la première connexion
            ]);

            // Log détaillé de la création du client auto-généré
            \Log::info('Client auto-généré créé avec succès', [
                'client_id' => $client->id,
                'client_email' => $client->email,
                'client_telephone' => $client->telephone,
                'auto_generated' => true,
                'temporary_password_generated' => true,
                'activation_code_generated' => true,
                'code_length' => strlen($code)
            ]);

            // TODO: Implémenter l'envoi réel d'email et SMS
            // Pour l'instant, on log les informations sensibles (à supprimer en production)
            \Log::warning('Informations temporaires du client auto-généré (à supprimer en production)', [
                'client_email' => $client->email,
                'temporary_password' => $password,
                'activation_code' => $code
            ]);

            return $client;

        } catch (\Exception $e) {
            \Log::error('Erreur lors de la création du client auto-généré', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            throw new ApiException(
                'Erreur lors de la création automatique du client: ' . $e->getMessage(),
                500
            );
        }
    }

    /**
     * Génère un numéro NCI unique
     */
    private function generateUniqueNCI(): string
    {
        do {
            // Format sénégalais: 13 chiffres commençant par année de naissance
            $year = random_int(1950, 2005);
            $remaining = str_pad((string) random_int(100000000, 999999999), 9, '0', STR_PAD_LEFT);
            $nci = $year . $remaining;
        } while (Client::where('nci', $nci)->exists());

        return $nci;
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
     * Bloque un compte épargne avec une durée déterminée
     * Seuls les comptes épargne actifs peuvent être bloqués
     *
     * @throws ApiException
     */
    public function blockCompte(string $compteId, array $data): Compte
    {
        return DB::transaction(function () use ($compteId, $data) {
            try {
                // Récupérer le compte avec le client
                $compte = Compte::with('client')->find($compteId);
                if (!$compte) {
                    throw new ApiException('Compte non trouvé', 404);
                }

                // Vérifier que c'est un compte épargne
                if ($compte->type !== 'epargne') {
                    throw new ApiException(
                        'Seuls les comptes épargne peuvent être bloqués. Type actuel: ' . $compte->type,
                        400
                    );
                }

                // Vérifier que le compte est actif
                if ($compte->statut !== 'actif') {
                    throw new ApiException(
                        'Seuls les comptes actifs peuvent être bloqués. Statut actuel: ' . $compte->statut,
                        400
                    );
                }

                // Calculer les dates de blocage
                $dateDebutBlocage = isset($data['dateDebutBlocage'])
                    ? \Carbon\Carbon::parse($data['dateDebutBlocage'])
                    : now();
                $dateFinBlocage = $this->calculateDateFinBlocage($dateDebutBlocage, $data['duree'], $data['unite']);

                // Sauvegarder les données avant modification pour le logging
                $originalData = [
                    'compte' => $compte->toArray(),
                    'client' => $compte->client->toArray()
                ];

                // Mettre à jour le compte
                $compte->update([
                    'statut' => 'bloque',
                    'motif_blocage' => $data['motif'],
                    'date_debut_blocage' => $dateDebutBlocage,
                    'date_fin_blocage' => $dateFinBlocage,
                ]);

                // Log de l'opération de blocage
                \Log::info('Compte bloqué avec succès', [
                    'compte_id' => $compte->id,
                    'numero_compte' => $compte->numero_compte,
                    'client_id' => $compte->client->id,
                    'motif_blocage' => $data['motif'],
                    'date_debut_blocage' => $dateDebutBlocage,
                    'date_fin_blocage' => $dateFinBlocage,
                    'duree' => $data['duree'],
                    'unite' => $data['unite'],
                    'ancienne_valeur' => $originalData
                ]);

                return $compte->load('client');

            } catch (\Exception $e) {
                \Log::error('Erreur lors du blocage du compte', [
                    'compte_id' => $compteId,
                    'data' => $data,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);

                throw new ApiException(
                    'Erreur lors du blocage du compte: ' . $e->getMessage(),
                    500
                );
            }
        });
    }

    /**
     * Débloque un compte épargne bloqué
     * Seuls les comptes bloqués peuvent être débloqués
     *
     * @throws ApiException
     */
    public function unblockCompte(string $compteId, array $data): Compte
    {
        return DB::transaction(function () use ($compteId, $data) {
            try {
                // Récupérer le compte avec le client
                $compte = Compte::with('client')->find($compteId);
                if (!$compte) {
                    throw new ApiException('Compte non trouvé', 404);
                }

                // Vérifier que le compte est bloqué
                if ($compte->statut !== 'bloque') {
                    throw new ApiException(
                        'Seuls les comptes bloqués peuvent être débloqués. Statut actuel: ' . $compte->statut,
                        400
                    );
                }

                // Sauvegarder les données avant modification pour le logging
                $originalData = [
                    'compte' => $compte->toArray(),
                    'client' => $compte->client->toArray()
                ];

                // Mettre à jour le compte
                $compte->update([
                    'statut' => 'actif',
                    'motif_deblocage' => $data['motif'],
                    'date_deblocage' => now(),
                ]);

                // Log de l'opération de déblocage
                \Log::info('Compte débloqué avec succès', [
                    'compte_id' => $compte->id,
                    'numero_compte' => $compte->numero_compte,
                    'client_id' => $compte->client->id,
                    'motif_deblocage' => $data['motif'],
                    'date_deblocage' => $compte->date_deblocage,
                    'ancienne_valeur' => $originalData
                ]);

                return $compte->load('client');

            } catch (\Exception $e) {
                \Log::error('Erreur lors du déblocage du compte', [
                    'compte_id' => $compteId,
                    'data' => $data,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);

                throw new ApiException(
                    'Erreur lors du déblocage du compte: ' . $e->getMessage(),
                    500
                );
            }
        });
    }

    /**
     * Calcule la date de fin de blocage en fonction de la durée et de l'unité
     */
    private function calculateDateFinBlocage(\Carbon\Carbon $dateDebut, int $duree, string $unite): \Carbon\Carbon
    {
        return match ($unite) {
            'jours' => $dateDebut->copy()->addDays($duree),
            'mois' => $dateDebut->copy()->addMonths($duree),
            'annees' => $dateDebut->copy()->addYears($duree),
            default => throw new \InvalidArgumentException("Unité de temps invalide: {$unite}")
        };
    }

    /**
     * Récupère un compte épargne archivé depuis la base Neon
     */
    private function getArchivedCompteFromNeon(string $id): ?Compte
    {
        try {
            // Utiliser la connexion Neon pour récupérer le compte archivé
            $neonCompte = DB::connection('neon')
                ->table('comptes')
                ->where('id', $id)
                ->where('type', 'epargne')
                ->where('statut', 'archive')
                ->whereNotNull('archived_at')
                ->first();

            if ($neonCompte) {
                // Créer une instance Compte depuis les données Neon
                $compte = new Compte();
                $compte->fill((array) $neonCompte);

                // Charger le client depuis Neon si nécessaire
                if ($neonCompte->client_id) {
                    $neonClient = DB::connection('neon')
                        ->table('clients')
                        ->where('id', $neonCompte->client_id)
                        ->first();

                    if ($neonClient) {
                        $client = new \App\Models\Client();
                        $client->fill((array) $neonClient);
                        $compte->setRelation('client', $client);
                    }
                }

                \Log::info('Compte archivé récupéré depuis la base Neon', [
                    'compte_id' => $compte->id,
                    'numero_compte' => $compte->numero_compte,
                    'type' => $compte->type
                ]);

                return $compte;
            }
        } catch (\Exception $e) {
            \Log::warning('Erreur lors de la récupération du compte archivé depuis Neon', [
                'compte_id' => $id,
                'error' => $e->getMessage()
            ]);
        }

        return null;
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