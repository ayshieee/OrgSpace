<?php

namespace App\Support;

/**
 * Standardizes an uploaded announcement image to a 1080×1350 (4:5 portrait)
 * output file without cropping or distorting the original content: the
 * source is scaled to *contain* the canvas (full image, no crop) and
 * composited sharp over a blurred, cover-scaled copy of the same image
 * filling the leftover space — an Instagram-style backdrop rather than a
 * flat color. Built on plain GD (already enabled, no new dependency),
 * matching the existing hand-rolled-GD precedent in StampsWatermark.
 */
class AnnouncementPortraitImageProcessor
{
    protected const CANVAS_WIDTH = 1080;

    protected const CANVAS_HEIGHT = 1350;

    protected const BLUR_PASSES = 20;

    protected const JPEG_QUALITY = 90;

    public static function process(string $sourceAbsolutePath, string $mimeType, string $destAbsolutePath): bool
    {
        $source = match ($mimeType) {
            'image/jpeg' => @imagecreatefromjpeg($sourceAbsolutePath),
            'image/png' => @imagecreatefrompng($sourceAbsolutePath),
            'image/gif' => @imagecreatefromgif($sourceAbsolutePath),
            default => null,
        };

        if (! $source) {
            return false;
        }

        // GD decoding a large truecolor photo can exceed the app's default
        // memory_limit; scoped to this call only, restored right after.
        $previousLimit = ini_get('memory_limit');
        ini_set('memory_limit', '256M');

        $srcWidth = imagesx($source);
        $srcHeight = imagesy($source);

        $canvas = imagecreatetruecolor(self::CANVAS_WIDTH, self::CANVAS_HEIGHT);

        self::paintBlurredBackdrop($canvas, $source, $srcWidth, $srcHeight);
        self::compositeContainedForeground($canvas, $source, $srcWidth, $srcHeight);

        $saved = imagejpeg($canvas, $destAbsolutePath, self::JPEG_QUALITY);

        imagedestroy($source);
        imagedestroy($canvas);
        ini_set('memory_limit', $previousLimit);

        return (bool) $saved;
    }

    /**
     * Fills the whole canvas with a cover-scaled, center-cropped, heavily
     * blurred copy of the source — this is only ever the backdrop, so
     * cropping it is fine (the sharp foreground below preserves the full
     * original content).
     */
    protected static function paintBlurredBackdrop($canvas, $source, int $srcWidth, int $srcHeight): void
    {
        $scale = max(self::CANVAS_WIDTH / $srcWidth, self::CANVAS_HEIGHT / $srcHeight);
        $scaledWidth = (int) ceil($srcWidth * $scale);
        $scaledHeight = (int) ceil($srcHeight * $scale);

        $scaled = imagecreatetruecolor($scaledWidth, $scaledHeight);
        imagecopyresampled($scaled, $source, 0, 0, 0, 0, $scaledWidth, $scaledHeight, $srcWidth, $srcHeight);

        $offsetX = (int) round(($scaledWidth - self::CANVAS_WIDTH) / 2);
        $offsetY = (int) round(($scaledHeight - self::CANVAS_HEIGHT) / 2);
        imagecopy($canvas, $scaled, 0, 0, $offsetX, $offsetY, self::CANVAS_WIDTH, self::CANVAS_HEIGHT);
        imagedestroy($scaled);

        for ($i = 0; $i < self::BLUR_PASSES; $i++) {
            imagefilter($canvas, IMG_FILTER_GAUSSIAN_BLUR);
        }

        // Darken slightly so the sharp foreground reads clearly against it.
        imagefilter($canvas, IMG_FILTER_BRIGHTNESS, -30);
    }

    /**
     * Scales the source to *contain* the canvas — the entire image, no
     * cropping — and centers it sharp on top of the blurred backdrop.
     */
    protected static function compositeContainedForeground($canvas, $source, int $srcWidth, int $srcHeight): void
    {
        $scale = min(self::CANVAS_WIDTH / $srcWidth, self::CANVAS_HEIGHT / $srcHeight);
        $fgWidth = (int) round($srcWidth * $scale);
        $fgHeight = (int) round($srcHeight * $scale);

        $fgX = (int) round((self::CANVAS_WIDTH - $fgWidth) / 2);
        $fgY = (int) round((self::CANVAS_HEIGHT - $fgHeight) / 2);

        imagecopyresampled($canvas, $source, $fgX, $fgY, 0, 0, $fgWidth, $fgHeight, $srcWidth, $srcHeight);
    }
}
