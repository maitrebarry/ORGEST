<?php

namespace App\Concerns;

use App\Models\Bureau;

/**
 * Cloisonne les données par bureau (application multi-bureaux) : un
 * utilisateur rattaché à un bureau (propriétaire ou gérant) ne voit et ne crée
 * que les enregistrements de SON bureau ; le superadmin (sans bureau_id) n'est
 * jamais filtré et voit tout.
 *
 * Le filtrage passe par une global scope, donc il s'applique aussi au binding
 * implicite de route ({achat}, {bareme}, {journee}, {mouvement}...) : un
 * identifiant appartenant à un autre bureau renvoie une 404, jamais une fuite
 * de données vers un utilisateur qui devine un ID.
 *
 * Les créations lancées hors requête HTTP authentifiée (commande artisan) ou
 * à partir d'un enregistrement parent déjà connu (ex. les lignes d'un
 * barème) doivent renseigner bureau_id explicitement — cette trait ne fait
 * qu'un remplissage de secours à partir de l'utilisateur connecté.
 */
trait BelongsToBureau
{
    public static function bootBelongsToBureau(): void
    {
        static::addGlobalScope('bureau', function ($query) {
            $utilisateur = auth()->user();

            if ($utilisateur && $utilisateur->bureau_id) {
                $query->where($query->getModel()->getTable().'.bureau_id', $utilisateur->bureau_id);
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
