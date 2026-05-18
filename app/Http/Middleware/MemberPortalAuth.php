<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class MemberPortalAuth
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->session()->get('member_portal_authenticated')) {
            // JSON/AJAX endpoints (e.g. the invoice-detail modal) expect a
            // 401, not a 302 redirect to the HTML login page.
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => 'Please sign in again.'], 401);
            }

            return redirect()->route('member-portal.login');
        }

        return $next($request);
    }
}
