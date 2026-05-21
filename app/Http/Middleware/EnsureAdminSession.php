<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdminSession
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! Auth::check() || ! Auth::user()?->is_admin || ! (bool) $request->session()->get('admin_authenticated', false)) {
            return redirect()->guest(route('admin.login'));
        }

        return $next($request);
    }
}
