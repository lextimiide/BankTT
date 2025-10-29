<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class SenegaleseNCI implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // Clean the input - remove spaces and convert to uppercase
        $cleaned = strtoupper(trim($value));

        // Senegalese NCI format: typically 13 digits or specific patterns
        // Common formats: 13 digits, or patterns like XXX-XXX-XXX-X
        if (empty($cleaned)) {
            $fail('Le numéro de carte d\'identité nationale est requis.');
            return;
        }

        // Check for 13 consecutive digits (most common format)
        if (preg_match('/^\d{13}$/', $cleaned)) {
            // Validate the format - Senegalese NCI often follows specific patterns
            // For now, accept 13 digits as valid
            return;
        }

        // Check for formatted NCI (XXX-XXX-XXX-X)
        if (preg_match('/^\d{3}-\d{3}-\d{3}-\d{1}$/', $cleaned)) {
            return;
        }

        // Check for other common formats
        if (preg_match('/^[A-Z0-9]{8,15}$/', $cleaned)) {
            return;
        }

        $fail('Le numéro de carte d\'identité nationale doit être un numéro valide (13 chiffres ou format XXX-XXX-XXX-X).');
    }
}
