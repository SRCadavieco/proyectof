<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Servicio para la generación de imágenes vía Together AI.
 *
 * Usa el endpoint de inferencia de Together AI compatible con la spec de OpenAI.
 * Modelos soportados:
 *   flux_dev  → black-forest-labs/FLUX.1-dev
 */
class TogetherService
{
    private const API_URL = 'https://api.together.xyz/v1/images/generations';

    private const MODEL_MAP = [
        'flux_dev' => 'black-forest-labs/FLUX.2-dev',
    ];

    public function generateDesign(string $prompt, ?string $backgroundColor = null, string $model = 'flux_dev'): array
    {
        return $this->generate($prompt, $model);
    }

    public function generateDesignWithContext(string $prompt, array $context = [], ?string $backgroundColor = null, string $model = 'flux_dev'): array
    {
        $fullPrompt = $prompt;
        if (!empty($context)) {
            $history    = implode("\n", array_map(fn($m) => "- {$m}", $context));
            $fullPrompt = "Previous context:\n{$history}\n\nNew request: {$prompt}";
        }

        return $this->generate($fullPrompt, $model);
    }

    /**
     * FLUX.2-dev image-to-image via the `image_url` parameter.
     * Together AI requires a real URL (not base64), so we write the image to
     * a temporary file in sys_get_temp_dir() (writable even on Cloud Run),
     * serve it through a signed Laravel route, then delete it.
     *
     * Note: Cloud Run's public/ directory is read-only at runtime, so we use
     * /tmp (always writable) and pass the URL only if the app can serve it.
     * If the app URL is not publicly reachable from Together's servers (e.g.
     * local dev), Together will fail and the caller retries with Gemini.
     */
    public function generateFromReference(string $prompt, string $imageBase64, string $mimeType = 'image/png', string $model = 'flux_dev'): array
    {
        // Strip data URI prefix if present
        $base64 = $imageBase64;
        if (str_starts_with($base64, 'data:image')) {
            $base64 = preg_replace('/^data:image\/[^;]+;base64,/i', '', $base64);
        }

        // Use sys_get_temp_dir() — always writable, including on Cloud Run
        $ext      = str_contains($mimeType, 'jpeg') || str_contains($mimeType, 'jpg') ? 'jpg' : 'png';
        $filename = \Illuminate\Support\Str::uuid() . '.' . $ext;
        $tmpDir   = rtrim(sys_get_temp_dir(), '/\\');
        $tmpPath  = $tmpDir . DIRECTORY_SEPARATOR . $filename;

        $written = @file_put_contents($tmpPath, base64_decode($base64));
        if ($written === false) {
            return [
                'success' => false,
                'error'   => 'Could not write temp image file for Together img2img',
                'status'  => 500,
            ];
        }

        // Serve the temp file via a short-lived public route
        $imageUrl = rtrim((string) config('app.url'), '/') . '/tmp-img/' . $filename;

        try {
            return $this->generateWithImageUrl($prompt, $imageUrl, $model);
        } finally {
            if (file_exists($tmpPath)) {
                @unlink($tmpPath);
            }
        }
    }

