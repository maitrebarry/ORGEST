<?php

namespace App\Models;

use App\Concerns\BelongsToBureau;
use App\Concerns\HasNumeroSequentiel;
use App\Support\TresorerieAuto;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * Ligne du relevé continu d'un client (module « Comptes clients »), distinct
 * du « Cahier de crédit » (Credit) : pas d'octroi ponctuel remboursable,
 * mais un cumul permanent (retraits, règlements en or, transport…) à
 * l'image d'un relevé bancaire.
 */
class MouvementCompteClient extends Model
{
    use HasNumeroSequentiel, BelongsToBureau;

    protected $table = 'mouvements_compte_client';

    public const TYPES = [
        'retrait' => 'Retrait remis au client',
        'achat_or' => 'Règlement en or',
        'transport' => 'Transport',
        'autre' => 'Autre',
    ];

    // Mêmes clés que MouvementFinancier::SENS (transmises telles quelles à
    // TresorerieAuto) mais lues du point de vue de la dette du CLIENT.
    public const SENS = [
        'sortie' => 'Sortie de caisse — le client reçoit (sa dette augmente)',
        'entree' => 'Entrée en caisse — le client rembourse (sa dette diminue)',
    ];

    protected $fillable = [
        'bureau_id',
        'numero',
        'client_id',
        'type',
        'sens',
        'montant',
        'solde_apres',
        'impacte_caisse',
        'mode_paiement',
        'date_mouvement',
        'observations',
        'user_id',
        'annule_at',
    ];

    protected function casts(): array
    {
        return [
            'montant' => 'decimal:2',
            'solde_apres' => 'decimal:2',
            'impacte_caisse' => 'boolean',
            'date_mouvement' => 'datetime',
            'annule_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (MouvementCompteClient $mouvement) {
            if (empty($mouvement->numero)) {
                $mouvement->numero = static::genererNumeroSequentiel('CC', 'numero', 4, true);
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

    public function getTypeLibelleAttribute(): string
    {
        return self::TYPES[$this->type] ?? $this->type;
    }

    public function getModePaiementLibelleAttribute(): ?string
    {
        return $this->mode_paiement ? (MouvementFinancier::MODES_PAIEMENT[$this->mode_paiement] ?? $this->mode_paiement) : null;
    }

    public function estAnnule(): bool
    {
        return ! is_null($this->annule_at);
    }

    /**
     * Enregistre un mouvement pour un client : calcule le nouveau solde à
     * partir du dernier solde connu, et — si le mouvement touche la caisse —
     * exige une journée financière ouverte (avec fonds suffisants pour une
     * sortie), exactement comme Credit::octroyer().
     */
    public static function enregistrer(array $donnees, Client $client, User $utilisateur): self
    {
        $impacteCaisse = (bool) ($donnees['impacte_caisse'] ?? false);
        $sens = $donnees['sens'];
        $montant = (float) $donnees['montant'];

        if ($impacteCaisse) {
            $journee = JourneeFinanciere::where('bureau_id', $utilisateur->bureau_id)->ouverte()->latest('date_ouverture')->first();

            if (! $journee) {
                throw new \RuntimeException("Aucune journée financière n'est ouverte : ouvrez d'abord la journée avant d'enregistrer un mouvement qui touche la caisse (ou décochez « touche la caisse » pour un règlement en nature).");
            }

            if ($sens === 'sortie' && $journee->soldeDisponible() < $montant) {
                $devise = $utilisateur->devise_symbole;
                throw new \RuntimeException('Fonds insuffisant : solde disponible de '.number_format($journee->soldeDisponible(), 0, ',', ' ').' '.$devise.". Enregistrez d'abord un approvisionnement.");
            }
        }

        return DB::transaction(function () use ($donnees, $client, $utilisateur, $impacteCaisse, $sens, $montant) {
            $soldeAvant = $client->soldeCompteCourant();
            $soldeApres = $sens === 'sortie' ? round($soldeAvant + $montant, 2) : round($soldeAvant - $montant, 2);

            $mouvement = static::create([
                'bureau_id' => $utilisateur->bureau_id,
                'client_id' => $client->id,
                'type' => $donnees['type'],
                'sens' => $sens,
                'montant' => $montant,
                'solde_apres' => $soldeApres,
                'impacte_caisse' => $impacteCaisse,
                'mode_paiement' => $impacteCaisse ? ($donnees['mode_paiement'] ?? null) : null,
                'date_mouvement' => $donnees['date_mouvement'] ?? now(),
                'observations' => $donnees['observations'] ?? null,
                'user_id' => $utilisateur->id,
            ]);

            if ($impacteCaisse) {
                TresorerieAuto::synchroniser(
                    $mouvement,
                    'compte_client',
                    $sens,
                    self::TYPES[$mouvement->type].' — '.$client->nom_complet.' ('.$mouvement->numero.')',
                    $montant,
                    $utilisateur,
                    $mouvement->mode_paiement
                );
            }

            return $mouvement;
        });
    }

    /**
     * Annulation contrôlée : réservée au DERNIER mouvement actif du client,
     * pour ne jamais invalider les solde_apres déjà figés sur les lignes
     * suivantes. Contrepasse le mouvement de caisse s'il y en avait un.
     */
    public function annuler(User $utilisateur): void
    {
        if ($this->estAnnule()) {
            throw new \RuntimeException('Ce mouvement est déjà annulé.');
        }

        $dernier = static::where('client_id', $this->client_id)
            ->whereNull('annule_at')
            ->orderByDesc('date_mouvement')
            ->orderByDesc('id')
            ->first();

        if (! $dernier || $dernier->id !== $this->id) {
            throw new \RuntimeException("Seul le dernier mouvement du relevé peut être annulé, pour ne pas fausser l'historique des soldes.");
        }

        DB::transaction(function () use ($utilisateur) {
            $this->update(['annule_at' => now()]);

            if ($this->impacte_caisse) {
                $sensInverse = $this->sens === 'sortie' ? 'entree' : 'sortie';
                TresorerieAuto::synchroniser($this, 'compte_client', $sensInverse, 'Annulation — '.$this->numero, (float) $this->montant, $utilisateur);
            }
        });
    }
}
