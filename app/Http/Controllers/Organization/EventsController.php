<?php

namespace App\Http\Controllers\Organization;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Organization\Concerns\ChecksOrganizationPermission;
use App\Models\Event;
use App\Models\Organization;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class EventsController extends Controller
{
    use ChecksOrganizationPermission;

    public function index(Request $request, Organization $organization): Response
    {
        $membership = $this->membership($request, $organization);
        $canManage = $membership->hasPermission('manage_events');
        $userId = $request->user()->id;

        $events = $organization->events()
            ->with('rsvps.user')
            ->orderBy('starts_at')
            ->get()
            ->map(fn (Event $event) => $this->formatEvent($event, $userId, $canManage));

        return Inertia::render('Organizations/Events/Index', [
            'organization' => ['id' => $organization->id, 'name' => $organization->name],
            'upcoming' => $events->reject(fn ($e) => $e['is_past'])->values(),
            'past' => $events->filter(fn ($e) => $e['is_past'])->sortByDesc('starts_at_raw')->values(),
            'canManage' => $canManage,
        ]);
    }

    public function store(Request $request, Organization $organization): RedirectResponse
    {
        $this->authorizeOrgPermission($request, $organization, 'manage_events');

        $validated = $this->validated($request);

        $organization->events()->create([
            ...$validated,
            'created_by' => $request->user()->id,
        ]);

        return back()->with('success', 'Event created.');
    }

    public function update(Request $request, Organization $organization, Event $event): RedirectResponse
    {
        $this->authorizeOrgPermission($request, $organization, 'manage_events');

        abort_unless($event->organization_id === $organization->id, 404);

        $event->update($this->validated($request));

        return back()->with('success', 'Event updated.');
    }

    public function destroy(Request $request, Organization $organization, Event $event): RedirectResponse
    {
        $this->authorizeOrgPermission($request, $organization, 'manage_events');

        abort_unless($event->organization_id === $organization->id, 404);

        $event->delete();

        return back()->with('success', 'Event deleted.');
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

    protected function validated(Request $request): array
    {
        // Vue leaves untouched optional inputs as '', which fails `date`
        // validation outright — normalize to null so `nullable` applies.
        $request->merge([
            'description' => $request->input('description') ?: null,
            'location' => $request->input('location') ?: null,
            'ends_at' => $request->input('ends_at') ?: null,
        ]);

        return $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'location' => ['nullable', 'string', 'max:255'],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
        ]);
    }

    protected function formatEvent(Event $event, string $userId, bool $canManage): array
    {
        $myRsvp = $event->rsvps->firstWhere('user_id', $userId);

        $payload = [
            'id' => $event->id,
            'title' => $event->title,
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
