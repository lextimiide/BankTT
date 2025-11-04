<?php

namespace App\Enums;

/**
 * Enumération des codes de statut HTTP utilisés dans l'API
 */
enum HttpStatusCode: int
{
    // 2xx Success
    case OK = 200;
    case CREATED = 201;
    case ACCEPTED = 202;
    case NO_CONTENT = 204;

    // 3xx Redirection
    case MOVED_PERMANENTLY = 301;
    case FOUND = 302;
    case NOT_MODIFIED = 304;

    // 4xx Client Errors
    case BAD_REQUEST = 400;
    case UNAUTHORIZED = 401;
    case FORBIDDEN = 403;
    case NOT_FOUND = 404;
    case METHOD_NOT_ALLOWED = 405;
    case CONFLICT = 409;
    case UNPROCESSABLE_ENTITY = 422;
    case TOO_MANY_REQUESTS = 429;

    // 5xx Server Errors
    case INTERNAL_SERVER_ERROR = 500;
    case NOT_IMPLEMENTED = 501;
    case BAD_GATEWAY = 502;
    case SERVICE_UNAVAILABLE = 503;
    case GATEWAY_TIMEOUT = 504;

    /**
     * Retourne le message par défaut associé au code de statut
     */
    public function getDefaultMessage(): string
    {
        return match($this) {
            self::OK => 'Opération réussie',
            self::CREATED => 'Ressource créée avec succès',
            self::ACCEPTED => 'Requête acceptée',
            self::NO_CONTENT => 'Aucun contenu',
            self::MOVED_PERMANENTLY => 'Déplacé de manière permanente',
            self::FOUND => 'Trouvé',
            self::NOT_MODIFIED => 'Non modifié',
            self::BAD_REQUEST => 'Requête invalide',
            self::UNAUTHORIZED => 'Non autorisé',
            self::FORBIDDEN => 'Accès interdit',
            self::NOT_FOUND => 'Ressource non trouvée',
            self::METHOD_NOT_ALLOWED => 'Méthode non autorisée',
            self::CONFLICT => 'Conflit',
            self::UNPROCESSABLE_ENTITY => 'Entité non traitable',
            self::TOO_MANY_REQUESTS => 'Trop de requêtes',
            self::INTERNAL_SERVER_ERROR => 'Erreur interne du serveur',
            self::NOT_IMPLEMENTED => 'Non implémenté',
            self::BAD_GATEWAY => 'Mauvaise passerelle',
            self::SERVICE_UNAVAILABLE => 'Service indisponible',
            self::GATEWAY_TIMEOUT => 'Délai d\'attente de la passerelle dépassé',
        };
    }

    /**
     * Vérifie si le code de statut indique un succès
     */
    public function isSuccess(): bool
    {
        return $this->value >= 200 && $this->value < 300;
    }

    /**
     * Vérifie si le code de statut indique une erreur client
     */
    public function isClientError(): bool
    {
        return $this->value >= 400 && $this->value < 500;
    }

    /**
     * Vérifie si le code de statut indique une erreur serveur
     */
    public function isServerError(): bool
    {
        return $this->value >= 500 && $this->value < 600;
    }
}