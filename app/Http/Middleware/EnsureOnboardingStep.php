<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Gates access to a wizard step's routes until the user's in-progress
 * (status=draft) organization has actually reached that step. Resolves the
 * draft organization and attaches it to the request as `draftOrganization`
 * for controllers to use.
 */
class EnsureOnboardingStep
{
    public function handle(Request $request, Closure $next, string $requiredStep): Response
    {
        $user = $request->user();

        $steps = config('organization.onboarding_steps');

        $membership = $user->organizationMemberships()
            ->whereHas('organization', fn ($q) => $q->where('status', 'draft'))
            ->with('organization')
            ->latest('joined_at')
            ->first();

        if (! $membership) {
            return redirect()->route('onboarding.profile.show');
        }

        $organization = $membership->organization;

        $currentIndex = array_search($organization->onboarding_step, $steps, true);
        $requiredIndex = array_search($requiredStep, $steps, true);

        if ($currentIndex === false || $requiredIndex === false || $currentIndex < $requiredIndex) {
            return redirect()->route("onboarding.{$organization->onboarding_step}.show");
        }

        $request->attributes->set('draftOrganization', $organization);

        return $next($request);
    }
}
