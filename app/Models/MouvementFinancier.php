<?php

namespace App\Models;

use App\Concerns\BelongsToBureau;
use Illuminate\Database\Eloquent\Model;

class MouvementFinancier extends Model
{
    use BelongsToBureau;

    public const NATURES = [
        'fonds_initial' => 'Fonds initial',
        'approvisionnement' => 'Approvisionnement',
        'paiement_achat' => "Paiement d'achat",
        'encaissement_vente' => 'Encaissement de vente',
        'autre' => 'Autre',
    ];

    public const SENS = [
        'entree' => 'Entrée',
        'sortie' => 'Sortie',
    ];

    public const MODES_PAIEMENT = [
        'especes' => 'Espèces',
        'virement' => 'Virement bancaire',
        'orange_money' => 'Orange Money',
        'wave' => 'Wave',
        'mobicash' => 'Mobicash',
        'autre' => 'Autre',
    ];

    protected $fillable = [
        'journee_financiere_id',
        'bureau_id',
        'nature',
        'sens',
        'libelle',
        'mode_paiement',
        'montant',
        'date_mouvement',
        'source_type',
        'source_id',
        'reversal_of_id',
        'user_id',
    ];

    protected function casts(): array
    {
        return [
            'montant' => 'decimal:2',
            'date_mouvement' => 'datetime',
        ];
    }

    public function journee()
    {
        return $this->belongsTo(JourneeFinanciere::class, 'journee_financiere_id');
    }

    public function source()
    {
        return $this->morphTo();
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function reversalOf()
    {
        return $this->belongsTo(self::class, 'reversal_of_id');
    }

    public function reversal()
    {
        return $this->hasOne(self::class, 'reversal_of_id');
    }

    public function estAutomatique(): bool
    {
        return ! is_null($this->source_id);
    }

    public function estContrepassation(): bool
    {
        return ! is_null($this->reversal_of_id);
    }

    public function getNatureLibelleAttribute(): string
    {
        return self::NATURES[$this->nature] ?? $this->nature;
    }

    public function getSensLibelleAttribute(): string
    {
        return self::SENS[$this->sens] ?? $this->sens;
    }

    public function getModePaiementLibelleAttribute(): ?string
    {
        return self::MODES_PAIEMENT[$this->mode_paiement] ?? $this->mode_paiement;
    }
}
