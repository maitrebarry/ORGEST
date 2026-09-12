<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function index(): View
    {
        $acteur = auth()->user();
        $query = User::with('roles')->latest();

        if (! $acteur->hasRole('superadmin')) {
            // Un propriétaire ne voit que les comptes de SON bureau (lui-même
            // et ses gérants) ; jamais un autre bureau ni le superadmin.
            $query->whereDoesntHave('roles', fn ($q) => $q->where('name', 'superadmin'))
                ->where('bureau_id', $acteur->bureau_id);
        }

        return view('users.index', [
            'users' => $query->get(),
            'roles' => $this->assignableRoles(),
        ]);
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        $roleName = $request->validated('role');
        $acteur = auth()->user();

        $data = $request->safe()->except('role');

        if (! $acteur->hasRole('superadmin')) {
            // Un subalterne créé par un propriétaire appartient automatiquement
            // à son bureau : ce n'est jamais un champ librement saisi.
            $data['bureau_id'] = $acteur->bureau_id;
        }

        $user = User::create($data);
        $user->assignRole($roleName);

        // Les rôles ne portent aucune permission : on attribue directement à
        // l'utilisateur le jeu de permissions par défaut de son rôle. Il pourra
        // ensuite être ajusté finement via « Assigner permissions ».
        $user->syncPermissions(config("role_permissions.{$roleName}", []));

        return redirect()
            ->route('user-permissions.index', ['user' => $user->id])
            ->with('status', 'Utilisateur créé. Voici ses permissions par défaut, ajustez-les si besoin.');
    }

    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $this->guardAgainstUnauthorizedTarget($user);

        $data = $request->safe()->except(['role', 'password']);

        if ($request->filled('password')) {
            $data['password'] = $request->validated('password');
        }

        $user->update($data);

        if (! $user->hasRole('superadmin')) {
            $user->syncRoles($request->validated('role'));
        }

        return redirect()->route('users.index')->with('status', 'Utilisateur mis à jour avec succès.');
    }

    public function toggleActif(User $user): RedirectResponse
    {
        if ($user->id === auth()->id()) {
            return back()->with('error', 'Vous ne pouvez pas désactiver votre propre compte.');
        }

        $this->guardAgainstUnauthorizedTarget($user);

        $user->update(['actif' => ! $user->actif]);

        return back()->with('status', $user->actif ? 'Compte activé.' : 'Compte désactivé.');
    }

    /**
     * Réservé par défaut au superadmin (qui contourne tout via Gate::before) ;
     * la permission « utilisateurs.supprimer » peut être donnée à un propriétaire au besoin.
     */
    public function destroy(User $user): RedirectResponse
    {
        if ($user->hasRole('superadmin')) {
            return back()->with('error', 'Un compte superadmin ne peut pas être supprimé.');
        }

        if ($user->id === auth()->id()) {
            return back()->with('error', 'Vous ne pouvez pas supprimer votre propre compte.');
        }

        $acteur = auth()->user();

        if (! $acteur->hasRole('superadmin') && ($user->bureau_id !== $acteur->bureau_id || ! $user->hasRole('gerant'))) {
            return back()->with('error', 'Vous ne pouvez supprimer que les gérants de votre bureau.');
        }

        $user->delete();

        return redirect()->route('users.index')->with('status', 'Utilisateur supprimé avec succès.');
    }

    /**
     * Un propriétaire ne peut créer/reclasser que des gérants (jamais un
     * autre propriétaire) ; seul le superadmin (via bureaux.gerer) crée un
     * bureau et son propriétaire.
     */
    private function assignableRoles()
    {
        if (auth()->user()->hasRole('superadmin')) {
            return Role::where('name', '!=', 'superadmin')->pluck('name');
        }

        return Role::where('name', 'gerant')->pluck('name');
    }

    /**
     * Hiérarchie stricte (application multi-bureaux) : le superadmin gère
     * tout ; le propriétaire ne gère que les gérants de SON bureau — jamais
     * le compte superadmin, jamais un autre propriétaire, jamais lui-même via
     * cet écran (son propre profil se modifie ailleurs).
     */
    private function guardAgainstUnauthorizedTarget(User $user): void
    {
        $acteur = auth()->user();

        if ($acteur->hasRole('superadmin')) {
            return;
        }

        if ($user->hasRole('superadmin')) {
            throw new AuthorizationException('Ce compte ne peut pas être modifié.');
        }

        if ($user->id === $acteur->id || $user->bureau_id !== $acteur->bureau_id || ! $user->hasRole('gerant')) {
            throw new AuthorizationException('Vous ne pouvez gérer que les gérants de votre bureau.');
        }
    }
}
