<?php

namespace Tests\Unit;

use App\Rules\MalianPhone;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class MalianPhoneTest extends TestCase
{
    #[DataProvider('saisiesEquivalentes')]
    public function test_normaliser_ramene_les_saisies_usuelles_a_huit_chiffres(string $saisie): void
    {
        $this->assertSame('74745669', MalianPhone::normaliser($saisie));
    }

    public static function saisiesEquivalentes(): array
    {
        return [
            'colle' => ['74745669'],
            'espaces' => ['74 74 56 69'],
            'espace insécable' => ["74\u{00A0}74\u{00A0}56\u{00A0}69"],
            'tirets' => ['74-74-56-69'],
            'points' => ['74.74.56.69'],
            'espaces autour' => ['  74745669  '],
            'indicatif +223' => ['+223 74 74 56 69'],
            'indicatif 00223' => ['00223 74745669'],
            'indicatif 223' => ['22374745669'],
        ];
    }

    public function test_normaliser_laisse_intact_ce_qui_n_est_pas_un_numero_malien(): void
    {
        $this->assertNull(MalianPhone::normaliser(null));
        $this->assertSame('abc', MalianPhone::normaliser('abc'));
        // 9 chiffres (ex. Sénégal) : rien n'est deviné, la règle le refusera.
        $this->assertSame('771234567', MalianPhone::normaliser('77 123 45 67'));
        $this->assertSame('7474566', MalianPhone::normaliser('74 74 56 6'));
    }
}
