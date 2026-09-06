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

        // If the user is logged in, grab their organization
        if ($user) {
            $membership = $user->organizationMemberships()->with('organization')->first();
            if ($membership) {
                $organization = $membership->organization;
            }
        }

        return [
            ...parent::share($request),
            'auth' => [
                'user' => $user,
                'organization' => $organization, // Send it to Vue!
            ],
        ];
    }
}