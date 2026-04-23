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
     * Together AI does not expose an img2img endpoint for FLUX.1-dev yet,
     * so we fall back to text-to-image, appending the instruction.
     */
    public function generateFromReference(string $prompt, string $imageBase64, string $mimeType = 'image/png', string $model = 'flux_dev'): array
    {
        return $this->generate($prompt, $model);
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
