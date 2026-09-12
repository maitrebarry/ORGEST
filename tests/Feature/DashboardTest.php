<?php

namespace Tests\Feature;

use App\Models\BaremeVersion;
use App\Models\Client;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedRolesAndPermissions();
    }

    public function test_proprietaire_sees_every_stat_card(): void
    {
        $proprietaire = $this->userWithRole('proprietaire');

        $response = $this->actingAs($proprietaire)->get('/');

        $response->assertOk();
        $response->assertSee('Clients actifs');
        $response->assertSee('Barres en stock');
        $response->assertSee("Achats aujourd'hui", false);
        $response->assertSee("Ventes aujourd'hui", false);
        $response->assertSee('Solde disponible');
        $response->assertSee('Utilisateurs actifs');
    }

    public function test_gerant_does_not_see_the_users_card(): void
    {
        // Le gérant n'a pas utilisateurs.voir par défaut.
        $gerant = $this->userWithRole('gerant');

        $response = $this->actingAs($gerant)->get('/');

        $response->assertOk();
        $response->assertSee('Clients actifs');
        $response->assertDontSee('Utilisateurs actifs');
    }

    public function test_todays_achat_count_and_amount_appear_on_the_dashboard(): void
    {
        BaremeVersion::creerNouvelleVersion('Barème', [
            ['densite_min' => '18.59', 'densite_max' => '18.64', 'carat' => '22.80'],
        ]);
        $gerant = $this->userWithRole('gerant');
        $client = Client::factory()->create();

        $this->actingAs($gerant)->post('/achats', [
            'client_id' => $client->id,
            'date_operation' => now(),
            'prix_base' => 80000,
            'barres' => [['poids' => '119.47', 'eau' => '6.42']],
        ]);

        $response = $this->actingAs($gerant)->get('/');
        $response->assertOk();
        // 1 achat aujourd'hui, montant 9 079 720.
        $response->assertSeeInOrder(["Achats aujourd'hui", '1'], false);
        $response->assertSee('9 079 720');
    }

    public function test_apercu_rapide_lists_recent_achats(): void
    {
        BaremeVersion::creerNouvelleVersion('Barème', [
            ['densite_min' => '18.59', 'densite_max' => '18.64', 'carat' => '22.80'],
        ]);
        $gerant = $this->userWithRole('gerant');
        $client = Client::factory()->create(['nom' => 'Traoré', 'prenom' => 'Fatoumata']);

        $this->actingAs($gerant)->post('/achats', [
            'client_id' => $client->id,
            'date_operation' => now(),
            'prix_base' => 80000,
            'barres' => [['poids' => '119.47', 'eau' => '6.42']],
        ]);

        $response = $this->actingAs($gerant)->get('/');
        $response->assertOk();
        $response->assertSee('DERNIERS ACHATS');
        $response->assertSee('Fatoumata Traoré');
    }

    public function test_quick_access_links_respect_permissions(): void
    {
        // clients.voir seul ne donne droit à aucune action rapide (elles
        // exigent toutes un .creer/.gerer) : le bloc entier doit disparaître.
        $gerantRestreint = $this->userWithRole('gerant');
        $gerantRestreint->syncPermissions(['clients.voir']);

        $response = $this->actingAs($gerantRestreint)->get('/');
        $response->assertOk();
        $response->assertDontSee('ACCÈS RAPIDES');
    }
}
