<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreOperationAchatRequest;
use App\Models\BaremeVersion;
use App\Models\Client;
use App\Models\JourneeFinanciere;
use App\Models\MouvementFinancier;
use App\Models\OperationAchat;
use App\Support\CalculateurAchat;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class OperationAchatController extends Controller
{
    public function index(): View
    {
        return view('achats.index', [
            'operations' => OperationAchat::with('client')->withCount('barres')->latest('date_operation')->latest('id')->get(),
        ]);
    }

    public function create(): View
    {
        return view('achats.create', [
            'clients' => Client::where('actif', true)->orderBy('nom')->get(),
            'baremeActif' => BaremeVersion::actif()->first(),
        ]);
    }

    public function store(StoreOperationAchatRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $bareme = BaremeVersion::actif()->first();

        if (! $bareme) {
            return back()->withErrors(['barres' => "Aucun barème actif : impossible de déterminer un carat. Créez d'abord une version du barème."])->withInput();
        }

        $prixBase = (string) $data['prix_base'];
        $calculs = [];
        $erreurs = [];

        foreach ($data['barres'] as $i => $barre) {
            $resultat = CalculateurAchat::calculerBarre((string) $barre['poids'], (string) $barre['eau'], $prixBase, $bareme);

            if ($resultat['erreur']) {
                $erreurs[] = 'Barre '.($i + 1).' : '.$resultat['erreur'];
            }

            $calculs[] = [...$barre, ...$resultat];
        }

        if (! empty($erreurs)) {
            // Aucune validation silencieuse (cahier des charges §3.3/§18) :
            // toute l'opération est rejetée si une seule barre ne correspond
            // à aucune entrée du barème.
            return back()->withErrors(['barres' => implode(' ', $erreurs)])->withInput();
        }

        $operation = DB::transaction(function () use ($data, $calculs, $bareme) {
            // Sommes en bcmath (jamais de float natif) : cohérent avec le
            // reste des calculs monétaires/poids de l'application.
            $poidsTotal = array_reduce($calculs, fn ($acc, $c) => bcadd($acc, (string) $c['poids'], 3), '0');
            $eauTotal = array_reduce($calculs, fn ($acc, $c) => bcadd($acc, (string) $c['eau'], 4), '0');
            $montantTotal = array_reduce($calculs, fn ($acc, $c) => bcadd($acc, (string) $c['montant'], 2), '0');

            // Pas de mode brouillon : une opération créée est immédiatement
            // définitive, ses barres entrent en stock tout de suite.
            $operation = OperationAchat::create([
                'client_id' => $data['client_id'],
                'date_operation' => $data['date_operation'],
                'prix_base' => $data['prix_base'],
                'bareme_version_id' => $bareme->id,
                'poids_total' => $poidsTotal,
                'eau_total' => $eauTotal,
                'montant_total' => $montantTotal,
                'montant_paye' => 0,
                'statut' => 'validee',
                'observations' => $data['observations'] ?? null,
                'user_id' => auth()->id(),
                'validee_at' => now(),
                'validee_par' => auth()->id(),
            ]);

            foreach ($calculs as $i => $c) {
                $operation->barres()->create([
                    'numero_barre' => $i + 1,
                    'poids' => $c['poids'],
                    'eau' => $c['eau'],
                    'densite_brute' => $c['densite_brute'],
                    'densite_tronquee' => $c['densite_tronquee'],
                    'bareme_ligne_id' => $c['bareme_ligne_id'],
                    'carat' => $c['carat'],
                    'prix_unitaire' => $c['prix_unitaire'],
                    'montant' => $c['montant'],
                    'statut' => 'en_stock',
                ]);
            }

            return $operation;
        });

        return redirect()->route('achats.show', $operation)->with('status', 'Opération d\'achat enregistrée avec succès.');
    }

    public function show(OperationAchat $achat): View
    {
        $achat->load('barres', 'client', 'user', 'valideur', 'baremeVersion');

        $journeeOuverte = JourneeFinanciere::where('bureau_id', $achat->bureau_id)->ouverte()->latest('date_ouverture')->first();

        return view('achats.show', [
            'achat' => $achat,
            'soldeDisponible' => $journeeOuverte?->soldeDisponible(),
        ]);
    }

    public function annuler(OperationAchat $achat): RedirectResponse
    {
        try {
            $achat->annuler('Annulée par '.auth()->user()->name.' le '.now()->format('d/m/Y à H:i'));
        } catch (\RuntimeException $e) {
            return back()->withErrors(['achat' => $e->getMessage()]);
        }

        return back()->with('status', 'Opération annulée.');
    }

    public function enregistrerPaiement(Request $request, OperationAchat $achat): RedirectResponse
    {
        // Le formatage en direct (espaces tous les 3 chiffres) doit être
        // retiré avant validation.
        $request->merge(['montant' => str_replace(' ', '', (string) $request->input('montant'))]);
        $request->validate([
            'montant' => ['required', 'numeric', 'min:0.01'],
            'mode_paiement' => ['required', Rule::in(array_keys(MouvementFinancier::MODES_PAIEMENT))],
        ]);

        try {
            $achat->enregistrerPaiement((string) $request->input('montant'), auth()->user(), $request->input('mode_paiement'));
        } catch (\RuntimeException $e) {
            return back()->withErrors(['montant' => $e->getMessage()]);
        }

        return back()->with('status', 'Paiement enregistré avec succès.');
    }

    public function pdf(OperationAchat $achat)
    {
        $achat->load('barres', 'client', 'user', 'bureau');

        $pdf = Pdf::loadView('pdf.achat', ['achat' => $achat])->setPaper('a4', 'portrait');

        return $pdf->stream('facture-achat-'.$achat->numero.'.pdf');
    }
}
