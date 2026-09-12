<?php

namespace App\Http\Controllers;

use App\Http\Requests\FermerJourneeRequest;
use App\Http\Requests\OuvrirJourneeRequest;
use App\Http\Requests\StoreApprovisionnementRequest;
use App\Http\Requests\StoreMouvementFinancierRequest;
use App\Models\JourneeFinanciere;
use App\Models\MouvementFinancier;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class TresorerieController extends Controller
{
    public function index(): View
    {
        $journeeOuverte = JourneeFinanciere::ouverte()->with('mouvements.user')->latest('date_ouverture')->first();

        return view('tresorerie.index', [
            'journeeOuverte' => $journeeOuverte,
            'historique' => JourneeFinanciere::where('statut', 'fermee')->with('ouvrePar', 'fermePar')->latest('date_ouverture')->get(),
        ]);
    }

    public function ouvrir(OuvrirJourneeRequest $request): RedirectResponse
    {
        try {
            $journee = JourneeFinanciere::ouvrirJournee(
                (float) $request->validated('montant_initial'),
                $request->validated('observations'),
                auth()->user()
            );
        } catch (\RuntimeException $e) {
            return back()->withErrors(['journee' => $e->getMessage()]);
        }

        return redirect()->route('tresorerie.index')->with('status', 'Journée financière ouverte avec succès.');
    }

    public function approvisionner(StoreApprovisionnementRequest $request, JourneeFinanciere $journee): RedirectResponse
    {
        if (! $journee->estOuverte()) {
            return back()->withErrors(['journee' => 'Cette journée est fermée.']);
        }

        $journee->mouvements()->create([
            'bureau_id' => $journee->bureau_id,
            'nature' => 'approvisionnement',
            'sens' => 'entree',
            'libelle' => $request->validated('libelle') ?: 'Approvisionnement',
            'montant' => $request->validated('montant'),
            'date_mouvement' => now(),
            'user_id' => auth()->id(),
        ]);

        return back()->with('status', 'Approvisionnement enregistré : le solde disponible est mis à jour.');
    }

    public function mouvement(StoreMouvementFinancierRequest $request, JourneeFinanciere $journee): RedirectResponse
    {
        if (! $journee->estOuverte()) {
            return back()->withErrors(['journee' => 'Cette journée est fermée : aucun mouvement ne peut y être ajouté.']);
        }

        $journee->mouvements()->create([
            'bureau_id' => $journee->bureau_id,
            'nature' => 'autre',
            'sens' => $request->validated('sens'),
            'libelle' => $request->validated('libelle'),
            'montant' => $request->validated('montant'),
            'date_mouvement' => now(),
            'user_id' => auth()->id(),
        ]);

        return back()->with('status', 'Mouvement enregistré avec succès.');
    }

    public function fermer(FermerJourneeRequest $request, JourneeFinanciere $journee): RedirectResponse
    {
        try {
            $journee->fermerJournee((float) $request->validated('solde_physique'), auth()->user());
        } catch (\RuntimeException $e) {
            return back()->withErrors(['journee' => $e->getMessage()]);
        }

        $ecart = $journee->ecart_fermeture;
        $devise = $journee->bureau?->devise_symbole ?? config('pays_devises.Mali.symbole');
        $message = 'Journée clôturée. Solde théorique : '.number_format((float) $journee->solde_theorique_fermeture, 0, ',', ' ').' '.$devise.', écart : '.number_format((float) $ecart, 0, ',', ' ').' '.$devise.'.';

        return redirect()->route('tresorerie.index')->with('status', $message);
    }

    public function destroyMouvement(MouvementFinancier $mouvement): RedirectResponse
    {
        if ($mouvement->estAutomatique()) {
            return back()->withErrors(['journee' => "Ce mouvement provient automatiquement d'une opération d'achat/vente : il ne peut pas être supprimé directement."]);
        }

        if (! $mouvement->journee->estOuverte()) {
            return back()->withErrors(['journee' => "Impossible de supprimer un mouvement d'une journée fermée."]);
        }

        $mouvement->delete();

        return back()->with('status', 'Mouvement supprimé.');
    }
}
