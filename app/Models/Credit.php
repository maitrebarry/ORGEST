<?php

namespace App\Models;

use App\Concerns\BelongsToBureau;
use App\Concerns\HasNumeroSequentiel;
use App\Support\TresorerieAuto;
use Illuminate\Database\Eloquent\Model;

class Credit extends Model
{
    use HasNumeroSequentiel, BelongsToBureau;

    protected $fillable = [
        'bureau_id',
        'numero',
        'client_id',
        'date_credit',
        'montant_accorde',
        'montant_remis',
        'montant_rembourse',
        'observations',
        'user_id',
        'annule_at',
    ];

    protected function casts(): array
    {
        return [
            'date_credit' => 'datetime',
            'montant_accorde' => 'decimal:2',
            'montant_remis' => 'decimal:2',
            'montant_rembourse' => 'decimal:2',
            'annule_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Credit $credit) {
            if (empty($credit->numero)) {
                $credit->numero = static::genererNumeroSequentiel('CR', 'numero', 4, true);
            }
        });
    }

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function remboursements()
    {
        return $this->hasMany(RemboursementCredit::class)->orderBy('date_remboursement')->orderBy('id');
    }

    public function estAnnule(): bool
    {
        return ! is_null($this->annule_at);
    }

    public function solde(): float
    {
        return round((float) $this->montant_remis - (float) $this->montant_rembourse, 2);
    }

    public function statutLibelle(): string
    {
        if ($this->estAnnule()) {
            return 'Annulé';
        }

        if ((float) $this->montant_rembourse <= 0) {
            return 'En cours';
        }

        return $this->solde() > 0 ? 'Partiellement remboursé' : 'Soldé';
    }

    public function statutBadge(): string
    {
        return match ($this->statutLibelle()) {
            'Soldé' => 'bg-success',
            'Partiellement remboursé' => 'bg-warning text-dark',
            'Annulé' => 'bg-secondary',
            default => 'bg-danger',
        };
    }

    /**
     * Octroi d'un crédit : décaissement réel, soumis aux mêmes garanties que
     * le paiement d'un achat (journée ouverte, fonds suffisant) — un crédit
     * n'est ni plus ni moins qu'une sortie de trésorerie vers un client.
     */
    public static function octroyer(array $donnees, \App\Models\User $utilisateur, ?string $modePaiement = null): self
    {
        $bureauId = $utilisateur->bureau_id;
        $devise = $utilisateur->devise_symbole;

        $journee = JourneeFinanciere::where('bureau_id', $bureauId)->ouverte()->latest('date_ouverture')->first();

        if (! $journee) {
            throw new \RuntimeException("Aucune journée financière n'est ouverte : ouvrez d'abord la journée (fonds initial) avant d'octroyer un crédit.");
        }

        if ($journee->soldeDisponible() < (float) $donnees['montant_remis']) {
            throw new \RuntimeException('Fonds insuffisant : solde disponible de '.number_format($journee->soldeDisponible(), 0, ',', ' ').' '.$devise.', ce crédit de '.number_format((float) $donnees['montant_remis'], 0, ',', ' ').' '.$devise." provoquerait une rupture. Enregistrez d'abord un approvisionnement.");
        }

        return \Illuminate\Support\Facades\DB::transaction(function () use ($donnees, $utilisateur, $modePaiement) {
            $credit = static::create([
                'client_id' => $donnees['client_id'],
                'date_credit' => $donnees['date_credit'],
                'montant_accorde' => $donnees['montant_accorde'],
                'montant_remis' => $donnees['montant_remis'],
                'montant_rembourse' => 0,
                'observations' => $donnees['observations'] ?? null,
                'user_id' => $utilisateur->id,
            ]);

            TresorerieAuto::synchroniser($credit, 'credit_octroi', 'sortie', 'Crédit '.$credit->numero.' — remise à '.$credit->client->nom_complet, (float) $credit->montant_remis, $utilisateur, $modePaiement);

            return $credit;
        });
    }

    /**
     * Annulation contrôlée : impossible dès qu'un remboursement existe (le
     * crédit a déjà commencé à vivre). Restitue le décaissement initial dans
     * la trésorerie plutôt que de simplement supprimer l'enregistrement.
     */
    public function annuler(): void
    {
        if ($this->estAnnule()) {
            throw new \RuntimeException('Ce crédit est déjà annulé.');
        }

        if ((float) $this->montant_rembourse > 0) {
            throw new \RuntimeException("Impossible d'annuler : ce crédit a déjà fait l'objet d'un remboursement.");
        }

        \Illuminate\Support\Facades\DB::transaction(function () {
            $this->update(['annule_at' => now()]);

            TresorerieAuto::synchroniser($this, 'credit_annulation', 'entree', 'Annulation crédit '.$this->numero, (float) $this->montant_remis, auth()->user());
        });
    }
}
