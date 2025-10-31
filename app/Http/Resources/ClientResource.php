<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ClientResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'titulaire' => $this->titulaire,
            'email' => $this->email,
            'telephone' => $this->telephone,
            'adresse' => $this->adresse,
            'nci' => $this->nci,
            'statut' => $this->statut,
            'dateCreation' => $this->created_at->toISOString(),
            'emailVerifiedAt' => $this->email_verified_at?->toISOString(),
            'metadata' => [
                'derniereModification' => $this->updated_at->toISOString(),
                'version' => 1,
            ],
            'comptes' => $this->whenLoaded('comptes', function () {
                return CompteResource::collection($this->comptes);
            }),
            'nombreComptes' => $this->whenLoaded('comptes', function () {
                return $this->comptes->count();
            }),
        ];
    }
}