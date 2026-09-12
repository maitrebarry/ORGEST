<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BarreAchat extends Model
{
    protected $table = 'barres_achat';

    protected $fillable = [
        'operation_achat_id',
        'operation_vente_id',
        'numero_barre',
        'poids',
        'eau',
        'densite_brute',
        'densite_tronquee',
        'bareme_ligne_id',
        'carat',
        'prix_unitaire',
        'montant',
        'prix_unitaire_vente',
        'montant_vente',
        'statut',
    ];

    protected function casts(): array
    {
        return [
            'poids' => 'decimal:3',
            'eau' => 'decimal:4',
            'densite_brute' => 'decimal:4',
            'densite_tronquee' => 'decimal:2',
            'carat' => 'decimal:2',
            'prix_unitaire' => 'decimal:2',
            'montant' => 'decimal:2',
            'prix_unitaire_vente' => 'decimal:2',
            'montant_vente' => 'decimal:2',
        ];
    }

    public function operation()
    {
        return $this->belongsTo(OperationAchat::class, 'operation_achat_id');
    }

    public function operationVente()
    {
        return $this->belongsTo(OperationVente::class, 'operation_vente_id');
    }

    public function estVendu(): bool
    {
        return $this->statut === 'vendu';
    }

    public function baremeLigne()
    {
        return $this->belongsTo(BaremeLigne::class);
    }

    public function scopeEnStock($query)
    {
        return $query->where('statut', 'en_stock');
    }

    /**
     * Réellement disponible à la vente : en stock ET issue d'un achat non
     * annulé (une opération annulée passe toutes ses barres à 'annulee').
     */
    public function scopeDisponible($query)
    {
        return $query->where('statut', 'en_stock')
            ->whereHas('operation', fn ($q) => $q->where('statut', 'validee'));
    }
}
