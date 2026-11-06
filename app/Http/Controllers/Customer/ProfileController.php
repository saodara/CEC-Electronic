<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\UserAvatar;
use App\Services\AvatarImage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function edit(Request $request): View
    {
        return view('account.profile', ['user' => $request->user()]);
    }

    public function update(Request $request, AvatarImage $avatars): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:20', 'regex:/^\+?[0-9][0-9\s\-]{6,18}$/'],
            // 4096px keeps GD inside PHP's default 128 MB memory limit.
            'avatar' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp', 'max:8192', 'dimensions:max_width=4096,max_height=4096'],
        ], [
            'avatar.mimes' => 'Please choose a JPG, PNG or WEBP image.',
            'avatar.max' => 'Please choose an image smaller than 8 MB.',
            'avatar.dimensions' => 'Please choose an image no larger than 4096 x 4096 pixels.',
        ]);

        $user = $request->user();

        if ($request->hasFile('avatar')) {
            $jpeg = $avatars->toJpeg($request->file('avatar'));

            if ($jpeg === null) {
                throw ValidationException::withMessages(['avatar' => 'We could not read that image. Please try another one.']);
            }

            UserAvatar::updateOrCreate(['user_id' => $user->id], ['image_base64' => base64_encode($jpeg)]);
        }

        $user->update([
            'name' => $data['name'],
            'phone' => preg_replace('/[\s\-]/', '', $data['phone']),
        ]);

        return back()->with('status', 'Your profile was updated.');
    }

    public function updateEmail(Request $request): RedirectResponse
    {
        $user = $request->user();

        $data = $request->validateWithBag('updateEmail', [
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'current_password' => ['required', 'current_password'],
        ]);

        $user->update(['email' => $data['email'], 'email_verified_at' => null]);

        return back()->with('status', 'Your email is now '.$data['email'].'. Use it the next time you log in.');
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $data = $request->validateWithBag('updatePassword', [
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Password::min(8)],
        ]);

        $request->user()->update(['password' => $data['password']]);

        // Cancels "Remember me" on other devices.
        Auth::logoutOtherDevices($data['password']);
        $request->session()->regenerate();

        return back()->with('status', 'Your password was changed.');
    }

    public function showAvatar(Request $request): Response
    {
        $avatar = UserAvatar::find($request->user()->id);

        abort_unless($avatar, 404);

        return response(base64_decode($avatar->image_base64), 200, [
            'Content-Type' => 'image/jpeg',
            'Cache-Control' => 'private, max-age=31536000',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function destroyAvatar(Request $request): RedirectResponse
    {
        UserAvatar::whereKey($request->user()->id)->delete();

        return back()->with('status', 'Your profile picture was removed.');
    }
}
