<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMouvementFinancierRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->filled('montant')) {
            $this->merge(['montant' => str_replace([' ', ','], ['', '.'], (string) $this->input('montant'))]);
        }
    }

    public function rules(): array
    {
        return [
            'sens' => ['required', Rule::in(['entree', 'sortie'])],
            'libelle' => ['required', 'string', 'max:255'],
            'montant' => ['required', 'numeric', 'min:1'],
        ];
    }
}
