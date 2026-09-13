<?php

namespace App\Models;

use App\Concerns\BelongsToBureau;
use App\Concerns\HasNumeroSequentiel;
use App\Support\TresorerieAuto;
use Illuminate\Database\Eloquent\Model;

class OperationVente extends Model
{
    use HasNumeroSequentiel, BelongsToBureau;

    public const STATUTS = [
        'validee' => 'Validée',
        'annulee' => 'Annulée',
    ];

    protected $table = 'operations_vente';

    protected $fillable = [
        'numero',
        'bureau_id',
        'client_id',
        'date_operation',
        'prix_base',
        'montant_total',
        'montant_paye',
        'statut',
        'observations',
        'user_id',
        'validee_at',
        'validee_par',
    ];

    protected function casts(): array
    {
        return [
            'date_operation' => 'datetime',
            'prix_base' => 'decimal:2',
            'montant_total' => 'decimal:2',
            'montant_paye' => 'decimal:2',
            'validee_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (OperationVente $operation) {
            if (empty($operation->numero)) {
                $operation->numero = static::genererNumeroSequentiel('VEN', 'numero', 4, true);
            }
        });
    }

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function barres()
    {
        return $this->hasMany(BarreAchat::class, 'operation_vente_id')->orderBy('id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function valideur()
    {
        return $this->belongsTo(User::class, 'validee_par');
    }

    public function getStatutLibelleAttribute(): string
    {
        return self::STATUTS[$this->statut] ?? $this->statut;
    }

    public function estValidee(): bool
    {
        return $this->statut === 'validee';
    }

    public function reste(): float
    {
        return round((float) $this->montant_total - (float) $this->montant_paye, 2);
    }

    /**
     * Encaissement (partiel ou total) de la part de l'acheteur. Une opération
     * est validée dès sa création : tout encaissement crédite donc le fonds
     * (symétrique à l'achat, qui le débite), sauf s'il n'y a aucune journée
     * financière ouverte.
     */
    public function enregistrerPaiement(string $montant, ?User $utilisateur = null, ?string $modePaiement = null): void
    {
        if ($this->statut === 'annulee') {
            throw new \RuntimeException('Impossible d\'encaisser une opération annulée.');
        }

        $nouveauMontantPaye = bcadd((string) $this->montant_paye, $montant, 2);

        if (bccomp($nouveauMontantPaye, (string) $this->montant_total, 2) > 0) {
            $devise = $this->bureau?->devise_symbole ?? config('pays_devises.Mali.symbole');
            throw new \RuntimeException('Le montant encaissé ne peut pas dépasser le montant total de l\'opération (reste à encaisser : '.number_format($this->reste(), 0, ',', ' ').' '.$devise.').');
        }

        $this->update(['montant_paye' => $nouveauMontantPaye]);

        TresorerieAuto::synchroniser($this, 'encaissement_vente', 'entree', 'Vente '.$this->numero.' — encaissement', (float) $montant, $utilisateur, $modePaiement);
    }

    /**
     * Corrige manuellement la densité, le carat et le prix unitaire de
     * vente d'une barre déjà enregistrée — recalcule le montant de la
     * barre et le montant total de l'opération. Refusé si cela ferait
     * passer le montant total sous le montant déjà encaissé.
     */
    public function corrigerBarre(BarreAchat $barre, string $densite, string $carat, string $prixUnitaireVente): void
    {
        if ($barre->operation_vente_id !== $this->id) {
            throw new \RuntimeException("Cette barre n'appartient pas à cette opération.");
        }

        $nouveauMontantBarre = bcmul((string) $barre->poids, $prixUnitaireVente, 2);
        $nouveauMontantTotal = bcsub(bcadd((string) $this->montant_total, $nouveauMontantBarre, 2), (string) $barre->montant_vente, 2);

        if (bccomp($nouveauMontantTotal, (string) $this->montant_paye, 2) < 0) {
            $devise = $this->bureau?->devise_symbole ?? config('pays_devises.Mali.symbole');
            throw new \RuntimeException('Ce changement ferait passer le montant total ('.number_format((float) $nouveauMontantTotal, 0, ',', ' ').' '.$devise.') sous le montant déjà encaissé ('.number_format((float) $this->montant_paye, 0, ',', ' ').' '.$devise.').');
        }

        $barre->update([
            'densite_tronquee' => $densite,
            'carat' => $carat,
            'prix_unitaire_vente' => $prixUnitaireVente,
            'montant_vente' => $nouveauMontantBarre,
        ]);

        $this->update(['montant_total' => $nouveauMontantTotal]);
    }

    /**
     * Annulation tracée : les barres vendues retournent en stock (rien ne
     * dépend encore d'une vente en aval dans l'application, contrairement à
     * l'achat où une barre déjà vendue bloque l'annulation).
     */
    public function annuler(string $motif): void
    {
        if ($this->statut === 'annulee') {
            throw new \RuntimeException('Cette opération est déjà annulée.');
        }

        $this->barres()->update([
            'operation_vente_id' => null,
            'prix_unitaire_vente' => null,
            'montant_vente' => null,
            'statut' => 'en_stock',
        ]);

        $this->update([
            'statut' => 'annulee',
            'observations' => trim(($this->observations ?? '')."\n[Annulée] ".$motif),
        ]);
    }
}
