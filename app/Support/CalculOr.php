<?php

namespace App\Support;

/**
 * Calculs métier de l'or, centralisés et testés (cahier des charges §4, §14).
 *
 * Toutes les opérations utilisent bcmath (précision arbitraire en chaînes de
 * caractères) plutôt que des float natifs, pour éviter toute erreur de
 * représentation binaire sur des calculs financiers.
 *
 * Règle verrouillée (§3.2/§4) : la densité est TRONQUÉE à 2 décimales, sans
 * arrondi. bcdiv() tronque nativement au lieu d'arrondir : c'est exactement
 * le comportement recherché, à condition de ne jamais utiliser round().
 *
 * Règle NON verrouillée (§17.3) : l'arrondi des montants monétaires
 * (prix unitaire, montant par barre) n'est pas confirmé par l'entreprise.
 * Par défaut on applique un arrondi standard (half-up) à 2 décimales — à
 * ajuster si l'entreprise confirme une autre convention (troncature, etc.).
 */
class CalculOr
{
    private const ECHELLE_DENSITE_BRUTE = 4;

    private const ECHELLE_DENSITE_TRONQUEE = 2;

    private const ECHELLE_MONETAIRE = 2;

    private const ECHELLE_INTERMEDIAIRE = 10;

    /**
     * Densité brute = Poids ÷ Eau, tronquée à 4 décimales (conservée pour la
     * traçabilité du calcul, cf. facture réelle observée chez l'entreprise).
     */
    public static function densiteBrute(string $poids, string $eau): string
    {
        if (bccomp($eau, '0', self::ECHELLE_INTERMEDIAIRE) === 0) {
            throw new \InvalidArgumentException("La valeur d'eau ne peut pas être nulle (division par zéro).");
        }

        return bcdiv($poids, $eau, self::ECHELLE_DENSITE_BRUTE);
    }

    /**
     * Densité utilisée pour la recherche dans le barème : troncature à 2
     * décimales SANS ARRONDI (règle verrouillée, cf. cahier des charges §4).
     * bcdiv tronque déjà à l'échelle demandée, donc aucun round() nulle part.
     */
    public static function tronquerDensite(string $densiteBrute): string
    {
        return bcdiv($densiteBrute, '1', self::ECHELLE_DENSITE_TRONQUEE);
    }

    /**
     * Prix unitaire = Prix de base ÷ 24 × Carat (cahier des charges §3.4).
     */
    public static function prixUnitaire(string $prixBase, string $carat): string
    {
        $parCarat = bcdiv($prixBase, '24', self::ECHELLE_INTERMEDIAIRE);
        $prixUnitaireBrut = bcmul($parCarat, $carat, self::ECHELLE_INTERMEDIAIRE);

        return self::arrondir($prixUnitaireBrut);
    }

    /**
     * Montant de la barre = Poids × Prix unitaire (cahier des charges §3.5).
     */
    public static function montant(string $poids, string $prixUnitaire): string
    {
        return self::arrondir(bcmul($poids, $prixUnitaire, self::ECHELLE_INTERMEDIAIRE));
    }

    /**
     * Arrondi standard (half-up) à 2 décimales, en bcmath pur (sans passer
     * par un float natif) : ajoute une demi-unité à l'échelle cible puis
     * tronque — équivalent à round($valeur, 2) mais sans risque de
     * représentation flottante sur de gros montants.
     */
    public static function arrondir(string $valeur, int $decimales = self::ECHELLE_MONETAIRE): string
    {
        $negatif = bccomp($valeur, '0', $decimales + 2) < 0;
        $abs = $negatif ? bcmul($valeur, '-1', $decimales + 2) : $valeur;

        $demi = bcdiv('5', bcpow('10', (string) ($decimales + 1)), $decimales + 2);
        $arrondi = bcdiv(bcadd($abs, $demi, $decimales + 2), '1', $decimales);

        return $negatif ? bcmul($arrondi, '-1', $decimales) : $arrondi;
    }
}
