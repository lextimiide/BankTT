<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AuthMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Vérifier le token de session dans les cookies
        $sessionToken = $request->cookie('session_token');

        if (!$sessionToken) {
            return response()->json([
                'success' => false,
                'message' => 'Accès non autorisé. Session manquante.',
                'errorCode' => 'UNAUTHORIZED',
                'timestamp' => now()->toISOString(),
                'path' => $request->path(),
                'traceId' => uniqid()
            ], 401);
        }

        // Vérifier que le token correspond à la session
        if ($sessionToken !== session('auth_token')) {
            return response()->json([
                'success' => false,
                'message' => 'Session invalide.',
                'errorCode' => 'INVALID_SESSION',
                'timestamp' => now()->toISOString(),
                'path' => $request->path(),
                'traceId' => uniqid()
            ], 401);
        }

        // Vérifier l'expiration de la session
        if (now()->timestamp > session('auth_expires')) {
            // Nettoyer la session expirée
            session()->forget(['auth_user_id', 'auth_user_type', 'auth_token', 'auth_expires']);

            return response()->json([
                'success' => false,
                'message' => 'Session expirée.',
                'errorCode' => 'SESSION_EXPIRED',
                'timestamp' => now()->toISOString(),
                'path' => $request->path(),
                'traceId' => uniqid()
            ], 401);
        }

        // Récupérer l'utilisateur depuis la session
        $userId = session('auth_user_id');
        $userType = session('auth_user_type');

        if (!$userId || !$userType) {
            return response()->json([
                'success' => false,
                'message' => 'Session corrompue.',
                'errorCode' => 'CORRUPTED_SESSION',
                'timestamp' => now()->toISOString(),
                'path' => $request->path(),
                'traceId' => uniqid()
            ], 401);
        }

        // Charger l'utilisateur réel
        $realUser = null;
        if ($userType === 'admin') {
            $realUser = \App\Models\Admin::find($userId);
        } elseif ($userType === 'client') {
            $realUser = \App\Models\Client::find($userId);
        }

        if (!$realUser) {
            // Nettoyer la session si l'utilisateur n'existe plus
            session()->forget(['auth_user_id', 'auth_user_type', 'auth_token', 'auth_expires']);

            return response()->json([
                'success' => false,
                'message' => 'Utilisateur non trouvé.',
                'errorCode' => 'USER_NOT_FOUND',
                'timestamp' => now()->toISOString(),
                'path' => $request->path(),
                'traceId' => uniqid()
            ], 401);
        }

        // Authentifier l'utilisateur
        Auth::login($realUser);

        // Stocker l'utilisateur dans la requête pour un accès facile
        $request->merge(['auth_user' => $realUser]);

        return $next($request);
    }
}
