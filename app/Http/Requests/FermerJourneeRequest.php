<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class FermerJourneeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->filled('solde_physique')) {
            $this->merge(['solde_physique' => str_replace(' ', '', (string) $this->input('solde_physique'))]);
        }
    }

    public function rules(): array
    {
        return [
            'solde_physique' => ['required', 'numeric', 'min:0'],
        ];
    }
}
