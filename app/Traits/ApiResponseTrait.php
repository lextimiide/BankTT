<?php

namespace App\Traits;

use Illuminate\Http\JsonResponse;

/**
 * Trait pour standardiser les réponses API avec structure simplifiée
 */
trait ApiResponseTrait
{
    /**
     * Structure unifiée de réponse API
     */
    private function formatResponse(
        bool $success,
        string $message,
        mixed $data = null,
        ?array $meta = null,
        ?string $errorCode = null,
        ?array $errors = null
    ): array {
        $response = [
            'success' => $success,
            'message' => $message,
        ];

        if ($data !== null) {
            $response['data'] = $data;
        }

        if ($meta !== null) {
            $response['meta'] = $meta;
        }

        if ($errorCode !== null) {
            $response['errorCode'] = $errorCode;
        }

        if ($errors !== null) {
            $response['errors'] = $errors;
        }

        return $response;
    }

    /**
     * Réponse de succès simplifiée
     */
    protected function success(mixed $data = null, string $message = 'Success'): JsonResponse
    {
        return response()->json(
            $this->formatResponse(true, $message, $data),
            200
        );
    }

    /**
     * Réponse d'erreur simplifiée
     */
    protected function error(
        string $message = 'Error',
        int $statusCode = 400,
        ?string $errorCode = null,
        ?array $errors = null
    ): JsonResponse {
        return response()->json(
            $this->formatResponse(false, $message, null, null, $errorCode, $errors),
            $statusCode
        );
    }

    /**
     * Réponse paginée simplifiée
     */
    protected function paginated(mixed $data, string $message = 'Data retrieved'): JsonResponse
    {
        $meta = [
            'pagination' => [
                'current_page' => $data->currentPage(),
                'per_page' => $data->perPage(),
                'total' => $data->total(),
                'last_page' => $data->lastPage(),
                'from' => $data->firstItem(),
                'to' => $data->lastItem(),
            ],
            'links' => [
                'first' => $data->url(1),
                'last' => $data->url($data->lastPage()),
                'prev' => $data->previousPageUrl(),
                'next' => $data->nextPageUrl(),
            ],
        ];

        return response()->json(
            $this->formatResponse(true, $message, $data->items(), $meta),
            200
        );
    }

    /**
     * Réponse de création simplifiée
     */
    protected function created(mixed $data = null, string $message = 'Created'): JsonResponse
    {
        return response()->json(
            $this->formatResponse(true, $message, $data),
            201
        );
    }

    /**
     * Réponse de mise à jour simplifiée
     */
    protected function updated(mixed $data = null, string $message = 'Updated'): JsonResponse
    {
        return response()->json(
            $this->formatResponse(true, $message, $data),
            200
        );
    }

    /**
     * Réponse de suppression simplifiée
     */
    protected function deleted(string $message = 'Deleted'): JsonResponse
    {
        return response()->json(
            $this->formatResponse(true, $message),
            200
        );
    }

    /**
     * Erreurs communes simplifiées
     */
    protected function notFound(string $resource = 'Resource'): JsonResponse
    {
        return $this->error("$resource not found", 404, 'NOT_FOUND');
    }

    protected function unauthorized(string $message = 'Unauthorized'): JsonResponse
    {
        return $this->error($message, 401, 'UNAUTHORIZED');
    }

    protected function forbidden(string $message = 'Forbidden'): JsonResponse
    {
        return $this->error($message, 403, 'FORBIDDEN');
    }

    protected function validationError(array $errors, string $message = 'Validation failed'): JsonResponse
    {
        return $this->error($message, 422, 'VALIDATION_ERROR', $errors);
    }

    protected function serverError(string $message = 'Internal server error'): JsonResponse
    {
        return $this->error($message, 500, 'INTERNAL_ERROR');
    }

    // Méthodes d'aide pour les anciens noms (rétrocompatibilité)
    protected function successResponse(mixed $data = null, string $message = 'Opération réussie', int $statusCode = 200, ?array $meta = null): JsonResponse
    {
        if ($meta) {
            return response()->json(
                $this->formatResponse(true, $message, $data, $meta),
                $statusCode
            );
        }
        return $this->success($data, $message)->setStatusCode($statusCode);
    }

    protected function errorResponse(string $message = 'Une erreur est survenue', int $statusCode = 400, ?array $errors = null, ?string $errorCode = null): JsonResponse
    {
        return $this->error($message, $statusCode, $errorCode, $errors);
    }

    protected function paginatedResponse(mixed $data, string $message = 'Données récupérées avec succès'): JsonResponse
    {
        return $this->paginated($data, $message);
    }

    protected function createdResponse(mixed $data, string $message = 'Ressource créée avec succès'): JsonResponse
    {
        return $this->created($data, $message);
    }

    protected function notFoundResponse(string $resource = 'Ressource'): JsonResponse
    {
        return $this->notFound($resource);
    }

    protected function validationErrorResponse(array $errors): JsonResponse
    {
        return $this->validationError($errors);
    }

    protected function serverErrorResponse(string $message = 'Erreur interne du serveur'): JsonResponse
    {
        return $this->serverError($message);
    }

    protected function unauthorizedResponse(string $message = 'Accès non autorisé'): JsonResponse
    {
        return $this->unauthorized($message);
    }

    protected function forbiddenResponse(string $message = 'Accès interdit'): JsonResponse
    {
        return $this->forbidden($message);
    }
}