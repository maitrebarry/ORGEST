<?php

namespace App\Http\Controllers;

use App\Http\Requests\PasswordUpdateRequest;
use App\Http\Requests\ProfileUpdateRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function edit(): View
    {
        return view('profile.edit', ['user' => auth()->user()]);
    }

    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $data = $request->safe()->except('photo');
        $user = $request->user();

        if ($request->hasFile('photo')) {
            if ($user->photo) {
                Storage::disk('public')->delete($user->photo);
            }

            $data['photo'] = $request->file('photo')->store('profils', 'public');
        }

        $user->update($data);

        return back()->with('status', 'Profil mis à jour avec succès.');
    }

    public function updatePassword(PasswordUpdateRequest $request): RedirectResponse
    {
        $request->user()->update(['password' => $request->validated('password')]);

        return back()->with('status', 'Mot de passe mis à jour avec succès.');
    }
}
