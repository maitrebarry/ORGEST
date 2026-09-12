<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class MalianPhone implements ValidationRule
{
    /**
     * Un numéro de téléphone malien comporte 8 chiffres, tous préfixes
     * confondus (5x, 6x, 7x, 8x, 9x). On ne s'appuie pas sur libphonenumber
     * dont la base pour le Mali est incomplète et rejette des préfixes
     * pourtant bien en service (81, 86, 87, 88…).
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! preg_match('/^\d{8}$/', (string) $value)) {
            $fail('Le numéro de téléphone doit contenir exactement 8 chiffres.');
        }
    }
}
