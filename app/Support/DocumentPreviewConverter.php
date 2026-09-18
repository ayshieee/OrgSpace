<?php

namespace App\Support;

use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

/**
 * Converts an Office document to PDF via a headless LibreOffice, so the
 * app's existing PDF preview (a plain <iframe>, the browser's own native
 * viewer) can be reused for DOCX/XLS/PPTX instead of building a separate
 * renderer per format. This is a system-level dependency (the `soffice`
 * binary), not a Composer/npm package — see config('services.libreoffice').
 */
class DocumentPreviewConverter
{
    public static function convert(string $sourceAbsolutePath, string $destAbsolutePath): bool
    {
        $outDir = dirname($destAbsolutePath);

        $process = new Process([
            config('services.libreoffice.binary'),
            '--headless',
            '--convert-to', 'pdf',
            '--outdir', $outDir,
            $sourceAbsolutePath,
        ]);
        $process->setTimeout((int) config('services.libreoffice.timeout', 60));

        try {
            $process->mustRun();
        } catch (ProcessFailedException|\Throwable $e) {
            Log::warning('LibreOffice conversion failed', [
                'source' => $sourceAbsolutePath,
                'error' => $e->getMessage(),
            ]);

            return false;
        }

        // LibreOffice names its output after the source filename, not our
        // chosen destination — locate and rename it into place.
        $producedPath = $outDir.DIRECTORY_SEPARATOR.pathinfo($sourceAbsolutePath, PATHINFO_FILENAME).'.pdf';

        if (! is_file($producedPath)) {
            Log::warning('LibreOffice conversion produced no output file', ['source' => $sourceAbsolutePath]);

            return false;
        }

        return rename($producedPath, $destAbsolutePath);
    }
}
