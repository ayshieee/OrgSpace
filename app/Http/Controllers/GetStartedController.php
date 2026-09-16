<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class GetStartedController extends Controller
{
    public function show(Request $request): Response|RedirectResponse
    {
        // Reachable any time from the hub's "Join or Create Organization"
        // button, not just right after signup — a user can belong to any
        // number of active organizations and still come back here to add
        // another. The only reason to redirect away is an organization
        // they've already started but not finished setting up.
        $draftMembership = $request->user()->organizationMemberships()
            ->whereHas('organization', fn ($query) => $query->where('status', 'draft'))
            ->with('organization')
            ->first();

        if ($draftMembership) {
            $organization = $draftMembership->organization;

            return redirect()->route("onboarding.{$organization->onboarding_step}.show");
        }

        return Inertia::render('GetStarted');
    }
}
