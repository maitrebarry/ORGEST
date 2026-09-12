<?php

namespace Tests\Feature;

use App\Models\Client;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClientManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedRolesAndPermissions();
    }

    public function test_user_without_permission_cannot_access_client_list(): void
    {
        $gerant = $this->userWithRole('gerant');
        // gerant a clients.voir par défaut : on retire pour tester le blocage
        $gerant->syncPermissions([]);

        $this->actingAs($gerant)->get('/clients')->assertForbidden();
    }

    public function test_gerant_can_create_a_client(): void
    {
        $gerant = $this->userWithRole('gerant');

        $response = $this->actingAs($gerant)->post('/clients', [
            'nom' => 'Diallo',
            'prenom' => 'Amadou',
            'telephone' => '70000000',
            'adresse' => 'Kéniéba',
        ]);

        $response->assertRedirect(route('clients.index'));
        $this->assertDatabaseHas('clients', ['nom' => 'Diallo', 'prenom' => 'Amadou']);
    }

    public function test_client_gets_a_sequential_identifiant_automatically(): void
    {
        $gerant = $this->userWithRole('gerant');

        $this->actingAs($gerant)->post('/clients', ['nom' => 'Premier']);
        $this->actingAs($gerant)->post('/clients', ['nom' => 'Second']);

        $premier = Client::where('nom', 'Premier')->first();
        $second = Client::where('nom', 'Second')->first();

        $this->assertSame('CL-0001', $premier->identifiant);
        $this->assertSame('CL-0002', $second->identifiant);
    }

    public function test_gerant_cannot_delete_a_client(): void
    {
        // clients.supprimer n'est pas dans le modèle par défaut du gérant :
        // seul le propriétaire (ou le superadmin) peut supprimer un client.
        $gerant = $this->userWithRole('gerant');
        $client = Client::factory()->create();

        $this->actingAs($gerant)->delete("/clients/{$client->id}")->assertForbidden();
    }

    public function test_proprietaire_can_update_and_delete_a_client(): void
    {
        $proprietaire = $this->userWithRole('proprietaire');
        $client = Client::factory()->create(['nom' => 'Ancien Nom']);

        $this->actingAs($proprietaire)->put("/clients/{$client->id}", ['nom' => 'Nouveau Nom'])
            ->assertRedirect(route('clients.index'));
        $this->assertSame('Nouveau Nom', $client->fresh()->nom);

        $this->actingAs($proprietaire)->delete("/clients/{$client->id}")->assertRedirect();
        $this->assertDatabaseMissing('clients', ['id' => $client->id]);
    }

    public function test_invalid_phone_is_rejected(): void
    {
        $gerant = $this->userWithRole('gerant');

        $this->actingAs($gerant)->post('/clients', [
            'nom' => 'Test',
            'telephone' => '123',
        ])->assertSessionHasErrors('telephone');
    }
}
