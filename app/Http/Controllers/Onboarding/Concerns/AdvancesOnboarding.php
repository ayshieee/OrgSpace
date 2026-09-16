<?php

namespace App\Http\Controllers\Onboarding\Concerns;

use App\Models\Organization;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

trait AdvancesOnboarding
{
    /**
     * Move the organization's onboarding_step forward. Never regresses it —
     * safe to call even when editing an earlier step from the Review screen.
     */
    protected function advanceTo(Organization $organization, string $step): void
    {
        $steps = config('organization.onboarding_steps');
        $current = array_search($organization->onboarding_step, $steps, true);
        $target = array_search($step, $steps, true);

        if ($current === false || ($target !== false && $target > $current)) {
            $organization->update(['onboarding_step' => $step]);
        }
    }

    /**
     * After a step is saved, either return to the Review summary (if the
     * user arrived here via an "Edit" link) or continue to the next step.
     */
    protected function redirectToNextStep(Request $request, string $nextStepRouteName): RedirectResponse
    {
        if ($request->input('return_to') === 'review') {
            return redirect()->route('onboarding.review.show');
        }

        return redirect()->route($nextStepRouteName);
    }

    protected function draftOrganization(Request $request): ?Organization
    {
        $organization = $request->attributes->get('draftOrganization');

        if ($organization) {
            return $organization;
        }

        $membership = $request->user()->organizationMemberships()
            ->whereHas('organization', fn ($q) => $q->where('status', 'draft'))
            ->with('organization')
            ->latest('joined_at')
            ->first();

        return $membership?->organization;
    }
}
