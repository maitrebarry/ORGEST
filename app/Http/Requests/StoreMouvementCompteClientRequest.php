<?php

namespace App\Http\Requests;

use App\Models\MouvementCompteClient;
use App\Models\MouvementFinancier;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMouvementCompteClientRequest extends FormRequest
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
            'type' => ['required', Rule::in(array_keys(MouvementCompteClient::TYPES))],
            'sens' => ['required', Rule::in(array_keys(MouvementCompteClient::SENS))],
            'montant' => ['required', 'numeric', 'min:0.01'],
            'date_mouvement' => ['required', 'date'],
            'impacte_caisse' => ['nullable', 'boolean'],
            'mode_paiement' => [Rule::requiredIf($this->boolean('impacte_caisse')), 'nullable', Rule::in(array_keys(MouvementFinancier::MODES_PAIEMENT))],
            'observations' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'mode_paiement.required' => 'Le mode de paiement est obligatoire pour un mouvement qui touche la caisse.',
        ];
    }
}
