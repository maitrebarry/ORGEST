<?php

namespace App\Http\Requests;

use App\Rules\MalianPhone;
use Illuminate\Foundation\Http\FormRequest;

class StoreClientRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nom' => ['required', 'string', 'max:255'],
            'prenom' => ['nullable', 'string', 'max:255'],
            'telephone' => ['nullable', 'string', new MalianPhone()],
            'adresse' => ['nullable', 'string', 'max:255'],
        ];
    }
}
