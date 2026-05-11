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
            // Let the prompt drive the composition: scenes get a full background,
            // isolated subjects render naturally. The Replicate + flood-fill pipeline
            // handles background removal afterwards regardless.
            $finalPrompt .= "\nNo decorative border or frame around the image, no apparel or clothing mockup, no text overlays.";
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

    private const CHAT_URL    = 'https://nano-gpt.com/v1/chat/completions';
    private const LLM_MODEL   = 'deepseek/deepseek-v4-flash';

    private const IMAGE_MODEL_NAMES = [
        'juggernaut_z' => 'Juggernaut-Z (cinematic photorealistic model)',
        'flux_dev'     => 'FLUX.2-dev (high-quality text-to-image diffusion)',
        'fabric_pro'   => 'Z-Image Turbo (fast artistic diffusion)',
        'fabric_light' => 'Z-Image Turbo (fast artistic diffusion)',
    ];

    /**
     * Optimizes the user's prompt for the target image model and generates
     * product title + description — all in a single LLM call via NanoGPT.
     *
     * Returns: ['optimized_prompt' => ..., 'title' => ..., 'description' => ...]
     * All values are null on failure so callers can fall back gracefully.
     */
    public function enrichPrompt(string $userPrompt, string $modelKey): array
    {
        $fallback = ['optimized_prompt' => null, 'title' => null, 'description' => null];

        $modelName = self::IMAGE_MODEL_NAMES[$modelKey] ?? 'AI image generation model';

        $system = <<<SYSTEM
You are an expert AI prompt engineer and e-commerce copywriter for print-on-demand apparel.
Given the user's image idea, respond ONLY with valid JSON in this exact format (no markdown, no extra text):
{
  "optimized_prompt": "...",
  "title": "...",
  "description": "..."
}

Rules:
- "optimized_prompt": Rewrite the user's idea as a precise, vivid prompt optimized for {$modelName}. Preserve the original subject and mood; make it more descriptive and specific. Max 350 characters.
- "title": Short product title for an apparel/merch store. Max 60 characters. No quotes.
- "description": Compelling 2-3 sentence product description for the store. Max 200 characters. No quotes.
SYSTEM;

        $content = $this->chatComplete([
            ['role' => 'system', 'content' => $system],
            ['role' => 'user',   'content' => $userPrompt],
        ]);

        if ($content === null) {
            return $fallback;
        }

        $json = preg_replace('/^```(?:json)?\s*|\s*```$/s', '', trim($content));

        try {
            $decoded = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        } catch (\Throwable) {
            Log::warning('NanoGptService::enrichPrompt JSON decode failed', ['raw' => mb_substr($content, 0, 200)]);
            return $fallback;
        }

        return [
            'optimized_prompt' => isset($decoded['optimized_prompt']) ? trim((string) $decoded['optimized_prompt']) : null,
            'title'            => isset($decoded['title'])            ? trim((string) $decoded['title'])            : null,
            'description'      => isset($decoded['description'])      ? trim((string) $decoded['description'])      : null,
        ];
    }

    private function chatComplete(array $messages): ?string
    {
        $apiKey = (string) config('services.nanogpt.key');
        if (empty($apiKey)) {
            return null;
        }

        $llmModel = (string) config('services.nanogpt.llm_model', self::LLM_MODEL);

        try {
            $response = Http::withHeaders(['x-api-key' => $apiKey])
                ->withToken($apiKey)
                ->timeout(30)
                ->post(self::CHAT_URL, [
                    'model'       => $llmModel,
                    'messages'    => $messages,
                    'max_tokens'  => 512,
                    'temperature' => 0.7,
                ]);

            if (!$response->successful()) {
                Log::warning('NanoGptService::chatComplete HTTP error', [
                    'status' => $response->status(),
                    'model'  => $llmModel,
                    'body'   => mb_substr($response->body(), 0, 500),
                ]);
                return null;
            }

            return $response->json('choices.0.message.content');
        } catch (\Throwable $e) {
            Log::warning('NanoGptService::chatComplete exception', ['error' => $e->getMessage()]);
            return null;
        }
    }

}