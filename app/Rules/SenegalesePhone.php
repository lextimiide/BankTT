<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class SenegalesePhone implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // Remove any spaces, dashes, or other non-numeric characters except +
        $cleaned = preg_replace('/[^\d+]/', '', $value);

        // Check if it starts with +221 or 221 or just the local number
        if (!preg_match('/^(?:\+221|221|7[0678]\d{7})$/', $cleaned)) {
            $fail('Le numéro de téléphone doit être un numéro sénégalais valide (ex: +221771234567 ou 771234567).');
            return;
        }

        // If it starts with 7, add 221 prefix for consistency
        if (preg_match('/^7[0678]\d{7}$/', $cleaned)) {
            $cleaned = '221' . $cleaned;
        }

        // Final validation for Senegalese mobile numbers
        if (!preg_match('/^(?:\+?221)?7[0678]\d{7}$/', $cleaned)) {
            $fail('Le numéro de téléphone doit être un numéro de mobile sénégalais valide.');
        }
    }
}
