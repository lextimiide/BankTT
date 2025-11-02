<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Exceptions\ApiException;
use App\Http\Requests\StoreCompteRequest;
use App\Http\Requests\UpdateCompteRequest;
use App\Http\Resources\CompteResource;
use App\Models\Compte;
use App\Services\CompteService;
use App\Traits\ApiResponseTrait;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class CompteController extends Controller
{
    use ApiResponseTrait;

    public function __construct(
        protected CompteService $compteService
    ) {}

    /**
     * Vérifie les permissions d'accès à un compte
     */
    private function checkAccountAccessPermission(Compte $compte, $user): void
    {
        // Admin peut accéder à tous les comptes
        if ($user instanceof \App\Models\Admin) {
            return;
        }

        // Client ne peut accéder qu'à ses propres comptes
        if ($user instanceof \App\Models\Client) {
            if ($compte->client_id !== $user->id) {
                throw new ApiException('Accès refusé. Vous ne pouvez accéder qu\'à vos propres comptes.', 403);
            }
            return;
        }

        // Utilisateur inconnu
        throw new ApiException('Type d\'utilisateur non autorisé.', 403);
    }

    /**
     * @OA\Get(
     *     path="/comptes",
     *     summary="Lister tous les comptes",
     *     description="Récupère une liste paginée de tous les comptes avec possibilité de filtrage avancé",
     *     operationId="getComptes",
     *     tags={"Comptes"},
     *     @OA\Server(
     *         url="https://banktt.onrender.com/api/v1",
     *         description="Serveur de production Render"
     *     ),
     *     @OA\Server(
     *          url="http://localhost:8000/api/v1",
     *         description="Serveur local de développement"
     *         
     *     ),
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         description="Numéro de la page",
     *         required=false,
     *         @OA\Schema(type="integer", default=1, minimum=1)
     *     ),
     *     @OA\Parameter(
     *         name="limit",
     *         in="query",
     *         description="Nombre d'éléments par page (max 100)",
     *         required=false,
     *         @OA\Schema(type="integer", default=15, minimum=1, maximum=100)
     *     ),
     *     @OA\Parameter(
     *         name="type",
     *         in="query",
     *         description="Filtrer par type de compte",
     *         required=false,
     *         @OA\Schema(type="string", enum={"cheque", "epargne", "courant"})
     *     ),
     *     @OA\Parameter(
     *         name="statut",
     *         in="query",
     *         description="Filtrer par statut du compte",
     *         required=false,
     *         @OA\Schema(type="string", enum={"actif", "inactif", "bloque", "ferme"})
     *     ),
     *     @OA\Parameter(
     *         name="search",
     *         in="query",
     *         description="Rechercher par titulaire ou numéro de compte",
     *         required=false,
     *         @OA\Schema(type="string", minLength=2, maxLength=100)
     *     ),
     *     @OA\Parameter(
     *         name="sort",
     *         in="query",
     *         description="Champ de tri",
     *         required=false,
     *         @OA\Schema(type="string", enum={"dateCreation", "solde", "titulaire", "numero_compte", "type"})
     *     ),
     *     @OA\Parameter(
     *         name="order",
     *         in="query",
     *         description="Ordre de tri",
     *         required=false,
     *         @OA\Schema(type="string", enum={"asc", "desc"}, default="desc")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Liste des comptes récupérée avec succès",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Comptes récupérés avec succès"),
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/Compte")),
     *             @OA\Property(property="meta", type="object",
     *                 @OA\Property(property="pagination", type="object",
     *                     @OA\Property(property="current_page", type="integer"),
     *                     @OA\Property(property="per_page", type="integer"),
     *                     @OA\Property(property="total", type="integer"),
     *                     @OA\Property(property="last_page", type="integer"),
     *                     @OA\Property(property="from", type="integer", nullable=true),
     *                     @OA\Property(property="to", type="integer", nullable=true)
     *                 ),
     *                 @OA\Property(property="links", type="object",
     *                     @OA\Property(property="first", type="string"),
     *                     @OA\Property(property="last", type="string"),
     *                     @OA\Property(property="prev", type="string", nullable=true),
     *                     @OA\Property(property="next", type="string", nullable=true)
     *                 )
     *             ),
     *             @OA\Property(property="timestamp", type="string", format="date-time"),
     *             @OA\Property(property="path", type="string"),
     *             @OA\Property(property="traceId", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Paramètres de requête invalides",
     *         @OA\JsonContent(ref="#/components/schemas/Error")
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Erreur interne du serveur",
     *         @OA\JsonContent(ref="#/components/schemas/Error")
     *     )
     * )
     */
    public function index(Request $request)
    {
        try {
            $result = $this->compteService->getComptes($request);
            return $this->successResponse($result, 'Comptes récupérés avec succès');
        } catch (ValidationException $e) {
            return $this->errorResponse('Paramètres de requête invalides', 422, $e->errors());
        } catch (\Exception $e) {
            \Log::error('Erreur lors de la récupération des comptes', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request' => $request->all()
            ]);
            return $this->errorResponse('Erreur interne du serveur', 500);
        }
    }

    /**
     * @OA\Post(
      *     path="/comptes",
      *     summary="Créer un nouveau compte bancaire",
      *     description="Crée un nouveau compte bancaire. Lors de la création d'un compte on effectue les actions suivantes : On vérifie l'existence du client. Si le client n'existe pas on le crée automatiquement.",
      *     operationId="createCompte",
      *     tags={"Comptes"},
      *     @OA\Server(
      *          url="https://banktt.onrender.com/api/v1",
      *         description="Serveur de production Render"
      *
      *     ),
      *     @OA\Server(
      *          url="http://localhost:8000/api/v1",
      *         description="Serveur local de développement"
      *     ),
      *     @OA\RequestBody(
      *         required=true,
      *         @OA\JsonContent(
      *             required={"type","solde","devise","client"},
      *             @OA\Property(property="type", type="string", enum={"cheque", "epargne", "courant"}, example="cheque"),
      *             @OA\Property(property="solde", type="number", format="decimal", minimum=10000, example=15000, description="Solde initial du compte"),
      *             @OA\Property(property="devise", type="string", enum={"FCFA", "EUR", "USD"}, example="FCFA"),
      *             @OA\Property(property="client", type="object", description="Informations du client à créer",
      *                 @OA\Property(property="titulaire", type="string", example="Hawa BB Wane"),
      *                 @OA\Property(property="email", type="string", format="email", example="hawa.wane@example.com"),
      *                 @OA\Property(property="telephone", type="string", example="+221771234567"),
      *                 @OA\Property(property="adresse", type="string", example="Dakar, Sénégal"),
      *                 @OA\Property(property="nci", type="string", example="1234567890124", description="Numéro de carte d'identité nationale")
      *             )
      *         )
      *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Compte créé avec succès",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Compte créé avec succès"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="string", format="uuid"),
     *                 @OA\Property(property="numeroCompte", type="string", example="CB241234567890"),
     *                 @OA\Property(property="titulaire", type="string", example="Hawa BB Wane"),
     *                 @OA\Property(property="type", type="string", example="cheque"),
     *                 @OA\Property(property="solde", type="number", example=500000),
     *                 @OA\Property(property="devise", type="string", example="FCFA"),
     *                 @OA\Property(property="dateCreation", type="string", format="date-time"),
     *                 @OA\Property(property="statut", type="string", example="actif"),
     *                 @OA\Property(property="metadata", type="object",
     *                     @OA\Property(property="derniereModification", type="string", format="date-time"),
     *                     @OA\Property(property="version", type="integer", example=1)
     *                 )
     *             ),
     *             @OA\Property(property="timestamp", type="string", format="date-time"),
     *             @OA\Property(property="path", type="string"),
     *             @OA\Property(property="traceId", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Données invalides",
     *         @OA\JsonContent(ref="#/components/schemas/Error")
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Erreur de validation",
     *         @OA\JsonContent(ref="#/components/schemas/Error")
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Erreur interne du serveur",
     *         @OA\JsonContent(ref="#/components/schemas/Error")
     *     )
     * )
     */
    public function store(StoreCompteRequest $request)
    {
        try {
            $compte = $this->compteService->createCompte($request->validated());

            return response()->json([
                'success' => true,
                'message' => 'Compte créé avec succès',
                'data' => [
                    'id' => $compte->id,
                    'numeroCompte' => $compte->numero_compte,
                    'titulaire' => $compte->client->titulaire,
                    'type' => $compte->type,
                    'solde' => $compte->solde,
                    'devise' => $compte->devise,
                    'dateCreation' => $compte->created_at->toISOString(),
                    'statut' => $compte->statut,
                    'metadata' => [
                        'derniereModification' => $compte->updated_at->toISOString(),
                        'version' => 1,
                    ],
                ],
                'timestamp' => now()->toISOString(),
                'path' => $request->path(),
                'traceId' => uniqid()
            ], 201);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Données de validation invalides',
                'errorCode' => 'VALIDATION_ERROR',
                'errors' => $e->errors(),
                'timestamp' => now()->toISOString(),
                'path' => $request->path(),
                'traceId' => uniqid()
            ], 422);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Client non trouvé',
                'errorCode' => 'CLIENT_NOT_FOUND',
                'timestamp' => now()->toISOString(),
                'path' => $request->path(),
                'traceId' => uniqid()
            ], 404);
        } catch (ApiException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'errorCode' => 'API_ERROR',
                'timestamp' => now()->toISOString(),
                'path' => $request->path(),
                'traceId' => uniqid()
            ], $e->getCode() ?: 400);
        } catch (\Exception $e) {
            \Log::error('Erreur lors de la création du compte', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request' => $request->all()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Erreur interne du serveur',
                'errorCode' => 'INTERNAL_ERROR',
                'timestamp' => now()->toISOString(),
                'path' => $request->path(),
                'traceId' => uniqid()
            ], 500);
        }
    }


    /**
     * @OA\Get(
     *     path="/comptes/numero/{numero}",
     *     summary="Récupérer un compte par numéro",
     *     description="Permet de récupérer les détails d'un compte bancaire en utilisant son numéro de compte. Accessible aux admins et aux propriétaires du compte.",
     *     operationId="getCompteByNumero",
     *     tags={"Comptes"},
     *     @OA\Server(
     *         url="https://banktt.onrender.com/api/v1",
     *         description="Serveur de production Render"
     *     ),
     *     @OA\Server(
     *         url="http://localhost:8000/api/v1",
     *         description="Serveur local de développement"
     *     ),
     *     @OA\Parameter(
     *         name="numero",
     *         in="path",
     *         description="Numéro du compte à récupérer",
     *         required=true,
     *         @OA\Schema(type="string", example="CB241234567890")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Compte récupéré avec succès",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", ref="#/components/schemas/Compte"),
     *             @OA\Property(property="timestamp", type="string", format="date-time"),
     *             @OA\Property(property="path", type="string"),
     *             @OA\Property(property="traceId", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Accès refusé",
     *         @OA\JsonContent(ref="#/components/schemas/Error")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Compte non trouvé",
     *         @OA\JsonContent(ref="#/components/schemas/Error")
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Erreur interne du serveur",
     *         @OA\JsonContent(ref="#/components/schemas/Error")
     *     )
     * )
     */
    public function showByNumero(string $numero)
    {
        try {
            // Récupérer l'utilisateur authentifié
            $user = request()->auth_user;

            // Récupérer le compte par numéro
            $compte = $this->compteService->getCompteByNumero($numero);

            // Vérifier les permissions d'accès
            if ($user) {
                $this->checkAccountAccessPermission($compte, $user);
            }

            return $this->successResponse(
                new CompteResource($compte->load('client')),
                'Compte récupéré avec succès'
            );
        } catch (ModelNotFoundException $e) {
            return $this->errorResponse(
                'Le compte avec le numéro spécifié n\'existe pas',
                404,
                [
                    'code' => 'COMPTE_NOT_FOUND',
                    'details' => ['numero' => $numero]
                ]
            );
        } catch (ApiException $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode() ?: 403);
        } catch (\Exception $e) {
            \Log::error('Erreur lors de la récupération du compte par numéro', [
                'numero' => $numero,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return $this->errorResponse('Erreur interne du serveur', 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/comptes/{compteId}",
     *     summary="Récupérer un compte spécifique",
     *     description="Permet de récupérer les détails d'un compte spécifique par son ID. La stratégie de recherche vérifie d'abord la base locale pour les comptes chèque ou épargne actifs, puis la base serverless si nécessaire.",
     *     operationId="getCompteById",
     *     tags={"Comptes"},
     *     @OA\Server(
     *         url="https://banktt.onrender.com/api/v1",
     *         description="Serveur de production Render"
     *     ),
     *     @OA\Server(
     *         url="http://localhost:8000/api/v1",
     *         description="Serveur local de développement"
     *     ),
     *     @OA\Parameter(
     *         name="compteId",
     *         in="path",
     *         description="UUID du compte à récupérer",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Compte récupéré avec succès",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="string", format="uuid", example="550e8400-e29b-41d4-a716-446655440000"),
     *                 @OA\Property(property="numeroCompte", type="string", example="C00123456"),
     *                 @OA\Property(property="titulaire", type="string", example="Amadou Diallo"),
     *                 @OA\Property(property="type", type="string", enum={"cheque", "epargne", "courant"}, example="epargne"),
     *                 @OA\Property(property="solde", type="number", format="decimal", example=1250000),
     *                 @OA\Property(property="devise", type="string", enum={"FCFA", "EUR", "USD"}, example="FCFA"),
     *                 @OA\Property(property="dateCreation", type="string", format="date-time", example="2023-03-15T00:00:00Z"),
     *                 @OA\Property(property="statut", type="string", enum={"actif", "inactif", "bloque", "ferme"}, example="bloque"),
     *                 @OA\Property(property="motifBlocage", type="string", example="Inactivité de 30+ jours", nullable=true),
     *                 @OA\Property(property="metadata", type="object",
     *                     @OA\Property(property="derniereModification", type="string", format="date-time", example="2023-06-10T14:30:00Z"),
     *                     @OA\Property(property="version", type="integer", example=1)
     *                 ),
     *                 @OA\Property(property="client", ref="#/components/schemas/Client")
     *             ),
     *             @OA\Property(property="timestamp", type="string", format="date-time"),
     *             @OA\Property(property="path", type="string"),
     *             @OA\Property(property="traceId", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Compte non trouvé",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="error", type="object",
     *                 @OA\Property(property="code", type="string", example="COMPTE_NOT_FOUND"),
     *                 @OA\Property(property="message", type="string", example="Le compte avec l'ID spécifié n'existe pas"),
     *                 @OA\Property(property="details", type="object",
     *                     @OA\Property(property="compteId", type="string", format="uuid", example="550e8400-e29b-41d4-a716-446655440000")
     *                 )
     *             ),
     *             @OA\Property(property="timestamp", type="string", format="date-time"),
     *             @OA\Property(property="path", type="string"),
     *             @OA\Property(property="traceId", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Erreur interne du serveur",
     *         @OA\JsonContent(ref="#/components/schemas/Error")
     *     )
     * )
     */
    public function show(Compte $compte)
    {
        try {
            // Vérifier les permissions d'accès au compte
            $user = request()->auth_user;
            if ($user) {
                $this->checkAccountAccessPermission($compte, $user);
            }

            // Route Model Binding automatically loads the compte with client relationship
            return $this->successResponse(
                new CompteResource($compte->load('client')),
                'Compte récupéré avec succès'
            );
        } catch (ModelNotFoundException $e) {
            return $this->errorResponse(
                'Le compte avec l\'ID spécifié n\'existe pas',
                404,
                [
                    'code' => 'COMPTE_NOT_FOUND',
                    'details' => ['compteId' => $compte->id ?? request('compteId')]
                ]
            );
        } catch (ApiException $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode() ?: 403);
        } catch (\Exception $e) {
            \Log::error('Erreur lors de la récupération du compte', [
                'compte_id' => $compte->id ?? request('compteId'),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return $this->errorResponse('Erreur interne du serveur', 500);
        }
    }

    /**
     * @OA\Put(
     *     path="/comptes/{id}",
     *     summary="Mettre à jour les informations d'un compte bancaire",
     *     description="Met à jour les informations du client associé au compte bancaire. Tous les champs sont optionnels mais au moins un champ doit être fourni.",
     *     operationId="updateCompte",
     *     tags={"Comptes"},
     *     @OA\Server(
     *         url="https://banktt.onrender.com/api/v1",
     *         description="Serveur de production Render"
     *     ),
     *     @OA\Server(
     *         url="http://localhost:8000/api/v1",
     *         description="Serveur local de développement"
     *     ),
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="UUID du compte à mettre à jour",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="titulaire", type="string", example="Amadou Diallo Junior", description="Nouveau nom du titulaire"),
     *             @OA\Property(property="informationsClient", type="object",
     *                 @OA\Property(property="telephone", type="string", example="771234568", description="Nouveau numéro de téléphone sénégalais"),
     *                 @OA\Property(property="email", type="string", format="email", example="amadou.diallo@example.com", description="Nouvelle adresse email (doit être unique)"),
     *                 @OA\Property(property="adresse", type="string", example="Dakar, Sénégal", description="Nouvelle adresse complète"),
     *                 @OA\Property(property="nci", type="string", example="1234567890123", description="Nouveau numéro de carte d'identité nationale")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Compte mis à jour avec succès",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Compte mis à jour avec succès"),
     *             @OA\Property(property="data", ref="#/components/schemas/Compte"),
     *             @OA\Property(property="timestamp", type="string", format="date-time"),
     *             @OA\Property(property="path", type="string"),
     *             @OA\Property(property="traceId", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Données invalides ou aucun champ fourni",
     *         @OA\JsonContent(ref="#/components/schemas/Error")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Compte non trouvé",
     *         @OA\JsonContent(ref="#/components/schemas/Error")
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Erreur de validation",
     *         @OA\JsonContent(ref="#/components/schemas/Error")
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Erreur interne du serveur",
     *         @OA\JsonContent(ref="#/components/schemas/Error")
     *     )
     * )
     */
    public function update(UpdateCompteRequest $request, string $id)
    {
        try {
            $compte = $this->compteService->updateCompte($id, $request->validated());
            return $this->successResponse(
                new CompteResource($compte),
                'Compte mis à jour avec succès'
            );
        } catch (ApiException $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode() ?: 500);
        } catch (\Exception $e) {
            \Log::error('Erreur lors de la mise à jour du compte', [
                'compte_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request' => $request->all()
            ]);
            return $this->errorResponse('Erreur interne du serveur', 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/comptes/{id}/bloquer",
     *     summary="Bloquer un compte épargne",
     *     description="Bloque un compte épargne actif pour une durée déterminée avec un motif spécifique. Seuls les comptes épargne actifs peuvent être bloqués.",
     *     operationId="blockCompte",
     *     tags={"Comptes"},
     *     @OA\Server(
     *         url="https://banktt.onrender.com/api/v1",
     *         description="Serveur de production Render"
     *     ),
     *     @OA\Server(
     *         url="http://localhost:8000/api/v1",
     *         description="Serveur local de développement"
     *     ),
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="UUID du compte épargne à bloquer",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"motif","duree","unite"},
     *             @OA\Property(property="motif", type="string", example="Activité suspecte détectée", description="Motif du blocage"),
     *             @OA\Property(property="duree", type="integer", example=30, description="Durée du blocage"),
     *             @OA\Property(property="unite", type="string", enum={"jours", "mois", "annees"}, example="jours", description="Unité de temps pour la durée"),
     *             @OA\Property(property="dateDebutBlocage", type="string", format="date-time", example="2025-10-30T14:30:00Z", description="Date de début du blocage (optionnel, défaut: maintenant)")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Compte bloqué avec succès",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Compte bloqué avec succès"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="string", format="uuid"),
     *                 @OA\Property(property="numeroCompte", type="string", example="CB241234567890"),
     *                 @OA\Property(property="statut", type="string", example="bloque"),
     *                 @OA\Property(property="motifBlocage", type="string", example="Activité suspecte détectée"),
     *                 @OA\Property(property="dateDebutBlocage", type="string", format="date-time"),
     *                 @OA\Property(property="dateFinBlocage", type="string", format="date-time"),
     *                 @OA\Property(property="client", ref="#/components/schemas/Client")
     *             ),
     *             @OA\Property(property="timestamp", type="string", format="date-time"),
     *             @OA\Property(property="path", type="string"),
     *             @OA\Property(property="traceId", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Requête invalide ou compte ne peut pas être bloqué",
     *         @OA\JsonContent(ref="#/components/schemas/Error")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Compte non trouvé",
     *         @OA\JsonContent(ref="#/components/schemas/Error")
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Erreur interne du serveur",
     *         @OA\JsonContent(ref="#/components/schemas/Error")
     *     )
     * )
     */
    public function block(\App\Http\Requests\BlockCompteRequest $request, string $id)
    {
        try {
            // Vérifier que seul un admin peut bloquer un compte
            $user = request()->auth_user;
            if (!$user instanceof \App\Models\Admin) {
                return $this->errorResponse('Accès refusé. Seuls les administrateurs peuvent bloquer des comptes.', 403);
            }

            $compte = $this->compteService->blockCompte($id, $request->validated());
            return $this->successResponse(
                new CompteResource($compte),
                'Compte bloqué avec succès'
            );
        } catch (ApiException $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode() ?: 500);
        } catch (\Exception $e) {
            \Log::error('Erreur lors du blocage du compte', [
                'compte_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request' => $request->all()
            ]);
            return $this->errorResponse('Erreur interne du serveur', 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/comptes/{id}/debloquer",
     *     summary="Débloquer un compte épargne",
     *     description="Débloque un compte épargne bloqué avec un motif spécifique. Seuls les comptes bloqués peuvent être débloqués.",
     *     operationId="unblockCompte",
     *     tags={"Comptes"},
     *     @OA\Server(
     *         url="https://banktt.onrender.com/api/v1",
     *         description="Serveur de production Render"
     *     ),
     *     @OA\Server(
     *         url="http://localhost:8000/api/v1",
     *         description="Serveur local de développement"
     *     ),
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="UUID du compte épargne à débloquer",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"motif"},
     *             @OA\Property(property="motif", type="string", example="Vérification complétée", description="Motif du déblocage")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Compte débloqué avec succès",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Compte débloqué avec succès"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="string", format="uuid"),
     *                 @OA\Property(property="numeroCompte", type="string", example="CB241234567890"),
     *                 @OA\Property(property="statut", type="string", example="actif"),
     *                 @OA\Property(property="dateDeblocage", type="string", format="date-time"),
     *                 @OA\Property(property="motifDeblocage", type="string", example="Vérification complétée"),
     *                 @OA\Property(property="client", ref="#/components/schemas/Client")
     *             ),
     *             @OA\Property(property="timestamp", type="string", format="date-time"),
     *             @OA\Property(property="path", type="string"),
     *             @OA\Property(property="traceId", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Requête invalide ou compte ne peut pas être débloqué",
     *         @OA\JsonContent(ref="#/components/schemas/Error")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Compte non trouvé",
     *         @OA\JsonContent(ref="#/components/schemas/Error")
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Erreur interne du serveur",
     *         @OA\JsonContent(ref="#/components/schemas/Error")
     *     )
     * )
     */
    public function unblock(\App\Http\Requests\UnblockCompteRequest $request, string $id)
    {
        try {
            $compte = $this->compteService->unblockCompte($id, $request->validated());
            return $this->successResponse(
                new CompteResource($compte),
                'Compte débloqué avec succès'
            );
        } catch (ApiException $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode() ?: 500);
        } catch (\Exception $e) {
            \Log::error('Erreur lors du déblocage du compte', [
                'compte_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request' => $request->all()
            ]);
            return $this->errorResponse('Erreur interne du serveur', 500);
        }
    }

    /**
     * @OA\Delete(
     *     path="/comptes/{id}",
     *     summary="Supprimer un compte bancaire (soft delete)",
     *     description="Supprime un compte bancaire de manière logicielle. Seuls les comptes actifs peuvent être supprimés. Le compte reste en base de données avec un timestamp de suppression.",
     *     operationId="deleteCompte",
     *     tags={"Comptes"},
     *     @OA\Server(
     *         url="https://banktt.onrender.com/api/v1",
     *         description="Serveur de production Render"
     *     ),
     *     @OA\Server(
     *         url="http://localhost:8000/api/v1",
     *         description="Serveur local de développement"
     *     ),
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="UUID du compte à supprimer",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Compte supprimé avec succès",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Compte supprimé avec succès"),
     *             @OA\Property(property="data", ref="#/components/schemas/Compte"),
     *             @OA\Property(property="timestamp", type="string", format="date-time"),
     *             @OA\Property(property="path", type="string"),
     *             @OA\Property(property="traceId", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Le compte n'est pas actif et ne peut pas être supprimé",
     *         @OA\JsonContent(ref="#/components/schemas/Error")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Compte non trouvé",
     *         @OA\JsonContent(ref="#/components/schemas/Error")
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Erreur interne du serveur",
     *         @OA\JsonContent(ref="#/components/schemas/Error")
     *     )
     * )
     */
    public function destroy(string $id)
    {
        try {
            $compte = $this->compteService->deleteCompte($id);
            return $this->successResponse(
                new CompteResource($compte),
                'Compte supprimé avec succès'
            );
        } catch (ApiException $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode() ?: 500);
        } catch (\Exception $e) {
            \Log::error('Erreur lors de la suppression du compte', [
                'compte_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return $this->errorResponse('Erreur interne du serveur', 500);
        }
    }
}
