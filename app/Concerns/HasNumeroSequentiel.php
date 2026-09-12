<?php

namespace App\Concerns;

/**
 * Génère un numéro séquentiel du type "PREFIXE-0001" (ou "PREFIXE-2026-0001"
 * si $parAnnee). Repris du pattern déjà utilisé (en double) dans Wari-nioumas
 * pour MandatPaiement et AttestationVente, factorisé ici pour être réutilisé
 * par tous les modèles numérotés d'ORGEST (clients, factures d'achat/vente...).
 */
trait HasNumeroSequentiel
{
    public static function genererNumeroSequentiel(string $prefixe, string $colonne = 'numero', int $largeur = 4, bool $parAnnee = false): string
    {
        $motif = $parAnnee ? $prefixe.'-'.date('Y').'-%' : $prefixe.'-%';

        $dernier = static::where($colonne, 'like', $motif)
            ->orderByRaw("CAST(SUBSTRING_INDEX({$colonne}, '-', -1) AS UNSIGNED) DESC")
            ->value($colonne);

        $numero = $dernier ? ((int) substr($dernier, strrpos($dernier, '-') + 1)) + 1 : 1;

        return $parAnnee
            ? $prefixe.'-'.date('Y').'-'.str_pad((string) $numero, $largeur, '0', STR_PAD_LEFT)
            : $prefixe.'-'.str_pad((string) $numero, $largeur, '0', STR_PAD_LEFT);
    }
}
