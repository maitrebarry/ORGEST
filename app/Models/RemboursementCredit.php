<?php

namespace App\Models;

use App\Concerns\BelongsToBureau;
use App\Concerns\HasNumeroSequentiel;
use App\Support\CalculateurAchat;
use App\Support\TresorerieAuto;
use Illuminate\Support\Facades\DB;

class RemboursementCredit extends \Illuminate\Database\Eloquent\Model
{
    use HasNumeroSequentiel, BelongsToBureau;

    public const MODES = [
        'especes' => 'Espèces',
        'or' => 'Or',
        'or_especes' => 'Or + espèces',
    ];

    protected $table = 'remboursements_credit';

    protected $fillable = [
        'bureau_id',
        'credit_id',
        'numero',
        'date_remboursement',
        'mode',
        'prix_base',
        'montant_especes',
        'montant_or',
        'montant_total',
        'reliquat',
        'observations',
        'user_id',
    ];

    protected function casts(): array
    {
        return [
            'date_remboursement' => 'datetime',
            'prix_base' => 'decimal:2',
            'montant_especes' => 'decimal:2',
            'montant_or' => 'decimal:2',
            'montant_total' => 'decimal:2',
            'reliquat' => 'decimal:2',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (RemboursementCredit $r) {
            if (empty($r->numero)) {
                $r->numero = static::genererNumeroSequentiel('REMB', 'numero', 4, true);
            }
        });
    }

