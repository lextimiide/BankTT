<?php

namespace App\Swagger;

use OpenApi\Annotations as OA;

/**
 * @OA\Info(
 *     title="Banque API",
 *     version="1.0.0",
 *     description="Documentation de l'API REST du projet Banque",
 *     @OA\Contact(
 *         name="Abdoulaye Diome",
 *         email="abdoulaye.diome@example.com"
 *     )
 * )
 * @OA\Server(
 *     url="https://bankt-1.onrender.com/api/v1",
 *     description="Serveur de production Render"
 * )
 * @OA\SecurityScheme(
 *     securityScheme="bearerAuth",
 *     type="http",
 *     scheme="bearer",
 *     bearerFormat="JWT",
 *     description="Authentification JWT - Utilisez le format: Bearer {token} ou les cookies d'authentification"
 * )
 * @OA\SecurityScheme(
 *     securityScheme="cookieAuth",
 *     type="apiKey",
 *     in="cookie",
 *     name="access_token",
 *     description="Authentification via cookie - Le token est automatiquement inclus dans les requêtes"
 * )
 * @OA\Tag(
 *     name="Comptes",
 *     description="Gestion des comptes bancaires"
 * )
 * @OA\Tag(
 *     name="Clients",
 *     description="Gestion des clients"
 * )
 * @OA\Tag(
 *     name="Transactions",
 *     description="Gestion des transactions"
 * )
 * @OA\Tag(
 *     name="Authentification",
 *     description="Authentification et gestion des tokens"
 * )
 * @OA\Schema(
 *     schema="Error",
 *     type="object",
 *     title="Error Response",
 *     description="Format de réponse d'erreur standard",
 *     @OA\Property(property="success", type="boolean", example=false),
 *     @OA\Property(property="message", type="string", example="Une erreur est survenue"),
 *     @OA\Property(property="errorCode", type="string", example="VALIDATION_ERROR"),
 *     @OA\Property(property="errors", type="object", nullable=true),
 *     @OA\Property(property="timestamp", type="string", format="date-time"),
 *     @OA\Property(property="path", type="string"),
 *     @OA\Property(property="traceId", type="string")
 * )
 * @OA\Schema(
 *     schema="Success",
 *     type="object",
 *     title="Success Response",
 *     description="Format de réponse de succès standard",
 *     @OA\Property(property="success", type="boolean", example=true),
 *     @OA\Property(property="message", type="string", example="Opération réussie"),
 *     @OA\Property(property="data", type="object", nullable=true),
 *     @OA\Property(property="meta", type="object", nullable=true),
 *     @OA\Property(property="timestamp", type="string", format="date-time"),
 *     @OA\Property(property="path", type="string"),
 *     @OA\Property(property="traceId", type="string")
 * )
 * @OA\Schema(
 *     schema="Client",
 *     type="object",
 *     title="Client",
 *     description="Client model",
 *     @OA\Property(property="id", type="string", format="uuid", description="Client UUID"),
 *     @OA\Property(property="titulaire", type="string", description="Nom complet du titulaire du compte"),
 *     @OA\Property(property="nci", type="string", description="Numéro de carte d'identité nationale"),
 *     @OA\Property(property="email", type="string", format="email", description="Adresse email du client"),
 *     @OA\Property(property="telephone", type="string", description="Numéro de téléphone"),
 *     @OA\Property(property="adresse", type="string", description="Adresse complète du client"),
 *     @OA\Property(property="statut", type="string", enum={"actif", "inactif", "suspendu"}, description="Statut du client"),
 *     @OA\Property(property="email_verified_at", type="string", format="date-time", description="Date de vérification de l'email"),
 *     @OA\Property(property="created_at", type="string", format="date-time", description="Date de création"),
 *     @OA\Property(property="updated_at", type="string", format="date-time", description="Date de dernière modification")
 * )
 * @OA\Schema(
 *     schema="Compte",
 *     type="object",
 *     title="Compte",
 *     description="Modèle de compte bancaire",
 *     @OA\Property(property="id", type="string", format="uuid", description="UUID du compte"),
 *     @OA\Property(property="numero_compte", type="string", description="Numéro unique du compte (auto-généré)"),
 *     @OA\Property(property="type", type="string", enum={"cheque", "epargne", "courant"}, description="Type de compte"),
 *     @OA\Property(property="solde_initial", type="number", format="decimal", description="Solde initial du compte"),
 *     @OA\Property(property="solde", type="number", format="decimal", description="Solde actuel calculé dynamiquement"),
 *     @OA\Property(property="devise", type="string", description="Devise du compte (FCFA, EUR, USD)"),
 *     @OA\Property(property="statut", type="string", enum={"actif", "inactif", "bloque", "ferme"}, description="Statut du compte"),
 *     @OA\Property(property="client_id", type="string", format="uuid", description="UUID du client propriétaire"),
 *     @OA\Property(property="client", ref="#/components/schemas/Client", description="Informations du client"),
 *     @OA\Property(property="created_at", type="string", format="date-time", description="Date de création"),
 *     @OA\Property(property="updated_at", type="string", format="date-time", description="Date de dernière modification")
 * )
 * @OA\Schema(
 *     schema="LoginRequest",
 *     type="object",
 *     title="Login Request",
 *     description="Données de connexion - Utilisez les comptes de test suivants : Admin: admin@banque.com / password123, Client: hawa.wane@example.com / password123",
 *     required={"email", "password"},
 *     @OA\Property(property="email", type="string", format="email", description="Adresse email de l'utilisateur", example="admin@banque.com"),
 *     @OA\Property(property="password", type="string", description="Mot de passe", example="password123")
 * )
 * @OA\Schema(
 *     schema="LoginResponse",
 *     type="object",
 *     title="Login Response",
 *     description="Réponse de connexion",
 *     @OA\Property(property="success", type="boolean", example=true),
 *     @OA\Property(property="message", type="string", example="Connexion réussie"),
 *     @OA\Property(property="data", type="object",
 *         @OA\Property(property="user", type="object",
 *             @OA\Property(property="id", type="string", description="ID de l'utilisateur"),
 *             @OA\Property(property="nom", type="string", description="Nom (pour Admin)"),
 *             @OA\Property(property="prenom", type="string", description="Prénom (pour Admin)"),
 *             @OA\Property(property="titulaire", type="string", description="Nom complet (pour Client)"),
 *             @OA\Property(property="email", type="string", description="Email"),
 *             @OA\Property(property="role", type="string", enum={"admin", "client"}, description="Rôle de l'utilisateur")
 *         ),
 *         @OA\Property(property="token_type", type="string", example="Bearer")
 *     ),
 *     @OA\Property(property="timestamp", type="string", format="date-time"),
 *     @OA\Property(property="path", type="string"),
 *     @OA\Property(property="traceId", type="string")
 * )
 */
class OpenApi
{
    // Cette classe sert uniquement à contenir les annotations Swagger
}