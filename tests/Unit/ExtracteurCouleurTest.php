<?php

namespace Tests\Unit;

use App\Support\ExtracteurCouleur;
use PHPUnit\Framework\TestCase;

class ExtracteurCouleurTest extends TestCase
{
    private function creerImageUnie(int $r, int $g, int $b): string
    {
        $chemin = tempnam(sys_get_temp_dir(), 'logo').'.png';
        $image = imagecreatetruecolor(20, 20);
        imagefill($image, 0, 0, imagecolorallocate($image, $r, $g, $b));
        imagepng($image, $chemin);
        imagedestroy($image);

        return $chemin;
    }

    public function test_extrait_la_couleur_exacte_dune_image_unie(): void
    {
        $chemin = $this->creerImageUnie(200, 30, 30);

        $couleur = ExtracteurCouleur::depuisImage($chemin);
        unlink($chemin);

        $this->assertSame('#c81e1e', $couleur);
    }

    public function test_ignore_le_quasi_blanc_et_retourne_null_si_rien_dautre(): void
    {
        $chemin = $this->creerImageUnie(255, 255, 255);

        $couleur = ExtracteurCouleur::depuisImage($chemin);
        unlink($chemin);

        $this->assertNull($couleur);
    }

    public function test_retourne_null_pour_un_fichier_introuvable(): void
    {
        $this->assertNull(ExtracteurCouleur::depuisImage('/chemin/totalement/inexistant.png'));
    }

    public function test_assombrir_reduit_chaque_canal_du_facteur_donne(): void
    {
        $this->assertSame('#163b67', ExtracteurCouleur::assombrir('#1d4e89'));
    }
}
