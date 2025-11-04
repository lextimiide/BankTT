<?php

namespace App\Enums;

/**
 * Enumération des codes d'erreur API utilisés dans l'application
 */
enum ApiErrorCode: string
{
    // Erreurs générales
    case VALIDATION_ERROR = 'VALIDATION_ERROR';
    case UNAUTHORIZED = 'UNAUTHORIZED';
    case FORBIDDEN = 'FORBIDDEN';
    case NOT_FOUND = 'NOT_FOUND';
    case INTERNAL_ERROR = 'INTERNAL_ERROR';

    // Erreurs spécifiques aux comptes
    case COMPTE_NOT_FOUND = 'COMPTE_NOT_FOUND';
    case COMPTE_ALREADY_EXISTS = 'COMPTE_ALREADY_EXISTS';
    case COMPTE_INSUFFICIENT_BALANCE = 'COMPTE_INSUFFICIENT_BALANCE';
    case COMPTE_BLOCKED = 'COMPTE_BLOCKED';
    case COMPTE_INACTIVE = 'COMPTE_INACTIVE';

    // Erreurs spécifiques aux clients
    case CLIENT_NOT_FOUND = 'CLIENT_NOT_FOUND';
    case CLIENT_ALREADY_EXISTS = 'CLIENT_ALREADY_EXISTS';
    case CLIENT_INVALID_DATA = 'CLIENT_INVALID_DATA';

    // Erreurs spécifiques aux transactions
    case TRANSACTION_FAILED = 'TRANSACTION_FAILED';
    case TRANSACTION_INVALID_AMOUNT = 'TRANSACTION_INVALID_AMOUNT';
    case TRANSACTION_INSUFFICIENT_FUNDS = 'TRANSACTION_INSUFFICIENT_FUNDS';

    // Erreurs d'authentification
    case INVALID_CREDENTIALS = 'INVALID_CREDENTIALS';
    case TOKEN_EXPIRED = 'TOKEN_EXPIRED';
    case TOKEN_INVALID = 'TOKEN_INVALID';

    /**
     * Retourne le message par défaut associé au code d'erreur
     */
    public function getDefaultMessage(): string
    {
        return match($this) {
            self::VALIDATION_ERROR => 'Erreur de validation des données',
            self::UNAUTHORIZED => 'Accès non autorisé',
            self::FORBIDDEN => 'Accès interdit',
            self::NOT_FOUND => 'Ressource non trouvée',
            self::INTERNAL_ERROR => 'Erreur interne du serveur',

            self::COMPTE_NOT_FOUND => 'Compte non trouvé',
            self::COMPTE_ALREADY_EXISTS => 'Ce compte existe déjà',
            self::COMPTE_INSUFFICIENT_BALANCE => 'Solde insuffisant',
            self::COMPTE_BLOCKED => 'Compte bloqué',
            self::COMPTE_INACTIVE => 'Compte inactif',

            self::CLIENT_NOT_FOUND => 'Client non trouvé',
            self::CLIENT_ALREADY_EXISTS => 'Ce client existe déjà',
            self::CLIENT_INVALID_DATA => 'Données client invalides',

            self::TRANSACTION_FAILED => 'Transaction échouée',
            self::TRANSACTION_INVALID_AMOUNT => 'Montant de transaction invalide',
            self::TRANSACTION_INSUFFICIENT_FUNDS => 'Fonds insuffisants pour cette transaction',

            self::INVALID_CREDENTIALS => 'Identifiants invalides',
            self::TOKEN_EXPIRED => 'Token expiré',
            self::TOKEN_INVALID => 'Token invalide',
        };
    }

    /**
     * Retourne le code HTTP associé par défaut
     */
    public function getDefaultHttpStatus(): int
    {
        return match($this) {
            self::VALIDATION_ERROR => 422,
            self::UNAUTHORIZED => 401,
            self::FORBIDDEN => 403,
            self::NOT_FOUND, self::COMPTE_NOT_FOUND, self::CLIENT_NOT_FOUND => 404,
            self::INTERNAL_ERROR => 500,

            self::COMPTE_ALREADY_EXISTS, self::CLIENT_ALREADY_EXISTS => 409,
            self::COMPTE_INSUFFICIENT_BALANCE, self::TRANSACTION_INSUFFICIENT_FUNDS => 400,
            self::COMPTE_BLOCKED, self::COMPTE_INACTIVE => 403,
            self::CLIENT_INVALID_DATA => 422,

            self::TRANSACTION_FAILED, self::TRANSACTION_INVALID_AMOUNT => 400,

            self::INVALID_CREDENTIALS => 401,
            self::TOKEN_EXPIRED, self::TOKEN_INVALID => 401,
        };
    }
}