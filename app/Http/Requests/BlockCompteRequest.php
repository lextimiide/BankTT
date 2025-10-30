<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BlockCompteRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // TODO: Implement proper authorization
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'motif' => 'required|string|max:500',
            'duree' => 'required|integer|min:1|max:365',
            'unite' => 'required|string|in:jours,mois,annees',
            'dateDebutBlocage' => 'sometimes|date|after:now'
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'motif.required' => 'Le motif de blocage est obligatoire',
            'motif.string' => 'Le motif doit être une chaîne de caractères',
            'motif.max' => 'Le motif ne peut pas dépasser 500 caractères',
            'duree.required' => 'La durée de blocage est obligatoire',
            'duree.integer' => 'La durée doit être un nombre entier',
            'duree.min' => 'La durée minimale est de 1 jour',
            'duree.max' => 'La durée maximale est de 365 jours',
            'unite.required' => 'L\'unité de temps est obligatoire',
            'unite.in' => 'L\'unité doit être jours, mois ou années',
            'dateDebutBlocage.date' => 'La date de début doit être une date valide',
            'dateDebutBlocage.after' => 'La date de début doit être dans le futur'
        ];
    }
}
