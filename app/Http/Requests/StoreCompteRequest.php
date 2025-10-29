<?php

namespace App\Http\Requests;

use App\Rules\SenegaleseNCI;
use App\Rules\SenegalesePhone;
use Illuminate\Foundation\Http\FormRequest;

class StoreCompteRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Allow all authenticated users for now
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            // Account fields
            'type' => 'required|in:cheque,epargne,courant',
            'soldeInitial' => 'required|numeric|min:10000',
            'devise' => 'required|in:FCFA,EUR,USD',

            // Client fields (required for new clients, optional for existing)
            'client.titulaire' => 'required_without:client.id|string|min:2|max:255',
            'client.email' => 'required_without:client.id|email|unique:clients,email',
            'client.telephone' => [
                'required_without:client.id',
                new SenegalesePhone(),
                'unique:clients,telephone'
            ],
            'client.adresse' => 'required_without:client.id|string|min:5|max:500',
            'client.nci' => [
                'nullable',
                new SenegaleseNCI()
            ],

            // For existing clients
            'client.id' => 'nullable|uuid|exists:clients,id'
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'type.required' => 'Le type de compte est obligatoire.',
            'type.in' => 'Le type de compte doit être : cheque, epargne ou courant.',
            'solde_initial.required' => 'Le solde initial est obligatoire.',
            'solde_initial.numeric' => 'Le solde initial doit être un nombre.',
            'solde_initial.min' => 'Le solde initial doit être d\'au moins 10 000 FCFA.',
            'devise.required' => 'La devise est obligatoire.',
            'devise.in' => 'La devise doit être : FCFA, EUR ou USD.',
            'client.titulaire.required_without' => 'Le nom du titulaire est obligatoire pour un nouveau client.',
            'client.titulaire.min' => 'Le nom du titulaire doit contenir au moins 2 caractères.',
            'client.titulaire.max' => 'Le nom du titulaire ne peut pas dépasser 255 caractères.',
            'client.email.required_without' => 'L\'email est obligatoire pour un nouveau client.',
            'client.email.email' => 'L\'email doit être une adresse email valide.',
            'client.email.unique' => 'Cet email est déjà utilisé.',
            'client.telephone.required_without' => 'Le numéro de téléphone est obligatoire pour un nouveau client.',
            'client.telephone.unique' => 'Ce numéro de téléphone est déjà utilisé.',
            'client.adresse.required_without' => 'L\'adresse est obligatoire pour un nouveau client.',
            'client.adresse.min' => 'L\'adresse doit contenir au moins 5 caractères.',
            'client.adresse.max' => 'L\'adresse ne peut pas dépasser 500 caractères.',
            'client.id.uuid' => 'L\'ID du client doit être un UUID valide.',
            'client.id.exists' => 'Le client spécifié n\'existe pas.'
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'type' => 'type de compte',
            'solde_initial' => 'solde initial',
            'devise' => 'devise',
            'client.titulaire' => 'nom du titulaire',
            'client.email' => 'email',
            'client.telephone' => 'numéro de téléphone',
            'client.adresse' => 'adresse',
            'client.nci' => 'numéro de carte d\'identité',
            'client.id' => 'ID du client'
        ];
    }
}
