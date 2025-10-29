<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Rules\SenegaleseNCI;
use App\Rules\SenegalesePhone;
use Illuminate\Foundation\Http\FormRequest;

class UpdateCompteRequest extends FormRequest
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
            // Titulaire (optionnel)
            'titulaire' => 'sometimes|string|min:2|max:255',

            // Informations client (structure imbriquée)
            'informationsClient' => 'sometimes|array',
            'informationsClient.telephone' => [
                'sometimes',
                new SenegalesePhone(),
                'unique:clients,telephone'
            ],
            'informationsClient.email' => [
                'sometimes',
                'email',
                'unique:clients,email'
            ],
            'informationsClient.adresse' => 'sometimes|string|min:5|max:500',
            'informationsClient.nci' => [
                'sometimes',
                'nullable',
                new SenegaleseNCI()
            ],

            // Validation personnalisée : au moins un champ doit être fourni
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            // Vérifier qu'au moins un champ est fourni pour la modification
            $hasTitulaire = $this->has('titulaire');
            $hasClientInfo = $this->has('informationsClient') &&
                           !empty(array_filter($this->input('informationsClient', [])));

            if (!$hasTitulaire && !$hasClientInfo) {
                $validator->errors()->add('general', 'Au moins un champ doit être fourni pour la modification.');
            }
        });
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Remove empty strings from unique validation to avoid SQL errors
        $clientId = $this->route('compte')?->client_id ?? '';

        if ($this->input('informationsClient.telephone') === '') {
            $this->request->remove('informationsClient.telephone');
        }
        if ($this->input('informationsClient.email') === '') {
            $this->request->remove('informationsClient.email');
        }
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'titulaire.sometimes' => 'Le titulaire est optionnel.',
            'titulaire.string' => 'Le titulaire doit être une chaîne de caractères.',
            'titulaire.min' => 'Le titulaire doit contenir au moins 2 caractères.',
            'titulaire.max' => 'Le titulaire ne peut pas dépasser 255 caractères.',
            'informationsClient.array' => 'Les informations client doivent être un objet.',
            'informationsClient.telephone.unique' => 'Ce numéro de téléphone est déjà utilisé.',
            'informationsClient.email.email' => 'L\'email doit être une adresse email valide.',
            'informationsClient.email.unique' => 'Cet email est déjà utilisé.',
            'informationsClient.adresse.min' => 'L\'adresse doit contenir au moins 5 caractères.',
            'informationsClient.adresse.max' => 'L\'adresse ne peut pas dépasser 500 caractères.',
            'general' => 'Au moins un champ doit être fourni pour la modification.'
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'titulaire' => 'titulaire du compte',
            'informationsClient.telephone' => 'numéro de téléphone',
            'informationsClient.email' => 'adresse email',
            'informationsClient.adresse' => 'adresse',
            'informationsClient.nci' => 'numéro de carte d\'identité'
        ];
    }
}
