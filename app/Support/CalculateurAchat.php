<?php

namespace App\Support;

use App\Models\BaremeVersion;

/**
 * Relie le calcul pur (CalculOr) à la recherche dans le barème pour produire
 * toutes les valeurs d'une barre d'achat. Ne valide jamais silencieusement :
 * si la densité ne correspond à aucune ligne du barème, 'erreur' est
 * renseignée et carat/prix_unitaire/montant restent null (cahier des
 * charges §3.3/§18 : "aucune validation silencieuse").
 */
class CalculateurAchat
{
    /**
     * @return array{densite_brute: string, densite_tronquee: string, bareme_ligne_id: ?int, carat: ?string, prix_unitaire: ?string, montant: ?string, erreur: ?string}
     */
    public static function calculerBarre(string $poids, string $eau, string $prixBase, BaremeVersion $bareme): array
    {
        $densiteBrute = CalculOr::densiteBrute($poids, $eau);
        $densiteTronquee = CalculOr::tronquerDensite($densiteBrute);

        $ligne = $bareme->trouverLigne((float) $densiteTronquee);

        if (! $ligne) {
            return [
                'densite_brute' => $densiteBrute,
                'densite_tronquee' => $densiteTronquee,
                'bareme_ligne_id' => null,
                'carat' => null,
                'prix_unitaire' => null,
                'montant' => null,
                'erreur' => "Densité {$densiteTronquee} absente du barème actif : aucun carat correspondant.",
            ];
        }

        $prixUnitaire = CalculOr::prixUnitaire($prixBase, (string) $ligne->carat);
        $montant = CalculOr::montant($poids, $prixUnitaire);

        return [
            'densite_brute' => $densiteBrute,
            'densite_tronquee' => $densiteTronquee,
            'bareme_ligne_id' => $ligne->id,
            'carat' => (string) $ligne->carat,
            'prix_unitaire' => $prixUnitaire,
            'montant' => $montant,
            'erreur' => null,
        ];
    }
}
