<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\ApiErrorCode;
use App\Enums\HttpStatusCode;
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
     * Vérifie les permissions d'accès à un compte via Policy
     */
    private function checkAccountAccessPermission(Compte $compte, $user): void
    {
        if (!$this->authorize('view', $compte)) {
            throw new ApiException('Accès refusé. Vous ne pouvez accéder qu\'à vos propres comptes.', 403);
        }
    }

    public function index(Request $request)
    {
        try {
            $result = $this->compteService->getComptes($request);
            return $this->successResponse($result, 'Comptes récupérés avec succès');
        } catch (ValidationException $e) {
            return $this->validationError($e->errors(), 'Paramètres de requête invalides');
        } catch (\Exception $e) {
            \Log::error('Erreur lors de la récupération des comptes', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request' => $request->all()
            ]);
            return $this->serverError();
        }
    }

    public function store(StoreCompteRequest $request)
    {
        $this->authorize('create', Compte::class);

        try {
            $compte = $this->compteService->createCompte($request->validated());

            return $this->created([
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
            ], 'Compte créé avec succès');
        } catch (ValidationException $e) {
            return $this->validationError($e->errors());
        } catch (ModelNotFoundException $e) {
            return $this->notFound('Client');
        } catch (ApiException $e) {
            return $this->error($e->getMessage(), $e->getCode() ?: HttpStatusCode::BAD_REQUEST->value, ApiErrorCode::INTERNAL_ERROR->value);
        } catch (\Exception $e) {
            \Log::error('Erreur lors de la création du compte', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request' => $request->all()
            ]);
            return $this->serverError();
        }
    }


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
            return $this->error($e->getMessage(), $e->getCode() ?: HttpStatusCode::FORBIDDEN->value, ApiErrorCode::FORBIDDEN->value);
        } catch (\Exception $e) {
            \Log::error('Erreur lors de la récupération du compte par numéro', [
                'numero' => $numero,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return $this->serverError();
        }
    }

    public function show(Compte $compte)
    {
        $this->authorize('view', $compte);

        try {
            return $this->success(
                new CompteResource($compte->load('client')),
                'Compte récupéré avec succès'
            );
        } catch (ModelNotFoundException $e) {
            return $this->notFound('Compte');
        } catch (\Exception $e) {
            \Log::error('Erreur lors de la récupération du compte', [
                'compte_id' => $compte->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return $this->serverError();
        }
    }

    public function update(UpdateCompteRequest $request, Compte $compte)
    {
        $this->authorize('update', $compte);

        try {
            $compte = $this->compteService->updateCompte($compte->id, $request->validated());
            return $this->updated(
                new CompteResource($compte),
                'Compte mis à jour avec succès'
            );
        } catch (ApiException $e) {
            return $this->error($e->getMessage(), $e->getCode() ?: HttpStatusCode::INTERNAL_SERVER_ERROR->value, ApiErrorCode::INTERNAL_ERROR->value);
        } catch (\Exception $e) {
            \Log::error('Erreur lors de la mise à jour du compte', [
                'compte_id' => $compte->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request' => $request->all()
            ]);
            return $this->serverError();
        }
    }

    public function block(\App\Http\Requests\BlockCompteRequest $request, Compte $compte)
    {
        $this->authorize('block', $compte);

        try {
            $compte = $this->compteService->blockCompte($compte->id, $request->validated());
            return $this->updated(
                new CompteResource($compte),
                'Compte bloqué avec succès'
            );
        } catch (ApiException $e) {
            return $this->error($e->getMessage(), $e->getCode() ?: 500);
        } catch (\Exception $e) {
            \Log::error('Erreur lors du blocage du compte', [
                'compte_id' => $compte->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request' => $request->all()
            ]);
            return $this->serverError();
        }
    }

    public function unblock(\App\Http\Requests\UnblockCompteRequest $request, Compte $compte)
    {
        $this->authorize('unblock', $compte);

        try {
            $compte = $this->compteService->unblockCompte($compte->id, $request->validated());
            return $this->updated(
                new CompteResource($compte),
                'Compte débloqué avec succès'
            );
        } catch (ApiException $e) {
            return $this->error($e->getMessage(), $e->getCode() ?: 500);
        } catch (\Exception $e) {
            \Log::error('Erreur lors du déblocage du compte', [
                'compte_id' => $compte->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request' => $request->all()
            ]);
            return $this->serverError();
        }
    }

    public function destroy(Compte $compte)
    {
        $this->authorize('delete', $compte);

        try {
            $compte = $this->compteService->deleteCompte($compte->id);
            return $this->deleted('Compte supprimé avec succès');
        } catch (ApiException $e) {
            return $this->error($e->getMessage(), $e->getCode() ?: 500);
        } catch (\Exception $e) {
            \Log::error('Erreur lors de la suppression du compte', [
                'compte_id' => $compte->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return $this->serverError();
        }
    }
}
