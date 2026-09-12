<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function create(): View
    {
        return view('auth.login', [
            'slides' => [
                ['icon' => 'bi-people', 'badge' => 'Clients', 'title' => 'Gérez vos clients en toute simplicité', 'text' => 'Fiches complètes, historique des achats et des ventes, recherche rapide.'],
                ['icon' => 'bi-coin', 'badge' => 'Achats d\'or', 'title' => 'Sécurisez le calcul de chaque barre', 'text' => 'Poids, eau, densité, carat et prix calculés automatiquement selon le barème.'],
                ['icon' => 'bi-box-seam', 'badge' => 'Stock d\'or', 'title' => 'Un stock tracé lot par lot', 'text' => 'Chaque barre reste identifiable, de l\'achat jusqu\'à la vente.'],
                ['icon' => 'bi-cash-coin', 'badge' => 'Ventes d\'or', 'title' => 'Vendez au prix du marché', 'text' => 'Sélection du stock disponible, calcul et facturation en un geste.'],
                ['icon' => 'bi-safe2', 'badge' => 'Trésorerie', 'title' => 'Fonds d\'achat et audit journalier', 'text' => 'Fonds initial, approvisionnements, sorties automatiques et clôture maîtrisée.'],
            ],
            'modules' => [
                ['icon' => 'bi-people', 'label' => 'Clients'],
                ['icon' => 'bi-coin', 'label' => 'Achats d\'or'],
                ['icon' => 'bi-columns-gap', 'label' => 'Barème'],
                ['icon' => 'bi-box-seam', 'label' => 'Stock d\'or'],
                ['icon' => 'bi-cash-coin', 'label' => 'Ventes d\'or'],
                ['icon' => 'bi-safe2', 'label' => 'Trésorerie'],
                ['icon' => 'bi-clipboard-check', 'label' => 'Audit / Clôture'],
                ['icon' => 'bi-receipt', 'label' => 'Facturation'],
                ['icon' => 'bi-graph-up-arrow', 'label' => 'Rapports'],
                ['icon' => 'bi-shield-lock', 'label' => 'Utilisateurs'],
                ['icon' => 'bi-speedometer2', 'label' => 'Tableau de bord'],
            ],
        ]);
    }

    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        return redirect()->intended(route('home'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
