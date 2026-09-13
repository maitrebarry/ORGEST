<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCreditRequest;
use App\Http\Requests\StoreRemboursementCreditRequest;
use App\Models\Client;
use App\Models\Credit;
use App\Models\RemboursementCredit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CreditController extends Controller
{
    public function index(Request $request): View
    {
        $credits = Credit::with('client');

        if ($request->filled('client_id')) {
            $credits->where('client_id', $request->input('client_id'));
        }

        if ($request->filled('debut')) {
            $credits->whereDate('date_credit', '>=', $request->input('debut'));
        }

        if ($request->filled('fin')) {
            $credits->whereDate('date_credit', '<=', $request->input('fin'));
        }

        $credits = $credits->latest('date_credit')->latest('id')->get();

        if ($request->filled('statut')) {
            $credits = $credits->filter(fn (Credit $c) => match ($request->input('statut')) {
                'en_cours' => ! $c->estAnnule() && (float) $c->montant_rembourse <= 0,
                'partiel' => ! $c->estAnnule() && (float) $c->montant_rembourse > 0 && $c->solde() > 0,
                'solde' => ! $c->estAnnule() && $c->solde() <= 0,
                default => true,
            })->values();
        }

        $tousLesCredits = Credit::query();

        return view('credits.index', [
            'credits' => $credits,
            'clients' => Client::orderBy('nom')->get(),
            'stats' => [
                'totalAccorde' => (clone $tousLesCredits)->sum('montant_remis'),
                'totalRembourse' => (clone $tousLesCredits)->sum('montant_rembourse'),
                'nbEnCours' => (clone $tousLesCredits)->whereNull('annule_at')->whereColumn('montant_rembourse', '<', 'montant_remis')->count(),
                'nbSoldes' => (clone $tousLesCredits)->whereNull('annule_at')->whereColumn('montant_rembourse', '>=', 'montant_remis')->count(),
                'remboursementsEspeces' => \App\Models\RemboursementCredit::where('mode', 'especes')->count(),
                'remboursementsOr' => \App\Models\RemboursementCredit::where('mode', 'or')->count(),
                'remboursementsMixtes' => \App\Models\RemboursementCredit::where('mode', 'or_especes')->count(),
            ],
        ]);
    }

    public function store(StoreCreditRequest $request): RedirectResponse
    {
        $data = $request->validated();

        try {
            $credit = Credit::octroyer($data, auth()->user(), $data['mode_paiement'] ?? null);
        } catch (\RuntimeException $e) {
            return back()->withErrors(['credit' => $e->getMessage()])->withInput();
        }

        return redirect()->route('credits.show', $credit)->with('status', 'Crédit enregistré avec succès.');
    }

    public function show(Credit $credit): View
    {
        $credit->load('client', 'user', 'remboursements.user', 'remboursements.lignesOr');

        return view('credits.show', [
            'credit' => $credit,
            'baremeActif' => \App\Models\BaremeVersion::actif()->first(),
        ]);
    }

    public function rembourser(StoreRemboursementCreditRequest $request, Credit $credit): RedirectResponse
    {
        $data = $request->validated();

        try {
            RemboursementCredit::enregistrer($credit, $data['mode'], $data, auth()->user(), $data['mode_paiement'] ?? null);
        } catch (\RuntimeException $e) {
            return back()->withErrors(['remboursement' => $e->getMessage()])->withInput();
        }

        return back()->with('status', 'Remboursement enregistré avec succès.');
    }

    public function annuler(Credit $credit): RedirectResponse
    {
        try {
            $credit->annuler();
        } catch (\RuntimeException $e) {
            return back()->withErrors(['credit' => $e->getMessage()]);
        }

        return back()->with('status', 'Crédit annulé avec succès.');
    }
}
