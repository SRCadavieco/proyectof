<?php

namespace App\Jobs;

use App\Models\ApiUsageLog;
use App\Models\Chat;
use App\Services\BackgroundRemovalService;
use App\Services\NanoGptService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class GenerateDesignJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 1;
    public int $timeout = 360;

    public function __construct(
        public readonly string  $generationId,
        public readonly int     $chatId,
        public readonly int     $userId,
        public readonly string  $prompt,
        public readonly string  $userPrompt,
        public readonly ?string $backgroundColor,
        public readonly string  $model,
        public readonly string  $provider,
    ) {}

    public function handle(NanoGptService $nanogpt, BackgroundRemovalService $backgrounds): void
    {
        $cacheKey = 'generation:' . $this->generationId;

        try {
            $result = $nanogpt->generateDesign(
                $this->prompt,
                $this->backgroundColor,
                $this->model
            );

            $imageValue      = null;
            $bgRemovalFailed = false;
            $bgRemovalMethod = null;
            $bgRemovalEngine = null;

            if (is_array($result)) {
                $base64   = $result['imageBase64'] ?? $result['image_base64'] ?? $result['base64'] ?? null;
                $imageUrl = $result['imageUrl']    ?? $result['image_url']    ?? $result['url']    ?? null;

                if ($base64) {
                    $shouldRemoveBg = $this->shouldApplyBackgroundRemoval($this->userPrompt, $this->backgroundColor);

                    if ($shouldRemoveBg) {
                        $noBg = $backgrounds->removeBackground($base64);
                        $bgRemovalMethod = $backgrounds->getLastMethod();
                        $bgRemovalEngine = $backgrounds->getEngineId();
                        try {
                            ApiUsageLog::record('replicate', 'remove_bg', 'remove_bg', $this->userId, $noBg !== null);
                        } catch (\Throwable) {}

                        if ($noBg) {
                            $base64 = $noBg;
                        } else {
                            $bgRemovalFailed = true;
                        }
                    } else {
                        $bgRemovalMethod = 'skipped_scene_intent';
                    }

                    if (!str_starts_with($base64, 'data:')) {
                        $base64 = 'data:image/png;base64,' . $base64;
                    }

                    $imageValue = $base64;
                } elseif ($imageUrl) {
                    $imageValue = $imageUrl;
                }
            }

            $aiSuccess = $imageValue !== null;
            try {
                ApiUsageLog::record($this->provider, $this->model, 'generate', $this->userId, $aiSuccess);
            } catch (\Throwable) {}

            if ($imageValue) {
                $chat = Chat::find($this->chatId);
                if ($chat) {
                    $chat->messages()->create([
                        'role'  => 'assistant',
                        'image' => $imageValue,
                        'model' => $this->model,
                    ]);
                    if (!$chat->title) {
                        $chat->update(['title' => Str::limit($this->userPrompt, 40)]);
                    }
                }

                Cache::put($cacheKey, [
                    'status'            => 'done',
                    'imageBase64'       => $imageValue,
                    'provider'          => $this->provider,
                    'model'             => $this->model,
                    'bg_removal_failed' => $bgRemovalFailed,
                    'bg_removal_method' => $bgRemovalMethod,
                    'bg_removal_engine' => $bgRemovalEngine,
                ], now()->addMinutes(10));
            } else {
                $aiError = is_array($result) ? ($result['error'] ?? 'No image in response') : 'Invalid result';
                Log::error('GenerateDesignJob: no image', [
                    'generation_id' => $this->generationId,
                    'error'         => $aiError,
                ]);
                Cache::put($cacheKey, [
                    'status' => 'error',
                    'error'  => $aiError,
                ], now()->addMinutes(10));
            }
        } catch (\Throwable $e) {
            Log::error('GenerateDesignJob exception', [
                'generation_id' => $this->generationId,
                'error'         => $e->getMessage(),
            ]);
            Cache::put($cacheKey, [
                'status' => 'error',
                'error'  => 'Generation failed: ' . $e->getMessage(),
            ], now()->addMinutes(10));
        }
    }

    /**
     * Decide whether Replicate background removal should run.
     *
     * Rules:
     * - If user picked a solid background color, never remove.
     * - If prompt explicitly asks for isolated/transparent output, remove.
     * - If prompt describes a scene/environment, skip removal.
     * - Default: remove (backward compatible for simple object prompts).
     */
    private function shouldApplyBackgroundRemoval(string $userPrompt, ?string $backgroundColor): bool
    {
        if (!empty($backgroundColor)) {
            return false;
        }

        $text = mb_strtolower($userPrompt);

        $isolateKeywords = [
            'sin fondo',
            'fondo transparente',
            'transparent background',
            'no background',
            'aislado',
            'isolated',
            'sticker',
            'logo',
            'png',
        ];

        foreach ($isolateKeywords as $keyword) {
            if (str_contains($text, $keyword)) {
                return true;
            }
        }

        $sceneKeywords = [
            'escena',
            'circuito',
            'en la calle',
            'carretera',
            'paisaje',
            'fondo',
            'entorno',
            'background',
            'environment',
            'scene',
            'city',
            'forest',
            'beach',
            'mountain',
            'sky',
            'road',
            'track',
            'racing',
        ];

        foreach ($sceneKeywords as $keyword) {
            if (str_contains($text, $keyword)) {
                return false;
            }
        }

        return true;
    }
}
