<?php

namespace App\Models;

use App\Support\ExtracteurCouleur;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class Bureau extends Model
{
    protected $table = 'bureaux';

    /** Repli si le pays n'est pas renseigné ou absent de config('pays_devises') — contexte d'origine de l'application. */
    private const PAYS_PAR_DEFAUT = 'Mali';

    protected $fillable = [
        'nom',
        'logo',
        'couleur',
        'adresse',
        'telephone',
        'email',
        'pays',
        'actif',
        'proprietaire_id',
    ];

    protected function casts(): array
    {
        return [
            'actif' => 'boolean',
        ];
    }

    public function proprietaire()
    {
        return $this->belongsTo(User::class, 'proprietaire_id');
    }

    public function utilisateurs()
    {
        return $this->hasMany(User::class);
    }

    public function getLogoUrlAttribute(): ?string
    {
        return $this->logo ? asset('storage/'.$this->logo) : null;
    }

    /**
     * Nuance plus sombre de la couleur dominante (titres/bordures), pour
     * rester lisible même quand le logo est très vif.
     */
    public function getCouleurSombreAttribute(): ?string
    {
        return $this->couleur ? ExtracteurCouleur::assombrir($this->couleur) : null;
    }

    /**
     * Devise déduite automatiquement du pays du bureau (config/pays_devises.php) :
     * l'utilisateur ne choisit jamais la devise elle-même, seulement le pays.
     */
    public function getDeviseAttribute(): array
    {
        return config('pays_devises.'.$this->pays) ?? config('pays_devises.'.self::PAYS_PAR_DEFAUT);
    }

    public function getDeviseSymboleAttribute(): string
    {
        return $this->devise['symbole'];
    }

    /**
     * Le "Bambara" (Total ÷ 5) est un usage propre au marché malien de l'or —
     * ne s'applique pas aux bureaux d'autres pays. Un bureau sans pays
     * renseigné est un enregistrement antérieur au multi-pays (contexte
     * d'origine malien), donc traité comme malien par défaut.
     */
    public function estAuMali(): bool
    {
        return ($this->pays ?? self::PAYS_PAR_DEFAUT) === self::PAYS_PAR_DEFAUT;
    }

    public function getDeviseCodeAttribute(): string
    {
        return $this->devise['code'];
    }

    /**
     * Supprime PHYSIQUEMENT et DÉFINITIVEMENT ce bureau et absolument toutes
     * les données qui lui sont rattachées (utilisateurs, clients, achats,
     * ventes, crédits, trésorerie, comptes clients, barème propre au
     * bureau). Choix explicite du client : AUCUNE sauvegarde, AUCUNE
     * suppression douce — une fois exécutée, rien n'est récupérable, même
     * par un superadmin. Réservé au superadmin : voir le garde-fou dans
     * UserController::destroy(), seul appelant.
     *
     * Ordre imposé par les clés étrangères : client_id est en RESTRICT sur
     * achats/ventes/crédits/comptes clients, donc ces tables doivent être
     * vidées avant les clients. Le reste (barres d'achat, remboursements,
     * mouvements financiers, lignes de barème) est supprimé automatiquement
     * en cascade par la base — SAUF les barres d'achat issues d'un
     * remboursement de crédit en or : barres_achat.remboursement_credit_id
     * est en nullOnDelete (pas en cascade), donc elles seraient orphelines
     * si on ne les supprimait pas explicitement avant les crédits.
     */
    public function supprimerDefinitivementAvecToutesSesDonnees(): void
    {
        DB::transaction(function () {
            DB::table('journee_financieres')->where('bureau_id', $this->id)->delete();

            $remboursementIds = DB::table('remboursements_credit')
                ->whereIn('credit_id', DB::table('credits')->where('bureau_id', $this->id)->select('id'))
                ->pluck('id');
            DB::table('barres_achat')->whereIn('remboursement_credit_id', $remboursementIds)->delete();

            DB::table('credits')->where('bureau_id', $this->id)->delete();
            DB::table('mouvements_compte_client')->where('bureau_id', $this->id)->delete();
            DB::table('operations_vente')->where('bureau_id', $this->id)->delete();
            DB::table('operations_achat')->where('bureau_id', $this->id)->delete();
            DB::table('clients')->where('bureau_id', $this->id)->delete();
            DB::table('bareme_versions')->where('bureau_id', $this->id)->delete();

            foreach (User::where('bureau_id', $this->id)->get() as $utilisateur) {
                if ($utilisateur->photo) {
                    Storage::disk('public')->delete($utilisateur->photo);
                }
                $utilisateur->delete();
            }

            if ($this->logo) {
                Storage::disk('public')->delete($this->logo);
            }

            $this->delete();
        });
    }
}
