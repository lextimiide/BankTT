<?php

namespace App\Http\Controllers;

use App\Models\Admin;
use App\Models\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
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
     */
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $user = null;
        $guard = null;

        // Essayer d'abord avec Admin
        $admin = Admin::where('email', $request->email)->first();
        if ($admin && Hash::check($request->password, $admin->password)) {
            $user = $admin;
            $guard = 'admins';
        }

        // Essayer avec Client si pas trouvé en tant qu'Admin
        if (!$user) {
            $client = Client::where('email', $request->email)->first();
            if ($client && Hash::check($request->password, $client->password)) {
                $user = $client;
                $guard = 'clients';
            }
        }

        if (!$user) {
            throw ValidationException::withMessages([
                'email' => ['Les informations d\'identification sont incorrectes.'],
            ]);
        }

        // Créer le token d'accès
        $token = $user->createToken('API Token')->accessToken;

        // Créer le refresh token
        $refreshToken = $user->createToken('Refresh Token')->accessToken;

        // Préparer la réponse
        $response = response()->json([
            'success' => true,
            'message' => 'Connexion réussie',
            'data' => [
                'user' => $user->role === 'admin' ? [
                    'id' => $user->id,
                    'nom' => $user->nom,
                    'prenom' => $user->prenom,
                    'email' => $user->email,
                    'role' => $user->role,
                ] : [
                    'id' => $user->id,
                    'titulaire' => $user->titulaire,
                    'email' => $user->email,
                    'role' => $user->role,
                ],
                'token_type' => 'Bearer',
            ],
            'timestamp' => now()->toISOString(),
            'path' => $request->path(),
            'traceId' => uniqid()
        ]);

        // Stocker les tokens dans les cookies
        $response->cookie('access_token', $token, 60, '/', null, false, true); // 60 minutes
        $response->cookie('refresh_token', $refreshToken, 60 * 24 * 7, '/', null, false, true); // 7 jours

        return $response;
    }

    /**
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
     */
    public function refresh(Request $request)
    {
        $user = Auth::guard('api')->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Token invalide ou expiré',
                'errorCode' => 'INVALID_TOKEN',
                'timestamp' => now()->toISOString(),
                'path' => $request->path(),
                'traceId' => uniqid()
            ], 401);
        }

        // Révoquer l'ancien token
        $user->token()->revoke();

        // Créer un nouveau token
        $newToken = $user->createToken('API Token')->accessToken;

        return response()->json([
            'success' => true,
            'message' => 'Token rafraîchi avec succès',
            'data' => [
                'access_token' => $newToken,
                'token_type' => 'Bearer',
            ],
            'timestamp' => now()->toISOString(),
            'path' => $request->path(),
            'traceId' => uniqid()
        ]);
    }

    /**
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
    public function logout(Request $request)
    {
        $user = Auth::guard('api')->user();

        if ($user) {
            // Révoquer tous les tokens de l'utilisateur
            $user->tokens()->delete();
        }

        // Préparer la réponse
        $response = response()->json([
            'success' => true,
            'message' => 'Déconnexion réussie',
            'timestamp' => now()->toISOString(),
            'path' => $request->path(),
            'traceId' => uniqid()
        ]);

        // Supprimer les cookies
        $response->cookie('access_token', '', -1, '/', null, false, true);
        $response->cookie('refresh_token', '', -1, '/', null, false, true);

        return $response;
    }
}
