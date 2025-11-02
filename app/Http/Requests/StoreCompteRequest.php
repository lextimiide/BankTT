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
            'type_compte' => 'required|in:cheque,epargne,courant',
            'solde' => 'required|numeric|min:10000',
            'devise' => 'required|in:FCFA,EUR,USD',

            // Client fields - optional, will be auto-generated if not provided
            'client_id' => 'nullable|uuid|exists:clients,id',
            'client' => 'nullable|array',
            'client.titulaire' => 'required_with:client|string|min:2|max:255',
            'client.email' => 'required_with:client|email|unique:clients,email',
            'client.telephone' => [
                'required_with:client',
                new \App\Rules\SenegalesePhone(),
                'unique:clients,telephone'
            ],
            'client.adresse' => 'required_with:client|string|min:5|max:500',
            'client.nci' => [
                'required_with:client',
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
            'type_compte.in' => 'Le type de compte doit être : cheque, epargne ou courant.',
            'solde.required' => 'Le solde initial est obligatoire.',
            'solde.numeric' => 'Le solde doit être un nombre.',
            'solde.min' => 'Le solde initial doit être d\'au moins 10 000 FCFA.',
            'devise.required' => 'La devise est obligatoire.',
            'devise.in' => 'La devise doit être : FCFA, EUR ou USD.',
            'client_id.uuid' => 'L\'ID du client doit être un UUID valide.',
            'client_id.exists' => 'Le client spécifié n\'existe pas.',
            'client.array' => 'Les informations client doivent être un objet.',
            'client.titulaire.required_with' => 'Le nom du titulaire est obligatoire lorsque des informations client sont fournies.',
            'client.titulaire.min' => 'Le nom du titulaire doit contenir au moins 2 caractères.',
            'client.titulaire.max' => 'Le nom du titulaire ne peut pas dépasser 255 caractères.',
            'client.email.required_with' => 'L\'email est obligatoire lorsque des informations client sont fournies.',
            'client.email.email' => 'L\'email doit être une adresse email valide.',
            'client.email.unique' => 'Cet email est déjà utilisé.',
            'client.telephone.required_with' => 'Le numéro de téléphone est obligatoire lorsque des informations client sont fournies.',
            'client.telephone.unique' => 'Ce numéro de téléphone est déjà utilisé.',
            'client.adresse.required_with' => 'L\'adresse est obligatoire lorsque des informations client sont fournies.',
            'client.adresse.min' => 'L\'adresse doit contenir au moins 5 caractères.',
            'client.adresse.max' => 'L\'adresse ne peut pas dépasser 500 caractères.',
            'client.nci.required_with' => 'Le numéro de carte d\'identité nationale est obligatoire lorsque des informations client sont fournies.'
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
            'solde' => 'solde initial',
            'devise' => 'devise',
            'client_id' => 'ID du client',
            'client' => 'informations client',
            'client.titulaire' => 'nom du titulaire',
            'client.email' => 'email',
            'client.telephone' => 'numéro de téléphone',
            'client.adresse' => 'adresse',
            'client.nci' => 'numéro de carte d\'identité'
        ];
    }
}
