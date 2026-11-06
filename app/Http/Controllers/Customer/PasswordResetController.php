<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\View\View;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;

class PasswordResetController extends Controller
{
    public function create(): View
    {
        return view('account.auth.forgot-password', ['canSendEmail' => $this->canSendEmail()]);
    }

    public function store(Request $request): RedirectResponse
    {
        if (! $this->canSendEmail()) {
            return redirect()->route('password.request');
        }

        $data = $request->validate(['email' => ['required', 'email']]);

        try {
            Password::sendResetLink(['email' => $data['email']]);
        } catch (TransportExceptionInterface $exception) {
            Log::error('Password reset email failed: '.$exception->getMessage());

            return back()->withErrors(['email' => 'We could not send the email right now. Please try again later.'])->onlyInput('email');
        }

        // The same answer whether or not the account exists, so this can't reveal who has one.
        return back()->with('status', 'If that email has an account, we sent a reset link to it. Check your inbox and spam folder.');
    }

    public function edit(Request $request, string $token): View
    {
        return view('account.auth.reset-password', ['token' => $token, 'email' => $request->query('email')]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', PasswordRule::min(8)],
        ]);

        $status = Password::reset($data, function (User $user, string $password) {
            $user->forceFill(['password' => $password, 'remember_token' => Str::random(60)])->save();

            event(new PasswordReset($user));
        });

        if ($status !== Password::PASSWORD_RESET) {
            return back()->withErrors(['email' => 'This reset link is invalid or has expired. Please request a new one.'])->onlyInput('email');
        }

        return redirect()->route('customer.login')->with('status', 'Your password was changed. You can log in now.');
    }

    /**
     * Without a mail service on the live site, a "we emailed you" message would be false.
     */
    private function canSendEmail(): bool
    {
        return ! app()->environment('production')
            || ! in_array(config('mail.default'), ['log', 'array'], true);
    }
}
