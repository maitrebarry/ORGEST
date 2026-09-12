<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreBaremeVersionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Normalise les décimales à virgule ("18,71" -> "18.71", clavier
     * français — sécurité serveur, le JS le fait déjà à la sortie du champ)
     * et retire les lignes entièrement vides avant validation (ex. une
     * ligne ajoutée par erreur puis jamais remplie) : on ne bloque pas tout
     * le formulaire pour ça, on l'ignore silencieusement.
     */
    protected function prepareForValidation(): void
    {
        if (! is_array($this->input('lignes'))) {
            return;
        }

        $lignes = collect($this->input('lignes'))
            ->map(function ($ligne) {
                foreach (['densite_min', 'densite_max', 'carat'] as $champ) {
                    if (isset($ligne[$champ])) {
                        $ligne[$champ] = str_replace(',', '.', trim((string) $ligne[$champ]));
                    }
                }

                return $ligne;
            })
            ->filter(function ($ligne) {
                foreach (['densite_min', 'densite_max', 'carat'] as $champ) {
                    if (($ligne[$champ] ?? '') !== '') {
                        return true;
                    }
                }

                return false;
            })
            ->values()
            ->all();

        $this->merge(['lignes' => $lignes]);
    }

    public function rules(): array
    {
        return [
            'libelle' => ['required', 'string', 'max:255'],
            'observations' => ['nullable', 'string'],
            'lignes' => ['required', 'array', 'min:1'],
            'lignes.*.densite_min' => ['required', 'numeric', 'min:0'],
            'lignes.*.densite_max' => ['required', 'numeric', 'min:0', 'gte:lignes.*.densite_min'],
            'lignes.*.carat' => ['required', 'numeric', 'min:0'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $lignes = collect($this->input('lignes', []))
                ->filter(fn ($l) => isset($l['densite_min'], $l['densite_max']))
                ->map(fn ($l) => ['min' => (float) $l['densite_min'], 'max' => (float) $l['densite_max']])
                ->sortBy('min')
                ->values();

            foreach ($lignes as $i => $ligne) {
                if ($ligne['min'] > $ligne['max']) {
                    $validator->errors()->add('lignes', "Une ligne a une densité minimum supérieure à la densité maximum ({$ligne['min']} > {$ligne['max']}).");

                    continue;
                }

                $suivante = $lignes->get($i + 1);
                if ($suivante && $ligne['max'] >= $suivante['min']) {
                    $validator->errors()->add(
                        'lignes',
                        "Chevauchement détecté entre les plages {$ligne['min']}–{$ligne['max']} et {$suivante['min']}–{$suivante['max']} : le barème doit être déterministe, sans plages qui se recoupent."
                    );
                }
            }
        });
    }
}
