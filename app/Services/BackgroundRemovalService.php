<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class BackgroundRemovalService
{
    private string $apiUrl = 'https://rnbulktools.top';
    private string $token;

    public function __construct()
    {
        $this->token = config('services.rnbulktools.token', '');
    }

    /**
     * Remove background using the RnBulkTools API.
     * @param string $imageBase64 Base64 string or data URL
     * @return string|null data URL (data:image/png;base64,...) or null on failure
     */
    public function removeBackground(string $imageBase64): ?string
    {
        $binary = $this->base64ToBinary($imageBase64);
        if ($binary === null) return null;

        $lastError = null;
        for ($attempt = 1; $attempt <= 2; $attempt++) {
            try {
                $response = Http::withToken($this->token)
                    ->timeout(60)
                    ->attach('file', $binary, 'image.png')
                    ->post("{$this->apiUrl}/remove-bg");

                if (!$response->successful()) {
                    $lastError = 'HTTP ' . $response->status() . ': ' . $response->body();
                    Log::warning("RnBulkTools remove-bg attempt {$attempt} failed", [
                        'status' => $response->status(),
                        'body'   => substr($response->body(), 0, 300),
                    ]);
                    if ($attempt < 2) continue;
                    break;
                }

                return $this->parseImageResponse($response, 'image/png');
            } catch (\Throwable $e) {
                $lastError = $e->getMessage();
                Log::warning("RnBulkTools remove-bg attempt {$attempt} exception", ['message' => $e->getMessage()]);
            }
        }

        Log::error('RnBulkTools remove-bg failed after retries', ['last_error' => $lastError]);

        // Fallback: use the local GD-based remover when external API fails/rate-limits.
        $fallback = $this->removeBackgroundByEdgeSample($imageBase64, 38);
        if ($fallback !== null) {
            Log::warning('RnBulkTools remove-bg fallback used: local edge-sample remover');
            return $fallback;
        }

        return null;
    }

    /**
     * Convert image to WebP using the RnBulkTools API.
     * @param string $imageBase64 Base64 string or data URL
     * @return string|null data URL (data:image/webp;base64,...) or null on failure
     */
    public function convertToWebp(string $imageBase64): ?string
    {
        $binary = $this->base64ToBinary($imageBase64);
        if ($binary === null) return null;

        try {
            $response = Http::withToken($this->token)
                ->timeout(60)
                ->asMultipart()
                ->post("{$this->apiUrl}/convert", [
                    ['name' => 'file',          'contents' => $binary, 'filename' => 'image.png'],
                    ['name' => 'target_format', 'contents' => 'webp'],
                ]);

            if (!$response->successful()) {
                Log::error('RnBulkTools convert failed', [
                    'status' => $response->status(),
                    'body'   => $response->body(),
                ]);
                return null;
            }

            return $this->parseImageResponse($response, 'image/webp');
        } catch (\Throwable $e) {
            Log::error('RnBulkTools convert exception', ['message' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Parse API response: handles binary image or JSON with url/base64 fields.
     */
    private function parseImageResponse(\Illuminate\Http\Client\Response $response, string $defaultMime): ?string
    {
        $contentType = $response->header('Content-Type') ?? '';

        // Binary image response
        if (str_contains($contentType, 'image/')) {
            $mime = strtok($contentType, ';');
            return "data:{$mime};base64," . base64_encode($response->body());
        }

        // JSON response with url or base64 data
        $json = $response->json();
        if (is_array($json)) {
            foreach (['url', 'image_url', 'result_url', 'file_url'] as $key) {
                if (!empty($json[$key]) && is_string($json[$key])) {
                    $imgResponse = Http::timeout(30)->get($json[$key]);
                    if ($imgResponse->successful()) {
                        $ct   = $imgResponse->header('Content-Type') ?? $defaultMime;
                        $mime = strtok($ct, ';');
                        return "data:{$mime};base64," . base64_encode($imgResponse->body());
                    }
                }
            }
            foreach (['base64', 'image', 'data', 'result'] as $key) {
                if (!empty($json[$key]) && is_string($json[$key])) {
                    $val = $json[$key];
                    if (str_starts_with($val, 'data:')) return $val;
                    return "data:{$defaultMime};base64," . $val;
                }
            }
        }

        // Fallback: treat entire body as raw binary
        $body = $response->body();
        if (strlen($body) > 0) {
            return "data:{$defaultMime};base64," . base64_encode($body);
        }

        return null;
    }

    /**
     * Convert base64 string or data URL to binary string.
     */
    private function base64ToBinary(string $input): ?string
    {
        if (str_starts_with($input, 'data:')) {
            $pos = strpos($input, ',');
            if ($pos === false) return null;
            $input = substr($input, $pos + 1);
        }

        $binary = base64_decode($input, true);
        return $binary !== false ? $binary : null;
    }

    // ---------------------------------------------------------------------------
    // Legacy GD-based method kept for reference (no longer used in production)
    // ---------------------------------------------------------------------------
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
