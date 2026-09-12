<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserDeletionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedRolesAndPermissions();
    }

    private function superadmin(): User
    {
        $user = User::factory()->create();
        $user->assignRole('superadmin');

        return $user;
    }

    public function test_proprietaire_cannot_delete_a_user_by_default(): void
    {
        // le propriétaire n'a pas utilisateurs.supprimer par défaut
        $proprietaire = $this->userWithRole('proprietaire');
        $cible = $this->userWithRole('gerant');
        $cible->update(['bureau_id' => $proprietaire->bureau_id]);

        $this->actingAs($proprietaire)->delete("/utilisateurs/{$cible->id}")->assertForbidden();
        $this->assertDatabaseHas('users', ['id' => $cible->id]);
    }

    public function test_proprietaire_can_delete_once_granted_the_permission(): void
    {
        $proprietaire = $this->userWithRole('proprietaire');
        $proprietaire->givePermissionTo('utilisateurs.supprimer'); // le superadmin la lui a donnée
        $cible = $this->userWithRole('gerant');
        $cible->update(['bureau_id' => $proprietaire->bureau_id]);

        $this->actingAs($proprietaire)->delete("/utilisateurs/{$cible->id}")->assertRedirect(route('users.index'));
        $this->assertDatabaseMissing('users', ['id' => $cible->id]);
    }

    public function test_superadmin_can_delete_a_user(): void
    {
        $admin = $this->superadmin();
        $cible = $this->userWithRole('gerant');

        $this->actingAs($admin)->delete("/utilisateurs/{$cible->id}")->assertRedirect(route('users.index'));
        $this->assertDatabaseMissing('users', ['id' => $cible->id]);
    }

    public function test_cannot_delete_a_superadmin_account(): void
    {
        $admin = $this->superadmin();
        $autreAdmin = $this->superadmin();

        $this->actingAs($admin)->delete("/utilisateurs/{$autreAdmin->id}")->assertRedirect();
        $this->assertDatabaseHas('users', ['id' => $autreAdmin->id]); // toujours là
    }

    public function test_cannot_delete_own_account(): void
    {
        $admin = $this->superadmin();

        $this->actingAs($admin)->delete("/utilisateurs/{$admin->id}")->assertRedirect();
        $this->assertDatabaseHas('users', ['id' => $admin->id]);
    }
}
