<?php

namespace Tests\Feature\Customer;

use App\Models\User;
use App\Models\UserAvatar;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ProfileControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_sent_to_login(): void
    {
        $this->get(route('account.profile'))->assertRedirect(route('customer.login'));
        $this->get(route('account.avatar'))->assertRedirect(route('customer.login'));
        $this->patch(route('account.profile.update'))->assertRedirect(route('customer.login'));
        $this->put(route('account.email.update'))->assertRedirect(route('customer.login'));
        $this->put(route('account.password.update'))->assertRedirect(route('customer.login'));
    }

    public function test_dashboard_links_to_edit_profile(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('account.dashboard'))
            ->assertOk()
            ->assertSee(route('account.profile'));
    }

    public function test_profile_page_renders(): void
    {
        $user = User::factory()->create(['email' => 'me@example.com']);

        $this->actingAs($user)
            ->get(route('account.profile'))
            ->assertOk()
            ->assertSee('me@example.com')
            ->assertSee('Change password');
    }

    public function test_name_and_phone_can_be_updated_without_a_picture(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->patch(route('account.profile.update'), ['name' => 'New Name', 'phone' => '012 220-152'])
            ->assertRedirect()
            ->assertSessionHas('status');

        $user->refresh();
        $this->assertSame('New Name', $user->name);
        $this->assertSame('012220152', $user->phone);
        $this->assertSame(0, UserAvatar::count());
    }

    public function test_uploaded_picture_becomes_a_small_square_jpeg_only_its_owner_receives(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();

        $this->actingAs($user)->patch(route('account.profile.update'), [
            'name' => $user->name,
            'phone' => '012220152',
            'avatar' => UploadedFile::fake()->image('me.png', 800, 500),
        ])->assertSessionHasNoErrors();

        $response = $this->get(route('account.avatar'))
            ->assertOk()
            ->assertHeader('Content-Type', 'image/jpeg');

        [$width, $height, $type] = getimagesizefromstring($response->getContent());
        $this->assertSame([256, 256, IMAGETYPE_JPEG], [$width, $height, $type]);
        $this->assertNotNull($user->avatarUrl());

        $this->actingAs($other)->get(route('account.avatar'))->assertNotFound();
    }

    public function test_uploaded_picture_shows_in_the_header_my_account_and_edit_profile(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('shop.home'))
            ->assertSee('images/ProfileAndOrder/boy-account.png');

        $this->patch(route('account.profile.update'), [
            'name' => $user->name,
            'phone' => '012220152',
            'avatar' => UploadedFile::fake()->image('me.png', 300, 300),
        ])->assertSessionHasNoErrors();

        $src = 'src="'.e($user->avatarUrl()).'"';

        $this->get(route('shop.home'))
            ->assertSee($src, false)
            ->assertDontSee('images/ProfileAndOrder/boy-account.png');

        foreach (['account.dashboard', 'account.profile'] as $page) {
            $response = $this->get(route($page))->assertOk();
            $this->assertGreaterThanOrEqual(2, substr_count($response->getContent(), $src), $page);
        }

        $this->get($user->avatarUrl())->assertOk()->assertHeader('Content-Type', 'image/jpeg');
    }

    public function test_new_picture_replaces_the_old_one(): void
    {
        $user = User::factory()->create();

        foreach (['first.jpg', 'second.jpg'] as $name) {
            $this->actingAs($user)->patch(route('account.profile.update'), [
                'name' => $user->name,
                'phone' => '012220152',
                'avatar' => UploadedFile::fake()->image($name, 300, 300),
            ])->assertSessionHasNoErrors();
        }

        $this->assertSame(1, UserAvatar::count());
    }

    public function test_unsafe_or_oversized_uploads_are_rejected(): void
    {
        $user = User::factory()->create();

        $uploads = [
            UploadedFile::fake()->create('evil.svg', 10, 'image/svg+xml'),
            UploadedFile::fake()->create('notes.txt', 10, 'text/plain'),
            UploadedFile::fake()->image('huge.jpg', 4097, 100),
            UploadedFile::fake()->image('heavy.jpg', 100, 100)->size(8193),
        ];

        foreach ($uploads as $upload) {
            $this->actingAs($user)->patch(route('account.profile.update'), [
                'name' => $user->name,
                'phone' => '012220152',
                'avatar' => $upload,
            ])->assertSessionHasErrors('avatar');
        }

        $this->assertSame(0, UserAvatar::count());
    }

    public function test_picture_can_be_removed(): void
    {
        $user = User::factory()->create();
        UserAvatar::create(['user_id' => $user->id, 'image_base64' => base64_encode('x')]);

        $this->actingAs($user)->delete(route('account.avatar.destroy'))->assertSessionHas('status');

        $this->assertSame(0, UserAvatar::count());
        $this->assertNull($user->avatarUrl());
    }

    public function test_email_change_needs_the_current_password(): void
    {
        $user = User::factory()->create(['email' => 'old@example.com', 'password' => 'secret-pass']);

        $this->actingAs($user)
            ->put(route('account.email.update'), ['email' => 'new@example.com', 'current_password' => 'wrong'])
            ->assertSessionHasErrorsIn('updateEmail', 'current_password');

        $this->assertSame('old@example.com', $user->fresh()->email);
    }

    public function test_form_errors_are_shown_on_the_page(): void
    {
        $user = User::factory()->create(['password' => 'secret-pass']);

        $this->actingAs($user)
            ->from(route('account.profile'))
            ->followingRedirects()
            ->put(route('account.email.update'), ['email' => 'new@example.com', 'current_password' => 'wrong'])
            ->assertOk()
            ->assertSee('The password is incorrect.')
            ->assertSee('value="new@example.com"', false);

        $this->actingAs($user)
            ->from(route('account.profile'))
            ->followingRedirects()
            ->put(route('account.password.update'), [
                'current_password' => 'secret-pass',
                'password' => 'short',
                'password_confirmation' => 'short',
            ])
            ->assertOk()
            ->assertSee('at least 8 characters');
    }

    public function test_email_cannot_be_one_another_account_uses(): void
    {
        User::factory()->create(['email' => 'taken@example.com']);
        $user = User::factory()->create(['password' => 'secret-pass']);

        $this->actingAs($user)
            ->put(route('account.email.update'), ['email' => 'taken@example.com', 'current_password' => 'secret-pass'])
            ->assertSessionHasErrorsIn('updateEmail', 'email');
    }

    public function test_changed_email_is_used_for_login(): void
    {
        $user = User::factory()->create(['email' => 'old@example.com', 'password' => 'secret-pass']);

        $this->actingAs($user)
            ->put(route('account.email.update'), ['email' => 'new@example.com', 'current_password' => 'secret-pass'])
            ->assertSessionHasNoErrors();

        $this->assertSame('new@example.com', $user->fresh()->email);
        $this->post(route('customer.logout'));

        $this->post(route('customer.login.store'), ['email' => 'old@example.com', 'password' => 'secret-pass'])
            ->assertSessionHasErrors('email');
        $this->post(route('customer.login.store'), ['email' => 'new@example.com', 'password' => 'secret-pass'])
            ->assertRedirect(route('account.dashboard'));
    }

    public function test_password_change_needs_the_current_password_and_confirmation(): void
    {
        $user = User::factory()->create(['password' => 'secret-pass']);

        $this->actingAs($user)
            ->put(route('account.password.update'), [
                'current_password' => 'wrong',
                'password' => 'brand-new-pass',
                'password_confirmation' => 'brand-new-pass',
            ])->assertSessionHasErrorsIn('updatePassword', 'current_password');

        $this->actingAs($user)
            ->put(route('account.password.update'), [
                'current_password' => 'secret-pass',
                'password' => 'brand-new-pass',
                'password_confirmation' => 'different',
            ])->assertSessionHasErrorsIn('updatePassword', 'password');

        $this->assertTrue(Hash::check('secret-pass', $user->fresh()->password));
    }

    public function test_changed_password_is_used_for_login(): void
    {
        $user = User::factory()->create(['email' => 'me@example.com', 'password' => 'secret-pass']);

        $this->actingAs($user)
            ->put(route('account.password.update'), [
                'current_password' => 'secret-pass',
                'password' => 'brand-new-pass',
                'password_confirmation' => 'brand-new-pass',
            ])->assertSessionHasNoErrors();

        $this->post(route('customer.logout'));

        $this->post(route('customer.login.store'), ['email' => 'me@example.com', 'password' => 'secret-pass'])
            ->assertSessionHasErrors('email');
        $this->post(route('customer.login.store'), ['email' => 'me@example.com', 'password' => 'brand-new-pass'])
            ->assertRedirect(route('account.dashboard'));
    }
}
