<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Real enforcement of "must change temporary password" — not just a
 * one-time nudge at login. Applied globally, so a member who logged in
 * with a wizard-generated password can't route around the force-change
 * page by hitting a bookmarked or typed-in URL directly.
 */
class EnsurePasswordIsCurrent
{
    /**
     * Routes that must stay reachable even while a password change is
     * pending — the force-change page itself, and logout (always let a
     * user leave).
     */
    protected const EXEMPT_ROUTES = [
        'password.force-change.show',
        'password.force-change.update',
        'logout',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || ! $user->must_change_password) {
            return $next($request);
        }

        if (in_array($request->route()?->getName(), self::EXEMPT_ROUTES, true)) {
            return $next($request);
        }

        return redirect()->route('password.force-change.show');
    }
}
