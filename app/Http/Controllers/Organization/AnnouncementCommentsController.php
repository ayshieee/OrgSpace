<?php

namespace App\Http\Controllers\Organization;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Organization\Concerns\ChecksOrganizationPermission;
use App\Models\Announcement;
use App\Models\AnnouncementComment;
use App\Models\AnnouncementCommentAttachment;
use App\Models\Organization;
use App\Notifications\CommentPosted;
use App\Notifications\CommentReplied;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AnnouncementCommentsController extends Controller
{
    use ChecksOrganizationPermission;

    // Mirrors AnnouncementsController::ALLOWED_MIMES/MAX_ATTACHMENT_KILOBYTES
    // — same allowlist and size cap, kept as its own constant since comment
    // attachments live in a separate table/disk path from announcement ones.
    protected const ALLOWED_MIMES = 'pdf,doc,docx,xls,xlsx,ppt,pptx,png,jpg,jpeg,gif,zip,csv,txt';

    protected const MAX_ATTACHMENT_KILOBYTES = 10240;

    protected const MAX_ATTACHMENTS = 5;

    public function store(Request $request, Organization $organization, Announcement $announcement): RedirectResponse
    {
        $this->membership($request, $organization); // any active member may comment
        abort_unless($announcement->organization_id === $organization->id, 404);

        $validated = $request->validate([
            'body' => ['required', 'string', 'max:2000'],
            'parent_id' => ['nullable', 'uuid', Rule::exists('announcement_comments', 'id')->where('announcement_id', $announcement->id)],
            'link_url' => ['nullable', 'url', 'max:2048'],
            'images' => ['nullable', 'array'],
            'images.*' => ['image', 'max:'.self::MAX_ATTACHMENT_KILOBYTES],
            'files' => ['nullable', 'array'],
            'files.*' => ['file', 'max:'.self::MAX_ATTACHMENT_KILOBYTES, 'mimes:'.self::ALLOWED_MIMES],
        ]);

        $images = $request->file('images', []);
        $files = $request->file('files', []);
        $totalAttachments = count($images) + count($files) + (! empty($validated['link_url']) ? 1 : 0);
        abort_if($totalAttachments > self::MAX_ATTACHMENTS, 422, 'Too many attachments.');

        $parent = null;

        if (! empty($validated['parent_id'])) {
            $parent = AnnouncementComment::findOrFail($validated['parent_id']);

            // A reply can never itself be replied to — the client only ever
            // shows a reply box on top-level comments, but this is the real
            // gate since a request could bypass the UI entirely.
            abort_if($parent->parent_id !== null, 422, 'Cannot reply to a reply.');
        }

        $comment = $announcement->allComments()->create([
            'user_id' => $request->user()->id,
            'parent_id' => $parent?->id,
            'body' => $validated['body'],
        ]);

        $storagePath = "organizations/{$organization->id}/announcements/{$announcement->id}/comments/{$comment->id}";

        foreach ($images as $upload) {
            $path = $upload->store($storagePath, 'local');

            $comment->attachments()->create([
                'uploaded_by' => $request->user()->id,
                'type' => 'image',
                'name' => $upload->getClientOriginalName(),
                'path' => $path,
                'mime_type' => $upload->getClientMimeType(),
                'size' => $upload->getSize(),
            ]);
        }

        foreach ($files as $upload) {
            $path = $upload->store($storagePath, 'local');

            $comment->attachments()->create([
                'uploaded_by' => $request->user()->id,
                'type' => 'file',
                'name' => $upload->getClientOriginalName(),
                'path' => $path,
                'mime_type' => $upload->getClientMimeType(),
                'size' => $upload->getSize(),
            ]);
        }

        if (! empty($validated['link_url'])) {
            $comment->attachments()->create([
                'uploaded_by' => $request->user()->id,
                'type' => 'link',
                'name' => $validated['link_url'],
                'url' => $validated['link_url'],
            ]);
        }

        if ($parent) {
            if ($parent->user_id !== $request->user()->id) {
                $parent->author->notify(new CommentReplied($comment));
            }
        } elseif ($announcement->user_id !== $request->user()->id) {
            $announcement->author->notify(new CommentPosted($comment));
        }

        return back()->with('success', 'Comment posted.');
    }

    public function destroy(Request $request, Organization $organization, Announcement $announcement, AnnouncementComment $comment): RedirectResponse
    {
        $membership = $this->membership($request, $organization);

        abort_unless(
            $announcement->organization_id === $organization->id && $comment->announcement_id === $announcement->id,
            404
        );
        abort_unless($comment->user_id === $request->user()->id || $membership->hasPermission('manage_announcements'), 403);

        $comment->load('replies.attachments', 'attachments');

        foreach ($comment->replies as $reply) {
            foreach ($reply->attachments as $attachment) {
                if ($attachment->path) {
                    Storage::disk('local')->delete($attachment->path);
                }
            }
        }

        foreach ($comment->attachments as $attachment) {
            if ($attachment->path) {
                Storage::disk('local')->delete($attachment->path);
            }
        }

        // Replies cascade-delete at the DB level.
        $comment->delete();

        return back()->with('success', 'Comment deleted.');
    }

    /**
     * Streams one comment attachment to an authenticated, currently-active
     * member of the owning organization only — same private-disk pattern as
     * AnnouncementsController::attachment().
     */
    public function attachment(Request $request, Organization $organization, Announcement $announcement, AnnouncementComment $comment, AnnouncementCommentAttachment $attachment): StreamedResponse
    {
        $this->membership($request, $organization);

        abort_unless(
            $announcement->organization_id === $organization->id
                && $comment->announcement_id === $announcement->id
                && $attachment->announcement_comment_id === $comment->id
                && $attachment->path !== null,
            404
        );

        return Storage::disk('local')->response($attachment->path, $attachment->name);
    }
}
