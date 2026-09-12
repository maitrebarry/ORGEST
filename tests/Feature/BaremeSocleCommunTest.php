<?php

namespace Tests\Feature;

use App\Models\Bureau;
use App\Models\BaremeVersion;
use App\Models\Client;
use App\Models\OperationAchat;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Le barème déjà présent dans le système (sans bureau, bureau_id NULL) est un
 * socle COMMUN utilisable par tous les bureaux — mais dès qu'un bureau le
 * modifie (nouvelle version ou correction), cela ne concerne QUE ce bureau,
 * jamais le socle commun ni les autres bureaux.
 */
class BaremeSocleCommunTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedRolesAndPermissions();
    }

    private function bureauAvecGerantEtProprietaire(string $nom): array
    {
        $bureau = Bureau::create(['nom' => $nom, 'actif' => true]);

        $proprietaire = User::factory()->create(['bureau_id' => $bureau->id]);
        $proprietaire->assignRole('proprietaire');
        $proprietaire->givePermissionTo(config('role_permissions.proprietaire', []));
        $bureau->update(['proprietaire_id' => $proprietaire->id]);

        $gerant = User::factory()->create(['bureau_id' => $bureau->id]);
        $gerant->assignRole('gerant');
        $gerant->givePermissionTo(config('role_permissions.gerant', []));

        return [$bureau, $proprietaire, $gerant];
    }

    /**
     * Le socle commun : créé sans utilisateur connecté (bureau_id NULL),
     * comme le ferait `bareme:importer-csv` en pratique.
     */
    private function creerSocleCommun(): BaremeVersion
    {
        $version = BaremeVersion::create(['libelle' => 'Barème commun', 'actif' => true]);
        $version->lignes()->create(['densite_min' => '18.59', 'densite_max' => '18.64', 'carat' => '22.80', 'ordre' => 0]);

        return $version;
    }

    public function test_a_bureau_without_its_own_bareme_sees_and_uses_the_shared_one(): void
    {
        $socleCommun = $this->creerSocleCommun();
        [, , $gerant] = $this->bureauAvecGerantEtProprietaire('Bureau A');
        $client = Client::factory()->create();

        $response = $this->actingAs($gerant)->get('/bareme');
        $response->assertOk();
        $response->assertSee('Barème commun');

        // Utilisable directement pour un achat (densité 18.60 dans 18.59-18.64).
        $this->actingAs($gerant)->post('/achats', [
            'client_id' => $client->id,
            'date_operation' => now(),
            'prix_base' => 80000,
            'barres' => [['poids' => '119.47', 'eau' => '6.42']],
        ]);

        $achat = OperationAchat::first();
        $this->assertNotNull($achat);
        $this->assertSame('22.80', $achat->barres->first()->carat);
        $this->assertSame($socleCommun->id, $achat->bareme_version_id);
    }

    public function test_editing_the_full_shared_bareme_forks_a_bureau_specific_copy(): void
    {
        $socleCommun = $this->creerSocleCommun();
        [$bureau, $proprietaire] = $this->bureauAvecGerantEtProprietaire('Bureau A');

        // "Modifier" reste accessible même si ce n'est pas ma version.
        $this->actingAs($proprietaire)->get("/bareme/{$socleCommun->id}/modifier")->assertOk();

        $response = $this->actingAs($proprietaire)->put("/bareme/{$socleCommun->id}", [
            'libelle' => 'Ma correction',
            'lignes' => [['densite_min' => '18.59', 'densite_max' => '18.64', 'carat' => '23.50']],
        ]);

        $copie = BaremeVersion::where('libelle', 'Ma correction')->first();
        $this->assertNotNull($copie, "La modification aurait dû créer une copie propre au bureau.");
        $response->assertRedirect(route('baremes.show', $copie));
        $this->assertSame($bureau->id, $copie->bureau_id);
        $this->assertSame('23.50', $copie->lignes->first()->carat);

        // Le socle commun n'a jamais été touché.
        $this->assertSame('Barème commun', $socleCommun->fresh()->libelle);
        $this->assertSame('22.80', $socleCommun->fresh()->lignes->first()->carat);
    }

    public function test_correcting_one_line_of_the_shared_bareme_forks_a_bureau_specific_copy(): void
    {
        $socleCommun = $this->creerSocleCommun();
        $ligne = $socleCommun->lignes->first();
        [$bureau, $proprietaire] = $this->bureauAvecGerantEtProprietaire('Bureau A');

        $response = $this->actingAs($proprietaire)->put("/bareme-ligne/{$ligne->id}", [
            'densite_min' => '18.59', 'densite_max' => '18.64', 'carat' => '23.50',
        ]);

        $copie = BaremeVersion::where('bureau_id', $bureau->id)->first();
        $this->assertNotNull($copie, "La correction aurait dû créer une copie propre au bureau.");
        $response->assertRedirect(route('baremes.show', $copie));
        $this->assertSame('23.50', $copie->lignes->first()->carat);

        // Le socle commun n'a jamais été touché.
        $this->assertSame('22.80', $ligne->fresh()->carat);
    }

    public function test_superadmin_can_edit_the_shared_bareme(): void
    {
        $socleCommun = $this->creerSocleCommun();
        $superadmin = User::factory()->create();
        $superadmin->assignRole('superadmin');

        $response = $this->actingAs($superadmin)->put("/bareme/{$socleCommun->id}", [
            'libelle' => 'Barème commun corrigé',
            'lignes' => [['densite_min' => '18.59', 'densite_max' => '18.64', 'carat' => '22.85']],
        ]);

        $response->assertRedirect(route('baremes.show', $socleCommun));
        $this->assertSame('Barème commun corrigé', $socleCommun->fresh()->libelle);
    }

    public function test_creating_a_bureau_specific_version_never_touches_the_shared_bareme_or_other_bureaus(): void
    {
        $socleCommun = $this->creerSocleCommun();
        [, $proprietaireA] = $this->bureauAvecGerantEtProprietaire('Bureau A');
        [, , $gerantB] = $this->bureauAvecGerantEtProprietaire('Bureau B');

        $this->actingAs($proprietaireA)->post('/bareme', [
            'libelle' => 'Barème corrigé de A',
            'lignes' => [['densite_min' => '18.59', 'densite_max' => '18.64', 'carat' => '23.00']],
        ]);

        // Le socle commun reste actif et intact.
        $this->assertTrue($socleCommun->fresh()->actif);
        $this->assertSame('Barème commun', $socleCommun->fresh()->libelle);

        // B continue de voir/utiliser le socle commun, jamais la version de A.
        $response = $this->actingAs($gerantB)->get('/bareme');
        $response->assertOk();
        $response->assertSee('Barème commun');
        $response->assertDontSee('Barème corrigé de A');
    }

    public function test_bureau_specific_active_version_takes_priority_over_shared_bareme(): void
    {
        $this->creerSocleCommun(); // carat 22.80 pour la même densité
        [, $proprietaireA, $gerantA] = $this->bureauAvecGerantEtProprietaire('Bureau A');
        $client = Client::factory()->create();

        $this->actingAs($proprietaireA)->post('/bareme', [
            'libelle' => 'Barème de A',
            'lignes' => [['densite_min' => '18.59', 'densite_max' => '18.64', 'carat' => '23.00']],
        ]);

        $this->actingAs($gerantA)->post('/achats', [
            'client_id' => $client->id,
            'date_operation' => now(),
            'prix_base' => 80000,
            'barres' => [['poids' => '119.47', 'eau' => '6.42']],
        ]);

        $achat = OperationAchat::first();
        // 23.00 (version de A), jamais 22.80 (socle commun) : la version du
        // bureau prime dès qu'elle existe.
        $this->assertSame('23.00', $achat->barres->first()->carat);
    }
}
