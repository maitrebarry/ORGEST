<?php

namespace App\Http\Requests;

use App\Rules\MalianPhone;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class StoreBureauRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nom' => ['required', 'string', 'max:255'],
            'logo' => ['nullable', 'image', 'max:2048'],
            'adresse' => ['nullable', 'string', 'max:255'],
            'telephone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'pays' => ['required', 'string', Rule::in(array_keys(config('pays_devises')))],
            'proprietaire_nom' => ['required', 'string', 'max:255'],
            'proprietaire_telephone' => ['required', 'string', new MalianPhone(), 'unique:users,phone'],
            'proprietaire_password' => ['required', 'confirmed', Password::defaults()],
        ];
    }
}
