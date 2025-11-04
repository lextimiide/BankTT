<?php

namespace App\Swagger;

use OpenApi\Annotations as OA;

/**
 * @OA\Get(
 *     path="/clients/recherche",
 *     summary="Rechercher un client par téléphone ou NCI",
 *     description="Permet de rechercher un client en utilisant son numéro de téléphone ou son numéro de carte d'identité nationale. Accessible uniquement aux administrateurs.",
 *     operationId="searchClient",
 *     tags={"Clients"},
 *     @OA\Server(
 *         url="https://banktt.onrender.com/api/v1",
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
class ClientAnnotations
{
    // Cette classe contient toutes les annotations Swagger pour les endpoints de clients
}