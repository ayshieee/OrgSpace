<?php

namespace App\Http\Controllers\Membership;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Models\OrganizationJoinRequest;
use App\Notifications\JoinRequestApproved;
use App\Notifications\JoinRequestDenied;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Lets an Adviser/Officer act on pending requests to join their organization,
 * surfaced on the org dashboard's "Pending Approvals" card.
 */
class JoinApprovalController extends Controller
{
    public function approve(Request $request, Organization $organization, OrganizationJoinRequest $joinRequest): RedirectResponse
    {
        $this->authorizeReviewer($request, $organization);

        abort_unless($joinRequest->organization_id === $organization->id && $joinRequest->isPending(), 404);

        if (! $organization->members()->where('user_id', $joinRequest->user_id)->exists()) {
            $organization->members()->create([
                'user_id' => $joinRequest->user_id,
                'is_active' => true,
            ]);
        }

        $joinRequest->update([
            'status' => 'approved',
            'responded_at' => now(),
            'responded_by' => $request->user()->id,
        ]);

        $joinRequest->user->notify(new JoinRequestApproved($organization));

        return back()->with('success', 'Request approved — they now have access to the organization.');
    }

    public function deny(Request $request, Organization $organization, OrganizationJoinRequest $joinRequest): RedirectResponse
    {
        $this->authorizeReviewer($request, $organization);

        abort_unless($joinRequest->organization_id === $organization->id && $joinRequest->isPending(), 404);

        $joinRequest->update([
            'status' => 'denied',
            'responded_at' => now(),
            'responded_by' => $request->user()->id,
        ]);

        $joinRequest->user->notify(new JoinRequestDenied($organization));

        return back()->with('success', 'Request denied.');
    }

    protected function authorizeReviewer(Request $request, Organization $organization): void
    {
        $membership = $organization->members()
            ->where('user_id', $request->user()->id)
            ->with('roles.permissions')
            ->first();

        abort_unless($membership && $membership->hasPermission('manage_roster'), 403);
    }
}
