<?php

namespace App\Http\Controllers\Organization;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Organization\Concerns\ChecksOrganizationPermission;
use App\Mail\AnnouncementReminder;
use App\Models\Announcement;
use App\Models\AnnouncementRead;
use App\Models\Organization;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class AnnouncementsController extends Controller
{
    use ChecksOrganizationPermission;

    public function index(Request $request, Organization $organization): Response
    {
        $membership = $this->membership($request, $organization);
        $canManage = $membership->hasPermission('manage_announcements');
        $user = $request->user();

        $announcements = $organization->announcements()
            ->with(['author', 'reads'])
            ->orderByDesc('is_pinned')
            ->latest()
            ->get();

        // Viewing the list counts as reading — the same "opened it" signal
        // most read-receipt systems use, without needing a separate click.
        $now = now();
        $newReads = $announcements
            ->reject(fn (Announcement $a) => $a->reads->contains('user_id', $user->id))
            ->map(fn (Announcement $a) => [
                'id' => (string) Str::uuid(),
                'announcement_id' => $a->id,
                'user_id' => $user->id,
                'read_at' => $now->toDateTimeString(),
            ]);

        if ($newReads->isNotEmpty()) {
            AnnouncementRead::insert($newReads->all());
        }

        $allMembers = $organization->members()->where('is_active', true)->with('user')->get();
        $totalMembers = $allMembers->count();

        $announcements = $announcements->map(function (Announcement $a) use ($canManage, $totalMembers, $allMembers, $user, $newReads) {
            $readByMe = $a->reads->contains('user_id', $user->id) || $newReads->contains('announcement_id', $a->id);

            $payload = [
                'id' => $a->id,
                'title' => $a->title,
                'body' => $a->body,
                'is_pinned' => $a->is_pinned,
                'author' => $a->author->name,
                'posted_at' => $a->created_at->diffForHumans(),
                'read_by_me' => $readByMe,
            ];

            if ($canManage) {
                $readsByUser = $a->reads->keyBy('user_id');
                $justMarkedForMe = $newReads->contains('announcement_id', $a->id);

                $receipts = $allMembers->map(function ($member) use ($readsByUser, $justMarkedForMe, $user) {
                    $read = $readsByUser->get($member->user_id);
                    $isMe = $member->user_id === $user->id;

                    return [
                        'name' => $member->user->name,
                        'is_read' => (bool) $read || ($isMe && $justMarkedForMe),
                        'read_at' => $read?->read_at->diffForHumans() ?? ($isMe && $justMarkedForMe ? 'just now' : null),
                    ];
                })->sortBy([['is_read', 'asc'], ['name', 'asc']])->values();

                $payload['read_count'] = $a->reads->count() + ($justMarkedForMe ? 1 : 0);
                $payload['total_members'] = $totalMembers;
                $payload['receipts'] = $receipts;
            }

            return $payload;
        });

        return Inertia::render('Organizations/Announcements/Index', [
            'organization' => ['id' => $organization->id, 'name' => $organization->name],
            'announcements' => $announcements,
            'canManage' => $canManage,
        ]);
    }

    public function store(Request $request, Organization $organization): RedirectResponse
    {
        $this->authorizeOrgPermission($request, $organization, 'manage_announcements');

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string', 'max:5000'],
            'is_pinned' => ['boolean'],
        ]);

        $organization->announcements()->create([
            'user_id' => $request->user()->id,
            'title' => $validated['title'],
            'body' => $validated['body'],
            'is_pinned' => $validated['is_pinned'] ?? false,
        ]);

        return back()->with('success', 'Announcement posted.');
    }

    public function update(Request $request, Organization $organization, Announcement $announcement): RedirectResponse
    {
        $this->authorizeOrgPermission($request, $organization, 'manage_announcements');

        abort_unless($announcement->organization_id === $organization->id, 404);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string', 'max:5000'],
            'is_pinned' => ['boolean'],
        ]);

        $announcement->update($validated);

        return back()->with('success', 'Announcement updated.');
    }

    public function destroy(Request $request, Organization $organization, Announcement $announcement): RedirectResponse
    {
        $this->authorizeOrgPermission($request, $organization, 'manage_announcements');

        abort_unless($announcement->organization_id === $organization->id, 404);

        $announcement->delete();

        return back()->with('success', 'Announcement deleted.');
    }

    /**
     * Emails everyone who hasn't opened this announcement yet — the real
     * substitute for the mockup's SMS/push fallback, using infrastructure
     * we actually have.
     */
    public function nudge(Request $request, Organization $organization, Announcement $announcement): RedirectResponse
    {
        $this->authorizeOrgPermission($request, $organization, 'manage_announcements');

        abort_unless($announcement->organization_id === $organization->id, 404);

        $readUserIds = $announcement->reads()->pluck('user_id');

        $unreadMembers = $organization->members()
            ->where('is_active', true)
            ->whereNotIn('user_id', $readUserIds)
            ->with('user')
            ->get();

        foreach ($unreadMembers as $member) {
            try {
                Mail::to($member->user->email)->send(new AnnouncementReminder($announcement, $member->user->name));
            } catch (\Throwable $e) {
                report($e);
            }
        }

        $count = $unreadMembers->count();

        return back()->with('success', $count > 0
            ? "Reminder sent to {$count} member".($count === 1 ? '' : 's').'.'
            : 'Everyone has already read this announcement.');
    }
}
