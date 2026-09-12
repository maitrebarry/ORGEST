<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBureauRequest;
use App\Http\Requests\UpdateBureauLogoRequest;
use App\Http\Requests\UpdateBureauRequest;
use App\Models\Bureau;
use App\Models\User;
use App\Support\ExtracteurCouleur;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class BureauController extends Controller
{
    public function index(): View
    {
        return view('bureaux.index', [
            'bureaux' => Bureau::with('proprietaire')->latest()->get(),
        ]);
    }

    /**
     * Seul le superadmin crée un bureau ET son propriétaire, dans le même
     * geste (le propriétaire ne peut pas s'auto-créer, ni un autre
     * propriétaire créer un pair — cf. cahier des charges multi-bureaux).
     */
    public function store(StoreBureauRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $bureau = DB::transaction(function () use ($data, $request) {
            $bureau = Bureau::create([
                'nom' => $data['nom'],
                'adresse' => $data['adresse'] ?? null,
                'telephone' => $data['telephone'] ?? null,
                'email' => $data['email'] ?? null,
                'pays' => $data['pays'],
                'actif' => true,
            ]);

            if ($request->hasFile('logo')) {
                $bureau->update($this->enregistrerLogo($bureau, $request->file('logo')));
            }

            $proprietaire = User::create([
                'name' => $data['proprietaire_nom'],
                'phone' => $data['proprietaire_telephone'],
                'password' => $data['proprietaire_password'],
                'bureau_id' => $bureau->id,
            ]);
            $proprietaire->assignRole('proprietaire');
            $proprietaire->syncPermissions(config('role_permissions.proprietaire', []));

            $bureau->update(['proprietaire_id' => $proprietaire->id]);

            return $bureau;
        });

        return redirect()->route('bureaux.index')
            ->with('status', 'Bureau « '.$bureau->nom.' » créé avec son propriétaire.');
    }

    public function edit(Bureau $bureau): View
    {
        return view('bureaux.edit', ['bureau' => $bureau->load('proprietaire')]);
    }

    public function update(UpdateBureauRequest $request, Bureau $bureau): RedirectResponse
    {
        $data = $request->validated();

        if ($request->hasFile('logo')) {
            $data = [...$data, ...$this->enregistrerLogo($bureau, $request->file('logo'))];
        }

        $data['actif'] = $request->boolean('actif');

        $bureau->update($data);

        return redirect()->route('bureaux.index')->with('status', 'Bureau mis à jour avec succès.');
    }

    /**
     * Le propriétaire ne peut modifier QUE le logo de son propre bureau —
     * jamais son nom/adresse/téléphone, réservés au superadmin. On n'accepte
     * donc aucun identifiant de bureau dans l'URL : on agit toujours sur le
     * bureau de l'utilisateur connecté. Déclenché depuis un modal (« Mon
     * bureau » dans le menu Configuration), pas une page dédiée.
     */
    public function updateLogo(UpdateBureauLogoRequest $request): RedirectResponse
    {
        $bureau = $request->user()->bureau;

        abort_if(! $bureau, 403);

        $bureau->update($this->enregistrerLogo($bureau, $request->file('logo')));

        return back()->with('status', 'Logo mis à jour avec succès.');
    }

    /**
     * Supprime l'ancien fichier, stocke le nouveau logo et en extrait la
     * couleur dominante (cf. App\Support\ExtracteurCouleur) pour harmoniser
     * les factures PDF avec le logo — sur échec d'extraction (image
     * illisible par GD, etc.), on garde la couleur précédente plutôt que de
     * la remettre à zéro.
     */
    private function enregistrerLogo(Bureau $bureau, UploadedFile $fichier): array
    {
        if ($bureau->logo) {
            Storage::disk('public')->delete($bureau->logo);
        }

        $chemin = $fichier->store('bureaux', 'public');

        return [
            'logo' => $chemin,
            'couleur' => ExtracteurCouleur::depuisImage(Storage::disk('public')->path($chemin)) ?? $bureau->couleur,
        ];
    }
}
