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
            'numero_compte' => 'nullable|string|unique:comptes,numero_compte',
            'type_compte' => 'required|in:epargne,courant',
            'solde' => 'required|numeric|min:0',

            // Client fields - either existing client ID or new client details
            'client_id' => 'nullable|uuid|exists:clients,id',
            'client.titulaire' => 'required_without:client_id|string|min:2|max:255',
            'client.email' => 'required_without:client_id|email|unique:clients,email',
            'client.telephone' => [
                'required_without:client_id',
                new \App\Rules\SenegalesePhone(),
                'unique:clients,telephone'
            ],
            'client.adresse' => 'required_without:client_id|string|min:5|max:500',
            'client.nci' => [
                'required_without:client_id',
                new \App\Rules\SenegaleseNCI()
            ]
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'numero_compte.string' => 'Le numéro de compte doit être une chaîne de caractères.',
            'numero_compte.unique' => 'Ce numéro de compte est déjà utilisé.',
            'type_compte.required' => 'Le type de compte est obligatoire.',
            'type_compte.in' => 'Le type de compte doit être : epargne ou courant.',
            'solde.required' => 'Le solde est obligatoire.',
            'solde.numeric' => 'Le solde doit être un nombre.',
            'solde.min' => 'Le solde ne peut pas être négatif.',
            'client_id.uuid' => 'L\'ID du client doit être un UUID valide.',
            'client_id.exists' => 'Le client spécifié n\'existe pas.',
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
            'client.nci.required_without' => 'Le numéro de carte d\'identité nationale est obligatoire pour un nouveau client.'
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'numero_compte' => 'numéro de compte',
            'type_compte' => 'type de compte',
            'solde' => 'solde',
            'client_id' => 'ID du client',
            'client.titulaire' => 'nom du titulaire',
            'client.email' => 'email',
            'client.telephone' => 'numéro de téléphone',
            'client.adresse' => 'adresse',
            'client.nci' => 'numéro de carte d\'identité'
        ];
    }
}
