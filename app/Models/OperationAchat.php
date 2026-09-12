<?php

namespace App\Models;

use App\Concerns\BelongsToBureau;
use App\Concerns\HasNumeroSequentiel;
use App\Support\TresorerieAuto;
use Illuminate\Database\Eloquent\Model;

class OperationAchat extends Model
{
    use HasNumeroSequentiel, BelongsToBureau;

    public const STATUTS = [
        'validee' => 'Validée',
        'annulee' => 'Annulée',
    ];

    protected $table = 'operations_achat';

    protected $fillable = [
        'numero',
        'bureau_id',
        'client_id',
        'date_operation',
        'prix_base',
        'bareme_version_id',
        'poids_total',
        'eau_total',
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
            'poids_total' => 'decimal:3',
            'eau_total' => 'decimal:4',
            'montant_total' => 'decimal:2',
            'montant_paye' => 'decimal:2',
            'validee_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (OperationAchat $operation) {
            if (empty($operation->numero)) {
                $operation->numero = static::genererNumeroSequentiel('ACH', 'numero', 4, true);
            }
        });
    }

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function barres()
    {
        return $this->hasMany(BarreAchat::class)->orderBy('numero_barre');
    }

    public function baremeVersion()
    {
        return $this->belongsTo(BaremeVersion::class);
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

    public function reste(): float
    {
        return round((float) $this->montant_total - (float) $this->montant_paye, 2);
    }

    public function estValidee(): bool
    {
        return $this->statut === 'validee';
    }

    /**
     * Enregistre un paiement partiel ou total au client (gestion de crédit :
     * l'entreprise peut devoir encore de l'argent au client vendeur d'or
     * tant que montant_paye < montant_total). Une opération est validée dès
     * sa création : tout paiement débite donc le fonds d'achat (cahier des
     * charges §8.3). Un paiement d'achat est un décaissement réel : il exige
     * une journée financière ouverte avec un solde suffisant — on ne laisse
     * jamais un achat « disparaître » du suivi de trésorerie faute de fonds
     * initial (confirmé par l'entreprise après un achat payé sans qu'aucun
     * fonds n'ait jamais été ouvert : la caisse doit toujours refléter la
     * réalité, jamais la contourner silencieusement).
     */
    public function enregistrerPaiement(string $montant, ?User $utilisateur = null, ?string $modePaiement = null): void
    {
        if ($this->statut === 'annulee') {
            throw new \RuntimeException('Impossible de payer une opération annulée.');
        }

        $nouveauMontantPaye = bcadd((string) $this->montant_paye, $montant, 2);

        if (bccomp($nouveauMontantPaye, (string) $this->montant_total, 2) > 0) {
            $devise = $this->bureau?->devise_symbole ?? config('pays_devises.Mali.symbole');
            throw new \RuntimeException('Le montant payé ne peut pas dépasser le montant total de l\'opération (reste à payer : '.number_format($this->reste(), 0, ',', ' ').' '.$devise.').');
        }

        $devise = $this->bureau?->devise_symbole ?? config('pays_devises.Mali.symbole');
        $journee = JourneeFinanciere::where('bureau_id', $this->bureau_id)->ouverte()->latest('date_ouverture')->first();

        if (! $journee) {
            throw new \RuntimeException("Aucune journée financière n'est ouverte : ouvrez d'abord la journée (fonds initial) avant d'enregistrer un paiement d'achat.");
        }

        if ($journee->soldeDisponible() < (float) $montant) {
            throw new \RuntimeException('Fonds insuffisant : solde disponible de '.number_format($journee->soldeDisponible(), 0, ',', ' ').' '.$devise.', ce paiement de '.number_format((float) $montant, 0, ',', ' ').' '.$devise." provoquerait une rupture. Enregistrez d'abord un approvisionnement.");
        }

        $this->update(['montant_paye' => $nouveauMontantPaye]);

        TresorerieAuto::synchroniser($this, 'paiement_achat', 'sortie', 'Achat '.$this->numero.' — paiement', (float) $montant, $utilisateur, $modePaiement);
    }

    /**
     * Annulation tracée (jamais de suppression physique d'une opération
     * validée). Impossible si une des barres a déjà été vendue.
     */
    public function annuler(string $motif): void
    {
        if ($this->statut === 'annulee') {
            throw new \RuntimeException('Cette opération est déjà annulée.');
        }

        if ($this->barres()->where('statut', 'vendu')->exists()) {
            throw new \RuntimeException("Impossible d'annuler : au moins une barre de cette opération a déjà été vendue.");
        }

        $this->barres()->update(['statut' => 'annulee']);

        $this->update([
            'statut' => 'annulee',
            'observations' => trim(($this->observations ?? '')."\n[Annulée] ".$motif),
        ]);
    }
}
