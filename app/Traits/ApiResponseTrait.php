<?php

namespace App\Traits;

use Illuminate\Http\JsonResponse;

trait ApiResponseTrait
{
    /**
     * Format de réponse standard pour les succès
     */
    protected function successResponse(
        mixed $data = null,
        string $message = 'Opération réussie',
        int $statusCode = 200,
        ?array $meta = null
    ): JsonResponse {
        $response = [
            'success' => true,
            'message' => $message,
            'timestamp' => now()->toISOString(),
            'path' => request()->path(),
            'traceId' => uniqid(),
        ];

        if ($data !== null) {
            $response['data'] = $data;
        }

        if ($meta !== null) {
            $response['meta'] = $meta;
        }

        return response()->json($response, $statusCode);
    }

    /**
     * Format de réponse standard pour les erreurs
     */
    protected function errorResponse(
        string $message = 'Une erreur est survenue',
        int $statusCode = 400,
        ?array $errors = null,
        ?string $errorCode = null
    ): JsonResponse {
        $response = [
            'success' => false,
            'message' => $message,
            'timestamp' => now()->toISOString(),
            'path' => request()->path(),
            'traceId' => uniqid(),
        ];

        if ($errors !== null) {
            $response['errors'] = $errors;
        }

        if ($errorCode !== null) {
            $response['errorCode'] = $errorCode;
        }

        return response()->json($response, $statusCode);
    }

    /**
     * Réponse paginée standard
     */
    protected function paginatedResponse(
        mixed $data,
        string $message = 'Données récupérées avec succès'
    ): JsonResponse {
        $pagination = [
            'currentPage' => $data->currentPage(),
            'totalPages' => $data->lastPage(),
            'totalItems' => $data->total(),
            'itemsPerPage' => $data->perPage(),
            'hasNext' => $data->hasMorePages(),
            'hasPrevious' => $data->currentPage() > 1,
        ];

        $links = [
            'self' => $data->url($data->currentPage()),
            'first' => $data->url(1),
            'last' => $data->url($data->lastPage()),
        ];

        if ($data->hasMorePages()) {
            $links['next'] = $data->url($data->currentPage() + 1);
        }

        if ($data->currentPage() > 1) {
            $links['previous'] = $data->url($data->currentPage() - 1);
        }

        return $this->successResponse(
            data: $data->items(),
            message: $message,
            meta: [
                'pagination' => $pagination,
                'links' => $links,
            ]
        );
    }

    /**
     * Réponse de ressource créée
     */
    protected function createdResponse(
        mixed $data,
        string $message = 'Ressource créée avec succès'
    ): JsonResponse {
        return $this->successResponse($data, $message, 201);
    }

    /**
     * Réponse de ressource non trouvée
     */
    protected function notFoundResponse(string $resource = 'Ressource'): JsonResponse
    {
        return $this->errorResponse("$resource introuvable", 404, null, 'RESOURCE_NOT_FOUND');
    }

    /**
     * Réponse de validation échouée
     */
    protected function validationErrorResponse(array $errors): JsonResponse
    {
        return $this->errorResponse('Erreur de validation', 422, $errors, 'VALIDATION_ERROR');
    }

    /**
     * Réponse d'erreur interne du serveur
     */
    protected function serverErrorResponse(string $message = 'Erreur interne du serveur'): JsonResponse
    {
        return $this->errorResponse($message, 500, null, 'INTERNAL_SERVER_ERROR');
    }

    /**
     * Réponse d'accès non autorisé
     */
    protected function unauthorizedResponse(string $message = 'Accès non autorisé'): JsonResponse
    {
        return $this->errorResponse($message, 401, null, 'UNAUTHORIZED');
    }

    /**
     * Réponse d'accès interdit
     */
    protected function forbiddenResponse(string $message = 'Accès interdit'): JsonResponse
    {
        return $this->errorResponse($message, 403, null, 'FORBIDDEN');
    }
}