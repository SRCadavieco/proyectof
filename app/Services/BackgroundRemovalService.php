<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class BackgroundRemovalService
{
    private string $replicateApiUrl = 'https://api.replicate.com/v1';
    // Use the model-name endpoint so we always hit the latest deployed version
    // without needing to pin a specific (possibly deprecated) version hash.
    private string $replicateModel = 'cjwbw/rembg';
    private string $token;
    private string $lastMethod = 'not_attempted';

    public function __construct()
    {
        $this->token = $this->resolveReplicateToken();
    }

    /**
     * Resolve Replicate token reliably even when config cache or worker state is stale.
     */
    private function resolveReplicateToken(): string
    {
        $candidates = [
            config('services.replicate.token', ''),
            env('REPLICATE_API_TOKEN'),
            env('_REPLICATE_API_TOKEN'),
            getenv('REPLICATE_API_TOKEN') ?: '',
            getenv('_REPLICATE_API_TOKEN') ?: '',
            $_ENV['REPLICATE_API_TOKEN'] ?? '',
            $_ENV['_REPLICATE_API_TOKEN'] ?? '',
        ];

        foreach ($candidates as $candidate) {
            if (is_string($candidate) && trim($candidate) !== '') {
                return trim($candidate);
            }
        }

        // Final fallback: parse the project's .env directly.
        $envPath = base_path('.env');
        if (is_file($envPath) && is_readable($envPath)) {
            $lines = @file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
            foreach ($lines as $line) {
                if (!is_string($line) || str_starts_with(trim($line), '#')) {
                    continue;
                }
                if (str_starts_with($line, 'REPLICATE_API_TOKEN=')) {
                    $value = trim(substr($line, strlen('REPLICATE_API_TOKEN=')), " \t\n\r\0\x0B\"'");
                    if ($value !== '') {
                        return $value;
                    }
                }
                if (str_starts_with($line, '_REPLICATE_API_TOKEN=')) {
                    $value = trim(substr($line, strlen('_REPLICATE_API_TOKEN=')), " \t\n\r\0\x0B\"'");
                    if ($value !== '') {
                        return $value;
                    }
                }
            }
        }

        return '';
    }

    public function getLastMethod(): string
    {
        return $this->lastMethod;
    }

    public function getEngineId(): string
    {
        return $this->replicateModel;
    }

    /**
     * Remove background using the Replicate API (cjwbw/rembg).
     *
     * @param string $imageBase64 Base64 string or data URL
     * @return string|null data URL (data:image/png;base64,...) or null on failure
     */
    public function removeBackground(string $imageBase64): ?string
    {
        $this->lastMethod = 'not_attempted';

        // Ensure we have a proper data URL to send to Replicate
        if (!str_starts_with($imageBase64, 'data:')) {
            $imageBase64 = 'data:image/png;base64,' . $imageBase64;
        }

        if (trim($this->token) === '') {
            Log::warning('Replicate remove-bg skipped: missing REPLICATE_API_TOKEN');
            $this->lastMethod = 'failed';
            return null;
        }

        try {
            // Use the model-name endpoint (/v1/models/{owner}/{name}/predictions) so we
            // always use the latest deployed version without a pinned hash.
            // Prefer:wait=60 asks Replicate to respond synchronously if it finishes in time;
            // timeout(70) must be > Prefer:wait so PHP doesn't cut the connection first.
            $createResponse = Http::withToken($this->token)
                ->withHeaders(['Prefer' => 'wait=60'])
                ->timeout(70)
                ->post("{$this->replicateApiUrl}/models/{$this->replicateModel}/predictions", [
                    'input' => [
                        'image' => $imageBase64,
                    ],
                ]);

            if (!$createResponse->successful()) {
                Log::warning('Replicate remove-bg create failed', [
                    'status' => $createResponse->status(),
                    'body'   => substr($createResponse->body(), 0, 500),
                    'model'  => $this->replicateModel,
                ]);
                throw new \RuntimeException('Create prediction failed: HTTP ' . $createResponse->status());
            }

            $prediction = $createResponse->json();

            // Replicate may return the result synchronously when Prefer:wait is honored.
            if (($prediction['status'] ?? '') === 'succeeded') {
                $output = $prediction['output'] ?? null;
            } else {
                $predictionId = $prediction['id'] ?? null;
                if (!$predictionId) {
                    Log::warning('Replicate remove-bg: no prediction ID', ['body' => $prediction]);
                    throw new \RuntimeException('No prediction ID returned from Replicate');
                }

                // Poll until succeeded or failed (max ~90 seconds)
                $maxAttempts = 30;
                $pollInterval = 3;
                $output = null;

                for ($i = 0; $i < $maxAttempts; $i++) {
                    sleep($pollInterval);

                    $pollResponse = Http::withToken($this->token)
                        ->timeout(15)
                        ->get("{$this->replicateApiUrl}/predictions/{$predictionId}");

                    if (!$pollResponse->successful()) {
                        Log::warning('Replicate poll failed', ['status' => $pollResponse->status()]);
                        continue;
                    }

                    $result = $pollResponse->json();
                    $status = $result['status'] ?? '';

                    if ($status === 'succeeded') {
                        $output = $result['output'] ?? null;
                        break;
                    }

                    if (in_array($status, ['failed', 'canceled'])) {
                        Log::warning('Replicate remove-bg prediction failed', [
                            'status' => $status,
                            'error'  => $result['error'] ?? '',
                        ]);
                        throw new \RuntimeException('Replicate prediction ' . $status);
                    }
                    // still starting/processing — continue polling
                }
            }

            if (!$output) {
                throw new \RuntimeException('Replicate remove-bg timed out or returned no output');
            }

            // Step 3: Download result image (output is a URL)
            $outputUrl = is_array($output) ? ($output[0] ?? null) : $output;
            if (!is_string($outputUrl) || $outputUrl === '') {
                throw new \RuntimeException('Replicate output URL is empty');
            }

            if (str_starts_with($outputUrl, 'data:')) {
                $this->lastMethod = 'api';
                return $this->featherAlphaEdges($outputUrl);
            }

            $imgResponse = Http::withToken($this->token)
                ->timeout(30)
                ->get($outputUrl);
            if (!$imgResponse->successful()) {
                throw new \RuntimeException('Failed to download Replicate output image');
            }

            $ct   = $imgResponse->header('Content-Type') ?? 'image/png';
            $mime = strtok($ct, ';');
            $this->lastMethod = 'api';
            $dataUrl = "data:{$mime};base64," . base64_encode($imgResponse->body());
            return $this->featherAlphaEdges($dataUrl);

        } catch (\Throwable $e) {
            Log::error('Replicate remove-bg failed', ['message' => $e->getMessage()]);
        }

        $this->lastMethod = 'failed';
        return null;
    }

    /**
     * Convert image to WebP using the RnBulkTools API.
     * @param string $imageBase64 Base64 string or data URL
     * @return string|null data URL (data:image/webp;base64,...) or null on failure
     */
    public function convertToWebp(string $imageBase64): ?string
    {
        // Legacy method kept for compatibility; conversion is currently disabled.
        return $imageBase64;
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
     * Soften the alpha channel edges of a PNG to reduce the hard "cookie-cutter" cutout look.
     * Detects pixels at the opacity boundary and feathers them over a 2-px radius.
     *
     * @param  string $dataUrl data URL (data:image/png;base64,...)
     * @param  int    $radius  feather radius in pixels (1-3 recommended)
     * @return string          data URL with softened alpha, or original if GD fails
     */
    private function featherAlphaEdges(string $dataUrl, int $radius = 2): string
    {
        try {
            $raw    = $this->stripDataHeader($dataUrl);
            $binary = base64_decode($raw, true);
            if (!$binary) return $dataUrl;

            $src = @imagecreatefromstring($binary);
            if (!$src) return $dataUrl;

            $w = imagesx($src);
            $h = imagesy($src);

            $out = imagecreatetruecolor($w, $h);
            imagealphablending($out, false);
            imagesavealpha($out, true);

            // First pass: read all alpha values into a flat array (0 = opaque, 127 = transparent)
            $alphas = [];
            for ($y = 0; $y < $h; $y++) {
                for ($x = 0; $x < $w; $x++) {
                    $col = imagecolorat($src, $x, $y);
                    $alphas[$y * $w + $x] = ($col >> 24) & 0x7F;
                }
            }

            // Second pass: for each pixel near an alpha edge, blend its opacity
            for ($y = 0; $y < $h; $y++) {
                for ($x = 0; $x < $w; $x++) {
                    $col = imagecolorat($src, $x, $y);
                    $r   = ($col >> 16) & 0xFF;
                    $g   = ($col >> 8)  & 0xFF;
                    $b   =  $col        & 0xFF;
                    $a   = $alphas[$y * $w + $x];

                    // Only process pixels that are fully or mostly opaque (not already transparent)
                    if ($a < 40) {
                        // Sample neighbours within radius
                        $sum = 0; $count = 0;
                        for ($dy = -$radius; $dy <= $radius; $dy++) {
                            for ($dx = -$radius; $dx <= $radius; $dx++) {
                                $nx = $x + $dx; $ny = $y + $dy;
                                if ($nx >= 0 && $nx < $w && $ny >= 0 && $ny < $h) {
                                    $sum += $alphas[$ny * $w + $nx];
                                    $count++;
                                }
                            }
                        }
                        $avgNeighbour = $count > 0 ? $sum / $count : $a;
                        // If surrounded by some transparent neighbours, feather proportionally
                        if ($avgNeighbour > 4) {
                            // scale: if avg neighbour alpha is high (transparent), raise our alpha too
                            $a = (int) min(127, $a + (int) round($avgNeighbour * 0.55));
                        }
                    }

                    imagesetpixel($out, $x, $y, imagecolorallocatealpha($out, $r, $g, $b, $a));
                }
            }

            ob_start();
            imagepng($out, null, 6);
            $png = ob_get_clean();
            imagedestroy($src);
            imagedestroy($out);

            if (!$png) return $dataUrl;
            return 'data:image/png;base64,' . base64_encode($png);

        } catch (\Throwable $e) {
            Log::warning('featherAlphaEdges failed, returning original', ['error' => $e->getMessage()]);
            return $dataUrl;
        }
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
