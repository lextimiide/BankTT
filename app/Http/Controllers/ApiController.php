<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

/**
 * @OA\Server(
 *     url="https://bankt-1.onrender.com/api/v1",
 *     description="Serveur de production Render"
 * )
 * @OA\Server(
 *     url="https://bankt-1.onrender.com/api/v1",
 *     description="Serveur de production Render"
 * )
 *
 * @OA\SecurityScheme(
 *     securityScheme="bearerAuth",
 *     type="http",
 *     scheme="bearer",
 *     bearerFormat="JWT",
 *     description="Authentification JWT - Utilisez le format: Bearer {token}"
 * )
 */

/**
 * @OA\Tag(
 *     name="Comptes",
 *     description="Gestion des comptes bancaires"
 * )
 * @OA\Tag(
 *     name="Clients",
 *     description="Gestion des clients"
 * )
 * @OA\Tag(
 *     name="Transactions",
 *     description="Gestion des transactions"
 * )
 * @OA\Tag(
 *     name="Users",
 *     description="Gestion des utilisateurs (non implémenté)"
 * )
 */

/**
 * @OA\Schema(
 *     schema="Error",
 *     type="object",
 *     title="Error Response",
 *     description="Standard error response format",
 *     @OA\Property(property="success", type="boolean", example=false),
 *     @OA\Property(property="message", type="string", example="Une erreur est survenue"),
 *     @OA\Property(property="errorCode", type="string", example="VALIDATION_ERROR"),
 *     @OA\Property(property="errors", type="object", nullable=true),
 *     @OA\Property(property="timestamp", type="string", format="date-time"),
 *     @OA\Property(property="path", type="string"),
 *     @OA\Property(property="traceId", type="string")
 * )
 */

/**
 * @OA\Schema(
 *     schema="Success",
 *     type="object",
 *     title="Success Response",
 *     description="Standard success response format",
 *     @OA\Property(property="success", type="boolean", example=true),
 *     @OA\Property(property="message", type="string", example="Opération réussie"),
 *     @OA\Property(property="data", type="object", nullable=true),
 *     @OA\Property(property="meta", type="object", nullable=true),
 *     @OA\Property(property="timestamp", type="string", format="date-time"),
 *     @OA\Property(property="path", type="string"),
 *     @OA\Property(property="traceId", type="string")
 * )
 */

/**
 * @OA\Schema(
 *     schema="Client",
 *     type="object",
 *     title="Client",
 *     description="Client model",
 *     @OA\Property(property="id", type="string", format="uuid", description="Client UUID"),
 *     @OA\Property(property="titulaire", type="string", description="Nom complet du titulaire du compte"),
 *     @OA\Property(property="nci", type="string", description="Numéro de carte d'identité nationale"),
 *     @OA\Property(property="email", type="string", format="email", description="Adresse email du client"),
 *     @OA\Property(property="telephone", type="string", description="Numéro de téléphone"),
 *     @OA\Property(property="adresse", type="string", description="Adresse complète du client"),
 *     @OA\Property(property="statut", type="string", enum={"actif", "inactif", "suspendu"}, description="Statut du client"),
 *     @OA\Property(property="email_verified_at", type="string", format="date-time", description="Date de vérification de l'email"),
 *     @OA\Property(property="created_at", type="string", format="date-time", description="Date de création"),
 *     @OA\Property(property="updated_at", type="string", format="date-time", description="Date de dernière modification")
 * )
 */

/**
 * @OA\Schema(
 *     schema="Compte",
 *     type="object",
 *     title="Compte",
 *     description="Modèle de compte bancaire",
 *     @OA\Property(property="id", type="string", format="uuid", description="UUID du compte"),
 *     @OA\Property(property="numero_compte", type="string", description="Numéro unique du compte (auto-généré)"),
 *     @OA\Property(property="type", type="string", enum={"cheque", "epargne", "courant"}, description="Type de compte"),
 *     @OA\Property(property="solde_initial", type="number", format="decimal", description="Solde initial du compte"),
 *     @OA\Property(property="solde", type="number", format="decimal", description="Solde actuel calculé dynamiquement"),
 *     @OA\Property(property="devise", type="string", description="Devise du compte (FCFA, EUR, USD)"),
 *     @OA\Property(property="statut", type="string", enum={"actif", "inactif", "bloque", "ferme"}, description="Statut du compte"),
 *     @OA\Property(property="client_id", type="string", format="uuid", description="UUID du client propriétaire"),
 *     @OA\Property(property="client", ref="#/components/schemas/Client", description="Informations du client"),
 *     @OA\Property(property="created_at", type="string", format="date-time", description="Date de création"),
 *     @OA\Property(property="updated_at", type="string", format="date-time", description="Date de dernière modification")
 * )
 */

