<?php

namespace App\Http\Controllers\Organization;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Organization\Concerns\ChecksOrganizationPermission;
use App\Http\Controllers\Organization\Concerns\StampsWatermark;
use App\Models\MusicEntry;
use App\Models\Organization;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class MusicController extends Controller
{
    use ChecksOrganizationPermission, StampsWatermark;

    protected const MAX_KILOBYTES = 20480;

    protected const ALLOWED_MIMES = 'pdf,png,jpg,jpeg';

    public function index(Request $request, Organization $organization): Response
    {
        $membership = $this->membership($request, $organization);
        $canManage = $membership->hasPermission('manage_library');

        $favoritedIds = $membership->favoritedMusic()->pluck('music_entries.id')->all();

        $entries = $organization->musicEntries()
            ->with('uploader')
            ->latest()
            ->get()
            ->map(fn (MusicEntry $entry) => $this->formatEntry($entry, $favoritedIds, $canManage));

        return Inertia::render('Organizations/Music/Index', [
            'organization' => ['id' => $organization->id, 'name' => $organization->name],
            'entries' => $entries,
            'canManage' => $canManage,
            'defaults' => [
                'watermark' => (bool) ($organization->settings['music_default_watermark'] ?? false),
                'restricted' => (bool) ($organization->settings['music_default_restricted'] ?? false),
            ],
        ]);
    }

    public function updateDefaults(Request $request, Organization $organization): RedirectResponse
    {
        $this->authorizeOrgPermission($request, $organization, 'manage_library');

        $validated = $request->validate([
            'watermark' => ['boolean'],
            'restricted' => ['boolean'],
        ]);

        $organization->update([
            'settings' => array_merge($organization->settings ?? [], [
                'music_default_watermark' => (bool) ($validated['watermark'] ?? false),
                'music_default_restricted' => (bool) ($validated['restricted'] ?? false),
            ]),
        ]);

        return back();
    }

    public function store(Request $request, Organization $organization): RedirectResponse
    {
        $this->authorizeOrgPermission($request, $organization, 'manage_library');

        $validated = $request->validate([
            'file' => ['required', 'file', 'max:'.self::MAX_KILOBYTES, 'mimes:'.self::ALLOWED_MIMES],
            'title' => ['required', 'string', 'max:255'],
            'composer' => ['nullable', 'string', 'max:255'],
            'arranger' => ['nullable', 'string', 'max:255'],
            'section' => ['nullable', 'string', 'max:255'],
            'category' => ['nullable', 'string', 'max:255'],
            'is_restricted' => ['boolean'],
            'apply_watermark' => ['boolean'],
        ]);

        $upload = $validated['file'];
        $mimeType = $upload->getClientMimeType();
        $path = $upload->store("organizations/{$organization->id}/music", 'public');

        $watermarked = false;

        if (! empty($validated['apply_watermark']) && in_array($mimeType, self::WATERMARKABLE_MIMES, true)) {
            $watermarked = $this->stampWatermark(Storage::disk('public')->path($path), $mimeType, $organization->name);
        }

        $organization->musicEntries()->create([
            'uploaded_by' => $request->user()->id,
            'title' => $validated['title'],
            'composer' => $validated['composer'] ?? null,
            'arranger' => $validated['arranger'] ?? null,
            'section' => $validated['section'] ?? null,
            'category' => $validated['category'] ?? null,
            'file_path' => $path,
            'file_type' => $mimeType,
            'file_size' => $upload->getSize(),
            'is_restricted' => (bool) ($validated['is_restricted'] ?? false),
            'is_watermarked' => $watermarked,
        ]);

        return back()->with('success', 'Score uploaded.');
    }

    public function toggleRestriction(Request $request, Organization $organization, MusicEntry $music): RedirectResponse
    {
        $this->authorizeOrgPermission($request, $organization, 'manage_library');

        abort_unless($music->organization_id === $organization->id, 404);

        $music->update(['is_restricted' => ! $music->is_restricted]);

        return back();
    }

    public function destroy(Request $request, Organization $organization, MusicEntry $music): RedirectResponse
    {
        $this->authorizeOrgPermission($request, $organization, 'manage_library');

        abort_unless($music->organization_id === $organization->id, 404);

        Storage::disk('public')->delete($music->file_path);
        $music->delete();

        return back()->with('success', 'Score removed.');
    }

    public function toggleFavorite(Request $request, Organization $organization, MusicEntry $music): RedirectResponse
    {
        $membership = $this->membership($request, $organization);

        abort_unless($music->organization_id === $organization->id, 404);

        if ($membership->favoritedMusic()->where('music_entries.id', $music->id)->exists()) {
            $membership->favoritedMusic()->detach($music->id);
        } else {
            $membership->favoritedMusic()->attach($music->id);
        }

        return back();
    }

    public function updateAnnotations(Request $request, Organization $organization, MusicEntry $music): RedirectResponse
    {
        // Any active member may annotate — this is a shared practice score,
        // not an admin-only action. Members can view and mark up their
        // section's music even without manage_library.
        $this->membership($request, $organization);

        abort_unless($music->organization_id === $organization->id, 404);

        $validated = $request->validate([
            'annotations' => ['required', 'array'],
        ]);

        $music->update(['annotations' => $validated['annotations']]);

        return back();
    }

    protected function formatEntry(MusicEntry $entry, array $favoritedIds, bool $canManage): array
    {
        $downloadable = $canManage || ! $entry->is_restricted;

        return [
            'id' => $entry->id,
            'title' => $entry->title,
            'composer' => $entry->composer,
            'arranger' => $entry->arranger,
            'section' => $entry->section,
            'category' => $entry->category,
            'url' => $downloadable ? '/storage/'.$entry->file_path : null,
            'file_type' => $entry->file_type,
            'size' => $this->formatBytes($entry->file_size),
            'uploader' => $entry->uploader->name,
            'uploaded_at' => $entry->created_at->format('M j, Y'),
            'is_restricted' => $entry->is_restricted,
            'is_watermarked' => $entry->is_watermarked,
            'annotations' => $entry->annotations ?? [],
            'is_favorited' => in_array($entry->id, $favoritedIds, true),
        ];
    }

    protected function formatBytes(int $bytes): string
    {
        if ($bytes < 1024) {
            return "{$bytes} B";
        }

        if ($bytes < 1048576) {
            return round($bytes / 1024, 1).' KB';
        }

        return round($bytes / 1048576, 1).' MB';
    }
}
