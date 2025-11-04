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

        // Créer les tokens OAuth2 avec Passport
        $accessToken = $user->createToken('API Access Token')->accessToken;
        $refreshToken = $user->createToken('API Refresh Token')->accessToken;

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
                'access_token' => $accessToken,
                'refresh_token' => $refreshToken,
                'token_type' => 'Bearer',
                'expires_in' => 3600, // 1 heure en secondes
            ],
            'timestamp' => now()->toISOString(),
            'path' => $request->path(),
            'traceId' => uniqid()
        ]);

        // Stocker les tokens dans les cookies (HttpOnly pour sécurité)
        $response->cookie('access_token', $accessToken, 60, '/', null, false, true); // 60 minutes
        $response->cookie('refresh_token', $refreshToken, 60 * 24 * 7, '/', null, false, true); // 7 jours

        return $response;
    }

    public function refresh(Request $request)
    {
        // Vérifier l'utilisateur authentifié via Passport
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

        // Révoquer l'ancien token d'accès
        $user->token()->revoke();

        // Créer un nouveau token d'accès
        $newAccessToken = $user->createToken('API Access Token')->accessToken;

        // Créer un nouveau refresh token
        $newRefreshToken = $user->createToken('API Refresh Token')->accessToken;

        // Préparer la réponse
        $response = response()->json([
            'success' => true,
            'message' => 'Token rafraîchi avec succès',
            'data' => [
                'access_token' => $newAccessToken,
                'token_type' => 'Bearer',
            ],
            'timestamp' => now()->toISOString(),
            'path' => $request->path(),
            'traceId' => uniqid()
        ]);

        // Mettre à jour les cookies
        $response->cookie('access_token', $newAccessToken, 60, '/', null, false, true); // 60 minutes
        $response->cookie('refresh_token', $newRefreshToken, 60 * 24 * 7, '/', null, false, true); // 7 jours

        return $response;
    }

    public function logout(Request $request)
    {
        // Vérifier l'utilisateur authentifié
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
