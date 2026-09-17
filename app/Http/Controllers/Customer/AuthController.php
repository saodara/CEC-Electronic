<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\User;
use App\Services\CartService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function __construct(private CartService $cartService)
    {
    }

    public function login(): View
    {
        return view('account.auth.login');
    }

    public function register(): View
    {
        return view('account.auth.register');
    }

    public function authenticate(Request $request): RedirectResponse|JsonResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $sessionId = $request->session()->getId();

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Email or password is incorrect.',
                    'errors' => ['email' => ['Email or password is incorrect.']],
                ], 422);
            }

            return back()
                ->withErrors(['email' => 'Email or password is incorrect.'])
                ->onlyInput('email');
        }

        $request->session()->regenerate();
        $this->cartService->mergeGuestCartIntoUser($sessionId, $request->user());

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Logged in successfully.',
                'redirect' => route('account.dashboard'),
            ]);
        }

        return redirect()->intended(route('account.dashboard'));
    }

    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::min(8)],
        ]);

        $sessionId = $request->session()->getId();

        $user = User::create($data);

        Order::query()
            ->whereNull('user_id')
            ->where('customer_email', $user->email)
            ->update(['user_id' => $user->id]);

        Auth::login($user);
        $request->session()->regenerate();
        $this->cartService->mergeGuestCartIntoUser($sessionId, $user);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Account created.',
                'redirect' => route('account.dashboard'),
            ]);
        }

        return redirect()->intended(route('account.dashboard'))->with('status', 'Account created.');
    }

    public function logout(Request $request): RedirectResponse|JsonResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Logged out.',
                'redirect' => route('shop.home'),
            ]);
        }

        return redirect()->route('shop.home')->with('status', 'Logged out.');
    }
}
