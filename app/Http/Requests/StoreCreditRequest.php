<?php

namespace App\Http\Requests;

use App\Models\MouvementFinancier;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCreditRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        foreach (['montant_accorde', 'montant_remis'] as $champ) {
            if ($this->filled($champ)) {
                $this->merge([$champ => str_replace([' ', ','], ['', '.'], (string) $this->input($champ))]);
            }
        }
    }

    public function rules(): array
    {
        return [
            'client_id' => ['required', 'exists:clients,id'],
            'date_credit' => ['required', 'date'],
            'montant_accorde' => ['required', 'numeric', 'min:0.01'],
            'montant_remis' => ['required', 'numeric', 'min:0.01'],
            'observations' => ['nullable', 'string'],
            'mode_paiement' => ['nullable', Rule::in(array_keys(MouvementFinancier::MODES_PAIEMENT))],
        ];
    }
}
