<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Exceptions\ApiException;
use App\Http\Requests\SearchClientRequest;
use App\Http\Resources\ClientResource;
use App\Models\Client;
use App\Services\ClientService;
use App\Traits\ApiResponseTrait;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ClientController extends Controller
{
    use ApiResponseTrait;

    public function __construct(
        protected ClientService $clientService
    ) {}

    /**
     * @OA\Get(
     *     path="/clients/recherche",
     *     summary="Rechercher un client par téléphone ou NCI",
     *     description="Permet de rechercher un client en utilisant son numéro de téléphone ou son numéro de carte d'identité nationale. Accessible uniquement aux administrateurs.",
     *     operationId="searchClient",
     *     tags={"Clients"},
     *     @OA\Server(
     *         url="https://bankt-1.onrender.com/api/v1",
     *         description="Serveur de production Render"
     *     ),
     *     @OA\Server(
     *         url="http://localhost:8000/api/v1",
     *         description="Serveur local de développement"
     *     ),
     *     @OA\Parameter(
     *         name="telephone",
     *         in="query",
     *         description="Numéro de téléphone du client",
     *         required=false,
     *         @OA\Schema(type="string", example="771234567")
     *     ),
     *     @OA\Parameter(
     *         name="nci",
     *         in="query",
     *         description="Numéro de carte d'identité nationale",
     *         required=false,
     *         @OA\Schema(type="string", example="1234567890123")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Client trouvé avec succès",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", ref="#/components/schemas/Client"),
     *             @OA\Property(property="timestamp", type="string", format="date-time"),
     *             @OA\Property(property="path", type="string"),
     *             @OA\Property(property="traceId", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Paramètres de recherche invalides",
     *         @OA\JsonContent(ref="#/components/schemas/Error")
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Accès refusé - Réservé aux administrateurs",
     *         @OA\JsonContent(ref="#/components/schemas/Error")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Client non trouvé",
     *         @OA\JsonContent(ref="#/components/schemas/Error")
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Erreur interne du serveur",
     *         @OA\JsonContent(ref="#/components/schemas/Error")
     *     )
     * )
     */
    public function search(SearchClientRequest $request)
    {
        try {
            // Vérifier que seul un admin peut rechercher des clients
            $user = request()->auth_user;
            if (!$user instanceof \App\Models\Admin) {
                return $this->errorResponse('Accès refusé. Seuls les administrateurs peuvent rechercher des clients.', 403);
            }

            $client = $this->clientService->searchClient($request->validated());

            return $this->successResponse(
                new ClientResource($client->load('comptes')),
                'Client trouvé avec succès'
            );
        } catch (ValidationException $e) {
            return $this->errorResponse('Paramètres de recherche invalides', 400, $e->errors());
        } catch (ModelNotFoundException $e) {
            return $this->errorResponse(
                'Aucun client trouvé avec les critères spécifiés',
                404,
                [
                    'code' => 'CLIENT_NOT_FOUND',
                    'search_params' => $request->validated()
                ]
            );
        } catch (ApiException $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode() ?: 500);
        } catch (\Exception $e) {
            \Log::error('Erreur lors de la recherche de client', [
                'search_params' => $request->validated(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return $this->errorResponse('Erreur interne du serveur', 500);
        }
    }
}