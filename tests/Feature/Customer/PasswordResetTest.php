<?php

namespace Tests\Feature\Customer;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_page_links_to_forgot_password(): void
    {
        $this->get(route('customer.login'))->assertOk()->assertSee(route('password.request'));
    }

    public function test_reset_link_is_emailed_to_an_existing_account(): void
    {
        Notification::fake();
        $user = User::factory()->create(['email' => 'me@example.com']);

        $this->post(route('password.email'), ['email' => 'me@example.com'])->assertSessionHas('status');

        Notification::assertSentTo($user, ResetPassword::class);
    }

    public function test_unknown_email_gets_the_same_answer_and_no_email(): void
    {
        Notification::fake();

        $this->post(route('password.email'), ['email' => 'nobody@example.com'])
            ->assertSessionHas('status', 'If that email has an account, we sent a reset link to it. Check your inbox and spam folder.')
            ->assertSessionHasNoErrors();

        Notification::assertNothingSent();
    }

    public function test_emailed_link_ignores_a_faked_host(): void
    {
        Notification::fake();
        config(['app.public_url' => 'https://shop.example']);
        $user = User::factory()->create(['email' => 'me@example.com']);

        $this->withHeaders(['Host' => 'evil.example', 'X-Forwarded-Host' => 'evil.example'])
            ->post(route('password.email'), ['email' => 'me@example.com']);

        Notification::assertSentTo($user, ResetPassword::class, function (ResetPassword $notification) use ($user) {
            $url = $notification->toMail($user)->actionUrl;

            return str_starts_with($url, 'https://shop.example/reset-password/')
                && ! str_contains($url, 'evil.example');
        });
    }

    public function test_password_can_be_reset_once_with_the_emailed_token(): void
    {
        $user = User::factory()->create(['email' => 'me@example.com', 'password' => 'old-password']);
        $rememberToken = $user->remember_token;
        $token = Password::createToken($user);

        $this->get(route('password.reset', ['token' => $token, 'email' => 'me@example.com']))
            ->assertOk()
            ->assertSee('value="me@example.com"', false);

        $reset = ['token' => $token, 'email' => 'me@example.com', 'password' => 'brand-new-pass', 'password_confirmation' => 'brand-new-pass'];

        $this->post(route('password.store'), $reset)
            ->assertRedirect(route('customer.login'))
            ->assertSessionHas('status');

        $user->refresh();
        $this->assertTrue(Hash::check('brand-new-pass', $user->password));
        $this->assertNotSame($rememberToken, $user->remember_token);

        $this->post(route('customer.login.store'), ['email' => 'me@example.com', 'password' => 'brand-new-pass'])
            ->assertRedirect(route('account.dashboard'));
        $this->post(route('customer.logout'));

        $this->post(route('password.store'), [...$reset, 'password' => 'third-password', 'password_confirmation' => 'third-password'])
            ->assertSessionHasErrors('email');
        $this->assertTrue(Hash::check('brand-new-pass', $user->fresh()->password));
    }

    public function test_wrong_token_or_another_users_email_cannot_reset(): void
    {
        $user = User::factory()->create(['email' => 'me@example.com', 'password' => 'old-password']);
        $other = User::factory()->create(['email' => 'other@example.com', 'password' => 'other-password']);
        $token = Password::createToken($user);

        foreach ([['bad-token', 'me@example.com'], [$token, 'other@example.com']] as [$tryToken, $email]) {
            $this->post(route('password.store'), [
                'token' => $tryToken,
                'email' => $email,
                'password' => 'brand-new-pass',
                'password_confirmation' => 'brand-new-pass',
            ])->assertSessionHasErrors('email');
        }

        $this->assertTrue(Hash::check('old-password', $user->fresh()->password));
        $this->assertTrue(Hash::check('other-password', $other->fresh()->password));
    }

    public function test_expired_link_cannot_reset(): void
    {
        $user = User::factory()->create(['email' => 'me@example.com', 'password' => 'old-password']);
        $token = Password::createToken($user);

        $this->travel(61)->minutes();

        $this->post(route('password.store'), [
            'token' => $token,
            'email' => 'me@example.com',
            'password' => 'brand-new-pass',
            'password_confirmation' => 'brand-new-pass',
        ])->assertSessionHasErrors('email');
    }

    public function test_live_site_without_mail_service_points_to_support_and_sends_nothing(): void
    {
        Notification::fake();
        $this->app['env'] = 'production';
        config(['mail.default' => 'log']);
        User::factory()->create(['email' => 'me@example.com']);

        $this->get(route('password.request'))
            ->assertOk()
            ->assertSee('https://t.me/CECELECTRONICS')
            ->assertDontSee('Send reset link');

        $this->withoutMiddleware(PreventRequestForgery::class)
            ->post(route('password.email'), ['email' => 'me@example.com'])
            ->assertRedirect(route('password.request'))
            ->assertSessionMissing('status');

        Notification::assertNothingSent();
    }

    public function test_live_site_with_mail_service_shows_the_form(): void
    {
        $this->app['env'] = 'production';
        config(['mail.default' => 'smtp']);

        $this->get(route('password.request'))->assertOk()->assertSee('Send reset link');
    }
}
