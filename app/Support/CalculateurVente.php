<?php

namespace App\Support;

use App\Models\BarreAchat;

/**
 * Calcule le prix/montant de VENTE d'une barre déjà en stock. Contrairement à
 * l'achat, aucune recherche dans le barème n'est nécessaire ici : le carat
 * est une propriété physique déjà déterminée et figée au moment de l'achat
 * (cf. BarreAchat::carat) — revendre la barre ne la réévalue pas, seul le
 * prix de base change (§17.6 : la formule elle-même, base÷24×carat, est la
 * même que pour l'achat, confirmée par une facture de vente réelle dans
 * CalculOrTest).
 */
class CalculateurVente
{
    /**
     * @return array{prix_unitaire: string, montant: string}
     */
    public static function calculerLigne(BarreAchat $barre, string $prixBaseVente): array
    {
        $prixUnitaire = CalculOr::prixUnitaire($prixBaseVente, (string) $barre->carat);
        $montant = CalculOr::montant((string) $barre->poids, $prixUnitaire);

        return [
            'prix_unitaire' => $prixUnitaire,
            'montant' => $montant,
        ];
    }
}
