<?php

namespace App\Http\Controllers;

use App\Models\BarreAchat;
use App\Models\Bureau;
use App\Models\Client;
use App\Models\JourneeFinanciere;
use App\Models\OperationAchat;
use App\Models\OperationVente;
use App\Models\User;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * Chaque bloc n'est calculé que si l'utilisateur a la permission
     * correspondante (un gérant sans fonds.voir ne doit pas voir le solde de
     * trésorerie, etc.) — les requêtes elles-mêmes restent cloisonnées par
     * bureau automatiquement via les global scopes des modèles.
     */
    public function index(): View
    {
        $user = auth()->user();

        // Le superadmin n'a pas de bureau_id : les global scopes bureau ne le
        // filtrent jamais, donc le tableau de bord "normal" agrégerait
        // silencieusement les données de TOUS les bureaux en un seul bloc de
        // chiffres sans queue ni tête. Il a besoin d'une vue de supervision
        // multi-bureaux à la place, jamais des chiffres d'un bureau précis.
        if ($user->hasRole('superadmin')) {
            return $this->indexSuperadmin();
        }

        $aujourdhui = now()->startOfDay();
        $data = [];

        if ($user->can('clients.voir')) {
            $data['clientsCount'] = Client::where('actif', true)->count();
        }

        if ($user->can('achats.voir')) {
            $data['stockCount'] = BarreAchat::disponible()->count();
            $data['stockPoids'] = (float) BarreAchat::disponible()->sum('poids');

            $achatsJour = OperationAchat::whereDate('date_operation', $aujourdhui)->where('statut', 'validee')->get();
            $data['achatsJourCount'] = $achatsJour->count();
            $data['achatsJourMontant'] = (float) $achatsJour->sum('montant_total');

            $data['derniersAchats'] = OperationAchat::with('client')->latest('date_operation')->latest('id')->limit(10)->get();
        }

        if ($user->can('ventes.voir')) {
            $ventesJour = OperationVente::whereDate('date_operation', $aujourdhui)->where('statut', 'validee')->get();
            $data['ventesJourCount'] = $ventesJour->count();
            $data['ventesJourMontant'] = (float) $ventesJour->sum('montant_total');

            $data['dernieresVentes'] = OperationVente::with('client')->latest('date_operation')->latest('id')->limit(10)->get();
        }

        if ($user->can('fonds.voir')) {
            $data['journeeOuverte'] = JourneeFinanciere::ouverte()->latest('date_ouverture')->first();
        }

        if ($user->can('utilisateurs.voir')) {
            $data['usersCount'] = User::where('actif', true)
                ->whereDoesntHave('roles', fn ($q) => $q->where('name', 'superadmin'))
                ->count();
        }

        return view('home', $data);
    }

    /**
     * Vue de supervision : un bureau = une ligne, avec ses propres compteurs
     * filtrés explicitement par bureau_id (jamais via le global scope,
     * puisqu'il ne fait rien pour un utilisateur sans bureau_id).
     */
    private function indexSuperadmin(): View
    {
        $aujourdhui = now()->startOfDay();

        $bureaux = Bureau::withCount('utilisateurs')->with('proprietaire')->latest('id')->get()
            ->map(function (Bureau $bureau) use ($aujourdhui) {
                $bureau->clientsActifs = Client::where('bureau_id', $bureau->id)->where('actif', true)->count();
                $bureau->achatsJour = OperationAchat::where('bureau_id', $bureau->id)
                    ->whereDate('date_operation', $aujourdhui)->where('statut', 'validee')->count();
                $bureau->ventesJour = OperationVente::where('bureau_id', $bureau->id)
                    ->whereDate('date_operation', $aujourdhui)->where('statut', 'validee')->count();

                return $bureau;
            });

        return view('home-superadmin', [
            'bureaux' => $bureaux,
            'nbBureaux' => $bureaux->count(),
            'nbBureauxActifs' => $bureaux->where('actif', true)->count(),
            'nbProprietaires' => User::role('proprietaire')->where('actif', true)->count(),
            'nbGerants' => User::role('gerant')->where('actif', true)->count(),
            'nbClientsTotal' => Client::where('actif', true)->count(),
        ]);
    }
}
