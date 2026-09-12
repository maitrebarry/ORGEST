<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreOperationVenteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Normalise la décimale à virgule du prix de base (clavier français),
     * cf. commentaire équivalent dans StoreOperationAchatRequest.
     */
    protected function prepareForValidation(): void
    {
        if ($this->filled('prix_base')) {
            $prixBase = str_replace(' ', '', (string) $this->input('prix_base'));
            $this->merge(['prix_base' => str_replace(',', '.', $prixBase)]);
        }
    }

    public function rules(): array
    {
        return [
            'client_id' => ['required', 'exists:clients,id'],
            'date_operation' => ['required', 'date'],
            'prix_base' => ['required', 'numeric', 'min:0.01'],
            'observations' => ['nullable', 'string'],
            'barres_ids' => ['required', 'array', 'min:1'],
            'barres_ids.*' => ['integer', 'exists:barres_achat,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'barres_ids.required' => 'Sélectionnez au moins une barre à vendre.',
        ];
    }
}
