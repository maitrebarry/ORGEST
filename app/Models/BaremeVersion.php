<?php

namespace App\Models;

use App\Concerns\BelongsToBureauOrGlobal;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class BaremeVersion extends Model
{
    use BelongsToBureauOrGlobal;

    protected $fillable = [
        'libelle',
        'actif',
        'observations',
        'user_id',
        'bureau_id',
    ];

    protected function casts(): array
    {
        return [
            'actif' => 'boolean',
        ];
    }

    public function lignes()
    {
        return $this->hasMany(BaremeLigne::class)->orderBy('ordre')->orderBy('densite_min');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * La version active « du moment » : si le bureau courant a sa PROPRE
     * version active, elle prime toujours sur le socle commun (bureau_id
     * NULL), même si les deux sont actives en même temps (ex. juste après
     * qu'un bureau a créé sa première version personnalisée).
     */
    public function scopeActif($query)
    {
        return $query->where('actif', true)->orderByRaw('bureau_id IS NULL');
    }

    /**
     * Recherche le carat correspondant à une densité déjà tronquée à 2
     * décimales. Retourne null si aucune ligne du barème ne correspond
     * (l'appelant doit alors bloquer l'opération, jamais valider en silence).
     */
    public function trouverLigne(float $densiteTronquee): ?BaremeLigne
    {
        return $this->lignes()
            ->where('densite_min', '<=', $densiteTronquee)
            ->where('densite_max', '>=', $densiteTronquee)
            ->first();
    }

    /**
     * Une version dont les lignes ont déjà servi à au moins une opération
     * d'achat/vente ne doit plus jamais être modifiée (seule une NOUVELLE
     * version peut corriger le barème à partir de ce moment-là) — cahier des
     * charges §5. Tant que le module Achat n'existe pas, aucune version n'est
     * encore "utilisée" : cette méthode renverra toujours faux jusqu'à ce
     * qu'elle soit branchée sur la relation réelle (barres achetées) en
     * phase 3.
     */
    public function estUtilisee(): bool
    {
        return false;
    }

    /**
     * Remplace entièrement les lignes d'une version existante (et son
     * libellé/ses observations), tant qu'elle n'est pas encore utilisée. Sert
     * à corriger une erreur de saisie sans créer une nouvelle version pour
     * autant — contrairement à creerNouvelleVersion(), ceci modifie la ligne
     * en place et est donc interdit dès qu'une opération en dépend.
     *
     * @param  array<int, array{densite_min: float|string, densite_max: float|string, carat: float|string}>  $lignes
     */
    public function remplacerLignes(string $libelle, array $lignes, ?string $observations = null): self
    {
        if ($this->estUtilisee()) {
            throw new \RuntimeException('Cette version du barème est déjà utilisée par des opérations : elle ne peut plus être modifiée.');
        }

        DB::transaction(function () use ($libelle, $lignes, $observations) {
            $this->update(['libelle' => $libelle, 'observations' => $observations]);

            $this->lignes()->delete();

            foreach ($lignes as $i => $ligne) {
                $this->lignes()->create([
                    'densite_min' => $ligne['densite_min'],
                    'densite_max' => $ligne['densite_max'],
                    'carat' => $ligne['carat'],
                    'ordre' => $i,
                    'bureau_id' => $this->bureau_id,
                ]);
            }
        });

        return $this->fresh('lignes');
    }

    /**
     * Crée une nouvelle version active du barème (et désactive l'ancienne,
     * sans jamais la réécrire — cahier des charges §5). Utilisé à la fois par
     * le formulaire web et par la commande d'import CSV.
     *
     * Le bureau est résolu explicitement (via $userId, ou l'utilisateur
     * connecté à défaut) plutôt que de dépendre uniquement de la global scope
     * "bureau" : la commande artisan tourne hors requête HTTP, donc sans
     * utilisateur authentifié — sans cela, la désactivation de l'ancienne
     * version toucherait celle de TOUS les bureaux au lieu du seul bureau
     * concerné.
     *
     * @param  array<int, array{densite_min: float|string, densite_max: float|string, carat: float|string}>  $lignes
     */
    public static function creerNouvelleVersion(string $libelle, array $lignes, ?int $userId = null, ?string $observations = null): self
    {
        return DB::transaction(function () use ($libelle, $lignes, $userId, $observations) {
            $bureauId = $userId ? User::find($userId)?->bureau_id : auth()->user()?->bureau_id;

            static::where('bureau_id', $bureauId)->where('actif', true)->update(['actif' => false]);

            $version = static::create([
                'libelle' => $libelle,
                'observations' => $observations,
                'actif' => true,
                'user_id' => $userId,
                'bureau_id' => $bureauId,
            ]);

            foreach ($lignes as $i => $ligne) {
                $version->lignes()->create([
                    'densite_min' => $ligne['densite_min'],
                    'densite_max' => $ligne['densite_max'],
                    'carat' => $ligne['carat'],
                    'ordre' => $i,
                    'bureau_id' => $bureauId,
                ]);
            }

            return $version;
        });
    }
}
