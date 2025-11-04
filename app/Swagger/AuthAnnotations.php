<?php

namespace App\Swagger;

use OpenApi\Annotations as OA;

/**
 * @OA\Post(
 *     path="/auth/login",
 *     tags={"Authentification"},
 *     summary="Connexion utilisateur",
 *     description="Authentifie un utilisateur (Admin ou Client) et retourne les tokens dans les cookies",
 *     operationId="login",
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(ref="#/components/schemas/LoginRequest")
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Connexion réussie",
 *         @OA\JsonContent(ref="#/components/schemas/LoginResponse"),
 *         @OA\Header(
 *             header="Set-Cookie",
 *             description="Cookies d'authentification (access_token et refresh_token)",
 *             @OA\Schema(type="string", example="access_token=eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9...; HttpOnly; Secure; SameSite=Lax, refresh_token=eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9...; HttpOnly; Secure; SameSite=Lax")
 *         )
 *     ),
 *     @OA\Response(
 *         response=422,
 *         description="Erreur de validation",
 *         @OA\JsonContent(ref="#/components/schemas/Error")
 *     )
 * )
 * @OA\Post(
 *     path="/auth/refresh",
 *     tags={"Authentification"},
 *     summary="Rafraîchir le token d'accès",
 *     description="Utilise le refresh token pour obtenir un nouveau token d'accès",
 *     operationId="refresh",
 *     security={{"bearerAuth": {}, "cookieAuth": {}}},
 *     @OA\Response(
 *         response=200,
 *         description="Token rafraîchi avec succès",
 *         @OA\JsonContent(
 *             @OA\Property(property="success", type="boolean", example=true),
 *             @OA\Property(property="message", type="string", example="Token rafraîchi avec succès"),
 *             @OA\Property(property="data", type="object",
 *                 @OA\Property(property="token_type", type="string", example="Bearer")
 *             ),
 *             @OA\Property(property="timestamp", type="string", format="date-time"),
 *             @OA\Property(property="path", type="string"),
 *             @OA\Property(property="traceId", type="string")
 *         ),
 *         @OA\Header(
 *             header="Set-Cookie",
 *             description="Nouveaux cookies d'authentification (access_token et refresh_token)",
 *             @OA\Schema(type="string", example="access_token=eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9...; HttpOnly; Secure; SameSite=Lax, refresh_token=eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9...; HttpOnly; Secure; SameSite=Lax")
 *         )
 *     ),
 *     @OA\Response(
 *         response=401,
 *         description="Token invalide ou expiré",
 *         @OA\JsonContent(ref="#/components/schemas/Error")
 *     )
 * )
 * @OA\Post(
 *     path="/auth/logout",
 *     tags={"Authentification"},
 *     summary="Déconnexion utilisateur",
 *     description="Déconnecte l'utilisateur et invalide tous ses tokens",
 *     operationId="logout",
 *     security={{"bearerAuth": {}, "cookieAuth": {}}},
 *     @OA\Response(
 *         response=200,
 *         description="Déconnexion réussie",
 *         @OA\JsonContent(
 *             @OA\Property(property="success", type="boolean", example=true),
 *             @OA\Property(property="message", type="string", example="Déconnexion réussie"),
 *             @OA\Property(property="timestamp", type="string", format="date-time"),
 *             @OA\Property(property="path", type="string"),
 *             @OA\Property(property="traceId", type="string")
 *         ),
 *         @OA\Header(
 *             header="Set-Cookie",
 *             description="Cookies d'authentification supprimés (access_token et refresh_token)",
 *             @OA\Schema(type="string", example="access_token=; expires=Thu, 01 Jan 1970 00:00:00 GMT; HttpOnly; Secure; SameSite=Lax, refresh_token=; expires=Thu, 01 Jan 1970 00:00:00 GMT; HttpOnly; Secure; SameSite=Lax")
 *         )
 *     ),
 *     @OA\Response(
 *         response=401,
 *         description="Utilisateur non authentifié",
 *         @OA\JsonContent(ref="#/components/schemas/Error")
 *     )
 * )
 */
class AuthAnnotations
{
    // Cette classe contient toutes les annotations Swagger pour les endpoints d'authentification
}