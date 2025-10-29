<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class LoggingMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $startTime = microtime(true);

        // Log de la requête entrante
        $this->logRequest($request);

        $response = $next($request);

        $endTime = microtime(true);
        $duration = round(($endTime - $startTime) * 1000, 2); // en millisecondes

        // Log de la réponse
        $this->logResponse($request, $response, $duration);

        return $response;
    }

    /**
     * Log les informations de la requête
     */
    private function logRequest(Request $request): void
    {
        $logData = [
            'timestamp' => now()->toISOString(),
            'host' => $request->getHost(),
            'method' => $request->getMethod(),
            'uri' => $request->getRequestUri(),
            'user_agent' => $request->userAgent(),
            'ip' => $request->ip(),
            'operation' => $this->getOperationName($request),
            'resource' => $this->getResourceName($request),
        ];

        Log::info('API Request', $logData);
    }

    /**
     * Log les informations de la réponse
     */
    private function logResponse(Request $request, Response $response, float $duration): void
    {
        $logData = [
            'timestamp' => now()->toISOString(),
            'host' => $request->getHost(),
            'method' => $request->getMethod(),
            'uri' => $request->getRequestUri(),
            'status_code' => $response->getStatusCode(),
            'duration_ms' => $duration,
            'operation' => $this->getOperationName($request),
            'resource' => $this->getResourceName($request),
            'response_size' => strlen($response->getContent()),
        ];

        // Log selon le niveau de statut
        if ($response->isSuccessful()) {
            Log::info('API Response Success', $logData);
        } elseif ($response->isClientError()) {
            Log::warning('API Response Client Error', $logData);
        } elseif ($response->isServerError()) {
            Log::error('API Response Server Error', $logData);
        } else {
            Log::info('API Response', $logData);
        }
    }

    /**
     * Détermine le nom de l'opération
     */
    private function getOperationName(Request $request): string
    {
        $method = $request->getMethod();
        $path = $request->getPathInfo();

        // Mapping des opérations selon les routes
        if (str_contains($path, '/comptes')) {
            switch ($method) {
                case 'GET':
                    return $path === '/api/v1/comptes' ? 'LIST_COMPTES' : 'GET_COMPTE';
                case 'POST':
                    return 'CREATE_COMPTE';
                case 'PUT':
                case 'PATCH':
                    return 'UPDATE_COMPTE';
                case 'DELETE':
                    return 'DELETE_COMPTE';
            }
        }

        return strtoupper($method) . '_UNKNOWN';
    }

    /**
     * Détermine le nom de la ressource
     */
    private function getResourceName(Request $request): string
    {
        $path = $request->getPathInfo();

        if (str_contains($path, '/comptes')) {
            return 'COMPTE';
        }

        return 'UNKNOWN';
    }
}
