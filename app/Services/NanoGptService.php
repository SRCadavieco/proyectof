<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class NanoGptService
{
    private const DEFAULT_API_URL = 'https://nano-gpt.com/v1/images/generations';

    public function generateDesign(string $prompt, ?string $backgroundColor = null, string $model = 'gpt_image_2'): array
    {
        return $this->generate($prompt, $backgroundColor, $model);
    }

    public function generateDesignWithContext(string $prompt, array $context = [], ?string $backgroundColor = null, string $model = 'gpt_image_2'): array
    {
        $finalPrompt = $prompt;

        if (!empty($context)) {
            $contextLines = collect($context)
                ->filter(fn ($line) => is_string($line) && trim($line) !== '')
                ->map(fn ($line) => '- ' . mb_substr(trim($line), 0, 160))
                ->take(6)
                ->implode("\n");

            if ($contextLines !== '') {
                $finalPrompt = "Conversation context (style and continuity reference):\n"
                    . $contextLines
                    . "\n\nCurrent request:\n"
                    . $prompt;
            }
        }

        // Keep payload bounded for stability with image models.
        $boundedPrompt = mb_substr($finalPrompt, 0, 1800);

        return $this->generate($boundedPrompt, $backgroundColor, $model);
    }

    private function generate(string $prompt, ?string $backgroundColor, string $model): array
    {
        $apiKey = (string) config('services.nanogpt.key');
        $url = (string) (config('services.nanogpt.image_url') ?: self::DEFAULT_API_URL);
        $modelName = (string) (config("services.nanogpt.models.{$model}") ?: config('services.nanogpt.models.gpt_image_2', 'gpt-image-2'));

        if ($apiKey === '') {
            return [
                'success' => false,
                'error' => 'Missing NanoGPT API key (NANOGPT_API_KEY)',
                'status' => 500,
                'code' => 'config_error',
            ];
        }

        $finalPrompt = $prompt;

        if (!empty($backgroundColor)) {
            $hex = strtolower(trim($backgroundColor));
            $finalPrompt .= "\nSolid uniform background color {$hex}. No transparency.";
        } else {
            // Strategy: ask for a contained scene illustration on pure white canvas.
            // The scene (subject + full environment/background) is fully rendered inside
            // the illustration area. Outside the illustration the canvas is plain white.
            // This gives rembg a clean, predictable white target to cut while preserving
            // the full scene context (sky, ground, surroundings) within the artwork.
            $finalPrompt .= "\nDraw a detailed scene illustration on a pure white canvas. The illustration includes the main subject AND its full surrounding environment (sky, ground, background scenery, atmosphere) — not an isolated object. The scene is fully rendered with rich detail and colour. Outside the illustrated scene the canvas is plain solid white. No vignette, no decorative border or frame, no rounded edges, no apparel, no clothing mockup, no text.";
        }

        // Keep payload bounded for stability with image models.
        $finalPrompt = mb_substr($finalPrompt, 0, 1800);

        $payload = [
            'model' => $modelName,
            'prompt' => $finalPrompt,
            'size' => '1024x1024',
            'n' => 1,
            'response_format' => 'b64_json',
        ];

        try {
            $response = Http::timeout(250)
                ->withHeaders(['x-api-key' => $apiKey])
                ->withToken($apiKey)
                ->acceptJson()
                ->post($url, $payload);

            if ($response->failed()) {
                $json = null;
                try {
                    $json = $response->json();
                } catch (\Throwable) {
                    $json = null;
                }

                $error = is_array($json)
                    ? ($json['error']['message'] ?? $json['error'] ?? $response->body())
                    : $response->body();

                return [
                    'success' => false,
                    'error' => $error,
                    'status' => $response->status(),
                ];
            }

            $json = $response->json();

            $b64 = $json['data'][0]['b64_json'] ?? null;
            if ($b64) {
                return [
                    'success' => true,
                    'imageBase64' => $b64,
                ];
            }

            $urlImage = $json['data'][0]['url'] ?? null;
            if ($urlImage) {
                try {
                    $imgResponse = Http::timeout(60)->get($urlImage);
                    if ($imgResponse->successful()) {
                        return [
                            'success' => true,
                            'imageBase64' => base64_encode($imgResponse->body()),
                        ];
                    }
                } catch (\Throwable $e) {
                    Log::warning('NanoGptService: could not fetch generated image URL', [
                        'url' => $urlImage,
                        'error' => $e->getMessage(),
                    ]);
                }

                return [
                    'success' => true,
                    'imageUrl' => $urlImage,
                ];
            }

            return [
                'success' => false,
                'error' => 'No image in NanoGPT response',
                'status' => 500,
            ];
        } catch (\Throwable $e) {
            Log::error('NanoGptService exception', ['error' => $e->getMessage()]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'status' => 500,
            ];
        }
    }

}