<?php

namespace App\Http\Controllers\Organization;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Organization\Concerns\ChecksOrganizationPermission;
use App\Models\Announcement;
use App\Models\Organization;
use App\Notifications\AnnouncementReaction as AnnouncementReactionNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class AnnouncementReactionsController extends Controller
{
    use ChecksOrganizationPermission;

    public function toggle(Request $request, Organization $organization, Announcement $announcement): RedirectResponse
    {
        $this->membership($request, $organization);
        abort_unless($announcement->organization_id === $organization->id, 404);

        $userId = $request->user()->id;
        $existing = $announcement->reactions()->where('user_id', $userId)->first();

        if ($existing) {
            $existing->delete();
        } else {
            $announcement->reactions()->create(['user_id' => $userId]);

            if ($announcement->user_id !== $userId) {
                $announcement->author->notify(new AnnouncementReactionNotification($announcement, $request->user()));
            }
        }

        // No flash message — a heart toggle is a silent, immediate UI update.
        return back();
    }
}
