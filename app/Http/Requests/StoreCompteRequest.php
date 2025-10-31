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
            'numero_compte' => 'required|string|unique:comptes,numero_compte',
            'type_compte' => 'required|in:epargne,courant',
            'solde' => 'required|numeric|min:0',
            'client_id' => 'required|uuid|exists:clients,id',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'numero_compte.required' => 'Le numéro de compte est obligatoire.',
            'numero_compte.string' => 'Le numéro de compte doit être une chaîne de caractères.',
            'numero_compte.unique' => 'Ce numéro de compte est déjà utilisé.',
            'type_compte.required' => 'Le type de compte est obligatoire.',
            'type_compte.in' => 'Le type de compte doit être : epargne ou courant.',
            'solde.required' => 'Le solde est obligatoire.',
            'solde.numeric' => 'Le solde doit être un nombre.',
            'solde.min' => 'Le solde ne peut pas être négatif.',
            'client_id.required' => 'L\'ID du client est obligatoire.',
            'client_id.uuid' => 'L\'ID du client doit être un UUID valide.',
            'client_id.exists' => 'Le client spécifié n\'existe pas.',
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
        ];
    }
}
