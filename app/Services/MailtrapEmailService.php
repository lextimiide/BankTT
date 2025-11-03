<?php

namespace App\Services;

use App\Contracts\EmailServiceInterface;
use App\Models\Client;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class MailtrapEmailService implements EmailServiceInterface
{
    /**
     * Envoie un email de bienvenue avec les identifiants de connexion via Mailtrap
     *
     * @param Client $client Le client destinataire
     * @param string $password Le mot de passe temporaire
     * @param string $numeroCompte Le numéro de compte
     * @return bool True si l'envoi a réussi
     */
    public function sendWelcomeEmail(Client $client, string $password, string $numeroCompte): bool
    {
        try {
            Log::info('Tentative d\'envoi d\'email de bienvenue via Mailtrap', [
                'client_id' => $client->id,
                'client_email' => $client->email,
                'numero_compte' => $numeroCompte
            ]);

            // Utilisation du système de mail Laravel (Mailtrap configuré dans .env)
            Mail::raw($this->buildEmailContent($client, $password, $numeroCompte), function ($message) use ($client) {
                $message->to($client->email, $client->titulaire)
                        ->subject('Bienvenue sur BankTT - Vos identifiants de connexion')
                        ->from(config('mail.from.address'), config('mail.from.name'));
            });

            Log::info('Email de bienvenue envoyé avec succès via Mailtrap', [
                'client_id' => $client->id,
                'client_email' => $client->email
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('Erreur lors de l\'envoi d\'email via Mailtrap', [
                'client_id' => $client->id,
                'client_email' => $client->email,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return false;
        }
    }

    /**
     * Construit le contenu de l'email
     */
    private function buildEmailContent(Client $client, string $password, string $numeroCompte): string
    {
        return <<<EOT
Bienvenue sur BankTT, {$client->titulaire} !

Votre compte bancaire a été créé avec succès.

Détails de votre compte :
- Numéro de compte : {$numeroCompte}
- Email : {$client->email}
- Mot de passe temporaire : {$password}

IMPORTANT :
- Ce mot de passe est temporaire et expire dans 24 heures
- Veuillez vous connecter et changer votre mot de passe immédiatement
- Conservez ces informations en lieu sûr

Pour vous connecter : [Lien vers l'application]

Si vous n'avez pas demandé la création de ce compte, veuillez nous contacter immédiatement.

Cordialement,
L'équipe BankTT
EOT;
    }
}