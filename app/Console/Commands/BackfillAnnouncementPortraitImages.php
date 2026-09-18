<?php

namespace App\Console\Commands;

use App\Models\AnnouncementAttachment;
use App\Support\AnnouncementPortraitImageProcessor;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class BackfillAnnouncementPortraitImages extends Command
{
    protected $signature = 'announcements:backfill-portrait-images';

    protected $description = 'Generates the 1080x1350 portrait rendition for existing announcement image attachments that predate this feature.';

    public function handle(): int
    {
        $processed = 0;
        $failed = 0;

        AnnouncementAttachment::whereNull('processed_path')
            ->where('mime_type', 'like', 'image/%')
            ->chunkById(50, function ($attachments) use (&$processed, &$failed) {
                foreach ($attachments as $attachment) {
                    $sourcePath = Storage::disk('local')->path($attachment->path);

                    if (! is_file($sourcePath)) {
                        $this->warn("Missing source file for attachment {$attachment->id}, skipping.");
                        $failed++;

                        continue;
                    }

                    $directory = dirname($attachment->path);
                    $candidatePath = $directory.'/'.pathinfo($attachment->path, PATHINFO_FILENAME).'-portrait.jpg';

                    $ok = AnnouncementPortraitImageProcessor::process(
                        $sourcePath,
                        $attachment->mime_type,
                        Storage::disk('local')->path($candidatePath)
                    );

                    if ($ok) {
                        $attachment->update(['processed_path' => $candidatePath]);
                        $processed++;
                    } else {
                        $this->warn("Failed to process attachment {$attachment->id}.");
                        $failed++;
                    }
                }
            });

        $this->info("Backfill complete: {$processed} processed, {$failed} failed/skipped.");

        return self::SUCCESS;
    }
}
