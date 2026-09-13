<?php

namespace App\Http\Controllers;

use App\Models\BarreAchat;
use Illuminate\View\View;

class StockController extends Controller
{
    public function index(): View
    {
        $barres = BarreAchat::disponible()->with('operation.client', 'remboursementCredit.credit.client')->orderBy('created_at')->get();

        return view('stock.index', [
            'barres' => $barres,
            'poidsTotal' => $barres->sum('poids'),
            'montantTotal' => $barres->sum('montant'),
        ]);
    }
}
