<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/')->assertRedirect(route('login'));
    }

    public function test_user_can_login_with_correct_credentials(): void
    {
        $user = User::factory()->create(['phone' => '70000000', 'password' => 'password']);

        $response = $this->post('/login', ['phone' => '70000000', 'password' => 'password']);

        $response->assertRedirect(route('home'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_user_cannot_login_with_wrong_password(): void
    {
        User::factory()->create(['phone' => '70000000', 'password' => 'password']);

        $response = $this->post('/login', ['phone' => '70000000', 'password' => 'wrong']);

        $response->assertSessionHasErrors('phone');
        $this->assertGuest();
    }

    public function test_deactivated_user_cannot_login(): void
    {
        User::factory()->create(['phone' => '70000000', 'password' => 'password', 'actif' => false]);

        $response = $this->post('/login', ['phone' => '70000000', 'password' => 'password']);

        $response->assertSessionHasErrors('phone');
        $this->assertGuest();
    }

    public function test_user_can_logout(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/logout')->assertRedirect(route('login'));
        $this->assertGuest();
    }
}
