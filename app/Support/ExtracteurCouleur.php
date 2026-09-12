<?php

namespace App\Support;

/**
 * Extrait une couleur dominante d'une image (le logo d'un bureau), pour que
 * les factures PDF puissent harmoniser leurs écritures/bordures avec le
 * logo au lieu d'utiliser le bleu par défaut de l'application.
 *
 * Moyenne les pixels d'une grille échantillonnée sur l'image (pas un vrai
 * algorithme de clustering — largement suffisant pour un logo d'entreprise,
 * généralement dominé par 1-2 couleurs franches), en ignorant le
 * quasi-blanc/quasi-noir/transparent (fond et contours neutres) pour ne pas
 * tirer la moyenne vers le gris.
 */
class ExtracteurCouleur
{
    public static function depuisImage(string $cheminAbsolu): ?string
    {
        if (! extension_loaded('gd') || ! is_readable($cheminAbsolu)) {
            return null;
        }

        $info = @getimagesize($cheminAbsolu);

        if (! $info) {
            return null;
        }

        $image = match ($info[2]) {
            IMAGETYPE_JPEG => @imagecreatefromjpeg($cheminAbsolu),
            IMAGETYPE_PNG => @imagecreatefrompng($cheminAbsolu),
            IMAGETYPE_GIF => @imagecreatefromgif($cheminAbsolu),
            IMAGETYPE_WEBP => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($cheminAbsolu) : null,
            default => null,
        };

        if (! $image) {
            return null;
        }

        $largeur = imagesx($image);
        $hauteur = imagesy($image);
        $pasX = max(1, intdiv($largeur, 40));
        $pasY = max(1, intdiv($hauteur, 40));

        $rTotal = $vTotal = $bTotal = $nombre = 0;

        for ($x = 0; $x < $largeur; $x += $pasX) {
            for ($y = 0; $y < $hauteur; $y += $pasY) {
                $index = imagecolorat($image, $x, $y);
                $couleur = imagecolorsforindex($image, $index);

                // GD : alpha 0 = opaque, 127 = totalement transparent.
                if (($couleur['alpha'] ?? 0) > 100) {
                    continue;
                }

                $luminance = 0.299 * $couleur['red'] + 0.587 * $couleur['green'] + 0.114 * $couleur['blue'];

                if ($luminance > 235 || $luminance < 20) {
                    continue;
                }

                $rTotal += $couleur['red'];
                $vTotal += $couleur['green'];
                $bTotal += $couleur['blue'];
                $nombre++;
            }
        }

        imagedestroy($image);

        if ($nombre === 0) {
            return null;
        }

        return self::versHex((int) round($rTotal / $nombre), (int) round($vTotal / $nombre), (int) round($bTotal / $nombre));
    }

    /**
     * Assombrit une couleur hex d'un facteur (0.75 par défaut), pour les
     * titres/bordures qui doivent rester lisibles sur fond clair même si le
     * logo est vif.
     */
    public static function assombrir(string $hex, float $facteur = 0.75): string
    {
        [$r, $g, $b] = self::rgbDepuisHex($hex);

        return self::versHex(
            (int) round($r * $facteur),
            (int) round($g * $facteur),
            (int) round($b * $facteur)
        );
    }

    private static function rgbDepuisHex(string $hex): array
    {
        $hex = ltrim($hex, '#');

        return [hexdec(substr($hex, 0, 2)), hexdec(substr($hex, 2, 2)), hexdec(substr($hex, 4, 2))];
    }

    private static function versHex(int $r, int $g, int $b): string
    {
        return sprintf('#%02x%02x%02x', max(0, min(255, $r)), max(0, min(255, $g)), max(0, min(255, $b)));
    }
}
