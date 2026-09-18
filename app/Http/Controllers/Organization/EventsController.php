<?php

namespace App\Http\Controllers\Organization;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Organization\Concerns\ChecksOrganizationPermission;
use App\Models\AttendanceSession;
use App\Models\Event;
use App\Models\EventChecklistItem;
use App\Models\Organization;
use App\Models\OrganizationMember;
use App\Notifications\EventCallSheetBroadcast;
use App\Notifications\EventRsvpNudge;
use App\Support\AnnouncementHtmlSanitizer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class EventsController extends Controller
{
    use ChecksOrganizationPermission;

    protected const IMAGE_MIMES = 'jpg,jpeg,png,webp';

    protected const MAX_IMAGE_KILOBYTES = 5120;

    public function index(Request $request, Organization $organization): Response
    {
        $membership = $this->membership($request, $organization);
        $canManage = $membership->hasPermission('manage_events');

        $userId = $request->user()->id;

        $events = $organization->events()
            ->with('rsvps.user')
            ->orderBy('starts_at')
            ->get()
            ->map(fn (Event $event) => $this->formatEvent($event, $organization, $userId, $canManage));

        return Inertia::render('Organizations/Events/Index', [
            'organization' => ['id' => $organization->id, 'name' => $organization->name],
            'upcoming' => $events->reject(fn ($e) => $e['is_past'])->values(),
            'past' => $events->filter(fn ($e) => $e['is_past'])->sortByDesc('starts_at_raw')->values(),
            'canManage' => $canManage,
            'canManageAttendance' => $membership->hasPermission('manage_attendance'),
            'attendanceModuleEnabled' => $organization->hasModuleEnabled('attendance'),
        ]);
    }

    public function store(Request $request, Organization $organization): RedirectResponse
    {
        $membership = $this->authorizeOrgPermission($request, $organization, 'manage_events');

        $validated = $this->validated($request);

        $imagePath = null;

        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store("organizations/{$organization->id}/events", 'local');
        }

        $event = $organization->events()->create([
            'title' => $validated['title'],
            'category' => $validated['category'],
            'description' => AnnouncementHtmlSanitizer::sanitize($validated['description'] ?? ''),
            'location' => $validated['location'],
            'starts_at' => $validated['starts_at'],
            'call_time' => $validated['call_time'],
            'ends_at' => $validated['ends_at'],
            'track_attendance' => $validated['track_attendance'] ?? false,
            'image_path' => $imagePath,
            'created_by' => $request->user()->id,
        ]);

        $canAutoCreateSession = $membership->hasPermission('manage_attendance') && $organization->hasModuleEnabled('attendance');

        if (($validated['track_attendance'] ?? false) && $canAutoCreateSession) {
            $this->createAttendanceSessionForEvent($organization, $event, $request->user()->id);
        }

        return back()->with('success', 'Event created.');
    }

    public function update(Request $request, Organization $organization, Event $event): RedirectResponse
    {
        $membership = $this->authorizeOrgPermission($request, $organization, 'manage_events');

        abort_unless($event->organization_id === $organization->id, 404);

        $validated = $this->validated($request);

        $imagePath = $event->image_path;

        if ($request->hasFile('image')) {
            if ($event->image_path) {
                Storage::disk('local')->delete($event->image_path);
            }
            $imagePath = $request->file('image')->store("organizations/{$organization->id}/events", 'local');
        } elseif ($request->boolean('remove_image')) {
            if ($event->image_path) {
                Storage::disk('local')->delete($event->image_path);
            }
            $imagePath = null;
        }

        $event->update([
            'title' => $validated['title'],
            'category' => $validated['category'],
            'description' => AnnouncementHtmlSanitizer::sanitize($validated['description'] ?? ''),
            'location' => $validated['location'],
            'starts_at' => $validated['starts_at'],
            'call_time' => $validated['call_time'],
            'ends_at' => $validated['ends_at'],
            'track_attendance' => $validated['track_attendance'] ?? false,
            'image_path' => $imagePath,
        ]);

        // Flipping the toggle on only ever creates a session if the event
        // doesn't already have one (avoids duplicates from off/on/off
        // cycles). Flipping it off never deletes an existing session or its
        // records — attendance/check-in data is historical and destroying
        // it must stay an explicit action in the Attendance module itself.
        $canAutoCreateSession = $membership->hasPermission('manage_attendance') && $organization->hasModuleEnabled('attendance');

        if (($validated['track_attendance'] ?? false) && $canAutoCreateSession && ! $event->attendanceSessions()->exists()) {
            $this->createAttendanceSessionForEvent($organization, $event, $request->user()->id);
        }

        return back()->with('success', 'Event updated.');
    }

    public function destroy(Request $request, Organization $organization, Event $event): RedirectResponse
    {
        $this->authorizeOrgPermission($request, $organization, 'manage_events');

        abort_unless($event->organization_id === $organization->id, 404);

        if ($event->image_path) {
            Storage::disk('local')->delete($event->image_path);
        }

        $event->delete();

        // Always the index, not back() — this can be called from the event's
        // own Show page, which would otherwise try to reload a now-404 URL.
        return redirect()->route('organizations.events.index', $organization->id)->with('success', 'Event deleted.');
    }

    public function rsvp(Request $request, Organization $organization, Event $event): RedirectResponse
    {
        $this->membership($request, $organization);

        abort_unless($event->organization_id === $organization->id, 404);

        $validated = $request->validate([
            'status' => ['required', 'in:going,maybe,not_going'],
        ]);

        $event->rsvps()->updateOrCreate(
            ['user_id' => $request->user()->id],
            ['status' => $validated['status']]
        );

        return back()->with('success', "RSVP saved.");
    }

    /**
     * Streams the event's cover image behind the same membership check as
     * every other org-scoped resource — never a public/guessable URL.
     */
    public function image(Request $request, Organization $organization, Event $event): StreamedResponse
    {
        $this->membership($request, $organization);

        abort_unless($event->organization_id === $organization->id && $event->image_path, 404);

        return Storage::disk('local')->response($event->image_path);
    }

    public function show(Request $request, Organization $organization, Event $event): Response
    {
        $membership = $this->membership($request, $organization);
        $canManage = $membership->hasPermission('manage_events');

        abort_unless($event->organization_id === $organization->id, 404);

        $event->load('rsvps.user');

        $userId = $request->user()->id;
        $payload = $this->formatEvent($event, $organization, $userId, $canManage);

        $activeMemberCount = $organization->members()->where('is_active', true)->count();
        $confirmed = $event->rsvps->where('status', 'going')->count();
        $excused = $event->rsvps->where('status', 'not_going')->count();

        $payload['clearance'] = [
            'active_member_count' => $activeMemberCount,
            'confirmed' => $confirmed,
            'excused' => $excused,
            'awaiting' => max(0, $activeMemberCount - $confirmed - $excused),
        ];

        $payload['sessions'] = $event->attendanceSessions()
            ->where('session_date', '>=', now()->toDateString())
            ->withCount([
                'records as present_count' => fn ($q) => $q->where('status', 'present'),
                'records as excused_count' => fn ($q) => $q->where('status', 'excused'),
                'records as absent_count' => fn ($q) => $q->where('status', 'absent'),
            ])
            ->orderBy('session_date')
            ->get()
            ->map(function (AttendanceSession $s) use ($organization) {
                $status = $s->status();

                return [
                    'id' => $s->id,
                    'title' => $s->title,
                    'description' => $s->description,
                    'session_date' => $s->session_date->format('M j, Y'),
                    'start_time' => $s->start_time,
                    'end_time' => $s->end_time,
                    'status' => $status,
                    'is_closed' => $s->isClosed(),
                    'is_live' => $status === 'active',
                    'present_count' => $s->present_count,
                    'excused_count' => $s->excused_count,
                    'absent_count' => $s->absent_count,
                    'show_url' => route('organizations.attendance.show', [$organization->id, $s->id]),
                ];
            })
            ->values();

        $payload['checklist_items'] = $event->checklistItems->map(fn (EventChecklistItem $item) => [
            'id' => $item->id,
            'label' => $item->label,
            'status' => $item->status,
            'sort_order' => $item->sort_order,
        ])->values();

        if ($canManage) {
            $rsvpsByUserId = $event->rsvps->keyBy('user_id');

            $payload['roster'] = $organization->members()
                ->where('is_active', true)
                ->with('user')
                ->get()
                ->sortBy(fn (OrganizationMember $m) => $m->user->name)
                ->map(function (OrganizationMember $m) use ($rsvpsByUserId) {
                    $rsvp = $rsvpsByUserId->get($m->user_id);

                    return [
                        'member_id' => $m->id,
                        'user_id' => $m->user_id,
                        'name' => $m->user->name,
                        'section' => $m->section,
                        'membership_number' => $m->membership_number,
                        'email' => $m->user->email,
                        'avatar_path' => $m->user->avatar_path,
                        'status' => $rsvp?->status,
                    ];
                })
                ->values();
        }

        return Inertia::render('Organizations/Events/Show', [
            'organization' => ['id' => $organization->id, 'name' => $organization->name],
            'event' => $payload,
            'canManage' => $canManage,
            'canManageAttendance' => $membership->hasPermission('manage_attendance'),
            'attendanceModuleEnabled' => $organization->hasModuleEnabled('attendance'),
        ]);
    }

    public function broadcastCallSheet(Request $request, Organization $organization, Event $event): RedirectResponse
    {
        $this->authorizeOrgPermission($request, $organization, 'manage_events');

        abort_unless($event->organization_id === $organization->id, 404);

        $members = $organization->members()->where('is_active', true)->with('user')->get();

        foreach ($members as $member) {
            $member->user->notify(new EventCallSheetBroadcast($event));
        }

        return back()->with('success', "Call sheet sent to {$members->count()} members.");
    }

    public function nudgeRsvp(Request $request, Organization $organization, Event $event, OrganizationMember $member): RedirectResponse
    {
        $this->authorizeOrgPermission($request, $organization, 'manage_events');

        abort_unless($event->organization_id === $organization->id && $member->organization_id === $organization->id, 404);

        $member->user->notify(new EventRsvpNudge($event));

        return back()->with('success', "Reminder sent to {$member->user->name}.");
    }

    public function storeChecklistItem(Request $request, Organization $organization, Event $event): RedirectResponse
    {
        $this->authorizeOrgPermission($request, $organization, 'manage_events');

        abort_unless($event->organization_id === $organization->id, 404);

        $validated = $request->validate([
            'label' => ['required', 'string', 'max:255'],
            'status' => ['nullable', 'in:pending,in_progress,cleared'],
        ]);

        $nextOrder = ($event->checklistItems()->max('sort_order') ?? -1) + 1;

        $event->checklistItems()->create([
            'label' => $validated['label'],
            'status' => $validated['status'] ?? 'pending',
            'sort_order' => $nextOrder,
            'created_by' => $request->user()->id,
        ]);

        return back()->with('success', 'Checklist item added.');
    }

    public function updateChecklistItem(Request $request, Organization $organization, Event $event, EventChecklistItem $item): RedirectResponse
    {
        $this->authorizeOrgPermission($request, $organization, 'manage_events');

        abort_unless($event->organization_id === $organization->id && $item->event_id === $event->id, 404);

        $validated = $request->validate([
            'label' => ['sometimes', 'required', 'string', 'max:255'],
            'status' => ['sometimes', 'required', 'in:pending,in_progress,cleared'],
            'sort_order' => ['sometimes', 'integer', 'min:0'],
        ]);

        $item->update($validated);

        return back()->with('success', 'Checklist item updated.');
    }

    public function destroyChecklistItem(Request $request, Organization $organization, Event $event, EventChecklistItem $item): RedirectResponse
    {
        $this->authorizeOrgPermission($request, $organization, 'manage_events');

        abort_unless($event->organization_id === $organization->id && $item->event_id === $event->id, 404);

        $item->delete();

        return back()->with('success', 'Checklist item removed.');
    }

    protected function createAttendanceSessionForEvent(Organization $organization, Event $event, string $userId): AttendanceSession
    {
        return AttendanceSession::createWithRoster($organization, [
            'title' => $event->title,
            'description' => null,
            'session_date' => $event->starts_at->toDateString(),
            'start_time' => $event->call_time ?? $event->starts_at->format('H:i'),
            'end_time' => $event->ends_at?->format('H:i'),
            'event_id' => $event->id,
            'created_by' => $userId,
        ]);
    }

    protected function validated(Request $request): array
    {
        // Vue leaves untouched optional inputs as '', which fails `date`
        // validation outright — normalize to null so `nullable` applies.
        $request->merge([
            'description' => $request->input('description') ?: null,
            'location' => $request->input('location') ?: null,
            'ends_at' => $request->input('ends_at') ?: null,
            'category' => $request->input('category') ?: null,
            'call_time' => $request->input('call_time') ?: null,
        ]);

        return $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'category' => ['nullable', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:20000'],
            'location' => ['nullable', 'string', 'max:255'],
            'starts_at' => ['required', 'date'],
            'call_time' => ['nullable', 'date_format:H:i'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'image' => ['nullable', 'file', 'image', 'mimes:'.self::IMAGE_MIMES, 'max:'.self::MAX_IMAGE_KILOBYTES],
            'remove_image' => ['boolean'],
            'track_attendance' => ['boolean'],
        ]);
    }

    protected function formatEvent(Event $event, Organization $organization, string $userId, bool $canManage): array
    {
        $myRsvp = $event->rsvps->firstWhere('user_id', $userId);

        $payload = [
            'id' => $event->id,
            'title' => $event->title,
            'category' => $event->category,
            'call_time' => $event->call_time,
            'description' => $event->description,
            'location' => $event->location,
            'starts_at' => $event->starts_at->format('D, M j, Y \a\t g:i A'),
            'starts_at_input' => $event->starts_at->format('Y-m-d\TH:i'),
            'ends_at_input' => $event->ends_at?->format('Y-m-d\TH:i'),
            'starts_at_raw' => $event->starts_at->toIso8601String(),
            'is_past' => $event->isPast(),
            'my_rsvp' => $myRsvp?->status,
            'going_count' => $event->rsvps->where('status', 'going')->count(),
            'maybe_count' => $event->rsvps->where('status', 'maybe')->count(),
            'image_url' => $event->image_path ? route('organizations.events.image', [$organization->id, $event->id]) : null,
            'track_attendance' => $event->track_attendance,
        ];

        if ($canManage) {
            $payload['rsvp_list'] = $event->rsvps
                ->sortBy(fn ($r) => $r->user->name)
                ->map(fn ($r) => ['name' => $r->user->name, 'status' => $r->status])
                ->values();
        }

        return $payload;
    }
}
