<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreOperationAchatRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Normalise les décimales à virgule ("27,89" -> "27.89", clavier
     * français) et retire les barres entièrement vides (ex. ligne ajoutée
     * puis jamais remplie) avant validation. Le JS du formulaire fait déjà
     * cette normalisation à la sortie du champ ; ceci est une sécurité
     * côté serveur si jamais elle n'a pas eu lieu (JS désactivé, soumission
     * au clavier sans quitter le champ...).
     */
    protected function prepareForValidation(): void
    {
        if ($this->filled('prix_base')) {
            $prixBase = str_replace(' ', '', (string) $this->input('prix_base'));
            $this->merge(['prix_base' => str_replace(',', '.', $prixBase)]);
        }

        if (! is_array($this->input('barres'))) {
            return;
        }

        $barres = collect($this->input('barres'))
            ->map(function ($barre) {
                foreach (['poids', 'eau', 'densite', 'carat', 'prix_unitaire'] as $champ) {
                    if (isset($barre[$champ])) {
                        $barre[$champ] = str_replace([' ', ','], ['', '.'], trim((string) $barre[$champ]));
                    }
                }

                return $barre;
            })
            ->filter(function ($barre) {
                foreach (['poids', 'eau'] as $champ) {
                    if (($barre[$champ] ?? '') !== '') {
                        return true;
                    }
                }

                return false;
            })
            ->values()
            ->all();

        $this->merge(['barres' => $barres]);
    }

    public function rules(): array
    {
        return [
            'client_id' => ['required', 'exists:clients,id'],
            'date_operation' => ['required', 'date'],
            'prix_base' => ['required', 'numeric', 'min:0.01'],
            'observations' => ['nullable', 'string'],
            'barres' => ['required', 'array', 'min:1'],
            'barres.*.poids' => ['required', 'numeric', 'min:0.001'],
            'barres.*.eau' => ['required', 'numeric', 'min:0.0001'],
            // Densité/carat/prix unitaire : optionnels, saisis directement
            // par l'opérateur pour surcharger le calcul automatique du
            // barème (ex. densité hors barème, prix négocié). S'ils sont
            // absents, le serveur calcule tout depuis poids/eau/prix_base
            // comme avant, barème obligatoire.
            'barres.*.densite' => ['nullable', 'numeric', 'min:0'],
            'barres.*.carat' => ['nullable', 'numeric', 'min:0'],
            'barres.*.prix_unitaire' => ['nullable', 'numeric', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'barres.required' => "Au moins une barre est requise pour enregistrer l'opération.",
            'barres.*.poids.required' => 'Le poids est obligatoire pour chaque barre.',
            'barres.*.eau.required' => "La valeur d'eau est obligatoire pour chaque barre.",
            'barres.*.eau.min' => "La valeur d'eau doit être supérieure à zéro (division par zéro impossible).",
        ];
    }
}
