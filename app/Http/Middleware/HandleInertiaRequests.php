<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function share(Request $request): array
    {
        $user = $request->user();
        $organization = null;
        $permissions = [];
        $enabledModules = [];

        // If the user is logged in, grab their organization and permissions.
        // When the current route is scoped to a specific organization (e.g.
        // /organizations/{organization}/dashboard), use that one instead of
        // just the user's first membership — matters once a user belongs to
        // more than one organization.
        if ($user) {
            $routeOrganization = $request->route('organization');

            $membership = $user->organizationMemberships()
                ->with(['organization', 'roles.permissions'])
                ->when(
                    $routeOrganization instanceof \App\Models\Organization,
                    fn ($query) => $query->where('organization_id', $routeOrganization->id),
                )
                ->first();

            if ($membership) {
                $organization = $membership->organization;

                $permissions = $membership->roles->contains('is_system', true)
                    ? array_keys(config('permissions.keys'))
                    : $membership->roles->flatMap(fn ($role) => $role->permissions->pluck('permission'))->unique()->values()->all();

                $enabledModules = $organization->features()->where('is_enabled', true)->pluck('module_key')->all();
            }
        }

        return [
            ...parent::share($request),
            'auth' => [
                'user' => $user,
                'organization' => $organization, // Send it to Vue!
                'permissions' => $permissions,
                'enabledModules' => $enabledModules,
                'unread_notifications_count' => $user?->unreadNotifications()->count() ?? 0,
            ],
            // Every controller in this app flashes via back()->with('success', ...)
            // / ->with('warning', ...) — this is what actually gets it in front
            // of the user, instead of it sitting unread in the session.
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'warning' => fn () => $request->session()->get('warning'),
                'error' => fn () => $request->session()->get('error'),
            ],
        ];
    }
}