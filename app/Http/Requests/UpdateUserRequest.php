<?php

namespace App\Http\Requests;

use App\Rules\MalianPhone;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Spatie\Permission\Models\Role;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $isSuperadmin = $this->route('user')?->hasRole('superadmin');

        return [
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', new MalianPhone(), Rule::unique('users', 'phone')->ignore($this->route('user'))],
            'password' => ['nullable', 'confirmed', Password::defaults()],
            'role' => $isSuperadmin
                ? ['nullable']
                : ['required', Rule::in($this->rolesAssignables())],
        ];
    }

    /**
     * Un propriétaire ne peut reclasser que vers le rôle gérant ; seul le
     * superadmin peut assigner n'importe quel rôle (sauf superadmin lui-même).
     */
    private function rolesAssignables(): array
    {
        if ($this->user()->hasRole('superadmin')) {
            return Role::where('name', '!=', 'superadmin')->pluck('name')->all();
        }

        return ['gerant'];
    }
}
