<?php

namespace App\Contracts;

use App\Models\Client;

interface EmailServiceInterface
{
    /**
     * Envoie un email de bienvenue avec les identifiants de connexion
     *
     * @param Client $client Le client destinataire
     * @param string $password Le mot de passe temporaire
     * @param string $numeroCompte Le numéro de compte
     * @return bool True si l'envoi a réussi
     */
    public function sendWelcomeEmail(Client $client, string $password, string $numeroCompte): bool;
}