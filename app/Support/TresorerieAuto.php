<?php

namespace App\Support;

use App\Models\JourneeFinanciere;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/**
 * Alimentation automatique de la trésorerie à partir d'une source métier
 * (paiement d'achat = sortie, encaissement de vente = entrée), reprenant le
 * principe de App\Support\CaisseAuto de Wari-nioumas.
 *
 * Règle (identique à Wari-nioumas) : si aucune journée financière n'est
 * ouverte, aucun mouvement n'est créé — on ne bloque pas l'opération métier
 * pour autant (§17.8 du cahier des charges reste à confirmer sur ce point).
 */
class TresorerieAuto
{
    public static function synchroniser(Model $source, string $nature, string $sens, string $libelle, float $montant, ?User $utilisateur, ?string $modePaiement = null): void
    {
        // Filtrage explicite par bureau_id de la source (plutôt que de ne
        // compter que sur la global scope, qui dépend de l'utilisateur
        // authentifié ambiant) : la journée financière ouverte doit être
        // celle du MÊME bureau que l'opération d'achat/vente à l'origine du
        // mouvement, jamais celle d'un autre bureau.
        $journee = JourneeFinanciere::where('bureau_id', $source->bureau_id)->ouverte()->latest('date_ouverture')->first();

        if (! $journee) {
            return;
        }

        $journee->mouvements()->create([
            'bureau_id' => $journee->bureau_id,
            'nature' => $nature,
            'sens' => $sens,
            'libelle' => $libelle,
            'mode_paiement' => $modePaiement,
            'montant' => $montant,
            'date_mouvement' => now(),
            'source_type' => $source->getMorphClass(),
            'source_id' => $source->getKey(),
            'user_id' => $utilisateur?->id,
        ]);
    }
}
