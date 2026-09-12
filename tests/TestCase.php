<?php

namespace Tests;

use App\Models\User;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

abstract class TestCase extends BaseTestCase
{
    /**
     * Ensure every role and permission the app knows about exists.
     * Roles carry no permissions — authorization is purely per-user (direct).
     */
    protected function seedRolesAndPermissions(): void
    {
        foreach (['superadmin', 'proprietaire', 'gerant'] as $role) {
            Role::findOrCreate($role);
        }

        foreach (collect(config('role_permissions'))->flatten()->unique() as $permission) {
            Permission::findOrCreate($permission);
        }

        // Permissions « hors rôle » créées par RoleSeeder (assignables mais
        // dans aucun rôle par défaut).
        Permission::findOrCreate('utilisateurs.supprimer');
        Permission::findOrCreate('bureaux.gerer');
    }

    /**
     * Create a user, label them with a role, and grant them that role's default
     * permissions directly (mirrors what UserController@store does in the app).
     */
    protected function userWithRole(string $role): User
    {
        $user = User::factory()->create();
        $user->assignRole($role);
        $user->givePermissionTo(config("role_permissions.{$role}", []));

        return $user;
    }
}
