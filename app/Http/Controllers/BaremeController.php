<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBaremeVersionRequest;
use App\Http\Requests\UpdateBaremeLigneRequest;
use App\Models\BaremeLigne;
use App\Models\BaremeVersion;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class BaremeController extends Controller
{
    public function index(): View
    {
        $versionActive = BaremeVersion::actif()->with('lignes')->first();

        return view('baremes.index', [
            'versionActive' => $versionActive,
            'versions' => BaremeVersion::withCount('lignes')->with('user')->latest()->get(),
        ]);
    }

    public function create(): View
    {
        return view('baremes.create');
    }

    public function store(StoreBaremeVersionRequest $request): RedirectResponse
    {
        $data = $request->validated();

        BaremeVersion::creerNouvelleVersion(
            $data['libelle'],
            $data['lignes'],
            auth()->id(),
            $data['observations'] ?? null,
        );

        return redirect()->route('baremes.index')->with('status', 'Nouvelle version du barème créée et activée avec succès.');
    }

    public function show(BaremeVersion $bareme): View
    {
        $bareme->load('lignes', 'user');

        return view('baremes.show', ['version' => $bareme]);
    }

    public function pdf(?BaremeVersion $bareme = null)
    {
        $version = $bareme ?? BaremeVersion::actif()->first();

        abort_if(! $version, 404, "Aucune version du barème n'est disponible.");

        $version->load('lignes');

        $pdf = Pdf::loadView('pdf.bareme', ['version' => $version])->setPaper('a4', 'portrait');

        return $pdf->stream('bareme-'.str($version->libelle)->slug().'.pdf');
    }

    public function edit(BaremeVersion $bareme): View|RedirectResponse
    {
        // Le socle commun (ou la version d'un autre bureau, en théorie
        // inatteignable ici grâce au scope) reste ouvert à "Modifier" : ce
        // n'est qu'à l'enregistrement (update()) qu'on bifurque vers une
        // COPIE propre au bureau plutôt que d'éditer en place — donc pas de
        // blocage sur estUtilisee() ici tant que ce n'est pas ma version.
        if ($this->estAMoi($bareme->bureau_id) && $bareme->estUtilisee()) {
            return redirect()->route('baremes.show', $bareme)
                ->withErrors(['bareme' => 'Cette version est déjà utilisée par des opérations : elle ne peut plus être modifiée. Créez une nouvelle version.']);
        }

        $bareme->load('lignes');

        return view('baremes.edit', ['version' => $bareme]);
    }

    public function update(StoreBaremeVersionRequest $request, BaremeVersion $bareme): RedirectResponse
    {
        $data = $request->validated();

        if (! $this->estAMoi($bareme->bureau_id)) {
            // Jamais d'édition en place du socle commun / d'un autre bureau :
            // "Modifier" en crée une copie propre à MON bureau.
            $copie = BaremeVersion::creerNouvelleVersion($data['libelle'], $data['lignes'], auth()->id(), $data['observations'] ?? null);

            return redirect()->route('baremes.show', $copie)
                ->with('status', "Votre propre version a été créée à partir de « {$bareme->libelle} » — le barème d'origine n'a pas été modifié.");
        }

        if ($bareme->estUtilisee()) {
            return back()->withErrors(['bareme' => 'Cette version est déjà utilisée par des opérations : elle ne peut plus être modifiée. Créez une nouvelle version.']);
        }

        $bareme->remplacerLignes($data['libelle'], $data['lignes'], $data['observations'] ?? null);

        return redirect()->route('baremes.show', $bareme)->with('status', 'Version du barème mise à jour avec succès.');
    }

    /**
     * Correction d'une seule ligne, sans toucher aux 69 autres — plus sûr
     * que de resoumettre tout le formulaire pour corriger une erreur de
     * saisie ponctuelle. Même principe de bifurcation que update() : si la
     * ligne appartient au socle commun (ou à un autre bureau), on crée une
     * copie complète de la version avec cette seule ligne corrigée, plutôt
     * que de modifier la ligne partagée en place.
     */
    public function updateLigne(UpdateBaremeLigneRequest $request, BaremeLigne $ligne): RedirectResponse
    {
        $version = $ligne->version;
        $donneesLigne = $request->validated();

        if (! $this->estAMoi($ligne->bureau_id)) {
            $lignes = $version->lignes->map(fn (BaremeLigne $l) => $l->id === $ligne->id
                ? $donneesLigne
                : ['densite_min' => $l->densite_min, 'densite_max' => $l->densite_max, 'carat' => $l->carat]
            )->values()->all();

            $copie = BaremeVersion::creerNouvelleVersion($version->libelle, $lignes, auth()->id(), $version->observations);

            return redirect()->route('baremes.show', $copie)
                ->with('status', "Votre propre version a été créée avec cette correction — le barème d'origine n'a pas été modifié.");
        }

        if ($version->estUtilisee()) {
            return back()->withErrors(['bareme' => 'Cette version est déjà utilisée par des opérations : impossible de modifier cette ligne.']);
        }

        $ligne->update($donneesLigne);

        return back()->with('status', 'Ligne mise à jour avec succès.');
    }

    /**
     * Vrai seulement si $bureauIdCible est EXACTEMENT le bureau de
     * l'utilisateur connecté — ce qui inclut, à dessein, le superadmin
     * (bureau_id NULL) face au socle commun (bureau_id NULL) : lui seul peut
     * éditer le socle commun en place.
     */
    private function estAMoi(?int $bureauIdCible): bool
    {
        return $bureauIdCible === auth()->user()?->bureau_id;
    }
}
