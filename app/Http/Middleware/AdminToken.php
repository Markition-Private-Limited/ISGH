<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class AdminToken
{
    public function handle(Request $request, Closure $next)
    {
        $token = config('services.admin.token');

        // Allow if token matches query param or Authorization header
        if (
            $token &&
            (
                $request->query('admin_token') === $token ||
                $request->header('X-Admin-Token') === $token ||
                session('admin_authenticated') === true
            )
        ) {
            // Persist in session so user doesn't need token on every page
            session(['admin_authenticated' => true]);
            return $next($request);
        }

        // Show a simple login form if token is missing/wrong
        if ($request->isMethod('post') && $request->input('admin_token') === $token) {
            session(['admin_authenticated' => true]);
            return redirect()->intended(route('admin.dashboard'));
        }

        return response()->view('admin.login', [], 401);
    }
}
