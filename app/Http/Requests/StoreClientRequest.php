<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreClientRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // À adapter selon vos besoins d'autorisation
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'titulaire' => 'required|string|max:255',
            'nci' => 'nullable|string|max:20|unique:clients,nci',
            'email' => 'required|email|unique:clients,email',
            'telephone' => 'required|string|max:20',
            'adresse' => 'required|string|max:500',
            'statut' => 'nullable|in:actif,inactif,suspendu',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'titulaire.required' => 'Le titulaire est obligatoire.',
            'nci.unique' => 'Ce numéro de carte d\'identité est déjà utilisé.',
            'email.required' => 'L\'email est obligatoire.',
            'email.email' => 'L\'email doit être valide.',
            'email.unique' => 'Cet email est déjà utilisé.',
            'telephone.required' => 'Le téléphone est obligatoire.',
            'adresse.required' => 'L\'adresse est obligatoire.',
            'statut.in' => 'Le statut doit être actif, inactif ou suspendu.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'titulaire' => 'titulaire',
            'nci' => 'numéro de carte d\'identité',
            'email' => 'email',
            'telephone' => 'téléphone',
            'adresse' => 'adresse',
            'statut' => 'statut',
        ];
    }
}
