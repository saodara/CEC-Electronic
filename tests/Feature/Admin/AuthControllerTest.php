<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_page_renders(): void
    {
        $this->get(route('admin.login'))
            ->assertOk()
            ->assertViewIs('admin.auth.login');
    }

    public function test_admin_can_login_with_valid_credentials(): void
    {
        $admin = User::factory()->admin()->create(['password' => bcrypt('password')]);

        $response = $this->post(route('admin.login.store'), [
            'email' => $admin->email,
            'password' => 'password',
        ]);

        $response->assertRedirect(route('admin.dashboard'));
        $this->assertTrue(session('admin_authenticated'));
        $this->assertAuthenticatedAs($admin);
    }

    public function test_non_admin_user_cannot_login(): void
    {
        $user = User::factory()->create(['is_admin' => false]);

        $response = $this->post(route('admin.login.store'), [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response->assertSessionHasErrors(['email']);
        $this->assertGuest();
        $this->assertNull(session('admin_authenticated'));
    }

    public function test_admin_login_fails_with_wrong_password(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->post(route('admin.login.store'), [
            'email' => $admin->email,
            'password' => 'wrong-password',
        ]);

        $response->assertSessionHasErrors(['email']);
        $this->assertGuest();
        $this->assertNull(session('admin_authenticated'));
    }

    public function test_admin_login_fails_with_unknown_email(): void
    {
        $response = $this->post(route('admin.login.store'), [
            'email' => 'nobody@example.com',
            'password' => 'password',
        ]);

        $response->assertSessionHasErrors(['email']);
        $this->assertGuest();
    }

    public function test_admin_can_logout(): void
    {
        $this->actingAsAdmin();

        $response = $this->post(route('admin.logout'));

        $response->assertRedirect(route('admin.login'));
        $this->assertGuest();
        $this->assertNull(session('admin_authenticated'));
    }
}
