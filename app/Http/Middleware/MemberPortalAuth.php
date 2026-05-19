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

        $response = $next($request);

        // Forbid the browser from caching authenticated pages. Without this the
        // browser keeps a copy in its back-forward cache, so after logout the
        // Back button restores the dashboard from cache without ever hitting
        // the server — meaning this guard never runs. `no-store` forces a fresh
        // request on Back/Forward, which then redirects to login.
        $response->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
        $response->headers->set('Pragma', 'no-cache');
        $response->headers->set('Expires', '0');

        return $response;
    }
}
