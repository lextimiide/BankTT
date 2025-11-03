<?php

namespace App\Policies;

use App\Models\Admin;
use App\Models\Client;
use App\Models\Compte;
use Illuminate\Auth\Access\Response;

class ComptePolicy
{
    /**
     * Determine whether the user can view any comptes.
     */
    public function viewAny($user): bool
    {
        return true; // Tous les utilisateurs authentifiés peuvent lister les comptes
    }

    /**
     * Determine whether the user can view the compte.
     */
    public function view($user, Compte $compte): bool
    {
        // Admin peut voir tous les comptes
        if ($user instanceof Admin) {
            return true;
        }

        // Client ne peut voir que ses propres comptes
        if ($user instanceof Client) {
            return $compte->client_id === $user->id;
        }

        return false;
    }

    /**
     * Determine whether the user can create comptes.
     */
    public function create($user): bool
    {
        // Seuls les admins peuvent créer des comptes
        return $user instanceof Admin;
    }

    /**
     * Determine whether the user can update the compte.
     */
    public function update($user, Compte $compte): bool
    {
        // Seuls les admins peuvent modifier les comptes
        return $user instanceof Admin;
    }

    /**
     * Determine whether the user can delete the compte.
     */
    public function delete($user, Compte $compte): bool
    {
        // Seuls les admins peuvent supprimer des comptes
        return $user instanceof Admin && $compte->isActif();
    }

    /**
     * Determine whether the user can block the compte.
     */
    public function block($user, Compte $compte): bool
    {
        // Seuls les admins peuvent bloquer des comptes épargne actifs
        return $user instanceof Admin &&
               $compte->type === 'epargne' &&
               $compte->isActif();
    }

    /**
     * Determine whether the user can unblock the compte.
     */
    public function unblock($user, Compte $compte): bool
    {
        // Seuls les admins peuvent débloquer des comptes bloqués
        return $user instanceof Admin && $compte->statut === 'bloque';
    }

    /**
     * Determine whether the user can restore the compte.
     */
    public function restore($user, Compte $compte): bool
    {
        return $user instanceof Admin;
    }

    /**
     * Determine whether the user can permanently delete the compte.
     */
    public function forceDelete($user, Compte $compte): bool
    {
        return $user instanceof Admin;
    }
}
