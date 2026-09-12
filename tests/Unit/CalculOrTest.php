<?php

namespace Tests\Unit;

use App\Support\CalculOr;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Vérifie CalculOr contre des lignes RÉELLES extraites de factures fournies
 * par l'entreprise (Bureau d'Achat et Vente d'Or — Diallo et Frères,
 * Kénieba) : facture ACHAT n°00-2024-7176 et VENTE n°00-2024-7191.
 */
class CalculOrTest extends TestCase
{
    #[DataProvider('lignesFactureReelle')]
    public function test_densite_tronquee_reproduit_le_carat_de_la_facture_reelle(string $poids, string $eau, string $densiteTronqueeAttendue): void
    {
        $brute = CalculOr::densiteBrute($poids, $eau);
        $tronquee = CalculOr::tronquerDensite($brute);

        $this->assertSame($densiteTronqueeAttendue, $tronquee);
    }

    public static function lignesFactureReelle(): array
    {
        return [
            'achat ligne 1 (119.47/6.42 -> carat 22.80)' => ['119.47', '6.42', '18.60'],
            'achat ligne 2 (90.15/4.86 -> carat 22.70)' => ['90.15', '4.86', '18.54'],
            'achat ligne 3 (54.88/2.94 -> carat 22.90)' => ['54.88', '2.94', '18.66'],
            'achat ligne 4 (49.91/2.68 -> carat 22.80)' => ['49.91', '2.68', '18.62'],
            'vente ligne 1 (84.26/4.40 -> carat 23.60)' => ['84.26', '4.40', '19.15'],
            'vente ligne 2 (62.66/3.43 -> carat 22.20)' => ['62.66', '3.43', '18.26'],
        ];
    }

    public function test_densite_brute_est_tronquee_jamais_arrondie(): void
    {
        // 90.15 / 4.86 = 18.549382... : un arrondi classique donnerait 18.5494
        // (comme l'affiche l'ancien logiciel), mais la règle du cahier des
        // charges interdit tout arrondi -> on tronque à 18.5493.
        $this->assertSame('18.5493', CalculOr::densiteBrute('90.15', '4.86'));
    }

    public function test_exemple_du_cahier_des_charges_densite_18_71_carat_22_90(): void
    {
        // Poids 27,89 / Eau 1,49 -> densité brute observée en vidéo : 18,7181
        // (et non 18,7194 comme l'exemple illustratif du cahier des charges,
        // qui n'était pas rattaché à un calcul réel).
        $brute = CalculOr::densiteBrute('27.89', '1.49');
        $this->assertSame('18.7181', $brute);
        $this->assertSame('18.71', CalculOr::tronquerDensite($brute));
    }

    public function test_prix_unitaire_formule_base_divise_24_fois_carat(): void
    {
        // Ex. manuscrit fourni : Base 78 000 / 24 * Carat 22,90 * Poids 27,89
        // -> Montant 2 075 713 F (arrondi à l'unité par l'entreprise sur son
        // brouillon papier ; ici on conserve 2 décimales : 2 075 713,25).
        $prixUnitaire = CalculOr::prixUnitaire('78000', '22.90');
        $this->assertSame('74425.00', $prixUnitaire);

        $montant = CalculOr::montant('27.89', $prixUnitaire);
        $this->assertSame('2075713.25', $montant);
    }

    public function test_prix_unitaire_reproduit_la_facture_vente_reelle(): void
    {
        // Base 82 000, carat 23,60 -> U/BASE observé sur la facture : 80 633,33
        $this->assertSame('80633.33', CalculOr::prixUnitaire('82000', '23.60'));

        // Base 80 000, carat 22,80 -> U/BASE observé sur la facture achat : 76 000,00
        $this->assertSame('76000.00', CalculOr::prixUnitaire('80000', '22.80'));

        // Base 80 000, carat 22,90 -> U/BASE observé : 76 333,33
        $this->assertSame('76333.33', CalculOr::prixUnitaire('80000', '22.90'));
    }

    public function test_arrondir_half_up_a_deux_decimales(): void
    {
        $this->assertSame('1.24', CalculOr::arrondir('1.235'));
        $this->assertSame('1.23', CalculOr::arrondir('1.234'));
        $this->assertSame('0.01', CalculOr::arrondir('0.005'));
        $this->assertSame('-1.24', CalculOr::arrondir('-1.235'));
    }

    public function test_densite_brute_rejette_une_division_par_zero(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        CalculOr::densiteBrute('27.89', '0');
    }
}
