<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreMouvementCompteClientRequest;
use App\Models\Client;
use App\Models\MouvementCompteClient;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CompteClientController extends Controller
{
    public function index(Request $request): View
    {
        $recherche = $request->input('recherche');
        $avecMouvementsSeulement = $request->boolean('avec_mouvements_seulement', true);

        $clients = Client::query()
            ->when($recherche, fn ($q) => $q->where(fn ($q2) => $q2
                ->where('nom', 'like', "%{$recherche}%")
                ->orWhere('prenom', 'like', "%{$recherche}%")))
            ->orderBy('nom')
            ->get()
            ->map(function (Client $client) {
                $actifs = $client->mouvementsCompte()->whereNull('annule_at')->get();
                $client->nb_mouvements = $actifs->count();
                $client->solde_courant = (float) ($actifs->last()->solde_apres ?? 0);
                $client->dernier_mouvement = $actifs->last();

                return $client;
            });

        if ($avecMouvementsSeulement) {
            $clients = $clients->filter(fn (Client $c) => $c->nb_mouvements > 0)->values();
        }

        return view('comptes-clients.index', [
            'clients' => $clients,
            'recherche' => $recherche,
            'stats' => [
                'nbClientsSuivis' => $clients->where('nb_mouvements', '>', 0)->count(),
                'totalDettes' => $clients->filter(fn ($c) => $c->solde_courant > 0)->sum('solde_courant'),
                'totalCrediteurs' => $clients->filter(fn ($c) => $c->solde_courant < 0)->sum(fn ($c) => abs($c->solde_courant)),
            ],
        ]);
    }

    public function show(Client $client): View
    {
        $mouvements = $client->mouvementsCompte()->with('user')->get();
        $dernierActif = $mouvements->whereNull('annule_at')->last();

        return view('comptes-clients.show', [
            'client' => $client,
            'mouvements' => $mouvements,
            'solde' => (float) ($dernierActif->solde_apres ?? 0),
            'dernierMouvementId' => $dernierActif?->id,
        ]);
    }

    public function store(StoreMouvementCompteClientRequest $request, Client $client): RedirectResponse
    {
        $data = $request->validated();
        $data['impacte_caisse'] = $request->boolean('impacte_caisse');

        try {
            MouvementCompteClient::enregistrer($data, $client, auth()->user());
        } catch (\RuntimeException $e) {
            return back()->withErrors(['mouvement' => $e->getMessage()])->withInput();
        }

        return redirect()->route('comptes-clients.show', $client)->with('status', 'Mouvement enregistré avec succès.');
    }

    public function annuler(MouvementCompteClient $mouvement): RedirectResponse
    {
        try {
            $mouvement->annuler(auth()->user());
        } catch (\RuntimeException $e) {
            return back()->withErrors(['mouvement' => $e->getMessage()]);
        }

        return back()->with('status', 'Mouvement annulé avec succès.');
    }

    public function pdf(Client $client)
    {
        $mouvements = $client->mouvementsCompte()->with('user')->get();
        $dernierActif = $mouvements->whereNull('annule_at')->last();

        $pdf = Pdf::loadView('pdf.compte-client', [
            'client' => $client,
            'mouvements' => $mouvements,
            'solde' => (float) ($dernierActif->solde_apres ?? 0),
        ])->setPaper('a4', 'portrait');

        return $pdf->stream('releve-'.$client->identifiant.'.pdf');
    }
}
