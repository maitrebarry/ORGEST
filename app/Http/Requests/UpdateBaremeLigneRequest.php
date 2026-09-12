<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpdateBaremeLigneRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Normalise les décimales à virgule ("18,71" -> "18.71") — sécurité
     * serveur, le JS le fait déjà à la sortie du champ.
     */
    protected function prepareForValidation(): void
    {
        foreach (['densite_min', 'densite_max', 'carat'] as $champ) {
            if ($this->filled($champ)) {
                $this->merge([$champ => str_replace(',', '.', (string) $this->input($champ))]);
            }
        }
    }

    public function rules(): array
    {
        return [
            'densite_min' => ['required', 'numeric', 'min:0'],
            'densite_max' => ['required', 'numeric', 'min:0', 'gte:densite_min'],
            'carat' => ['required', 'numeric', 'min:0'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $ligne = $this->route('ligne');
            $min = (float) $this->input('densite_min');
            $max = (float) $this->input('densite_max');

            $chevauche = $ligne->version->lignes()
                ->where('id', '!=', $ligne->id)
                ->where('densite_min', '<=', $max)
                ->where('densite_max', '>=', $min)
                ->exists();

            if ($chevauche) {
                $validator->errors()->add('densite_min', 'Cette plage chevauche une autre ligne déjà existante du barème.');
            }
        });
    }
}