    public function credit()
    {
        return $this->belongsTo(Credit::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function lignesOr()
    {
        return $this->hasMany(BarreAchat::class, 'remboursement_credit_id')->orderBy('numero_barre');
    }

    public function getModeLibelleAttribute(): string
    {
        return self::MODES[$this->mode] ?? $this->mode;
    }

    /**
     * Enregistre un remboursement (espèces, or, ou or + espèces) sur un
     * crédit. Réutilise EXACTEMENT le même moteur de calcul que les achats
     * d'or (App\Support\CalculateurAchat) pour valoriser l'or apporté — même
     * règle de troncature de densité, même recherche dans le barème, même
     * formule de prix unitaire. Aucune validation silencieuse : si une barre
     * ne correspond à aucune ligne du barème, tout le remboursement est
     * rejeté.
     *
     * Formule unifiée (couvre les 3 modes ainsi que les cas d'or excédentaire
     * ou insuffisant décrits dans la spécification) :
     *   totalApporte = espècesApportées + valeurOr
     *   si totalApporte <= soldeAvant : rien à rendre, tout s'impute à la dette
     *   sinon : l'excédent devient un reliquat rendu en espèces au client
     *
     * @param  array{montant_especes?: string, prix_base?: string, barres?: array<array{poids: string, eau: string}>}  $donnees
     */
    public static function enregistrer(Credit $credit, string $mode, array $donnees, User $utilisateur, ?string $modePaiementEspeces = null): self
    {
        if ($credit->estAnnule()) {
            throw new \RuntimeException('Impossible de rembourser un crédit annulé.');
        }

        $soldeAvant = $credit->solde();

        if ($soldeAvant <= 0) {
            throw new \RuntimeException('Ce crédit est déjà soldé.');
        }

        $devise = $utilisateur->devise_symbole;
        $especesApportees = (string) ($donnees['montant_especes'] ?? '0');

        if ($mode === 'especes' && bccomp($especesApportees, (string) $soldeAvant, 2) > 0) {
            throw new \RuntimeException('Le montant remboursé ('.number_format((float) $especesApportees, 0, ',', ' ').' '.$devise.') ne peut pas dépasser le solde restant ('.number_format($soldeAvant, 0, ',', ' ').' '.$devise.').');
        }

        $lignesCalculees = [];
        $valeurOr = '0';

        if ($mode !== 'especes') {
            $bareme = BaremeVersion::actif()->first();

            if (! $bareme) {
                throw new \RuntimeException("Aucun barème actif : impossible de valoriser l'or apporté.");
            }

            $prixBase = (string) ($donnees['prix_base'] ?? '0');
            $erreurs = [];

            foreach (($donnees['barres'] ?? []) as $i => $barre) {
                $resultat = CalculateurAchat::calculerBarre((string) $barre['poids'], (string) $barre['eau'], $prixBase, $bareme);

                if ($resultat['erreur']) {
                    $erreurs[] = 'Barre '.($i + 1).' : '.$resultat['erreur'];

                    continue;
                }

                $lignesCalculees[] = [...$barre, ...$resultat];
                $valeurOr = bcadd($valeurOr, $resultat['montant'], 2);
            }

            if (! empty($erreurs)) {
                throw new \RuntimeException(implode(' ', $erreurs));
            }

            if (empty($lignesCalculees)) {
                throw new \RuntimeException("Au moins une barre d'or est requise pour ce mode de remboursement.");
            }
        }

        $totalApporte = bcadd($especesApportees, $valeurOr, 2);

        if (bccomp($totalApporte, (string) $soldeAvant, 2) > 0) {
            $reliquat = bcsub($totalApporte, (string) $soldeAvant, 2);
            $montantApplique = (string) $soldeAvant;
        } else {
            $reliquat = '0';
            $montantApplique = $totalApporte;
        }

        return DB::transaction(function () use ($credit, $mode, $especesApportees, $valeurOr, $montantApplique, $reliquat, $lignesCalculees, $donnees, $utilisateur, $modePaiementEspeces) {
            $remboursement = static::create([
                'credit_id' => $credit->id,
                'date_remboursement' => $donnees['date_remboursement'] ?? now(),
                'mode' => $mode,
                'prix_base' => $mode !== 'especes' ? ($donnees['prix_base'] ?? null) : null,
                'montant_especes' => $especesApportees,
                'montant_or' => $valeurOr,
                'montant_total' => $montantApplique,
                'reliquat' => $reliquat,
                'observations' => $donnees['observations'] ?? null,
                'user_id' => $utilisateur->id,
            ]);

            foreach ($lignesCalculees as $i => $c) {
                $remboursement->lignesOr()->create([
                    'numero_barre' => $i + 1,
                    'poids' => $c['poids'],
                    'eau' => $c['eau'],
                    'densite_brute' => $c['densite_brute'],
                    'densite_tronquee' => $c['densite_tronquee'],
                    'bareme_ligne_id' => $c['bareme_ligne_id'],
                    'carat' => $c['carat'],
                    'prix_unitaire' => $c['prix_unitaire'],
                    'montant' => $c['montant'],
                    'statut' => 'en_stock',
                ]);
            }

            $credit->update(['montant_rembourse' => bcadd((string) $credit->montant_rembourse, $montantApplique, 2)]);

            if (bccomp($especesApportees, '0', 2) > 0) {
                TresorerieAuto::synchroniser($remboursement, 'credit_remboursement', 'entree', 'Remboursement crédit '.$credit->numero.' — espèces', (float) $especesApportees, $utilisateur, $modePaiementEspeces);
            }

            if (bccomp($reliquat, '0', 2) > 0) {
                $journee = JourneeFinanciere::where('bureau_id', $credit->bureau_id)->ouverte()->latest('date_ouverture')->first();

                if (! $journee) {
                    throw new \RuntimeException("Aucune journée financière n'est ouverte : impossible de remettre le reliquat en espèces.");
                }

                if ($journee->soldeDisponible() < (float) $reliquat) {
                    $devise = $utilisateur->devise_symbole;
                    throw new \RuntimeException('Fonds insuffisant pour remettre le reliquat de '.number_format((float) $reliquat, 0, ',', ' ').' '.$devise.' au client.');
                }

                TresorerieAuto::synchroniser($remboursement, 'credit_reliquat', 'sortie', 'Reliquat rendu — crédit '.$credit->numero, (float) $reliquat, $utilisateur);
            }

            return $remboursement;
        });
    }
}
