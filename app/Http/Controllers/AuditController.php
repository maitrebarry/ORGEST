<?php

namespace App\Http\Controllers;

use App\Models\JourneeFinanciere;
use App\Models\MouvementFinancier;
use App\Models\OperationAchat;
use App\Models\OperationVente;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AuditController extends Controller
{
    /**
     * Journal d'activité consolidé (achats, ventes, mouvements de
     * trésorerie) sur une période donnée. Ne calcule VOLONTAIREMENT aucune
     * marge/gain : la méthode comptable exacte (marge brute, résultat net...)
     * n'est pas confirmée par l'entreprise (cahier des charges §17.12) — on
     * se limite aux faits bruts (comptages, poids, montants séparés achat et
     * vente) et on laisse l'interprétation au lecteur.
     */
    public function index(Request $request): View
    {
        $debut = $request->filled('debut')
            ? \Illuminate\Support\Carbon::parse($request->input('debut'))->startOfDay()
            : now()->startOfMonth();
        $fin = $request->filled('fin')
            ? \Illuminate\Support\Carbon::parse($request->input('fin'))->endOfDay()
            : now()->endOfDay();

        $achats = OperationAchat::whereBetween('date_operation', [$debut, $fin])
            ->with('client', 'user')->get();
        $ventes = OperationVente::whereBetween('date_operation', [$debut, $fin])
            ->with('client', 'user', 'barres')->get();
        $mouvements = MouvementFinancier::whereBetween('date_mouvement', [$debut, $fin])
            ->with('user')->get();
        $journeesFermees = JourneeFinanciere::where('statut', 'fermee')
            ->whereBetween('date_fermeture', [$debut, $fin])
            ->orderByDesc('date_fermeture')
            ->get();

        $evenements = collect()
            ->concat($achats->map(fn (OperationAchat $a) => [
                'date' => $a->date_operation,
                'type' => 'achat',
                'libelle' => 'Achat '.$a->numero.' — '.$a->client->nom_complet,
                'montant' => (float) $a->montant_total,
                'sens' => 'sortie',
                'statut' => $a->statut_libelle,
                'user' => $a->user?->name,
            ]))
            ->concat($ventes->map(fn (OperationVente $v) => [
                'date' => $v->date_operation,
                'type' => 'vente',
                'libelle' => 'Vente '.$v->numero.' — '.$v->client->nom_complet,
                'montant' => (float) $v->montant_total,
                'sens' => 'entree',
                'statut' => $v->statut_libelle,
                'user' => $v->user?->name,
            ]))
            // fonds_initial/approvisionnement/autre uniquement : paiement_achat
            // et encaissement_vente sont déjà représentés par leur achat/vente
            // d'origine ci-dessus, les lister aussi ferait doublon.
            ->concat($mouvements->whereIn('nature', ['fonds_initial', 'approvisionnement', 'autre'])->map(fn (MouvementFinancier $m) => [
                'date' => $m->date_mouvement,
                'type' => 'mouvement',
                'libelle' => $m->libelle,
                'montant' => (float) $m->montant,
                'sens' => $m->sens,
                'statut' => null,
                'user' => $m->user?->name,
            ]))
            ->sortByDesc('date')
            ->values();

        return view('audit.index', [
            'debut' => $debut,
            'fin' => $fin,
            'evenements' => $evenements,
            'journeesFermees' => $journeesFermees,
            'resume' => [
                'nombreAchats' => $achats->where('statut', 'validee')->count(),
                'nombreVentes' => $ventes->where('statut', 'validee')->count(),
                'poidsAchete' => (float) $achats->where('statut', 'validee')->sum('poids_total'),
                'poidsVendu' => (float) $ventes->where('statut', 'validee')->flatMap->barres->sum('poids'),
                'montantAchats' => (float) $achats->where('statut', 'validee')->sum('montant_total'),
                'montantVentes' => (float) $ventes->where('statut', 'validee')->sum('montant_total'),
            ],
        ]);
    }
}
