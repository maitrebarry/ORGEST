<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Spatie\Permission\Models\Permission;

class PermissionController extends Controller
{
    public function index(Request $request): View
    {
        $grouped = $this->groupedPermissions($request->query('search'));

        if ($request->ajax()) {
            return view('permissions._groups', ['grouped' => $grouped]);
        }

        return view('permissions.index', ['grouped' => $grouped]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150', 'unique:permissions,name'],
        ]);

        Permission::create(['name' => $validated['name']]);

        return back()->with('status', 'Permission créée avec succès.');
    }

    private function groupedPermissions(?string $search)
    {
        return Permission::withCount('users')
            ->when($search, fn ($query) => $query->where('name', 'like', '%'.$search.'%'))
            ->orderBy('name')
            ->get()
            ->groupBy(fn (Permission $permission) => str($permission->name)->before('.')->toString());
    }
}
