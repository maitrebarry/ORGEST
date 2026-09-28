<?php

namespace App\Models;

use App\Concerns\BelongsToBureau;
use App\Concerns\HasNumeroSequentiel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Client extends Model
{
    use HasFactory, HasNumeroSequentiel, BelongsToBureau;

    protected $fillable = [
        'identifiant',
        'nom',
        'prenom',
        'telephone',
        'adresse',
        'actif',
        'user_id',
        'bureau_id',
    ];

    protected function casts(): array
    {
        return [
            'actif' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Client $client) {
            if (empty($client->identifiant)) {
                $client->identifiant = static::genererNumeroSequentiel('CL', 'identifiant');
            }
        });
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function getNomCompletAttribute(): string
    {
        return trim($this->prenom.' '.$this->nom);
    }

    /**
     * Module « Comptes clients » (distinct du Cahier de crédit) : relevé
     * continu par client, ordonné chronologiquement.
     */
    public function mouvementsCompte()
    {
        return $this->hasMany(MouvementCompteClient::class)->orderBy('date_mouvement')->orderBy('id');
    }

    /**
     * Solde courant = solde_apres du dernier mouvement actif (non annulé).
     * Positif : le client doit à l'entreprise. Négatif : l'entreprise lui doit.
     */
    public function soldeCompteCourant(): float
    {
        // reorder() est indispensable ici : mouvementsCompte() porte déjà un
        // tri ASC (chronologique) par défaut, qui s'ajouterait sinon à celui-
        // ci au lieu de le remplacer (les appels orderBy successifs de
        // Laravel s'accumulent) et ferait retomber sur la plus ANCIENNE ligne.
        return (float) ($this->mouvementsCompte()
            ->whereNull('annule_at')
            ->reorder('date_mouvement', 'desc')
            ->orderByDesc('id')
            ->value('solde_apres') ?? 0);
    }
}
