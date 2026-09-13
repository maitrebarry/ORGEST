<?php

namespace App\Http\Requests;

use App\Models\MouvementFinancier;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRemboursementCreditRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        foreach (['montant_especes', 'prix_base'] as $champ) {
            if ($this->filled($champ)) {
                $this->merge([$champ => str_replace([' ', ','], ['', '.'], (string) $this->input($champ))]);
            }
        }

        if (is_array($this->input('barres'))) {
            $barres = collect($this->input('barres'))->map(function ($barre) {
                foreach (['poids', 'eau'] as $champ) {
                    if (isset($barre[$champ])) {
                        $barre[$champ] = str_replace([' ', ','], ['', '.'], trim((string) $barre[$champ]));
                    }
                }

                return $barre;
            })->filter(fn ($barre) => filled($barre['poids'] ?? null) || filled($barre['eau'] ?? null))->values()->all();

            $this->merge(['barres' => $barres]);
        }
    }

    public function rules(): array
    {
        $mode = $this->input('mode');

        return [
            'date_remboursement' => ['required', 'date'],
            'mode' => ['required', Rule::in(array_keys(\App\Models\RemboursementCredit::MODES))],
            'montant_especes' => [Rule::requiredIf(in_array($mode, ['especes', 'or_especes'])), 'nullable', 'numeric', 'min:0.01'],
            'mode_paiement' => [Rule::requiredIf($mode !== 'or'), 'nullable', Rule::in(array_keys(MouvementFinancier::MODES_PAIEMENT))],
            'prix_base' => [Rule::requiredIf(in_array($mode, ['or', 'or_especes'])), 'nullable', 'numeric', 'min:0.01'],
            'barres' => [Rule::requiredIf(in_array($mode, ['or', 'or_especes'])), 'nullable', 'array', 'min:1'],
            'barres.*.poids' => ['required_with:barres', 'numeric', 'min:0.001'],
            'barres.*.eau' => ['required_with:barres', 'numeric', 'min:0.0001'],
            'observations' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'montant_especes.required' => 'Le montant en espèces est obligatoire pour ce mode de remboursement.',
            'prix_base.required' => "Le prix de base du jour est obligatoire pour valoriser l'or apporté.",
            'barres.required' => "Au moins une barre d'or est requise pour ce mode de remboursement.",
            'barres.*.poids.required_with' => 'Le poids est obligatoire pour chaque barre.',
            'barres.*.eau.required_with' => "La valeur d'eau est obligatoire pour chaque barre.",
        ];
    }
}
