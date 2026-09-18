<?php

namespace App\Http\Controllers\Organization;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Organization\Concerns\ChecksOrganizationPermission;
use App\Mail\AnnouncementReminder;
use App\Models\Announcement;
use App\Models\AnnouncementAttachment;
use App\Models\AnnouncementComment;
use App\Models\AnnouncementRead;
use App\Models\Organization;
use App\Notifications\AnnouncementNudge;
use App\Notifications\AnnouncementPinned;
use App\Support\AnnouncementHtmlSanitizer;
use App\Support\AnnouncementPortraitImageProcessor;
use App\Support\CommentTextLinkifier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AnnouncementsController extends Controller
{
    use ChecksOrganizationPermission;

    // Mirrors FilesController::ALLOWED_MIMES — same allowlist, kept as its
    // own constant here since attachments are stored and served completely
    // differently (private disk, authenticated route) from the Files module.
    protected const ALLOWED_MIMES = 'pdf,doc,docx,xls,xlsx,ppt,pptx,png,jpg,jpeg,gif,zip,csv,txt';

    protected const MAX_ATTACHMENT_KILOBYTES = 10240;

    public function index(Request $request, Organization $organization): Response
    {
        $membership = $this->membership($request, $organization);
        $canManage = $membership->hasPermission('manage_announcements');
        $user = $request->user();

        $announcements = $organization->announcements()
            ->with([
                'author', 'reads', 'attachments', 'reactions',
                'comments.author', 'comments.attachments',
                'comments.replies.author', 'comments.replies.attachments',
            ])
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

        $announcements = $announcements->map(function (Announcement $a) use ($canManage, $totalMembers, $allMembers, $user, $newReads, $organization) {
            $readByMe = $a->reads->contains('user_id', $user->id) || $newReads->contains('announcement_id', $a->id);

            $payload = [
                'id' => $a->id,
                'title' => $a->title,
                'body' => $a->body,
                'is_pinned' => $a->is_pinned,
                'is_important' => $a->is_important,
                'author' => $a->author->name,
                'posted_at' => $a->created_at->diffForHumans(),
                'read_by_me' => $readByMe,
                'link_url' => $a->link_url,
                'attachments' => $a->attachments->map(fn ($att) => [
                    'id' => $att->id,
                    'name' => $att->name,
                    'mime_type' => $att->mime_type,
                    'size' => $this->formatBytes($att->size),
                    'url' => route('organizations.announcements.attachments.show', [$organization->id, $a->id, $att->id]),
                    'processed_url' => $att->processed_path
                        ? route('organizations.announcements.attachments.processed', [$organization->id, $a->id, $att->id])
                        : null,
                ])->values(),
                'reaction_count' => $a->reactions->count(),
                'reacted_by_me' => $a->reactions->contains('user_id', $user->id),
                'comments' => $a->comments->map(fn (AnnouncementComment $c) => $this->formatComment($c, $organization, $a, $canManage, $user))->values(),
                'comment_count' => $a->comments->sum(fn ($c) => 1 + $c->replies->count()),
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
            'body' => ['required', 'string', 'max:20000'],
            'is_pinned' => ['boolean'],
            'is_important' => ['boolean'],
            'link_url' => ['nullable', 'url', 'max:2048'],
            'attachments' => ['nullable', 'array', 'max:5'],
            'attachments.*' => ['file', 'max:'.self::MAX_ATTACHMENT_KILOBYTES, 'mimes:'.self::ALLOWED_MIMES],
        ]);

        $announcement = $organization->announcements()->create([
            'user_id' => $request->user()->id,
            'title' => $validated['title'],
            'body' => AnnouncementHtmlSanitizer::sanitize($validated['body']),
            'is_pinned' => $validated['is_pinned'] ?? false,
            'is_important' => $validated['is_important'] ?? false,
            'link_url' => $validated['link_url'] ?? null,
        ]);

        foreach ($request->file('attachments', []) as $upload) {
            $directory = "organizations/{$organization->id}/announcements/{$announcement->id}";
            $path = $upload->store($directory, 'local');
            $mimeType = $upload->getClientMimeType();

            $processedPath = null;

            if (str_starts_with($mimeType, 'image/')) {
                $candidatePath = $directory.'/'.pathinfo($path, PATHINFO_FILENAME).'-portrait.jpg';

                $processed = AnnouncementPortraitImageProcessor::process(
                    Storage::disk('local')->path($path),
                    $mimeType,
                    Storage::disk('local')->path($candidatePath)
                );

                if ($processed) {
                    $processedPath = $candidatePath;
                }
            }

            $announcement->attachments()->create([
                'uploaded_by' => $request->user()->id,
                'name' => $upload->getClientOriginalName(),
                'path' => $path,
                'processed_path' => $processedPath,
                'mime_type' => $mimeType,
                'size' => $upload->getSize(),
            ]);
        }

        if ($announcement->is_pinned) {
            $recipients = $organization->members()
                ->where('is_active', true)
                ->where('user_id', '!=', $request->user()->id)
                ->with('user')
                ->get()
                ->pluck('user');

            Notification::send($recipients, new AnnouncementPinned($announcement));
        }

        return back()->with('success', 'Announcement posted.');
    }

    public function update(Request $request, Organization $organization, Announcement $announcement): RedirectResponse
    {
        $this->authorizeOrgPermission($request, $organization, 'manage_announcements');

        abort_unless($announcement->organization_id === $organization->id, 404);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string', 'max:20000'],
            'is_pinned' => ['boolean'],
            'is_important' => ['boolean'],
        ]);

        $announcement->update([
            'title' => $validated['title'],
            'body' => AnnouncementHtmlSanitizer::sanitize($validated['body']),
            'is_pinned' => $validated['is_pinned'] ?? false,
            'is_important' => $validated['is_important'] ?? false,
        ]);

        return back()->with('success', 'Announcement updated.');
    }

    public function destroy(Request $request, Organization $organization, Announcement $announcement): RedirectResponse
    {
        $this->authorizeOrgPermission($request, $organization, 'manage_announcements');

        abort_unless($announcement->organization_id === $organization->id, 404);

        foreach ($announcement->attachments as $attachment) {
            Storage::disk('local')->delete($attachment->path);

            if ($attachment->processed_path) {
                Storage::disk('local')->delete($attachment->processed_path);
            }
        }

        $announcement->delete();

        return back()->with('success', 'Announcement deleted.');
    }

    /**
     * Streams one attachment to an authenticated, currently-active member
     * of the owning organization only — attachments live on the `local`
     * disk (storage/app/private, no public symlink), so this route is the
     * only way to ever reach the file. Unlike the Files module's public-disk
     * URLs, there is no unauthenticated path to this content at all.
     */
    public function attachment(Request $request, Organization $organization, Announcement $announcement, AnnouncementAttachment $attachment): StreamedResponse
    {
        $this->membership($request, $organization);

        abort_unless(
            $announcement->organization_id === $organization->id && $attachment->announcement_id === $announcement->id,
            404
        );

        return Storage::disk('local')->response($attachment->path, $attachment->name);
    }

    /**
     * Streams the standardized 1080×1350 portrait rendition of an image
     * attachment — same auth chain as attachment(), just a different file.
     */
    public function attachmentProcessed(Request $request, Organization $organization, Announcement $announcement, AnnouncementAttachment $attachment): StreamedResponse
    {
        $this->membership($request, $organization);

        abort_unless(
            $announcement->organization_id === $organization->id
                && $attachment->announcement_id === $announcement->id
                && $attachment->processed_path !== null,
            404
        );

        return Storage::disk('local')->response($attachment->processed_path, $attachment->name);
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

            $member->user->notify(new AnnouncementNudge($announcement));
        }

        $count = $unreadMembers->count();

        return back()->with('success', $count > 0
            ? "Reminder sent to {$count} member".($count === 1 ? '' : 's').'.'
            : 'Everyone has already read this announcement.');
    }

    /**
     * Recurses exactly one level into replies — a reply's own `replies`
     * collection is always empty (the store endpoint refuses to nest a
     * reply under a reply), so this never recurses further than that.
     */
    protected function formatComment(AnnouncementComment $comment, Organization $organization, Announcement $announcement, bool $canManage, $user): array
    {
        return [
            'id' => $comment->id,
            'body' => CommentTextLinkifier::linkify($comment->body),
            'author' => [
                'id' => $comment->author->id,
                'name' => $comment->author->name,
                'avatar_path' => $comment->author->avatar_path,
            ],
            'posted_at' => $comment->created_at->diffForHumans(),
            'can_delete' => $comment->user_id === $user->id || $canManage,
            'attachments' => $comment->attachments->map(fn ($att) => [
                'id' => $att->id,
                'type' => $att->type,
                'name' => $att->name,
                'mime_type' => $att->mime_type,
                'size' => $att->size ? $this->formatBytes($att->size) : null,
                'url' => $att->type === 'link'
                    ? $att->url
                    : route('organizations.announcements.comments.attachments.show', [$organization->id, $announcement->id, $comment->id, $att->id]),
            ])->values(),
            'replies' => $comment->replies->map(fn (AnnouncementComment $r) => $this->formatComment($r, $organization, $announcement, $canManage, $user))->values(),
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
