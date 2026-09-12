<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class OuvrirJourneeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Le formatage en direct (espaces tous les 3 chiffres, cf.
     * layouts/admin.blade.php) doit être retiré avant validation.
     */
    protected function prepareForValidation(): void
    {
        if ($this->filled('montant_initial')) {
            $this->merge(['montant_initial' => str_replace(' ', '', (string) $this->input('montant_initial'))]);
        }
    }

    public function rules(): array
    {
        return [
            'montant_initial' => ['required', 'numeric', 'min:0'],
            'observations' => ['nullable', 'string'],
        ];
    }
}
