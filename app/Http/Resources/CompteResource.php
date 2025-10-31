<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CompteResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $data = [
            'id' => $this->id,
            'numeroCompte' => $this->numero_compte,
            'titulaire' => $this->whenLoaded('client', fn() => $this->client->titulaire),
            'type' => $this->type,
            'solde' => $this->solde,
            'devise' => $this->devise,
            'dateCreation' => $this->created_at->toISOString(),
            'statut' => $this->statut,
            'metadata' => [
                'derniereModification' => $this->updated_at->toISOString(),
                'version' => 1,
            ],
            'client' => $this->whenLoaded('client', fn() => [
                'id' => $this->client->id,
                'titulaire' => $this->client->titulaire,
                'email' => $this->client->email,
                'telephone' => $this->client->telephone,
            ]),
        ];

        // Ajouter les informations de blocage pour les comptes épargne
        if ($this->type === 'epargne') {
            $data['blocage'] = [
                'dateDebutBlocage' => $this->date_debut_blocage?->toISOString(),
                'dateFinBlocage' => $this->date_fin_blocage?->toISOString(),
                'motifBlocage' => $this->motif_blocage,
                'motifDeblocage' => $this->motif_deblocage,
                'dateDeblocage' => $this->date_deblocage?->toISOString(),
            ];
        }

        return $data;
    }
}
