<?php

namespace App\Http\Controllers\Organization\Concerns;

use App\Models\Organization;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

trait StampsWatermark
{
    protected const WATERMARKABLE_MIMES = ['image/jpeg', 'image/png'];

    /**
     * Original text-band watermark — still used by MusicController and as
     * the fallback here when an organization has no logo uploaded.
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

    /**
     * Composites the organization's actual logo into the bottom-right corner
     * of an uploaded image, subtly (opacity-reduced), preserving the logo's
     * own transparency where it has any. Falls back to the plain text band
     * when the org has no logo, or the logo file can't be loaded for any
     * reason — this method never fails outright just because a logo is
     * missing or unreadable.
     */
    protected function stampImageWatermark(string $absolutePath, string $mimeType, Organization $organization): bool
    {
        $logoPath = $this->resolveLogoAbsolutePath($organization);

        if (! $logoPath) {
            return $this->stampWatermark($absolutePath, $mimeType, $organization->name);
        }

        $image = match ($mimeType) {
            'image/jpeg' => @imagecreatefromjpeg($absolutePath),
            'image/png' => @imagecreatefrompng($absolutePath),
            default => null,
        };

        if (! $image) {
            return false;
        }

        $logo = $this->loadImageForWatermark($logoPath);

        if (! $logo) {
            imagedestroy($image);

            return $this->stampWatermark($absolutePath, $mimeType, $organization->name);
        }

        imagealphablending($image, true);
        imagesavealpha($image, true);

        $width = imagesx($image);
        $height = imagesy($image);
        $logoWidth = imagesx($logo);
        $logoHeight = imagesy($logo);

        $targetWidth = (int) max(32, min($logoWidth, round($width * 0.15)));
        $targetHeight = (int) round($logoHeight * ($targetWidth / $logoWidth));

        $scaledLogo = imagecreatetruecolor($targetWidth, $targetHeight);
        imagealphablending($scaledLogo, false);
        imagesavealpha($scaledLogo, true);
        $transparent = imagecolorallocatealpha($scaledLogo, 0, 0, 0, 127);
        imagefilledrectangle($scaledLogo, 0, 0, $targetWidth, $targetHeight, $transparent);
        imagecopyresampled($scaledLogo, $logo, 0, 0, 0, 0, $targetWidth, $targetHeight, $logoWidth, $logoHeight);

        $this->reduceOpacity($scaledLogo, 0.6);

        $margin = max(8, (int) round($width * 0.02));
        $destX = $width - $targetWidth - $margin;
        $destY = $height - $targetHeight - $margin;

        imagecopy($image, $scaledLogo, $destX, $destY, 0, 0, $targetWidth, $targetHeight);

        imagedestroy($logo);
        imagedestroy($scaledLogo);

        $saved = match ($mimeType) {
            'image/jpeg' => imagejpeg($image, $absolutePath, 90),
            'image/png' => imagepng($image, $absolutePath),
            default => false,
        };

        imagedestroy($image);

        return (bool) $saved;
    }

    /**
     * Stamps the org logo (or, without one, a text band) onto every page of
     * an existing PDF via FPDI/FPDF — imports each page as a template so the
     * original content stays native/searchable rather than being flattened
     * to an image. Fails closed: any exception leaves the original file
     * untouched and reports the failure rather than silently claiming
     * success.
     */
    protected function stampPdfWatermark(string $absolutePath, Organization $organization): bool
    {
        $tempLogoPath = null;

        try {
            $pdf = new \setasign\Fpdi\Fpdi;
            $pageCount = $pdf->setSourceFile($absolutePath);

            $logoPath = $this->resolveLogoAbsolutePath($organization);
            $tempLogoPath = $logoPath ? $this->prepareTransparentLogoPng($logoPath) : null;

            for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
                $templateId = $pdf->importPage($pageNo);
                $size = $pdf->getTemplateSize($templateId);

                $pdf->AddPage($size['orientation'], [$size['width'], $size['height']]);
                $pdf->useTemplate($templateId);

                if ($tempLogoPath) {
                    [$logoPxWidth, $logoPxHeight] = getimagesize($tempLogoPath);
                    $targetWidth = $size['width'] * 0.18;
                    $targetHeight = $logoPxHeight * ($targetWidth / $logoPxWidth);
                    $margin = max(4, $size['width'] * 0.02);

                    $pdf->Image($tempLogoPath, $size['width'] - $targetWidth - $margin, $size['height'] - $targetHeight - $margin, $targetWidth, $targetHeight, 'PNG');
                } else {
                    $pdf->SetFont('Helvetica', '', 8);
                    $pdf->SetTextColor(130, 130, 130);
                    $pdf->SetXY(5, $size['height'] - 8);
                    $pdf->Cell(0, 5, mb_convert_encoding("Watermarked for {$organization->name} - do not redistribute", 'ISO-8859-1', 'UTF-8'));
                }
            }

            $pdf->Output('F', $absolutePath);

            return true;
        } catch (\Throwable $e) {
            Log::warning('PDF watermark failed', ['organization_id' => $organization->id, 'error' => $e->getMessage()]);

            return false;
        } finally {
            if ($tempLogoPath) {
                @unlink($tempLogoPath);
            }
        }
    }

    protected function resolveLogoAbsolutePath(Organization $organization): ?string
    {
        if (! $organization->logo_path) {
            return null;
        }

        if (! Storage::disk('public')->exists($organization->logo_path)) {
            return null;
        }

        return Storage::disk('public')->path($organization->logo_path);
    }

    protected function loadImageForWatermark(string $absolutePath)
    {
        $info = @getimagesize($absolutePath);

        if (! $info) {
            return null;
        }

        $image = match ($info['mime']) {
            'image/jpeg' => @imagecreatefromjpeg($absolutePath),
            'image/png' => @imagecreatefrompng($absolutePath),
            'image/webp' => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($absolutePath) : null,
            'image/gif' => @imagecreatefromgif($absolutePath),
            default => null,
        };

        return $image ?: null;
    }

    /**
     * Pre-bakes opacity into a temp PNG copy of the logo (with its own
     * per-pixel alpha preserved) — FPDF natively supports PNG alpha via a
     * soft mask when embedding, so this avoids needing a custom SetAlpha
     * extension for the PDF path entirely.
     */
    protected function prepareTransparentLogoPng(string $logoAbsolutePath): ?string
    {
        $logo = $this->loadImageForWatermark($logoAbsolutePath);

        if (! $logo) {
            return null;
        }

        imagealphablending($logo, false);
        imagesavealpha($logo, true);
        $this->reduceOpacity($logo, 0.55);

        $tempPath = sys_get_temp_dir().'/'.uniqid('watermark_logo_', true).'.png';
        imagepng($logo, $tempPath);
        imagedestroy($logo);

        return $tempPath;
    }

    /**
     * Scales down an already-alpha image's opacity in place (GD has no
     * single built-in "multiply alpha channel" filter).
     */
    protected function reduceOpacity($image, float $opacity): void
    {
        $width = imagesx($image);
        $height = imagesy($image);

        for ($x = 0; $x < $width; $x++) {
            for ($y = 0; $y < $height; $y++) {
                $colorIndex = imagecolorat($image, $x, $y);
                $alpha = ($colorIndex >> 24) & 0x7F;
                $newAlpha = (int) min(127, $alpha + (127 - $alpha) * (1 - $opacity));
                $rgb = imagecolorsforindex($image, $colorIndex);
                $newColor = imagecolorallocatealpha($image, $rgb['red'], $rgb['green'], $rgb['blue'], $newAlpha);
                imagesetpixel($image, $x, $y, $newColor);
            }
        }
    }
}
