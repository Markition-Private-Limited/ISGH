<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class PortalAuth
{
    public function handle(Request $request, Closure $next)
    {
        if (! session('portal_authenticated')) {
            return redirect()->route('portal.login')
                ->with('error', 'Please sign in to access the staff portal.');
        }

        return $next($request);
    }
}