    private function generateWithImageUrl(string $prompt, string $imageUrl, string $model): array
    {
        $token     = (string) config('services.together.key');
        $modelName = self::MODEL_MAP[$model] ?? self::MODEL_MAP['flux_dev'];

        if (empty($token)) {
            return [
                'success' => false,
                'error'   => 'Missing Together AI API key (TOGETHER_API_KEY)',
                'status'  => 500,
                'code'    => 'config_error',
            ];
        }

        $payload = [
            'model'     => $modelName,
            'prompt'    => $prompt,
            'image_url' => $imageUrl,
            'width'     => 1024,
            'height'    => 1024,
            'steps'     => 28,
            'n'         => 1,
        ];

        try {
            Log::debug('TogetherService img2img request', [
                'model'     => $modelName,
                'image_url' => $imageUrl,
                'prompt'    => substr($prompt, 0, 120),
            ]);

            $response = Http::withToken($token)
                ->timeout(120)
                ->post(self::API_URL, $payload);

            Log::debug('TogetherService img2img response', [
                'status' => $response->status(),
            ]);

            if ($response->failed()) {
                $json  = null;
                try { $json = $response->json(); } catch (\Throwable) {}
                $error = is_array($json)
                    ? ($json['error']['message'] ?? $json['error'] ?? $response->body())
                    : $response->body();

                return ['success' => false, 'error' => $error, 'status' => $response->status()];
            }

            $json = $response->json();

            $b64 = $json['data'][0]['b64_json'] ?? null;
            if ($b64) {
                return ['success' => true, 'imageBase64' => $b64];
            }

            $url = $json['data'][0]['url'] ?? null;
            if ($url) {
                $imgResponse = Http::timeout(60)->get($url);
                if ($imgResponse->successful()) {
                    return ['success' => true, 'imageBase64' => base64_encode($imgResponse->body())];
                }
                return ['success' => true, 'imageUrl' => $url];
            }

            return ['success' => false, 'error' => 'No image in Together response', 'status' => 500];

        } catch (\Throwable $e) {
            Log::error('TogetherService img2img exception', ['error' => $e->getMessage()]);
            return ['success' => false, 'error' => $e->getMessage(), 'status' => 500];
        }
    }

    // ─────────────────────────────────────────────────────────────────────────

    private function generate(string $prompt, string $model): array
    {
        $token     = (string) config('services.together.key');
        $modelName = self::MODEL_MAP[$model] ?? self::MODEL_MAP['flux_dev'];

        if (empty($token)) {
            return [
                'success' => false,
                'error'   => 'Missing Together AI API key (TOGETHER_API_KEY)',
                'status'  => 500,
                'code'    => 'config_error',
            ];
        }

        $payload = [
            'model'   => $modelName,
            'prompt'  => $prompt,
            'width'   => 1024,
            'height'  => 1024,
            'steps'   => 28,
            'n'       => 1,
        ];

        try {
            Log::debug('TogetherService request', [
                'model'   => $modelName,
                'prompt'  => substr($prompt, 0, 120),
            ]);

            $response = Http::withToken($token)
                ->timeout(120)
                ->post(self::API_URL, $payload);

            Log::debug('TogetherService response', [
                'status'       => $response->status(),
                'content_type' => $response->header('Content-Type'),
            ]);

            if ($response->failed()) {
                $json  = null;
                try { $json = $response->json(); } catch (\Throwable) {}
                $error = is_array($json)
                    ? ($json['error']['message'] ?? $json['error'] ?? $response->body())
                    : $response->body();

                return [
                    'success' => false,
                    'error'   => $error,
                    'status'  => $response->status(),
                ];
            }

            $json = $response->json();

            // Fallback: base64 (b64_json)
            $b64 = $json['data'][0]['b64_json'] ?? null;
            if ($b64) {
                return [
                    'success'     => true,
                    'imageBase64' => $b64,
                ];
            }

            // Together returns { data: [ { url: "..." } ] } — a short redirect URL
            // that has no CORS headers, so we must proxy it server-side.
            $url = $json['data'][0]['url'] ?? null;

            if ($url) {
                try {
                    $imgResponse = Http::timeout(60)->get($url);
                    if ($imgResponse->successful()) {
                        return [
                            'success'     => true,
                            'imageBase64' => base64_encode($imgResponse->body()),
                        ];
                    }
                } catch (\Throwable $e) {
                    Log::warning('TogetherService: could not proxy image URL', [
                        'url'     => $url,
                        'message' => $e->getMessage(),
                    ]);
                }
                // Last resort: return the URL and let the frontend try
                return [
                    'success'  => true,
                    'imageUrl' => $url,
                ];
            }

            Log::warning('TogetherService: unexpected response shape', ['json' => $json]);

            return [
                'success' => false,
                'error'   => 'No image in Together AI response',
                'status'  => 500,
            ];

        } catch (\Throwable $e) {
            Log::error('TogetherService exception', ['message' => $e->getMessage()]);
            return [
                'success' => false,
                'error'   => $e->getMessage(),
                'status'  => 500,
            ];
        }
    }
}
