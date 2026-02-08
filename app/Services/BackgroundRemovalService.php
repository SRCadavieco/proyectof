<?php

namespace App\Services;

/**
 * Simple server-side background removal using GD.
 *
 * Strategy:
 * - Sample multiple random edge pixels to estimate background color (median RGB).
 * - For each pixel, if the color distance to background <= tolerance, make it transparent.
 * - Return a data URL (PNG base64) with alpha preserved.
 */
class BackgroundRemovalService
{
    /**
     * Remove (make transparent) background similar to the edge color.
     * @param string $imageBase64 Base64 string or data URL (PNG/JPEG)
     * @param int $tolerance Euclidean RGB distance threshold (typical 30-60)
     * @return string|null data URL (data:image/png;base64,...) or null on failure
     */
    public function removeBackgroundByEdgeSample(string $imageBase64, int $tolerance = 40): ?string
    {
        $raw = $this->stripDataHeader($imageBase64);
        $binary = base64_decode($raw, true);
        if ($binary === false) {
            return null;
        }

        $src = imagecreatefromstring($binary);
        if (!$src) {
            return null;
        }

        $width = imagesx($src);
        $height = imagesy($src);

        // Prepare output with alpha channel preserved
        $out = imagecreatetruecolor($width, $height);
        imagealphablending($out, false);
        imagesavealpha($out, true);
        $transparent = imagecolorallocatealpha($out, 0, 0, 0, 127);

        // Estimate background color from edge samples (median RGB)
        $bg = $this->estimateBackgroundColor($src, $width, $height);
        if (!$bg) {
            imagedestroy($src);
            imagedestroy($out);
            return null;
        }
        [$br, $bgc, $bb] = $bg; // r,g,b

        // Iterate pixels; make similar-to-background transparent, keep others
        for ($y = 0; $y < $height; $y++) {
            for ($x = 0; $x < $width; $x++) {
                $col = imagecolorat($src, $x, $y);
                $pr = ($col >> 16) & 0xFF;
                $pg = ($col >> 8) & 0xFF;
                $pb = $col & 0xFF;
                $alpha = ($col & 0x7F000000) >> 24; // 0 opaque .. 127 transparent

                $dist = sqrt(($pr - $br) * ($pr - $br) + ($pg - $bgc) * ($pg - $bgc) + ($pb - $bb) * ($pb - $bb));
                if ($dist <= $tolerance) {
                    imagesetpixel($out, $x, $y, $transparent);
                } else {
                    $color = imagecolorallocatealpha($out, $pr, $pg, $pb, $alpha);
                    imagesetpixel($out, $x, $y, $color);
                }
            }
        }

        // Encode PNG with alpha
        ob_start();
        imagepng($out);
        $png = ob_get_clean();
        imagedestroy($src);
        imagedestroy($out);

        if (!$png) {
            return null;
        }
        return 'data:image/png;base64,' . base64_encode($png);
    }

    /**
     * Strip data URL header if present.
     */
    private function stripDataHeader(string $input): string
    {
        if (str_starts_with($input, 'data:')) {
            $pos = strpos($input, ',');
            if ($pos !== false) {
                return substr($input, $pos + 1);
            }
        }
        return $input;
    }

    /**
     * Sample multiple edge pixels and return median RGB.
     * @return array{0:int,1:int,2:int}|null
     */
    private function estimateBackgroundColor($img, int $width, int $height): ?array
    {
        $samples = [];
        $margin = max(5, (int) floor(min($width, $height) * 0.02));
        $count = 24; // sample different edges
        for ($i = 0; $i < $count; $i++) {
            $side = random_int(0, 3);
            if ($side === 0) { // top
                $x = random_int(0, max($width - 1, 0));
                $y = random_int(0, max($margin - 1, 0));
            } elseif ($side === 1) { // right
                $x = max($width - 1 - random_int(0, max($margin - 1, 0)), 0);
                $y = random_int(0, max($height - 1, 0));
            } elseif ($side === 2) { // bottom
                $x = random_int(0, max($width - 1, 0));
                $y = max($height - 1 - random_int(0, max($margin - 1, 0)), 0);
            } else { // left
                $x = random_int(0, max($margin - 1, 0));
                $y = random_int(0, max($height - 1, 0));
            }
            $col = imagecolorat($img, $x, $y);
            $samples[] = [($col >> 16) & 0xFF, ($col >> 8) & 0xFF, $col & 0xFF];
        }
        if (empty($samples)) {
            return null;
        }
        $r = array_column($samples, 0);
        $g = array_column($samples, 1);
        $b = array_column($samples, 2);
        sort($r); sort($g); sort($b);
        $mid = (int) floor(count($samples) / 2);
        return [$r[$mid], $g[$mid], $b[$mid]];
    }
}
