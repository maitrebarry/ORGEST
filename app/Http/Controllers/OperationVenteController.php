<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreOperationVenteRequest;
use App\Models\BarreAchat;
use App\Models\Client;
use App\Models\MouvementFinancier;
use App\Models\OperationVente;
use App\Support\CalculateurVente;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class OperationVenteController extends Controller
{
    public function index(): View
    {
        return view('ventes.index', [
            'operations' => OperationVente::with('client')->withCount('barres')->latest('date_operation')->latest('id')->get(),
        ]);
    }

    public function create(): View
    {
        return view('ventes.create', [
            'clients' => Client::where('actif', true)->orderBy('nom')->get(),
            'barresDisponibles' => BarreAchat::disponible()->with('operation.client')->orderBy('id')->get(),
        ]);
    }

    public function store(StoreOperationVenteRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $prixBase = (string) $data['prix_base'];

        // Aucune validation silencieuse : si une seule barre sélectionnée
        // n'est plus disponible entre-temps (déjà vendue par quelqu'un
        // d'autre, annulée...), toute l'opération est rejetée.
        $barres = BarreAchat::disponible()->whereIn('id', $data['barres_ids'])->get();

        if ($barres->count() !== count($data['barres_ids'])) {
            return back()->withErrors(['barres_ids' => "Au moins une des barres sélectionnées n'est plus disponible en stock (déjà vendue entre-temps ?). Rechargez la page."])->withInput();
        }

        $operation = DB::transaction(function () use ($data, $prixBase, $barres) {
            $calculs = $barres->mapWithKeys(fn (BarreAchat $barre) => [$barre->id => CalculateurVente::calculerLigne($barre, $prixBase)]);

            $montantTotal = $calculs->reduce(fn ($acc, $c) => bcadd($acc, $c['montant'], 2), '0');

            $operation = OperationVente::create([
                'client_id' => $data['client_id'],
                'date_operation' => $data['date_operation'],
                'prix_base' => $data['prix_base'],
                'montant_total' => $montantTotal,
                'montant_paye' => 0,
                'statut' => 'validee',
                'observations' => $data['observations'] ?? null,
                'user_id' => auth()->id(),
                'validee_at' => now(),
                'validee_par' => auth()->id(),
            ]);

            foreach ($barres as $barre) {
                $barre->update([
                    'operation_vente_id' => $operation->id,
                    'prix_unitaire_vente' => $calculs[$barre->id]['prix_unitaire'],
                    'montant_vente' => $calculs[$barre->id]['montant'],
                    'statut' => 'vendu',
                ]);
            }

            return $operation;
        });

        return redirect()->route('ventes.show', $operation)->with('status', "Opération de vente enregistrée avec succès.");
    }

    public function show(OperationVente $vente): View
    {
        $vente->load('barres', 'client', 'user', 'valideur');

        return view('ventes.show', ['vente' => $vente]);
    }

    public function annuler(Request $request, OperationVente $vente): RedirectResponse
    {
        try {
            $vente->annuler('Annulée par '.auth()->user()->name.' le '.now()->format('d/m/Y à H:i').' — les barres retournent en stock.');
        } catch (\RuntimeException $e) {
            return back()->withErrors(['vente' => $e->getMessage()]);
        }

        return back()->with('status', 'Vente annulée : les barres sont de nouveau disponibles en stock.');
    }

    public function enregistrerPaiement(Request $request, OperationVente $vente): RedirectResponse
    {
        $request->merge(['montant' => str_replace([' ', ','], ['', '.'], (string) $request->input('montant'))]);
        $request->validate([
            'montant' => ['required', 'numeric', 'min:0.01'],
            'mode_paiement' => ['required', Rule::in(array_keys(MouvementFinancier::MODES_PAIEMENT))],
        ]);

        try {
            $vente->enregistrerPaiement((string) $request->input('montant'), auth()->user(), $request->input('mode_paiement'));
        } catch (\RuntimeException $e) {
            return back()->withErrors(['montant' => $e->getMessage()]);
        }

        return back()
            ->with('status', 'Encaissement enregistré avec succès.')
            ->with('facture_url', route('ventes.pdf', $vente));
    }

    public function pdf(OperationVente $vente)
    {
        $vente->load('barres', 'client', 'user', 'bureau');

        $pdf = Pdf::loadView('pdf.vente', ['vente' => $vente])->setPaper('a4', 'portrait');

        return $pdf->stream('facture-vente-'.$vente->numero.'.pdf');
    }

    public function modifierBarre(Request $request, OperationVente $vente, BarreAchat $barre): RedirectResponse
    {
        $request->merge([
            'densite_tronquee' => str_replace([' ', ','], ['', '.'], (string) $request->input('densite_tronquee')),
            'carat' => str_replace([' ', ','], ['', '.'], (string) $request->input('carat')),
            'prix_unitaire_vente' => str_replace([' ', ','], ['', '.'], (string) $request->input('prix_unitaire_vente')),
        ]);
        $request->validate([
            'densite_tronquee' => ['required', 'numeric', 'min:0'],
            'carat' => ['required', 'numeric', 'min:0'],
            'prix_unitaire_vente' => ['required', 'numeric', 'min:0'],
        ]);

        try {
            $vente->corrigerBarre(
                $barre,
                (string) $request->input('densite_tronquee'),
                (string) $request->input('carat'),
                (string) $request->input('prix_unitaire_vente')
            );
        } catch (\RuntimeException $e) {
            return back()->withErrors(['barre' => $e->getMessage()]);
        }

        return back()->with('status', 'Barre modifiée avec succès.');
    }
}
