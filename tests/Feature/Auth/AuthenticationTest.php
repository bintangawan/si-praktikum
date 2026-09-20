<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_can_be_rendered(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
    }

    public function test_users_can_authenticate_using_the_login_screen(): void
    {
        $user = User::factory()->create();

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard', absolute: false));
    }

    public function test_users_can_not_authenticate_with_invalid_password(): void
    {
        $user = User::factory()->create();

        $response = $this->followingRedirects()->from('/login')->post('/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        $this->assertGuest();
        $response
            ->assertSee('<dialog', false)
            ->assertSee('Login gagal')
            ->assertSee('Email atau password Anda salah. Silakan coba lagi.')
            ->assertDontSee('auth.failed');
    }

    public function test_unknown_email_shows_the_same_login_error(): void
    {
        $this->from('/login')->post('/login', [
            'email' => 'unknown@example.com',
            'password' => 'wrong-password',
        ])->assertSessionHasErrors([
            'email' => 'Email atau password Anda salah. Silakan coba lagi.',
        ]);

        $this->assertGuest();
    }

    public function test_login_rate_limit_shows_a_readable_message(): void
    {
        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->post('/login', [
                'email' => 'unknown@example.com',
                'password' => 'wrong-password',
            ]);
        }

        $this->followingRedirects()->from('/login')->post('/login', [
            'email' => 'unknown@example.com',
            'password' => 'wrong-password',
        ])
            ->assertSee('Terlalu banyak percobaan login.')
            ->assertDontSee('auth.throttle');

        $this->assertGuest();
    }

    public function test_users_can_logout(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/logout');

        $this->assertGuest();
        $response->assertRedirect('/');
    }
}
