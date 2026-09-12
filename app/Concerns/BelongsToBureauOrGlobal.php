<?php

namespace App\Concerns;

use App\Models\Bureau;

/**
 * Variante de BelongsToBureau spécifique au barème : un enregistrement SANS
 * bureau (bureau_id NULL) est un socle COMMUN visible par tous les bureaux
 * (utilisable pour les calculs tant qu'un bureau n'a pas créé sa propre
 * version). Un bureau qui crée une nouvelle version ou corrige une ligne ne
 * modifie JAMAIS ce socle commun ni celui d'un autre bureau : cela ne
 * concerne que SON propre bureau (bureau_id renseigné explicitement — cf.
 * BaremeVersion::creerNouvelleVersion()).
 *
 * Le socle commun (bureau_id NULL) reste néanmoins en LECTURE SEULE pour
 * tout le monde sauf le superadmin : voir les gardes explicites dans
 * BaremeController (edit/update/updateLigne) qui comparent bureau_id à
 * l'utilisateur courant — la visibilité partagée ici n'implique pas le
 * droit de modifier le socle commun.
 */
trait BelongsToBureauOrGlobal
{
    public static function bootBelongsToBureauOrGlobal(): void
    {
        static::addGlobalScope('bureau', function ($query) {
            $utilisateur = auth()->user();

            if ($utilisateur && $utilisateur->bureau_id) {
                $table = $query->getModel()->getTable();
                $query->where(function ($q) use ($table, $utilisateur) {
                    $q->where("{$table}.bureau_id", $utilisateur->bureau_id)
                        ->orWhereNull("{$table}.bureau_id");
                });
            }
        });

        static::creating(function ($model) {
            if (empty($model->bureau_id) && ($utilisateur = auth()->user()) && $utilisateur->bureau_id) {
                $model->bureau_id = $utilisateur->bureau_id;
            }
        });
    }

    public function bureau()
    {
        return $this->belongsTo(Bureau::class);
    }
}