/**
 * @OA\Schema(
 *     schema="Transaction",
 *     type="object",
 *     title="Transaction",
 *     description="Modèle de transaction bancaire",
 *     @OA\Property(property="id", type="string", format="uuid", description="UUID de la transaction"),
 *     @OA\Property(property="numero_transaction", type="string", description="Numéro unique de transaction (auto-généré)"),
 *     @OA\Property(property="type", type="string", enum={"depot", "retrait", "virement", "transfert", "frais"}, description="Type de transaction"),
 *     @OA\Property(property="montant", type="number", format="decimal", description="Montant de la transaction"),
 *     @OA\Property(property="devise", type="string", description="Devise de la transaction"),
 *     @OA\Property(property="description", type="string", description="Description de la transaction"),
 *     @OA\Property(property="statut", type="string", enum={"en_attente", "validee", "rejete", "annulee"}, description="Statut de la transaction"),
 *     @OA\Property(property="date_transaction", type="string", format="date-time", description="Date de la transaction"),
 *     @OA\Property(property="compte_id", type="string", format="uuid", description="UUID du compte source"),
 *     @OA\Property(property="compte_destination_id", type="string", format="uuid", nullable=true, description="UUID du compte destination (pour virements)"),
 *     @OA\Property(property="compte", ref="#/components/schemas/Compte", description="Informations du compte source"),
 *     @OA\Property(property="compte_destination", ref="#/components/schemas/Compte", description="Informations du compte destination"),
 *     @OA\Property(property="created_at", type="string", format="date-time", description="Date de création"),
 *     @OA\Property(property="updated_at", type="string", format="date-time", description="Date de dernière modification")
 * )
 */
class ApiController extends Controller
{

    /**
     * @OA\Get(
     *     path="/api/v1/clients",
     *     summary="Get all clients",
     *     description="Retrieve a paginated list of all clients",
     *     tags={"Clients"},
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         description="Page number",
     *         required=false,
     *         @OA\Schema(type="integer", default=1)
     *     ),
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="Items per page",
     *         required=false,
     *         @OA\Schema(type="integer", default=15)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="List of clients",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="id", type="string", format="uuid"),
     *                     @OA\Property(property="titulaire", type="string"),
     *                     @OA\Property(property="email", type="string", format="email"),
     *                     @OA\Property(property="telephone", type="string"),
     *                     @OA\Property(property="statut", type="string", enum={"actif","inactif","suspendu"})
     *                 )
     *             ),
     *             @OA\Property(property="meta", type="object"),
     *             @OA\Property(property="timestamp", type="string", format="date-time"),
     *             @OA\Property(property="path", type="string"),
     *             @OA\Property(property="traceId", type="string")
     *         )
     *     )
     * )
     */
    public function getClients()
    {
        $clients = \App\Models\Client::paginate(request('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => $clients->items(),
            'meta' => [
                'current_page' => $clients->currentPage(),
                'per_page' => $clients->perPage(),
                'total' => $clients->total(),
                'last_page' => $clients->lastPage(),
            ],
            'timestamp' => now(),
            'path' => request()->path(),
            'traceId' => uniqid()
        ]);
    }

   /**
    * @OA\Post(
    *     path="/api/v1/clients",
    *     summary="Create a new client",
    *     description="Create a new client with validation",
    *     tags={"Clients"},
    *     @OA\RequestBody(
    *         required=true,
    *         @OA\JsonContent(
    *             required={"titulaire","email","telephone","adresse"},
    *             @OA\Property(property="titulaire", type="string", example="Hawa BB Wane"),
    *             @OA\Property(property="nci", type="string", example="", description="Numéro de carte d'identité (optionnel)"),
    *             @OA\Property(property="email", type="string", format="email", example="cheikh.sy@example.com"),
    *             @OA\Property(property="telephone", type="string", example="+221771234567"),
    *             @OA\Property(property="adresse", type="string", example="Dakar, Sénégal"),
    *             @OA\Property(property="statut", type="string", enum={"actif","inactif","suspendu"}, example="actif")
    *         )
    *     ),
    *     @OA\Response(
    *         response=201,
    *         description="Client created successfully",
    *         @OA\JsonContent(
    *             type="object",
    *             @OA\Property(property="success", type="boolean", example=true),
    *             @OA\Property(property="data", ref="#/components/schemas/Client"),
    *             @OA\Property(property="message", type="string", example="Client créé avec succès"),
    *             @OA\Property(property="timestamp", type="string", format="date-time"),
    *             @OA\Property(property="path", type="string"),
    *             @OA\Property(property="traceId", type="string")
    *         )
    *     ),
    *     @OA\Response(
    *         response=422,
    *         description="Validation error",
    *         @OA\JsonContent(
    *             type="object",
    *             @OA\Property(property="success", type="boolean", example=false),
    *             @OA\Property(property="message", type="string", example="Erreur de validation"),
    *             @OA\Property(property="errors", type="object")
    *         )
    *     )
    * )
    */
    public function createClient(\App\Http\Requests\StoreClientRequest $request)
    {
        $client = \App\Models\Client::create($request->validated());

        return response()->json([
            'success' => true,
            'data' => $client,
            'message' => 'Client créé avec succès',
            'timestamp' => now(),
            'path' => request()->path(),
            'traceId' => uniqid()
        ], 201);
    }
}