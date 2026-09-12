<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_update_own_profile(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->put('/profil', ['name' => 'Nouveau Nom', 'phone' => '78888888'])
            ->assertRedirect();

        $this->assertSame('Nouveau Nom', $user->fresh()->name);
        $this->assertSame('78888888', $user->fresh()->phone);
    }

    public function test_user_can_update_password_with_correct_current_password(): void
    {
        $user = User::factory()->create(['password' => 'ancien-mdp']);

        $this->actingAs($user)->put('/profil/mot-de-passe', [
            'current_password' => 'ancien-mdp',
            'password' => 'NouveauMotDePasse1!',
            'password_confirmation' => 'NouveauMotDePasse1!',
        ])->assertRedirect();

        $this->assertTrue(Hash::check('NouveauMotDePasse1!', $user->fresh()->password));
    }

    public function test_user_cannot_update_password_with_wrong_current_password(): void
    {
        $user = User::factory()->create(['password' => 'ancien-mdp']);

        $this->actingAs($user)->put('/profil/mot-de-passe', [
            'current_password' => 'faux-mdp',
            'password' => 'NouveauMotDePasse1!',
            'password_confirmation' => 'NouveauMotDePasse1!',
        ])->assertSessionHasErrors('current_password');
    }
}
