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

        // Essayer d'abord avec Admin
        $admin = Admin::where('email', $request->email)->first();
        if ($admin && Hash::check($request->password, $admin->password)) {
            $user = $admin;
        }

        // Essayer avec Client si pas trouvé en tant qu'Admin
        if (!$user) {
            $client = Client::where('email', $request->email)->first();
            if ($client && Hash::check($request->password, $client->password)) {
                $user = $client;
            }
        }

        if (!$user) {
            throw ValidationException::withMessages([
                'email' => ['Les informations d\'identification sont incorrectes.'],
            ]);
        }

        // Authentifier l'utilisateur manuellement
        Auth::login($user);

        // Générer un token de session simple
        $sessionToken = hash('sha256', $user->id . $user->email . now()->timestamp . uniqid());

        // Stocker les informations en session
        session([
            'auth_user_id' => $user->id,
            'auth_user_type' => $user instanceof Admin ? 'admin' : 'client',
            'auth_token' => $sessionToken,
            'auth_expires' => now()->addMinutes(60)->timestamp, // 1 heure
        ]);

        // Préparer la réponse
        $response = response()->json([
            'success' => true,
            'message' => 'Connexion réussie',
            'data' => [
                'user' => $user instanceof Admin ? [
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
            ],
            'timestamp' => now()->toISOString(),
            'path' => $request->path(),
            'traceId' => uniqid()
        ]);

        // Stocker le token de session dans les cookies
        $response->cookie('session_token', $sessionToken, 60, '/', null, false, true); // 60 minutes

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
        // Vérifier le token de session
        $sessionToken = $request->cookie('session_token');

        if (!$sessionToken || $sessionToken !== session('auth_token')) {
            return response()->json([
                'success' => false,
                'message' => 'Session invalide ou expirée',
                'errorCode' => 'INVALID_SESSION',
                'timestamp' => now()->toISOString(),
                'path' => $request->path(),
                'traceId' => uniqid()
            ], 401);
        }

        // Vérifier l'expiration
        if (now()->timestamp > session('auth_expires')) {
            // Nettoyer la session
            session()->forget(['auth_user_id', 'auth_user_type', 'auth_token', 'auth_expires']);

            return response()->json([
                'success' => false,
                'message' => 'Session expirée',
                'errorCode' => 'SESSION_EXPIRED',
                'timestamp' => now()->toISOString(),
                'path' => $request->path(),
                'traceId' => uniqid()
            ], 401);
        }

        // Générer un nouveau token de session
        $userId = session('auth_user_id');
        $userType = session('auth_user_type');
        $newSessionToken = hash('sha256', $userId . $userType . now()->timestamp . uniqid());

        // Mettre à jour la session
        session([
            'auth_token' => $newSessionToken,
            'auth_expires' => now()->addMinutes(60)->timestamp,
        ]);

        // Préparer la réponse
        $response = response()->json([
            'success' => true,
            'message' => 'Session rafraîchie avec succès',
            'timestamp' => now()->toISOString(),
            'path' => $request->path(),
            'traceId' => uniqid()
        ]);

        // Mettre à jour le cookie
        $response->cookie('session_token', $newSessionToken, 60, '/', null, false, true);

        return $response;
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
        // Nettoyer la session
        session()->forget(['auth_user_id', 'auth_user_type', 'auth_token', 'auth_expires']);

        // Déconnecter l'utilisateur
        Auth::logout();

        // Préparer la réponse
        $response = response()->json([
            'success' => true,
            'message' => 'Déconnexion réussie',
            'timestamp' => now()->toISOString(),
            'path' => $request->path(),
            'traceId' => uniqid()
        ]);

        // Supprimer le cookie de session
        $response->cookie('session_token', '', -1, '/', null, false, true);

        return $response;
    }
}
