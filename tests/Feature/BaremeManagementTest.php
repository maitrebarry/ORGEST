<?php

namespace Tests\Feature;

use App\Models\BaremeVersion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BaremeManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedRolesAndPermissions();
    }

    public function test_gerant_can_view_but_not_manage_bareme(): void
    {
        $gerant = $this->userWithRole('gerant');

        $this->actingAs($gerant)->get('/bareme')->assertOk();
        $this->actingAs($gerant)->get('/bareme-nouvelle-version')->assertForbidden();
        $this->actingAs($gerant)->post('/bareme', ['libelle' => 'Test'])->assertForbidden();
    }

    public function test_proprietaire_can_create_a_new_active_version(): void
    {
        $proprietaire = $this->userWithRole('proprietaire');

        $response = $this->actingAs($proprietaire)->post('/bareme', [
            'libelle' => 'Barème initial',
            'lignes' => [
                ['densite_min' => '18.65', 'densite_max' => '18.71', 'carat' => '22.90'],
                ['densite_min' => '19.10', 'densite_max' => '19.16', 'carat' => '23.60'],
            ],
        ]);

        $response->assertRedirect(route('baremes.index'));

        $version = BaremeVersion::first();
        $this->assertTrue($version->actif);
        $this->assertCount(2, $version->lignes);
    }

    public function test_creating_a_new_version_deactivates_the_previous_one(): void
    {
        $proprietaire = $this->userWithRole('proprietaire');

        $this->actingAs($proprietaire)->post('/bareme', [
            'libelle' => 'V1',
            'lignes' => [['densite_min' => '18.00', 'densite_max' => '18.50', 'carat' => '21.00']],
        ]);
        $ancienne = BaremeVersion::where('libelle', 'V1')->first();

        $this->actingAs($proprietaire)->post('/bareme', [
            'libelle' => 'V2',
            'lignes' => [['densite_min' => '18.00', 'densite_max' => '18.50', 'carat' => '22.00']],
        ]);

        $this->assertFalse($ancienne->fresh()->actif);
        $this->assertTrue(BaremeVersion::where('libelle', 'V2')->first()->actif);
    }

    public function test_entirely_empty_lignes_are_silently_ignored(): void
    {
        $proprietaire = $this->userWithRole('proprietaire');

        $response = $this->actingAs($proprietaire)->post('/bareme', [
            'libelle' => 'Avec lignes vides',
            'lignes' => [
                ['densite_min' => '18.00', 'densite_max' => '18.50', 'carat' => '21.00'],
                ['densite_min' => '', 'densite_max' => '', 'carat' => ''],
                ['densite_min' => '18.51', 'densite_max' => '19.00', 'carat' => '21.50'],
                ['densite_min' => '', 'densite_max' => '', 'carat' => ''],
            ],
        ]);

        $response->assertRedirect(route('baremes.index'));
        $version = BaremeVersion::where('libelle', 'Avec lignes vides')->first();
        $this->assertCount(2, $version->lignes);
    }

    public function test_overlapping_ranges_are_rejected(): void
    {
        $proprietaire = $this->userWithRole('proprietaire');

        $response = $this->actingAs($proprietaire)->post('/bareme', [
            'libelle' => 'Chevauchement',
            'lignes' => [
                ['densite_min' => '18.00', 'densite_max' => '18.50', 'carat' => '21.00'],
                ['densite_min' => '18.40', 'densite_max' => '19.00', 'carat' => '22.00'],
            ],
        ]);

        $response->assertSessionHasErrors('lignes');
        $this->assertDatabaseMissing('bareme_versions', ['libelle' => 'Chevauchement']);
    }

    public function test_proprietaire_can_edit_lignes_of_an_unused_version(): void
    {
        $proprietaire = $this->userWithRole('proprietaire');

        $this->actingAs($proprietaire)->post('/bareme', [
            'libelle' => 'Brouillon',
            'lignes' => [['densite_min' => '18.00', 'densite_max' => '18.50', 'carat' => '21.00']],
        ]);
        $version = BaremeVersion::where('libelle', 'Brouillon')->first();

        $response = $this->actingAs($proprietaire)->put("/bareme/{$version->id}", [
            'libelle' => 'Brouillon corrigé',
            'lignes' => [
                ['densite_min' => '18.00', 'densite_max' => '18.44', 'carat' => '21.00'],
                ['densite_min' => '18.45', 'densite_max' => '18.50', 'carat' => '21.10'],
            ],
        ]);

        $response->assertRedirect(route('baremes.show', $version));
        $version->refresh();
        $this->assertSame('Brouillon corrigé', $version->libelle);
        $this->assertCount(2, $version->lignes);
        // toujours la même version (même id), pas une nouvelle créée
        $this->assertSame(1, BaremeVersion::count());
    }

    public function test_proprietaire_can_correct_a_single_ligne_without_touching_others(): void
    {
        $proprietaire = $this->userWithRole('proprietaire');

        $this->actingAs($proprietaire)->post('/bareme', [
            'libelle' => 'V',
            'lignes' => [
                ['densite_min' => '18.00', 'densite_max' => '18.44', 'carat' => '21.00'],
                ['densite_min' => '18.45', 'densite_max' => '18.50', 'carat' => '21.10'],
            ],
        ]);
        $version = BaremeVersion::first();
        $ligneACorriger = $version->lignes->first();
        $autreLigne = $version->lignes->last();

        $response = $this->actingAs($proprietaire)->put("/bareme-ligne/{$ligneACorriger->id}", [
            'densite_min' => '18.00',
            'densite_max' => '18.43', // correction : ne touche plus 18.44
            'carat' => '20.90',
        ]);

        $response->assertRedirect();
        $ligneACorriger->refresh();
        $this->assertSame('18.43', $ligneACorriger->densite_max);
        $this->assertSame('20.90', $ligneACorriger->carat);
        // l'autre ligne n'a pas bougé
        $this->assertSame('18.45', $autreLigne->fresh()->densite_min);
        $this->assertSame('21.10', $autreLigne->fresh()->carat);
    }

    public function test_correcting_a_ligne_still_rejects_overlaps_with_siblings(): void
    {
        $proprietaire = $this->userWithRole('proprietaire');

        $this->actingAs($proprietaire)->post('/bareme', [
            'libelle' => 'V',
            'lignes' => [
                ['densite_min' => '18.00', 'densite_max' => '18.44', 'carat' => '21.00'],
                ['densite_min' => '18.45', 'densite_max' => '18.50', 'carat' => '21.10'],
            ],
        ]);
        $version = BaremeVersion::first();
        $ligne = $version->lignes->first();

        $response = $this->actingAs($proprietaire)->put("/bareme-ligne/{$ligne->id}", [
            'densite_min' => '18.00',
            'densite_max' => '18.46', // chevauche la seconde ligne (18.45-18.50)
            'carat' => '21.00',
        ]);

        $response->assertSessionHasErrors('densite_min');
        $this->assertSame('18.44', $ligne->fresh()->densite_max);
    }

    public function test_gerant_cannot_edit_bareme_lignes(): void
    {
        $proprietaire = $this->userWithRole('proprietaire');
        $this->actingAs($proprietaire)->post('/bareme', [
            'libelle' => 'V',
            'lignes' => [['densite_min' => '18.00', 'densite_max' => '18.50', 'carat' => '21.00']],
        ]);
        $version = BaremeVersion::first();

        $gerant = $this->userWithRole('gerant');
        $this->actingAs($gerant)->get("/bareme/{$version->id}/modifier")->assertForbidden();
        $this->actingAs($gerant)->put("/bareme/{$version->id}", ['libelle' => 'Hack'])->assertForbidden();
    }

    public function test_trouver_ligne_returns_correct_carat_for_density(): void
    {
        $version = BaremeVersion::create(['libelle' => 'T', 'actif' => true]);
        $version->lignes()->create(['densite_min' => 18.65, 'densite_max' => 18.71, 'carat' => 22.90, 'ordre' => 0]);
        $version->lignes()->create(['densite_min' => 19.10, 'densite_max' => 19.16, 'carat' => 23.60, 'ordre' => 1]);

        $this->assertSame('22.90', $version->trouverLigne(18.68)->carat);
        $this->assertSame('23.60', $version->trouverLigne(19.15)->carat);
        $this->assertNull($version->trouverLigne(18.90));
    }
}
