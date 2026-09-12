<?php

namespace App\Models;

use App\Support\ExtracteurCouleur;
use Illuminate\Database\Eloquent\Model;

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
}
