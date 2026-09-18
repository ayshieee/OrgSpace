<?php

namespace App\Console\Commands;

use App\Models\OrgFile;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class MigrateOrgFilesToPrivateDisk extends Command
{
    protected $signature = 'files:migrate-to-private-disk';

    protected $description = 'One-off backfill: copies existing OrgFile uploads from the public disk to the private local disk, fixing files that predate the private-disk security rework.';

    public function handle(): int
    {
        $moved = 0;
        $alreadyMigrated = 0;
        $missing = 0;

        OrgFile::where('is_folder', false)
            ->whereNotNull('path')
            ->chunkById(50, function ($files) use (&$moved, &$alreadyMigrated, &$missing) {
                foreach ($files as $file) {
                    if (Storage::disk('local')->exists($file->path)) {
                        $alreadyMigrated++;

                        continue;
                    }

                    if (! Storage::disk('public')->exists($file->path)) {
                        $this->warn("Missing source for file {$file->id} ({$file->name}), skipping.");
                        $missing++;

                        continue;
                    }

                    Storage::disk('local')->put($file->path, Storage::disk('public')->get($file->path));
                    Storage::disk('public')->delete($file->path);
                    $moved++;
                }
            });

        $this->info("Migration complete: {$moved} moved, {$alreadyMigrated} already private, {$missing} missing.");

        return self::SUCCESS;
    }
}
