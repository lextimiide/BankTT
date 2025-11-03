<?php

namespace App\Services;

use App\Contracts\EmailServiceInterface;
use App\Models\Client;
use Illuminate\Support\Facades\Log;
use SendinBlue\Client\Api\TransactionalEmailsApi;
use SendinBlue\Client\Configuration;
use SendinBlue\Client\Model\SendSmtpEmail;
use GuzzleHttp\Client as GuzzleClient;

class BrevoEmailService implements EmailServiceInterface
{
    private TransactionalEmailsApi $apiInstance;

    public function __construct()
    {
        // Configuration de l'API Brevo
        $config = Configuration::getDefaultConfiguration()
            ->setApiKey('api-key', config('services.brevo.api_key'));

        $this->apiInstance = new TransactionalEmailsApi(
            new GuzzleClient(),
            $config
        );
    }

    /**
     * Envoie un email de bienvenue avec les identifiants de connexion via Brevo
     *
     * @param Client $client Le client destinataire
     * @param string $password Le mot de passe temporaire
     * @param string $numeroCompte Le numéro de compte
     * @return bool True si l'envoi a réussi
     */
    public function sendWelcomeEmail(Client $client, string $password, string $numeroCompte): bool
    {
        try {
            Log::info('Tentative d\'envoi d\'email de bienvenue via Brevo', [
                'client_id' => $client->id,
                'client_email' => $client->email,
                'numero_compte' => $numeroCompte
            ]);

            // Création de l'email
            $sendSmtpEmail = new SendSmtpEmail([
                'subject' => 'Bienvenue sur BankTT - Vos identifiants de connexion',
                'sender' => [
                    'name' => config('mail.from.name', 'BankTT'),
                    'email' => config('mail.from.address', 'noreply@banktt.local')
                ],
                'to' => [[
                    'email' => $client->email,
                    'name' => $client->titulaire
                ]],
                'htmlContent' => $this->buildHtmlEmailContent($client, $password, $numeroCompte),
                'textContent' => $this->buildTextEmailContent($client, $password, $numeroCompte)
            ]);

            // Envoi de l'email
            $result = $this->apiInstance->sendTransacEmail($sendSmtpEmail);

            Log::info('Email de bienvenue envoyé avec succès via Brevo', [
                'client_id' => $client->id,
                'client_email' => $client->email,
                'brevo_message_id' => $result->getMessageId()
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('Erreur lors de l\'envoi d\'email via Brevo', [
                'client_id' => $client->id,
                'client_email' => $client->email,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return false;
        }
    }

    /**
     * Construit le contenu HTML de l'email
     */
    private function buildHtmlEmailContent(Client $client, string $password, string $numeroCompte): string
    {
        return <<<HTML
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bienvenue sur BankTT</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background-color: #007bff; color: white; padding: 20px; text-align: center; }
        .content { padding: 20px; background-color: #f9f9f9; }
        .credentials { background-color: white; padding: 15px; border-left: 4px solid #007bff; margin: 20px 0; }
        .warning { background-color: #fff3cd; border: 1px solid #ffeaa7; padding: 15px; margin: 20px 0; }
        .footer { text-align: center; padding: 20px; font-size: 12px; color: #666; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🏦 Bienvenue sur BankTT</h1>
            <p>Votre compte bancaire a été créé avec succès</p>
        </div>

        <div class="content">
            <p>Bonjour <strong>{$client->titulaire}</strong>,</p>

            <p>Nous sommes ravis de vous accueillir sur BankTT ! Votre compte bancaire a été créé avec succès.</p>

            <div class="credentials">
                <h3>📋 Vos identifiants de connexion</h3>
                <p><strong>Numéro de compte :</strong> {$numeroCompte}</p>
                <p><strong>Email :</strong> {$client->email}</p>
                <p><strong>Mot de passe temporaire :</strong> <code>{$password}</code></p>
            </div>

            <div class="warning">
                <h4>⚠️ Important</h4>
                <ul>
                    <li>Ce mot de passe est temporaire et expire dans 24 heures</li>
                    <li>Veuillez vous connecter et changer votre mot de passe immédiatement</li>
                    <li>Conservez ces informations en lieu sûr</li>
                </ul>
            </div>

            <p>Pour vous connecter à votre espace client : <a href="#">Accéder à mon compte</a></p>

            <p>Si vous n'avez pas demandé la création de ce compte, veuillez nous contacter immédiatement.</p>

            <p>Cordialement,<br>L'équipe BankTT</p>
        </div>

        <div class="footer">
            <p>Cet email a été envoyé automatiquement, merci de ne pas y répondre.</p>
            <p>&copy; 2024 BankTT - Tous droits réservés</p>
        </div>
    </div>
</body>
</html>
HTML;
    }

    /**
     * Construit le contenu texte de l'email (fallback)
     */
    private function buildTextEmailContent(Client $client, string $password, string $numeroCompte): string
    {
        return <<<TEXT
Bienvenue sur BankTT, {$client->titulaire} !

Votre compte bancaire a été créé avec succès.

DETAILS DE VOTRE COMPTE :
- Numero de compte : {$numeroCompte}
- Email : {$client->email}
- Mot de passe temporaire : {$password}

IMPORTANT :
- Ce mot de passe est temporaire et expire dans 24 heures
- Veuillez vous connecter et changer votre mot de passe immediatement
- Conservez ces informations en lieu sur

Pour vous connecter : [Lien vers l'application]

Si vous n'avez pas demande la creation de ce compte, veuillez nous contacter immediatement.

Cordialement,
L'equipe BankTT
TEXT;
    }
}