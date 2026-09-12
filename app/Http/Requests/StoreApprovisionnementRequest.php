<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreApprovisionnementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->filled('montant')) {
            $this->merge(['montant' => str_replace(' ', '', (string) $this->input('montant'))]);
        }
    }

    public function rules(): array
    {
        return [
            'montant' => ['required', 'numeric', 'min:1'],
            'libelle' => ['nullable', 'string', 'max:255'],
        ];
    }
}
