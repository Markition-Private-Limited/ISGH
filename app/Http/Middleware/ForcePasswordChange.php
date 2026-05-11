<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class ForcePasswordChange
{
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check() && Auth::user()->must_change_password) {
            if (! $request->routeIs('portal.password.change', 'portal.password.update', 'portal.logout')) {
                return redirect()->route('portal.password.change');
            }
        }

        return $next($request);
    }
}
