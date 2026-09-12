<?php

namespace App\Http\Requests;

use App\Rules\MalianPhone;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProfileUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', new MalianPhone(), Rule::unique('users', 'phone')->ignore($this->user()->id)],
            'photo' => ['nullable', 'image', 'max:2048'],
        ];
    }
}
