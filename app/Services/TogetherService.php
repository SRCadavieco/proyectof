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
    private const API_URL     = 'https://api.together.xyz/v1/images/generations';
    private const CHAT_URL    = 'https://api.together.xyz/v1/chat/completions';
    private const LLM_MODEL   = 'deepseek-ai/DeepSeek-V4-Flash';

    private const MODEL_MAP = [
        'flux_dev' => 'black-forest-labs/FLUX.2-dev',
    ];

    private const IMAGE_MODEL_NAMES = [
        'juggernaut_z' => 'Juggernaut-Z (cinematic photorealistic model)',
        'flux_dev'     => 'FLUX.2-dev (high-quality text-to-image diffusion)',
        'fabric_pro'   => 'Z-Image Turbo (fast artistic diffusion)',
        'fabric_light' => 'Z-Image Turbo (fast artistic diffusion)',
    ];

    /**
     * Optimizes the user's prompt for the target image model and generates
     * product title + description — all in a single LLM call.
     *
     * Returns an array with keys:
     *   optimized_prompt  – rewritten prompt tuned for the diffusion model
     *   title             – product title (≤60 chars)
     *   description       – 2-3 sentence product description (≤200 chars)
     *
     * On any failure the method returns empty/null values so callers can
     * fall back to the original prompt without disrupting image generation.
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

        // Strip potential markdown code fences the model may add
        $json = preg_replace('/^```(?:json)?\s*|\s*```$/s', '', trim($content));

        try {
            $decoded = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        } catch (\Throwable) {
            Log::warning('TogetherService::enrichPrompt JSON decode failed', ['raw' => mb_substr($content, 0, 200)]);
            return $fallback;
        }

        return [
            'optimized_prompt' => isset($decoded['optimized_prompt']) ? trim((string) $decoded['optimized_prompt']) : null,
            'title'            => isset($decoded['title'])            ? trim((string) $decoded['title'])            : null,
            'description'      => isset($decoded['description'])      ? trim((string) $decoded['description'])      : null,
        ];
    }

    /**
     * Thin wrapper around Together's chat completions endpoint.
     * Returns the assistant message content, or null on failure.
     */
    private function chatComplete(array $messages): ?string
    {
        $token = (string) config('services.together.key');
        if (empty($token)) {
            return null;
        }

        $llmModel = (string) config('services.together.llm_model', self::LLM_MODEL);

        try {
            $response = Http::withToken($token)
                ->timeout(30)
                ->post(self::CHAT_URL, [
                    'model'       => $llmModel,
                    'messages'    => $messages,
                    'max_tokens'  => 512,
                    'temperature' => 0.7,
                ]);

            if (!$response->successful()) {
                Log::warning('TogetherService::chatComplete HTTP error', [
                    'status' => $response->status(),
                    'body'   => mb_substr($response->body(), 0, 300),
                ]);
                return null;
            }

            return $response->json('choices.0.message.content');
        } catch (\Throwable $e) {
            Log::warning('TogetherService::chatComplete exception', ['error' => $e->getMessage()]);
            return null;
        }
    }

    public function generateDesign(string $prompt, ?string $backgroundColor = null, string $model = 'flux_dev'): array
    {
        return $this->generate($prompt, $model);
    }

    public function generateDesignWithContext(string $prompt, array $context = [], ?string $backgroundColor = null, string $model = 'flux_dev'): array
    {
        // Diffusion models (FLUX) use a CLIP encoder with ~77 token limit.
        // Conversation history makes prompts too long — ignore context and truncate.
        $truncated = mb_substr($prompt, 0, 350);

        return $this->generate($truncated, $model);
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
            'model'           => $modelName,
            'prompt'          => $prompt,
            'negative_prompt' => 'comic panel border, panel frame, speech bubble, text box, manga panel, vignette border, white border frame, page layout, multiple panels, shield frame, badge frame, crest frame, hexagonal border, hexagon frame, diamond frame, shaped border, emblem frame, coat of arms frame, geometric frame, circular frame, oval frame, decorative border, ornamental frame, sigil frame, logo frame',
            'width'           => 1024,
            'height'          => 1024,
            'steps'           => 28,
            'n'               => 1,
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
