<?php

namespace App\Models;

use App\Concerns\BelongsToBureau;
use Illuminate\Database\Eloquent\Model;

class JourneeFinanciere extends Model
{
    use BelongsToBureau;

    public const STATUTS = [
        'ouverte' => 'Ouverte',
        'fermee' => 'Fermée',
    ];

    protected $fillable = [
        'bureau_id',
        'statut',
        'date_ouverture',
        'date_fermeture',
        'solde_theorique_fermeture',
        'solde_physique_fermeture',
        'ecart_fermeture',
        'observations',
        'ouverte_par',
        'fermee_par',
    ];

    protected function casts(): array
    {
        return [
            'date_ouverture' => 'datetime',
            'date_fermeture' => 'datetime',
            'solde_theorique_fermeture' => 'decimal:2',
            'solde_physique_fermeture' => 'decimal:2',
            'ecart_fermeture' => 'decimal:2',
        ];
    }

    public function mouvements()
    {
        return $this->hasMany(MouvementFinancier::class)->orderBy('date_mouvement')->orderBy('id');
    }

    public function ouvrePar()
    {
        return $this->belongsTo(User::class, 'ouverte_par');
    }

    public function fermePar()
    {
        return $this->belongsTo(User::class, 'fermee_par');
    }

    public function scopeOuverte($query)
    {
        return $query->where('statut', 'ouverte');
    }

    public function estOuverte(): bool
    {
        return $this->statut === 'ouverte';
    }

    public function totalEntrees(): float
    {
        return (float) $this->mouvements()->where('sens', 'entree')->sum('montant');
    }

    public function totalSorties(): float
    {
        return (float) $this->mouvements()->where('sens', 'sortie')->sum('montant');
    }

    public function fondsInitial(): float
    {
        return (float) $this->mouvements()->where('nature', 'fonds_initial')->sum('montant');
    }

    public function totalApprovisionnements(): float
    {
        return (float) $this->mouvements()->where('nature', 'approvisionnement')->sum('montant');
    }

    public function totalPaiementsAchats(): float
    {
        return (float) $this->mouvements()->where('nature', 'paiement_achat')->sum('montant');
    }

    public function totalEncaissementsVentes(): float
    {
        return (float) $this->mouvements()->where('nature', 'encaissement_vente')->sum('montant');
    }

    /**
     * Solde disponible = total des entrées − total des sorties du fonds
     * (cahier des charges §8.5). Le fonds initial compte comme une entrée.
     */
    public function soldeDisponible(): float
    {
        return $this->totalEntrees() - $this->totalSorties();
    }

    /**
     * Fonds déjà épuisé : plus aucun achat ne pourra être payé tant qu'un
     * approvisionnement n'aura pas été enregistré.
     */
    public function estEnRupture(): bool
    {
        return $this->soldeDisponible() <= 0;
    }

    /**
     * Solde bas mais pas encore épuisé : sert à alerter AVANT la rupture
     * plutôt qu'une fois qu'elle est déjà là. Relatif au total des entrées
     * du jour (fonds initial + approvisionnements) plutôt qu'un montant fixe
     * en FCFA, pour rester pertinent quelle que soit l'échelle du bureau.
     */
    public function soldeFaible(float $seuil = 0.10): bool
    {
        if ($this->estEnRupture()) {
            return false;
        }

        $totalEntrees = $this->totalEntrees();

        return $totalEntrees > 0 && $this->soldeDisponible() <= $totalEntrees * $seuil;
    }

    public static function ouvrirJournee(float $montantInitial, ?string $observations, User $utilisateur): self
    {
        if (static::where('bureau_id', $utilisateur->bureau_id)->ouverte()->exists()) {
            throw new \RuntimeException('Une journée financière est déjà ouverte. Fermez-la avant d\'en ouvrir une nouvelle.');
        }

        $journee = static::create([
            'bureau_id' => $utilisateur->bureau_id,
            'statut' => 'ouverte',
            'date_ouverture' => now(),
            'observations' => $observations,
            'ouverte_par' => $utilisateur->id,
        ]);

        $journee->mouvements()->create([
            'bureau_id' => $utilisateur->bureau_id,
            'nature' => 'fonds_initial',
            'sens' => 'entree',
            'libelle' => 'Fonds initial du jour',
            'montant' => $montantInitial,
            'date_mouvement' => now(),
            'user_id' => $utilisateur->id,
        ]);

        return $journee;
    }

    public function fermerJournee(float $soldePhysique, User $utilisateur): void
    {
        if (! $this->estOuverte()) {
            throw new \RuntimeException('Cette journée est déjà fermée.');
        }

        $soldeTheorique = $this->soldeDisponible();

        $this->update([
            'statut' => 'fermee',
            'date_fermeture' => now(),
            'solde_theorique_fermeture' => $soldeTheorique,
            'solde_physique_fermeture' => $soldePhysique,
            'ecart_fermeture' => round($soldePhysique - $soldeTheorique, 2),
            'fermee_par' => $utilisateur->id,
        ]);
    }
}
