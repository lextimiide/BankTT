<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\ApiErrorCode;
use App\Enums\HttpStatusCode;
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

    public function search(SearchClientRequest $request)
    {
        $this->authorize('search', Client::class);

        try {
            $client = $this->clientService->searchClient($request->validated());

            return $this->success(
                new ClientResource($client->load('comptes')),
                'Client trouvé avec succès'
            );
        } catch (ValidationException $e) {
            return $this->validationError($e->errors());
        } catch (ModelNotFoundException $e) {
            return $this->notFound('Client');
        } catch (ApiException $e) {
            return $this->error($e->getMessage(), $e->getCode() ?: HttpStatusCode::INTERNAL_SERVER_ERROR->value, ApiErrorCode::INTERNAL_ERROR->value);
        } catch (\Exception $e) {
            \Log::error('Erreur lors de la recherche de client', [
                'search_params' => $request->validated(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return $this->serverError();
        }
    }
}