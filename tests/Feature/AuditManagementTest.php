<?php

namespace Tests\Feature;

use App\Models\BaremeVersion;
use App\Models\Client;
use App\Models\OperationAchat;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuditManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedRolesAndPermissions();
    }

    private function creerBaremeReel(): BaremeVersion
    {
        return BaremeVersion::creerNouvelleVersion('Barème test', [
            ['densite_min' => '18.59', 'densite_max' => '18.64', 'carat' => '22.80'],
        ]);
    }

    public function test_gerant_cannot_access_audit(): void
    {
        $gerant = $this->userWithRole('gerant');

        $this->actingAs($gerant)->get('/audit')->assertForbidden();
    }

    public function test_proprietaire_sees_achats_and_ventes_in_the_activity_journal(): void
    {
        $this->creerBaremeReel();
        $proprietaire = $this->userWithRole('proprietaire');
        $client = Client::factory()->create();

        $this->actingAs($proprietaire)->post('/achats', [
            'client_id' => $client->id,
            'date_operation' => now(),
            'prix_base' => 80000,
            'barres' => [['poids' => '119.47', 'eau' => '6.42']],
        ]);
        $achat = OperationAchat::first();

        $response = $this->actingAs($proprietaire)->get('/audit');
        $response->assertOk();
        $response->assertSee($achat->numero);
        $response->assertViewHas('resume', function ($resume) use ($achat) {
            return $resume['nombreAchats'] === 1
                && $resume['montantAchats'] === (float) $achat->montant_total;
        });
    }

    public function test_audit_never_exposes_a_margin_or_gain_figure(): void
    {
        // Rappel métier : la définition exacte de la marge/du gain n'est pas
        // confirmée par l'entreprise — cette page ne doit jamais en afficher.
        $proprietaire = $this->userWithRole('proprietaire');

        $response = $this->actingAs($proprietaire)->get('/audit');
        $response->assertOk();
        $response->assertDontSee('Marge');
        $response->assertDontSee('Gain');
        $response->assertDontSeeText('Bénéfice');
    }

    public function test_date_filter_excludes_events_outside_the_range(): void
    {
        $this->creerBaremeReel();
        $proprietaire = $this->userWithRole('proprietaire');
        $client = Client::factory()->create();

        $this->actingAs($proprietaire)->post('/achats', [
            'client_id' => $client->id,
            'date_operation' => now(),
            'prix_base' => 80000,
            'barres' => [['poids' => '119.47', 'eau' => '6.42']],
        ]);

        $response = $this->actingAs($proprietaire)->get('/audit?debut='.now()->addDays(5)->format('Y-m-d').'&fin='.now()->addDays(10)->format('Y-m-d'));
        $response->assertOk();
        $response->assertViewHas('evenements', fn ($evenements) => $evenements->isEmpty());
    }
}
