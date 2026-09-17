<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function create(): View
    {
        return view('admin.auth.login');
    }

    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::attempt(['email' => $data['email'], 'password' => $data['password']], false) || ! Auth::user()?->is_admin) {
            Auth::logout();

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'The admin email or password is incorrect.',
                    'errors' => ['email' => ['The admin email or password is incorrect.']],
                ], 422);
            }

            return back()
                ->withErrors(['email' => 'The admin email or password is incorrect.'])
                ->onlyInput('email');
        }

        $request->session()->regenerate();
        $request->session()->put('admin_authenticated', true);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Logged in successfully.',
                'redirect' => route('admin.dashboard'),
            ]);
        }

        return redirect()->intended(route('admin.dashboard'));
    }

    public function destroy(Request $request): RedirectResponse|JsonResponse
    {
        $request->session()->forget('admin_authenticated');
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Logged out.',
                'redirect' => route('admin.login'),
            ]);
        }

        return redirect()->route('admin.login');
    }
}
