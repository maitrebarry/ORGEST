<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            'superadmin',
            'proprietaire',
            'gerant',
        ];

        foreach ($roles as $role) {
            Role::findOrCreate($role);
        }

        // Toutes les permissions connues de l'application (celles listées dans
        // config/role_permissions.php, dédupliquées), plus « bureaux.gerer »
        // qui n'appartient au modèle par défaut d'aucun rôle : seul le
        // superadmin la possède, via le contournement Gate::before.
        $permissions = collect(config('role_permissions'))
            ->flatten()
            ->push('bureaux.gerer')
            ->unique()
            ->values();

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        // Les rôles ne portent AUCUNE permission : les autorisations sont
        // strictement individuelles (permissions directes par utilisateur).
        foreach ($roles as $role) {
            Role::findByName($role)->syncPermissions([]);
        }

        $superadmin = User::firstOrCreate(
            ['phone' => '74745669'],
            ['name' => 'Moustapha BARRY', 'password' => 'superadmin74']
        );

        $superadmin->assignRole('superadmin');
    }
}
