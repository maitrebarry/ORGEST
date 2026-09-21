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
    /**
     * Ramène une saisie usuelle ("74 74 56 69", "74-74-56-69", "+223 74 74 56
     * 69", "00223…") au format stocké : 8 chiffres collés. À appeler AVANT la
     * validation, l'unicité et la connexion, qui comparent la valeur brute.
     */
    public static function normaliser(mixed $valeur): mixed
    {
        if (! is_string($valeur)) {
            return $valeur;
        }

        $numero = preg_replace('/[\s\x{00A0}.\-()]+/u', '', $valeur);

        if (preg_match('/^(?:\+|00)?223(\d{8})$/', $numero, $morceaux)) {
            return $morceaux[1];
        }

        return $numero;
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! preg_match('/^\d{8}$/', (string) $value)) {
            $fail('Le numéro de téléphone doit contenir exactement 8 chiffres.');
        }
    }
}
