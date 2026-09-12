<?php

namespace Tests\Feature;

use App\Models\BaremeVersion;
use App\Models\Client;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StockManagementTest extends TestCase
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
            ['densite_min' => '18.65', 'densite_max' => '18.71', 'carat' => '22.90'],
        ]);
    }

    public function test_gerant_sees_available_bars_and_only_those(): void
    {
        $this->creerBaremeReel();
        $gerant = $this->userWithRole('gerant');
        $client = Client::factory()->create();

        $this->actingAs($gerant)->post('/achats', [
            'client_id' => $client->id,
            'date_operation' => now(),
            'prix_base' => 80000,
            'barres' => [
                ['poids' => '119.47', 'eau' => '6.42'],
                ['poids' => '54.88', 'eau' => '2.94'],
            ],
        ]);

        $response = $this->actingAs($gerant)->get('/stock');
        $response->assertOk();
        $response->assertViewHas('barres', fn ($barres) => $barres->count() === 2);
    }

    public function test_sold_bars_disappear_from_stock(): void
    {
        $this->creerBaremeReel();
        $gerant = $this->userWithRole('gerant');
        $vendeur = Client::factory()->create();
        $acheteur = Client::factory()->create();

        $this->actingAs($gerant)->post('/achats', [
            'client_id' => $vendeur->id,
            'date_operation' => now(),
            'prix_base' => 80000,
            'barres' => [['poids' => '119.47', 'eau' => '6.42']],
        ]);

        $barre = \App\Models\BarreAchat::disponible()->first();

        $this->actingAs($gerant)->post('/ventes', [
            'client_id' => $acheteur->id,
            'date_operation' => now(),
            'prix_base' => 82000,
            'barres_ids' => [$barre->id],
        ]);

        $response = $this->actingAs($gerant)->get('/stock');
        $response->assertOk();
        $response->assertViewHas('barres', fn ($barres) => $barres->isEmpty());
    }

    public function test_user_without_permission_cannot_access_stock(): void
    {
        $gerant = $this->userWithRole('gerant');
        $gerant->syncPermissions([]);

        $this->actingAs($gerant)->get('/stock')->assertForbidden();
    }
}
