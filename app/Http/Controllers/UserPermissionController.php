<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Spatie\Permission\Models\Permission;

class UserPermissionController extends Controller
{
    /**
     * Hiérarchie des permissions (application multi-bureaux) : le superadmin
     * ajuste les permissions du propriétaire ; le propriétaire ajuste celles
     * de ses propres gérants uniquement. On ne fait donc jamais confiance au
     * payload pour la liste des permissions proposées : elle est dérivée du
     * rôle de la personne CIBLÉE (config/role_permissions.php), jamais de qui
     * fait l'action.
     */
    public function index(Request $request): View
    {
        $acteur = auth()->user();

        $users = User::whereDoesntHave('roles', fn ($query) => $query->where('name', 'superadmin'))
            ->when(! $acteur->hasRole('superadmin'), function ($query) use ($acteur) {
                // Un propriétaire ne gère que les gérants de SON bureau.
                $query->where('bureau_id', $acteur->bureau_id)->where('id', '!=', $acteur->id);
            })
            ->orderBy('name')
            ->get();

        $selectedUser = null;
        $groupedPermissions = collect();
        $userPermissionNames = [];
        $poolTotal = 0;

        if ($request->filled('user')) {
            $selectedUser = $users->firstWhere('id', (int) $request->input('user'));
        }

        if ($selectedUser) {
            $pool = $this->poolAssignable($selectedUser);
            $poolTotal = count($pool);

            $groupedPermissions = Permission::whereIn('name', $pool)->orderBy('name')->get()
                ->groupBy(fn (Permission $permission) => str($permission->name)->before('.')->toString());

            $userPermissionNames = $selectedUser->permissions->pluck('name')->all();
        }

        return view('user-permissions.index', [
            'users' => $users,
            'selectedUser' => $selectedUser,
            'groupedPermissions' => $groupedPermissions,
            'userPermissionNames' => $userPermissionNames,
            'totalPermissions' => $poolTotal,
        ]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        abort_if($user->hasRole('superadmin'), 403);

        $acteur = auth()->user();

        if (! $acteur->hasRole('superadmin')) {
            abort_if(
                $user->bureau_id !== $acteur->bureau_id || $user->id === $acteur->id || ! $user->hasRole('gerant'),
                403,
                "Vous ne pouvez ajuster que les permissions des gérants de votre bureau."
            );
        }

        $pool = $this->poolAssignable($user);

        $validated = $request->validate([
            'permissions' => ['array'],
            'permissions.*' => ['string', Rule::in($pool)],
        ]);

        $user->syncPermissions($validated['permissions'] ?? []);

        return redirect()->route('user-permissions.index', ['user' => $user->id])
            ->with('status', 'Permissions mises à jour pour '.$user->name.'.');
    }

    /**
     * Le jeu de permissions qu'il est possible d'accorder à $user est celui
     * du modèle par défaut de SON rôle — jamais toutes les permissions du
     * système, pour éviter qu'un propriétaire ne puisse élever un gérant
     * au niveau propriétaire/superadmin via ce formulaire.
     */
    private function poolAssignable(User $user): array
    {
        $role = $user->roles->first()?->name;

        return config("role_permissions.{$role}", []);
    }
}
