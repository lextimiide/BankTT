<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SearchClientRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Authorization handled in controller
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'telephone' => 'nullable|string|regex:/^[0-9]{9}$/',
            'nci' => 'nullable|string|regex:/^[0-9]{13}$/',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'telephone.regex' => 'Le numéro de téléphone doit contenir exactement 9 chiffres.',
            'nci.regex' => 'Le numéro de carte d\'identité nationale doit contenir exactement 13 chiffres.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'telephone' => 'numéro de téléphone',
            'nci' => 'numéro de carte d\'identité nationale',
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            if (!$this->has('telephone') && !$this->has('nci')) {
                $validator->errors()->add('search_params', 'Au moins un paramètre de recherche (telephone ou nci) doit être fourni.');
            }
        });
    }
}