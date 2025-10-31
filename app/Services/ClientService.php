<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\ApiException;
use App\Models\Client;
use Illuminate\Database\Eloquent\Collection;

class ClientService
{
    /**
     * Rechercher un client par téléphone ou NCI
     *
     * @param array $searchParams
     * @return Client
     * @throws ApiException
     */
    public function searchClient(array $searchParams): Client
    {
        $query = Client::query();

        // Recherche par téléphone
        if (isset($searchParams['telephone']) && !empty($searchParams['telephone'])) {
            $query->where('telephone', $searchParams['telephone']);
        }

        // Recherche par NCI
        if (isset($searchParams['nci']) && !empty($searchParams['nci'])) {
            $query->where('nci', $searchParams['nci']);
        }

        $client = $query->first();

        if (!$client) {
            throw new ApiException('Client non trouvé', 404);
        }

        return $client;
    }

    /**
     * Récupérer un client par son ID
     *
     * @param string $id
     * @return Client
     * @throws ApiException
     */
    public function getClientById(string $id): Client
    {
        $client = Client::find($id);

        if (!$client) {
            throw new ApiException('Client non trouvé', 404);
        }

        return $client;
    }

    /**
     * Récupérer tous les clients avec pagination
     *
     * @param array $filters
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getClients(array $filters = []): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        $query = Client::query();

        // Appliquer les filtres
        if (isset($filters['statut'])) {
            $query->where('statut', $filters['statut']);
        }

        if (isset($filters['search']) && !empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('titulaire', 'LIKE', "%{$search}%")
                  ->orWhere('email', 'LIKE', "%{$search}%")
                  ->orWhere('telephone', 'LIKE', "%{$search}%");
            });
        }

        return $query->paginate($filters['limit'] ?? 15);
    }

    /**
     * Créer un nouveau client
     *
     * @param array $data
     * @return Client
     * @throws ApiException
     */
    public function createClient(array $data): Client
    {
        try {
            // Générer un mot de passe temporaire sécurisé
            $password = $this->generateSecurePassword();

            // Générer un code unique pour la première connexion
            $code = $this->generateUniqueCode();

            $client = Client::create([
                'titulaire' => $data['titulaire'],
                'email' => $data['email'],
                'telephone' => $data['telephone'],
                'adresse' => $data['adresse'],
                'nci' => $data['nci'] ?? null,
                'password' => bcrypt($password),
                'code' => $code,
                'statut' => 'actif',
                'email_verified_at' => null,
            ]);

            \Log::info('Nouveau client créé avec succès', [
                'client_id' => $client->id,
                'client_email' => $client->email,
                'temporary_password_generated' => true,
                'activation_code_generated' => true,
            ]);

            return $client;

        } catch (\Exception $e) {
            \Log::error('Erreur lors de la création du client', [
                'error' => $e->getMessage(),
                'client_data' => $data,
                'trace' => $e->getTraceAsString()
            ]);

            throw new ApiException(
                'Erreur lors de la création du client: ' . $e->getMessage(),
                500
            );
        }
    }

    /**
     * Mettre à jour un client
     *
     * @param string $id
     * @param array $data
     * @return Client
     * @throws ApiException
     */
    public function updateClient(string $id, array $data): Client
    {
        $client = $this->getClientById($id);

        try {
            $client->update($data);

            \Log::info('Client mis à jour avec succès', [
                'client_id' => $client->id,
                'updated_fields' => array_keys($data)
            ]);

            return $client;

        } catch (\Exception $e) {
            \Log::error('Erreur lors de la mise à jour du client', [
                'client_id' => $id,
                'error' => $e->getMessage(),
                'data' => $data,
                'trace' => $e->getTraceAsString()
            ]);

            throw new ApiException(
                'Erreur lors de la mise à jour du client: ' . $e->getMessage(),
                500
            );
        }
    }

    /**
     * Supprimer un client (soft delete)
     *
     * @param string $id
     * @return Client
     * @throws ApiException
     */
    public function deleteClient(string $id): Client
    {
        $client = $this->getClientById($id);

        // Vérifier que le client n'a pas de comptes actifs
        if ($client->comptes()->where('statut', 'actif')->exists()) {
            throw new ApiException(
                'Impossible de supprimer un client qui possède des comptes actifs',
                400
            );
        }

        try {
            $client->delete();

            \Log::info('Client supprimé avec succès', [
                'client_id' => $client->id,
                'deleted_at' => $client->deleted_at
            ]);

            return $client;

        } catch (\Exception $e) {
            \Log::error('Erreur lors de la suppression du client', [
                'client_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            throw new ApiException(
                'Erreur lors de la suppression du client: ' . $e->getMessage(),
                500
            );
        }
    }

    /**
     * Génère un mot de passe sécurisé
     */
    private function generateSecurePassword(): string
    {
        $uppercase = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $lowercase = 'abcdefghijklmnopqrstuvwxyz';
        $numbers = '0123456789';
        $specialChars = '!@#$%^&*';

        $password = '';
        $password .= $uppercase[random_int(0, strlen($uppercase) - 1)];
        $password .= $lowercase[random_int(0, strlen($lowercase) - 1)];
        $password .= $numbers[random_int(0, strlen($numbers) - 1)];
        $password .= $specialChars[random_int(0, strlen($specialChars) - 1)];

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
            $code = strtoupper(\Illuminate\Support\Str::random(8));
        } while (Client::where('code', $code)->exists());

        return $code;
    }
}