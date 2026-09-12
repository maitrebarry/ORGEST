<?php

namespace Tests\Feature;

use App\Models\Bureau;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedRolesAndPermissions();
    }

    private function superadmin(array $attributes = []): User
    {
        $user = User::factory()->create($attributes);
        $user->assignRole('superadmin');

        return $user;
    }

    /**
     * Propriétaire rattaché à un vrai bureau (nécessaire pour les tests de
     * cloisonnement par bureau, contrairement à userWithRole() qui ne crée
     * aucun bureau).
     */
    private function proprietaireDeBureau(string $nomBureau = 'Bureau A'): User
    {
        $bureau = Bureau::create(['nom' => $nomBureau, 'actif' => true]);
        $proprietaire = User::factory()->create(['bureau_id' => $bureau->id]);
        $proprietaire->assignRole('proprietaire');
        $proprietaire->givePermissionTo(config('role_permissions.proprietaire', []));
        $bureau->update(['proprietaire_id' => $proprietaire->id]);

        return $proprietaire;
    }

    public function test_user_without_permission_cannot_access_user_list(): void
    {
        // gerant's default permissions do not include utilisateurs.voir
        $gerant = $this->userWithRole('gerant');

        $this->actingAs($gerant)->get('/utilisateurs')->assertForbidden();
    }

    public function test_proprietaire_can_create_a_gerant(): void
    {
        $proprietaire = $this->userWithRole('proprietaire');

        $response = $this->actingAs($proprietaire)->post('/utilisateurs', [
            'name' => 'Nouveau Gérant',
            'phone' => '73333333',
            'role' => 'gerant',
            'password' => 'MotDePasse1!',
            'password_confirmation' => 'MotDePasse1!',
        ]);

        $nouveau = User::where('phone', '73333333')->first();
        $this->assertNotNull($nouveau);
        $response->assertRedirect(route('user-permissions.index', ['user' => $nouveau->id]));
        $this->assertTrue($nouveau->hasRole('gerant'));
    }

    public function test_new_gerant_is_granted_their_role_default_permissions_directly(): void
    {
        $proprietaire = $this->userWithRole('proprietaire');

        $this->actingAs($proprietaire)->post('/utilisateurs', [
            'name' => 'Nouveau Gérant',
            'phone' => '73333333',
            'role' => 'gerant',
            'password' => 'MotDePasse1!',
            'password_confirmation' => 'MotDePasse1!',
        ]);

        $nouveau = User::where('phone', '73333333')->first();

        // Le modèle par défaut du gérant ne contient pas les actions sensibles.
        $this->assertFalse($nouveau->hasDirectPermission('utilisateurs.voir'));
        $this->assertFalse($nouveau->hasDirectPermission('achats.annuler'));
        $this->assertTrue($nouveau->hasDirectPermission('achats.creer'));
    }

    public function test_gerant_created_by_a_proprietaire_inherits_their_bureau(): void
    {
        $proprietaire = $this->proprietaireDeBureau();

        $this->actingAs($proprietaire)->post('/utilisateurs', [
            'name' => 'Gérant du bureau',
            'phone' => '73333333',
            'role' => 'gerant',
            'password' => 'MotDePasse1!',
            'password_confirmation' => 'MotDePasse1!',
        ]);

        $nouveau = User::where('phone', '73333333')->first();
        $this->assertSame($proprietaire->bureau_id, $nouveau->bureau_id);
    }

    public function test_proprietaire_cannot_assign_a_role_other_than_gerant(): void
    {
        $proprietaire = $this->userWithRole('proprietaire');

        $response = $this->actingAs($proprietaire)->post('/utilisateurs', [
            'name' => 'Tentative',
            'phone' => '73333333',
            'role' => 'proprietaire',
            'password' => 'MotDePasse1!',
            'password_confirmation' => 'MotDePasse1!',
        ]);

        $response->assertSessionHasErrors('role');
        $this->assertNull(User::where('phone', '73333333')->first());
    }

    public function test_removing_a_permission_actually_revokes_access(): void
    {
        $proprietaire = $this->userWithRole('proprietaire');
        $this->assertTrue($proprietaire->can('utilisateurs.voir'));

        // strip that direct permission (as the "Assigner permissions" screen would)
        $proprietaire->syncPermissions([]);

        $this->assertFalse($proprietaire->fresh()->can('utilisateurs.voir'));
    }

    public function test_proprietaire_cannot_edit_superadmin(): void
    {
        $proprietaire = $this->userWithRole('proprietaire');
        $superadmin = $this->superadmin();

        $this->actingAs($proprietaire)->put("/utilisateurs/{$superadmin->id}", [
            'name' => 'Hacked Name',
            'phone' => $superadmin->phone,
            'role' => 'gerant',
        ])->assertForbidden();
    }

    public function test_proprietaire_cannot_manage_a_gerant_from_another_bureau(): void
    {
        $proprietaireA = $this->proprietaireDeBureau('Bureau A');
        $proprietaireB = $this->proprietaireDeBureau('Bureau B');

        $gerantB = User::factory()->create(['bureau_id' => $proprietaireB->bureau_id]);
        $gerantB->assignRole('gerant');
        $gerantB->givePermissionTo(config('role_permissions.gerant', []));

        $this->actingAs($proprietaireA)->put("/utilisateurs/{$gerantB->id}", [
            'name' => 'Hacked',
            'phone' => $gerantB->phone,
            'role' => 'gerant',
        ])->assertForbidden();

        $proprietaireA->givePermissionTo('utilisateurs.supprimer');
        $this->actingAs($proprietaireA)->delete("/utilisateurs/{$gerantB->id}")
            ->assertSessionHas('error');
        $this->assertDatabaseHas('users', ['id' => $gerantB->id]);
    }

    public function test_proprietaire_cannot_deactivate_own_account(): void
    {
        $proprietaire = $this->userWithRole('proprietaire');

        $this->actingAs($proprietaire)->patch("/utilisateurs/{$proprietaire->id}/desactiver");

        $this->assertTrue($proprietaire->fresh()->actif);
    }

    public function test_superadmin_is_hidden_from_other_roles(): void
    {
        $proprietaire = $this->userWithRole('proprietaire');
        $this->superadmin(['name' => 'Ghost Admin']);

        $response = $this->actingAs($proprietaire)->get('/utilisateurs');

        $response->assertOk();
        $response->assertDontSee('Ghost Admin');
    }

    public function test_proprietaire_only_sees_users_from_their_own_bureau(): void
    {
        $proprietaireA = $this->proprietaireDeBureau('Bureau A');
        $proprietaireB = $this->proprietaireDeBureau('Bureau B — Autre Nom');

        $response = $this->actingAs($proprietaireA)->get('/utilisateurs');

        $response->assertOk();
        $response->assertDontSee($proprietaireB->name);
    }

    public function test_superadmin_sees_everyone_including_other_superadmins(): void
    {
        $superadminViewer = $this->superadmin();
        $this->superadmin(['name' => 'Second Admin']);

        $response = $this->actingAs($superadminViewer)->get('/utilisateurs');

        $response->assertOk();
        $response->assertSee('Second Admin');
    }

    public function test_superadmin_can_edit_another_superadmin_without_losing_the_role(): void
    {
        $superadminViewer = $this->superadmin();
        $target = $this->superadmin(['name' => 'Old Name']);

        $response = $this->actingAs($superadminViewer)->put("/utilisateurs/{$target->id}", [
            'name' => 'New Name',
            'phone' => $target->phone,
        ]);

        $response->assertRedirect(route('users.index'));
        $this->assertSame('New Name', $target->fresh()->name);
        $this->assertTrue($target->fresh()->hasRole('superadmin'));
    }
}
