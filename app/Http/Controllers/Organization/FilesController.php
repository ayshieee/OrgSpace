<?php

namespace App\Http\Controllers\Organization;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Organization\Concerns\ChecksOrganizationPermission;
use App\Models\OrgFile;
use App\Models\Organization;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class FilesController extends Controller
{
    use ChecksOrganizationPermission;

    protected const MAX_KILOBYTES = 10240;

    protected const ALLOWED_MIMES = 'pdf,doc,docx,xls,xlsx,ppt,pptx,png,jpg,jpeg,gif,zip,csv,txt';

    protected const WATERMARKABLE_MIMES = ['image/jpeg', 'image/png'];

    public function index(Request $request, Organization $organization): Response
    {
        $membership = $this->membership($request, $organization);
        $canManage = $membership->hasPermission('manage_files');

        $files = $organization->files()
            ->with('uploader')
            ->latest()
            ->get()
            ->map(fn (OrgFile $file) => $this->formatFile($file, $canManage));

        return Inertia::render('Organizations/Files/Index', [
            'organization' => ['id' => $organization->id, 'name' => $organization->name],
            'files' => $files,
            'canManage' => $canManage,
            'defaults' => [
                'watermark' => (bool) ($organization->settings['files_default_watermark'] ?? false),
                'restricted' => (bool) ($organization->settings['files_default_restricted'] ?? false),
            ],
        ]);
    }

    public function updateDefaults(Request $request, Organization $organization): RedirectResponse
    {
        $this->authorizeOrgPermission($request, $organization, 'manage_files');

        $validated = $request->validate([
            'watermark' => ['boolean'],
            'restricted' => ['boolean'],
        ]);

        $organization->update([
            'settings' => array_merge($organization->settings ?? [], [
                'files_default_watermark' => (bool) ($validated['watermark'] ?? false),
                'files_default_restricted' => (bool) ($validated['restricted'] ?? false),
            ]),
        ]);

        return back();
    }

    public function store(Request $request, Organization $organization): RedirectResponse
    {
        $this->authorizeOrgPermission($request, $organization, 'manage_files');

        $validated = $request->validate([
            'file' => ['required', 'file', 'max:'.self::MAX_KILOBYTES, 'mimes:'.self::ALLOWED_MIMES],
            'is_restricted' => ['boolean'],
            'apply_watermark' => ['boolean'],
        ]);

        $upload = $validated['file'];
        $mimeType = $upload->getClientMimeType();
        $path = $upload->store("organizations/{$organization->id}/files", 'public');

        $watermarked = false;

        if (! empty($validated['apply_watermark']) && in_array($mimeType, self::WATERMARKABLE_MIMES, true)) {
            $watermarked = $this->stampWatermark(Storage::disk('public')->path($path), $mimeType, $organization->name);
        }

        $organization->files()->create([
            'uploaded_by' => $request->user()->id,
            'name' => $upload->getClientOriginalName(),
            'path' => $path,
            'size' => $upload->getSize(),
            'mime_type' => $mimeType,
            'is_restricted' => (bool) ($validated['is_restricted'] ?? false),
            'is_watermarked' => $watermarked,
        ]);

        return back()->with('success', 'File uploaded.');
    }

    public function toggleRestriction(Request $request, Organization $organization, OrgFile $file): RedirectResponse
    {
        $this->authorizeOrgPermission($request, $organization, 'manage_files');

        abort_unless($file->organization_id === $organization->id, 404);

        $file->update(['is_restricted' => ! $file->is_restricted]);

        return back();
    }

    public function destroy(Request $request, Organization $organization, OrgFile $file): RedirectResponse
    {
        $this->authorizeOrgPermission($request, $organization, 'manage_files');

        abort_unless($file->organization_id === $organization->id, 404);

        Storage::disk('public')->delete($file->path);
        $file->delete();

        return back()->with('success', 'File deleted.');
    }

    /**
     * Stamps a real, visible watermark band into the actual image bytes
     * (not just a UI badge) — only for the mime types GD can reliably
     * re-encode. Returns whether it actually happened.
     */
    protected function stampWatermark(string $absolutePath, string $mimeType, string $label): bool
    {
        $image = match ($mimeType) {
            'image/jpeg' => @imagecreatefromjpeg($absolutePath),
            'image/png' => @imagecreatefrompng($absolutePath),
            default => null,
        };

        if (! $image) {
            return false;
        }

        $width = imagesx($image);
        $height = imagesy($image);
        $bandHeight = max(24, (int) round($height * 0.06));

        $overlay = imagecolorallocatealpha($image, 0, 0, 0, 55);
        imagefilledrectangle($image, 0, $height - $bandHeight, $width, $height, $overlay);

        $text = "Watermarked for {$label} — do not redistribute";
        $white = imagecolorallocate($image, 255, 255, 255);
        imagestring($image, 3, 8, $height - $bandHeight + (int) (($bandHeight - 15) / 2), $text, $white);

        $saved = match ($mimeType) {
            'image/jpeg' => imagejpeg($image, $absolutePath, 90),
            'image/png' => imagepng($image, $absolutePath),
            default => false,
        };

        imagedestroy($image);

        return (bool) $saved;
    }

    protected function formatFile(OrgFile $file, bool $canManage): array
    {
        $downloadable = $canManage || ! $file->is_restricted;

        return [
            'id' => $file->id,
            'name' => $file->name,
            'url' => $downloadable ? '/storage/'.$file->path : null,
            'size' => $this->formatBytes($file->size),
            'mime_type' => $file->mime_type,
            'uploader' => $file->uploader->name,
            'uploaded_at' => $file->created_at->format('M j, Y'),
            'is_restricted' => $file->is_restricted,
            'is_watermarked' => $file->is_watermarked,
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
