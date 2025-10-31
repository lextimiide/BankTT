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
        // Vérifier d'abord le token dans l'en-tête Authorization
        $token = $request->bearerToken();

        // Si pas de token dans l'en-tête, vérifier dans les cookies
        if (!$token) {
            $token = $request->cookie('access_token');
        }

        if (!$token) {
            return response()->json([
                'success' => false,
                'message' => 'Accès non autorisé. Token manquant.',
                'errorCode' => 'UNAUTHORIZED',
                'timestamp' => now()->toISOString(),
                'path' => $request->path(),
                'traceId' => uniqid()
            ], 401);
        }

        // Authentifier l'utilisateur avec le token Passport
        if (!Auth::guard('api')->check()) {
            return response()->json([
                'success' => false,
                'message' => 'Token invalide ou expiré.',
                'errorCode' => 'INVALID_TOKEN',
                'timestamp' => now()->toISOString(),
                'path' => $request->path(),
                'traceId' => uniqid()
            ], 401);
        }

        return $next($request);
    }
}
