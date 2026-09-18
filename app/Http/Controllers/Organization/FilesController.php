<?php

namespace App\Http\Controllers\Organization;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Organization\Concerns\ChecksOrganizationPermission;
use App\Http\Controllers\Organization\Concerns\StampsWatermark;
use App\Models\OrgFile;
use App\Models\Organization;
use App\Models\OrganizationMember;
use App\Support\DocumentPreviewConverter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FilesController extends Controller
{
    use ChecksOrganizationPermission, StampsWatermark;

    protected const MAX_KILOBYTES = 10240;

    protected const ALLOWED_MIMES = 'pdf,doc,docx,xls,xlsx,ppt,pptx,png,jpg,jpeg,gif,zip,csv,txt';

    protected const MAX_BATCH_FILES = 50;

    // Mime types LibreOffice can convert to PDF for in-app preview — public
    // since MicrosoftIntegrationController also gates on it.
    public const CONVERTIBLE_MIMES = [
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.ms-excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'application/vnd.ms-powerpoint',
        'application/vnd.openxmlformats-officedocument.presentationml.presentation',
    ];

    public function index(Request $request, Organization $organization): Response
    {
        $membership = $this->membership($request, $organization);
        $canManage = $membership->hasPermission('manage_files');

        $folder = null;

        if ($request->query('folder')) {
            $folder = OrgFile::where('organization_id', $organization->id)
                ->where('is_folder', true)
                ->findOrFail($request->query('folder'));

            abort_unless($folder->canBeViewedBy($membership), 403);
        }

        $items = $organization->files()
            ->where('parent_id', $folder?->id)
            ->with('uploader')
            ->withCount('children')
            ->orderByDesc('is_folder')
            ->orderBy('name')
            ->get()
            ->filter(fn (OrgFile $file) => $file->canBeViewedBy($membership))
            ->map(fn (OrgFile $file) => $this->formatItem($file, $organization, $membership))
            ->values();

        $breadcrumbs = [];
        $walker = $folder;

        while ($walker) {
            array_unshift($breadcrumbs, ['id' => $walker->id, 'name' => $walker->name]);
            $walker = $walker->parent;
        }

        return Inertia::render('Organizations/Files/Index', [
            'organization' => ['id' => $organization->id, 'name' => $organization->name],
            'folder' => $folder ? [
                'id' => $folder->id,
                'name' => $folder->name,
                'view_permission' => $folder->view_permission,
                'edit_permission' => $folder->edit_permission,
            ] : null,
            'breadcrumbs' => $breadcrumbs,
            'items' => $items,
            'canManage' => $canManage,
            // Root-level uploads/folder-creation always require manage_files
            // (there is no "parent folder" to inherit an edit-tier from at
            // the top level) — must match store()/storeFolder()'s own gate.
            'currentFolderTier' => $folder ? $folder->tierFor($membership) : ($canManage ? 'manage' : null),
            'permissionKeys' => collect(config('permissions.keys'))
                ->map(fn ($label, $key) => ['key' => $key, 'label' => $label])
                ->values(),
            'microsoftConnected' => $organization->microsoftConnection()->exists(),
            'microsoftAccountEmail' => $organization->microsoftConnection?->ms_account_email,
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
        $membership = $this->membership($request, $organization);

        $validated = $request->validate([
            'parent_id' => ['nullable', 'uuid', Rule::exists('org_files', 'id')
                ->where(fn ($q) => $q->where('organization_id', $organization->id)->where('is_folder', true))],
            'files' => ['required', 'array', 'min:1', 'max:'.self::MAX_BATCH_FILES],
            'files.*' => ['file'],
            'relative_paths' => ['nullable', 'array'],
            'relative_paths.*' => ['nullable', 'string', 'max:1024'],
            'is_restricted' => ['boolean'],
            'apply_watermark' => ['boolean'],
            'view_permission' => ['nullable', Rule::in(array_keys(config('permissions.keys')))],
            'edit_permission' => ['nullable', Rule::in(array_keys(config('permissions.keys')))],
        ]);

        $rootParentId = $validated['parent_id'] ?? null;
        $parentFolder = $rootParentId ? OrgFile::findOrFail($rootParentId) : null;

        if ($parentFolder) {
            abort_unless($parentFolder->canBeEditedBy($membership), 403);
        } else {
            abort_unless($membership->hasPermission('manage_files'), 403);
        }

        $viewPermission = array_key_exists('view_permission', $validated) ? $validated['view_permission'] : $parentFolder?->view_permission;
        $editPermission = array_key_exists('edit_permission', $validated) ? $validated['edit_permission'] : $parentFolder?->edit_permission;

        $relativePaths = $validated['relative_paths'] ?? [];
        $isRestricted = (bool) ($validated['is_restricted'] ?? false);
        $applyWatermark = (bool) ($validated['apply_watermark'] ?? false);

        $folderMemo = [];
        $skipped = [];
        $createdCount = 0;

        foreach ($request->file('files', []) as $index => $upload) {
            try {
                $this->validateFile($upload);
            } catch (ValidationException $e) {
                $reason = collect($e->errors())->flatten()->first() ?? 'invalid file';
                $skipped[] = $upload->getClientOriginalName().' ('.$reason.')';

                continue;
            }

            $relativePath = $relativePaths[$index] ?? '';
            $parentId = $relativePath !== ''
                ? $this->resolveFolderPath($organization, $rootParentId, $relativePath, $request->user()->id, $folderMemo, $viewPermission, $editPermission)
                : $rootParentId;

            $mimeType = $upload->getClientMimeType();
            $path = $upload->store("organizations/{$organization->id}/files", 'local');
            $absolutePath = Storage::disk('local')->path($path);

            $watermarked = false;

            if ($applyWatermark && in_array($mimeType, self::WATERMARKABLE_MIMES, true)) {
                $watermarked = $this->stampImageWatermark($absolutePath, $mimeType, $organization);
            } elseif ($applyWatermark && $mimeType === 'application/pdf') {
                $watermarked = $this->stampPdfWatermark($absolutePath, $organization);
            }

            [$previewPath, $previewStatus] = $this->buildPreview($organization, $path, $mimeType);

            $organization->files()->create([
                'parent_id' => $parentId,
                'uploaded_by' => $request->user()->id,
                'name' => $upload->getClientOriginalName(),
                'path' => $path,
                'size' => $upload->getSize(),
                'mime_type' => $mimeType,
                'is_restricted' => $isRestricted,
                'is_watermarked' => $watermarked,
                'view_permission' => $viewPermission,
                'edit_permission' => $editPermission,
                'preview_path' => $previewPath,
                'preview_status' => $previewStatus,
            ]);

            $createdCount++;
        }

        if ($skipped) {
            $message = "{$createdCount} file".($createdCount === 1 ? '' : 's')." uploaded. Skipped: ".implode(', ', $skipped);

            return back()->with('warning', $message);
        }

        return back()->with('success', "{$createdCount} file".($createdCount === 1 ? '' : 's').' uploaded.');
    }

    public function storeFolder(Request $request, Organization $organization): RedirectResponse
    {
        $membership = $this->membership($request, $organization);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'regex:/^[^\/\\\\]+$/'],
            'parent_id' => ['nullable', 'uuid', Rule::exists('org_files', 'id')
                ->where(fn ($q) => $q->where('organization_id', $organization->id)->where('is_folder', true))],
            'view_permission' => ['nullable', Rule::in(array_keys(config('permissions.keys')))],
            'edit_permission' => ['nullable', Rule::in(array_keys(config('permissions.keys')))],
        ]);

        $parentFolder = ($validated['parent_id'] ?? null) ? OrgFile::findOrFail($validated['parent_id']) : null;

        if ($parentFolder) {
            abort_unless($parentFolder->canBeEditedBy($membership), 403);
        } else {
            abort_unless($membership->hasPermission('manage_files'), 403);
        }

        $organization->files()->create([
            'parent_id' => $parentFolder?->id,
            'uploaded_by' => $request->user()->id,
            'name' => trim($validated['name']),
            'is_folder' => true,
            'view_permission' => array_key_exists('view_permission', $validated) ? $validated['view_permission'] : $parentFolder?->view_permission,
            'edit_permission' => array_key_exists('edit_permission', $validated) ? $validated['edit_permission'] : $parentFolder?->edit_permission,
        ]);

        return back()->with('success', 'Folder created.');
    }

    public function updatePermissions(Request $request, Organization $organization, OrgFile $file): RedirectResponse
    {
        $this->authorizeOrgPermission($request, $organization, 'manage_files');

        abort_unless($file->organization_id === $organization->id, 404);

        $validated = $request->validate([
            'view_permission' => ['nullable', Rule::in(array_keys(config('permissions.keys')))],
            'edit_permission' => ['nullable', Rule::in(array_keys(config('permissions.keys')))],
            'cascade' => ['boolean'],
        ]);

        $file->update([
            'view_permission' => $validated['view_permission'] ?? null,
            'edit_permission' => $validated['edit_permission'] ?? null,
        ]);

        if ($file->is_folder && ($validated['cascade'] ?? true)) {
            $this->cascadePermissions($file, $file->view_permission, $file->edit_permission);
        }

        return back()->with('success', 'Permissions updated.');
    }

    protected function cascadePermissions(OrgFile $folder, ?string $viewPermission, ?string $editPermission): void
    {
        foreach ($folder->children()->get() as $child) {
            $child->update(['view_permission' => $viewPermission, 'edit_permission' => $editPermission]);

            if ($child->is_folder) {
                $this->cascadePermissions($child, $viewPermission, $editPermission);
            }
        }
    }

    /**
     * Per-file validation, kept separate from the request-level rules so one
     * bad file in a batch (or a whole uploaded folder) is skipped and
     * reported rather than sinking every other file in the same request.
     */
    protected function validateFile($upload): void
    {
        validator(['file' => $upload], [
            'file' => ['file', 'max:'.self::MAX_KILOBYTES, 'mimes:'.self::ALLOWED_MIMES],
        ])->validate();
    }

    /**
     * Resolves (creating as needed) the folder chain implied by a relative
     * path like "MyFolder/sub/doc.pdf" under $rootParentId, memoized within
     * the request so many files in the same subfolder don't each recreate
     * the same intermediate folder rows. New intermediate folders inherit
     * the same permission tier chosen for this whole upload batch.
     */
    protected function resolveFolderPath(Organization $organization, ?string $rootParentId, string $relativePath, string $userId, array &$memo, ?string $viewPermission, ?string $editPermission): ?string
    {
        $segments = explode('/', $relativePath);
        array_pop($segments); // drop the filename itself

        $parentId = $rootParentId;

        foreach (array_filter($segments, fn ($s) => $s !== '') as $segment) {
            $memoKey = $parentId.'|'.$segment;

            if (! isset($memo[$memoKey])) {
                $memo[$memoKey] = OrgFile::firstOrCreate(
                    [
                        'organization_id' => $organization->id,
                        'parent_id' => $parentId,
                        'is_folder' => true,
                        'name' => $segment,
                    ],
                    [
                        'uploaded_by' => $userId,
                        'view_permission' => $viewPermission,
                        'edit_permission' => $editPermission,
                    ]
                )->id;
            }

            $parentId = $memo[$memoKey];
        }

        return $parentId;
    }

    /**
     * Converts an Office document to a cached PDF for in-app preview.
     * Images and PDFs need no conversion; unsupported types (zip/csv/txt)
     * simply have no preview path.
     */
    protected function buildPreview(Organization $organization, string $path, string $mimeType): array
    {
        if (str_starts_with($mimeType, 'image/') || $mimeType === 'application/pdf') {
            return [null, OrgFile::PREVIEW_NATIVE];
        }

        if (! in_array($mimeType, self::CONVERTIBLE_MIMES, true)) {
            return [null, OrgFile::PREVIEW_UNSUPPORTED];
        }

        $dest = "organizations/{$organization->id}/files/previews/".Str::uuid().'.pdf';
        $ok = DocumentPreviewConverter::convert(Storage::disk('local')->path($path), Storage::disk('local')->path($dest));

        return $ok ? [$dest, OrgFile::PREVIEW_CONVERTED] : [null, OrgFile::PREVIEW_FAILED];
    }

    public function toggleRestriction(Request $request, Organization $organization, OrgFile $file): RedirectResponse
    {
        $this->authorizeOrgPermission($request, $organization, 'manage_files');

        abort_unless($file->organization_id === $organization->id, 404);
        abort_if($file->is_folder, 422, 'Folders cannot be restricted.');

        $file->update(['is_restricted' => ! $file->is_restricted]);

        return back();
    }

    public function rename(Request $request, Organization $organization, OrgFile $file): RedirectResponse
    {
        $membership = $this->membership($request, $organization);

        abort_unless($file->organization_id === $organization->id, 404);
        abort_unless($file->canBeEditedBy($membership), 403);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'regex:/^[^\/\\\\]+$/'],
        ]);

        $newName = trim($validated['name']);

        if (! $file->is_folder) {
            $currentExt = pathinfo($file->name, PATHINFO_EXTENSION);
            $newExt = pathinfo($newName, PATHINFO_EXTENSION);

            if ($currentExt !== '' && $newExt === '') {
                $newName .= '.'.$currentExt;
            }
        }

        $file->update(['name' => $newName]);

        return back()->with('success', ($file->is_folder ? 'Folder' : 'File').' renamed.');
    }

    public function destroy(Request $request, Organization $organization, OrgFile $file): RedirectResponse
    {
        $this->authorizeOrgPermission($request, $organization, 'manage_files');

        abort_unless($file->organization_id === $organization->id, 404);

        $this->deleteRecursively($file);

        return back()->with('success', ($file->is_folder ? 'Folder' : 'File').' deleted.');
    }

    protected function deleteRecursively(OrgFile $file): void
    {
        if ($file->is_folder) {
            foreach ($file->children()->get() as $child) {
                $this->deleteRecursively($child);
            }
        } else {
            Storage::disk('local')->delete($file->path);

            if ($file->preview_path) {
                Storage::disk('local')->delete($file->preview_path);
            }
        }

        $file->delete();
    }

    /**
     * Streams a file inline (browser renders images/PDFs natively where it
     * can) to any member who can see it — restricted files are still
     * previewable by any member who holds the view tier, only downloading
     * them is gated to managers (see download()). Office documents that
     * were successfully converted are served as the cached PDF instead of
     * the raw file, so the preview modal can render real content.
     */
    public function show(Request $request, Organization $organization, OrgFile $file): StreamedResponse
    {
        $membership = $this->membership($request, $organization);

        abort_unless($file->organization_id === $organization->id && ! $file->is_folder, 404);
        abort_unless($file->canBeViewedBy($membership), 403);

        if ($file->preview_status === OrgFile::PREVIEW_CONVERTED && $file->preview_path) {
            return Storage::disk('local')->response($file->preview_path, pathinfo($file->name, PATHINFO_FILENAME).'.pdf');
        }

        return Storage::disk('local')->response($file->path, $file->name);
    }

    /**
     * Forces a download (attachment disposition) of the ORIGINAL file
     * (never the converted preview PDF) — restricted files can only be
     * downloaded by members with manage_files.
     */
    public function download(Request $request, Organization $organization, OrgFile $file): StreamedResponse
    {
        $membership = $this->membership($request, $organization);

        abort_unless($file->organization_id === $organization->id && ! $file->is_folder, 404);
        abort_unless($file->canBeViewedBy($membership), 403);
        abort_if($file->is_restricted && ! $membership->hasPermission('manage_files'), 403);

        return Storage::disk('local')->download($file->path, $file->name);
    }

    protected function formatItem(OrgFile $file, Organization $organization, OrganizationMember $membership): array
    {
        $tier = $file->tierFor($membership);
        $downloadable = ! $file->is_folder && $tier !== null && ($tier === 'manage' || ! $file->is_restricted);

        return [
            'id' => $file->id,
            'name' => $file->name,
            'is_folder' => $file->is_folder,
            'items_count' => $file->is_folder ? $file->children_count : null,
            'preview_url' => $file->is_folder ? null : route('organizations.files.show', [$organization->id, $file->id]),
            'download_url' => $downloadable ? route('organizations.files.download', [$organization->id, $file->id]) : null,
            'size' => $file->is_folder ? null : $this->formatBytes($file->size),
            'mime_type' => $file->mime_type,
            'uploader' => $file->uploader->name,
            'uploaded_at' => $file->created_at->format('M j, Y'),
            'is_restricted' => $file->is_restricted,
            'is_watermarked' => $file->is_watermarked,
            'permission_tier' => $tier,
            'view_permission' => $file->view_permission,
            'edit_permission' => $file->edit_permission,
            'preview_status' => $file->preview_status,
            'ms_connected_file' => $file->ms_drive_item_id !== null,
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
