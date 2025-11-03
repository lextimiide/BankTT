<?php

namespace App\Policies;

use App\Models\Admin;
use App\Models\Client;
use Illuminate\Auth\Access\Response;

class ClientPolicy
{
    /**
     * Determine whether the user can view any clients.
     */
    public function viewAny($user): bool
    {
        // Seuls les admins peuvent lister tous les clients
        return $user instanceof Admin;
    }

    /**
     * Determine whether the user can view the client.
     */
    public function view($user, Client $client): bool
    {
        // Admin peut voir tous les clients
        if ($user instanceof Admin) {
            return true;
        }

        // Client ne peut voir que son propre profil
        if ($user instanceof Client) {
            return $client->id === $user->id;
        }

        return false;
    }

    /**
     * Determine whether the user can create clients.
     */
    public function create($user): bool
    {
        // Seuls les admins peuvent créer des clients
        return $user instanceof Admin;
    }

    /**
     * Determine whether the user can update the client.
     */
    public function update($user, Client $client): bool
    {
        // Admin peut modifier tous les clients
        if ($user instanceof Admin) {
            return true;
        }

        // Client ne peut modifier que son propre profil
        if ($user instanceof Client) {
            return $client->id === $user->id;
        }

        return false;
    }

    /**
     * Determine whether the user can delete the client.
     */
    public function delete($user, Client $client): bool
    {
        // Seuls les admins peuvent supprimer des clients
        // Et seulement si le client n'a pas de comptes actifs
        return $user instanceof Admin &&
               !$client->comptes()->where('statut', 'actif')->exists();
    }

    /**
     * Determine whether the user can search clients.
     */
    public function search($user): bool
    {
        // Seuls les admins peuvent rechercher des clients
        return $user instanceof Admin;
    }

    /**
     * Determine whether the user can restore the client.
     */
    public function restore($user, Client $client): bool
    {
        return $user instanceof Admin;
    }

    /**
     * Determine whether the user can permanently delete the client.
     */
    public function forceDelete($user, Client $client): bool
    {
        return $user instanceof Admin;
    }
}
