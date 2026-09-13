<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BarreAchat extends Model
{
    protected $table = 'barres_achat';

    protected $fillable = [
        'operation_achat_id',
        'operation_vente_id',
        'remboursement_credit_id',
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

    /**
     * Renseigné uniquement quand la barre provient d'un remboursement de
     * crédit en or (au lieu d'un achat classique) — voir
     * remboursement_credit_id, exclusif de operation_achat_id.
     */
    public function remboursementCredit()
    {
        return $this->belongsTo(RemboursementCredit::class, 'remboursement_credit_id');
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
     * Réellement disponible à la vente : en stock ET (issue d'un achat non
     * annulé — une opération annulée passe toutes ses barres à 'annulee' —
     * OU issue d'un remboursement de crédit en or, toujours définitif dès
     * son enregistrement, comme un achat).
     */
    public function scopeDisponible($query)
    {
        // whereHas('remboursementCredit') s'appuie sur le global scope de
        // RemboursementCredit (BelongsToBureau) pour cloisonner par bureau —
        // barres_achat n'a pas sa propre colonne bureau_id, un simple
        // whereNotNull('remboursement_credit_id') fuiterait les barres
        // d'un autre bureau.
        return $query->where('statut', 'en_stock')
            ->where(function ($q) {
                $q->whereHas('operation', fn ($qq) => $qq->where('statut', 'validee'))
                    ->orWhereHas('remboursementCredit');
            });
    }
}
