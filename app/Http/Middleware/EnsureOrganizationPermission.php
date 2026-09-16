<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Gates an org-scoped admin route behind a permission the current user must
 * hold in their active (status=active) organization. Attaches the resolved
 * organization to the request as `organization` for controllers to use.
 */
class EnsureOrganizationPermission
{
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        $membership = $request->user()->organizationMemberships()
            ->whereHas('organization', fn ($q) => $q->where('status', 'active'))
            ->with(['organization', 'roles.permissions'])
            ->first();

        if (! $membership) {
            return redirect()->route('dashboard');
        }

        if (! $membership->hasPermission($permission)) {
            abort(403);
        }

        $request->attributes->set('organization', $membership->organization);

        return $next($request);
    }
}
