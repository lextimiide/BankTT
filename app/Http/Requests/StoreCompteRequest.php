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
            'type' => 'required|in:cheque,epargne,courant',
            'soldeInitial' => 'required|numeric|min:10000',
            'devise' => 'required|in:FCFA,EUR,USD',

            // Client fields (always required - no existing client support)
            'client.titulaire' => 'required|string|min:2|max:255',
            'client.email' => 'required|email|unique:clients,email',
            'client.telephone' => [
                'required',
                new SenegalesePhone(),
                'unique:clients,telephone'
            ],
            'client.adresse' => 'required|string|min:5|max:500',
            'client.nci' => [
                'required',
                new SenegaleseNCI()
            ]
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'numero_compte.unique' => 'Ce numéro de compte est déjà utilisé.',
            'type.required' => 'Le type de compte est obligatoire.',
            'type.in' => 'Le type de compte doit être : cheque, epargne ou courant.',
            'soldeInitial.required' => 'Le solde initial est obligatoire.',
            'soldeInitial.numeric' => 'Le solde initial doit être un nombre.',
            'soldeInitial.min' => 'Le solde initial doit être d\'au moins 10 000 FCFA.',
            'devise.required' => 'La devise est obligatoire.',
            'devise.in' => 'La devise doit être : FCFA, EUR ou USD.',
            'client.titulaire.required' => 'Le nom du titulaire est obligatoire.',
            'client.titulaire.min' => 'Le nom du titulaire doit contenir au moins 2 caractères.',
            'client.titulaire.max' => 'Le nom du titulaire ne peut pas dépasser 255 caractères.',
            'client.email.required' => 'L\'email est obligatoire.',
            'client.email.email' => 'L\'email doit être une adresse email valide.',
            'client.email.unique' => 'Cet email est déjà utilisé.',
            'client.telephone.required' => 'Le numéro de téléphone est obligatoire.',
            'client.telephone.unique' => 'Ce numéro de téléphone est déjà utilisé.',
            'client.adresse.required' => 'L\'adresse est obligatoire.',
            'client.adresse.min' => 'L\'adresse doit contenir au moins 5 caractères.',
            'client.adresse.max' => 'L\'adresse ne peut pas dépasser 500 caractères.',
            'client.nci.required' => 'Le numéro de carte d\'identité nationale est obligatoire.'
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'numero_compte' => 'numéro de compte',
            'type' => 'type de compte',
            'soldeInitial' => 'solde initial',
            'devise' => 'devise',
            'client.titulaire' => 'nom du titulaire',
            'client.email' => 'email',
            'client.telephone' => 'numéro de téléphone',
            'client.adresse' => 'adresse',
            'client.nci' => 'numéro de carte d\'identité'
        ];
    }
}
