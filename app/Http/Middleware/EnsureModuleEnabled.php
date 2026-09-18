<?php

namespace App\Http\Middleware;

use App\Models\Organization;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Blocks a module's routes entirely when the organization hasn't enabled
 * that module — a disabled module should 404 like it doesn't exist, not
 * just disappear from the sidebar. Resolves the organization the same way
 * ChecksOrganizationPermission does (the bound {organization} route
 * parameter), since that's how every module controller already works.
 */
class EnsureModuleEnabled
{
    public function handle(Request $request, Closure $next, string $moduleKey): Response
    {
        $organization = $request->route('organization');

        abort_unless($organization instanceof Organization, 404);

        $enabled = $organization->features()
            ->where('module_key', $moduleKey)
            ->where('is_enabled', true)
            ->exists();

        abort_unless($enabled, 404);

        return $next($request);
    }
}
