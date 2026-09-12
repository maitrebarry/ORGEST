<?php

namespace App\Http\Requests;

use App\Rules\MalianPhone;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Spatie\Permission\Models\Role;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', new MalianPhone(), 'unique:users,phone'],
            'password' => ['required', 'confirmed', Password::defaults()],
            'role' => ['required', Rule::in($this->rolesAssignables())],
        ];
    }

    /**
     * Un propriétaire ne peut créer que des gérants pour son bureau ; seul le
     * superadmin peut assigner n'importe quel rôle (sauf superadmin lui-même,
     * réservé au bootstrap applicatif).
     */
    private function rolesAssignables(): array
    {
        if ($this->user()->hasRole('superadmin')) {
            return Role::where('name', '!=', 'superadmin')->pluck('name')->all();
        }

        return ['gerant'];
    }
}
