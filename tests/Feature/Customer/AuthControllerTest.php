<?php

namespace Tests\Feature\Customer;

use App\Models\CartItem;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_page_renders(): void
    {
        $this->get(route('customer.login'))
            ->assertOk()
            ->assertViewIs('account.auth.login');
    }

    public function test_register_page_renders(): void
    {
        $this->get(route('customer.register'))
            ->assertOk()
            ->assertViewIs('account.auth.register');
    }

    public function test_user_can_login_with_valid_credentials(): void
    {
        $user = User::factory()->create();

        $response = $this->post(route('customer.login.store'), [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response->assertRedirect(route('account.dashboard'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_login_fails_with_wrong_password(): void
    {
        $user = User::factory()->create();

        $response = $this->post(route('customer.login.store'), [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        $response->assertSessionHasErrors(['email']);
        $this->assertGuest();
    }

    public function test_login_fails_with_unknown_email(): void
    {
        $response = $this->post(route('customer.login.store'), [
            'email' => 'nobody@example.com',
            'password' => 'password',
        ]);

        $response->assertSessionHasErrors(['email']);
        $this->assertGuest();
    }

    public function test_registration_creates_user_logs_in_and_redirects(): void
    {
        $response = $this->post(route('customer.register.store'), [
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertRedirect(route('account.dashboard'));
        $response->assertSessionHas('status', 'Account created.');

        $this->assertDatabaseHas('users', ['email' => 'jane@example.com']);
        $this->assertAuthenticated();
    }

    public function test_registration_fails_without_required_fields(): void
    {
        $this->post(route('customer.register.store'), [])
            ->assertSessionHasErrors(['name', 'email', 'password']);

        $this->assertDatabaseCount('users', 0);
    }

    public function test_registration_fails_with_duplicate_email(): void
    {
        $existing = User::factory()->create(['email' => 'jane@example.com']);

        $this->post(route('customer.register.store'), [
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertSessionHasErrors(['email']);

        $this->assertDatabaseCount('users', 1);
    }

    public function test_registration_fails_when_password_confirmation_does_not_match(): void
    {
        $this->post(route('customer.register.store'), [
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'password' => 'password123',
            'password_confirmation' => 'different-password',
        ])->assertSessionHasErrors(['password']);

        $this->assertDatabaseCount('users', 0);
    }

    public function test_registration_attaches_matching_guest_orders_to_the_new_user(): void
    {
        $order = Order::factory()->create([
            'customer_email' => 'jane@example.com',
            'user_id' => null,
        ]);

        $this->post(route('customer.register.store'), [
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $user = User::where('email', 'jane@example.com')->firstOrFail();

        $this->assertSame($user->id, $order->refresh()->user_id);
    }

    public function test_guest_cart_is_merged_into_user_on_login(): void
    {
        // Establish a real guest session via an actual request, since the
        // simulated test client assigns a brand-new session id to any request
        // that doesn't carry the session cookie forward. The Set-Cookie value
        // is encrypted (EncryptCookies middleware), so we round-trip that
        // exact value on the follow-up request rather than reconstructing it —
        // and separately read the real (plaintext) session id off the session
        // store itself, since that's what CartItem.session_id and the
        // controller's own `$request->session()->getId()` operate on.
        $cookieName = config('session.cookie');
        $guestResponse = $this->get(route('customer.login'));
        $encryptedCookie = $guestResponse->getCookie($cookieName)->getValue();
        $sessionId = $this->app['session']->getId();

        $cartItem = CartItem::factory()->create([
            'session_id' => $sessionId,
            'user_id' => null,
        ]);

        $user = User::factory()->create();

        $this->withCookie($cookieName, $encryptedCookie)->post(route('customer.login.store'), [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $cartItem->refresh();

        $this->assertSame($user->id, $cartItem->user_id);
        $this->assertNull($cartItem->session_id);
    }

    public function test_user_can_logout(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->post(route('customer.logout'));

        $response->assertRedirect(route('shop.home'));
        $response->assertSessionHas('status', 'Logged out.');
        $this->assertGuest();
    }
}
