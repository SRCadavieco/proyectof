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
                    $dataUrl = str_starts_with($base64, 'data:') ? $base64 : 'data:image/png;base64,' . $base64;

                    // Detect whether the image has a white canvas (isolated subject)
                    // or a coloured scene background.
                    // - White edges → call Replicate to isolate the subject, then flood-fill residual white.
                    // - Coloured edges (scene) → skip Replicate; only flood-fill any white margin.
                    $isWhiteCanvas = empty($this->backgroundColor) && $backgrounds->hasWhiteEdges($dataUrl);

                    if ($isWhiteCanvas) {
                        $noBg = $backgrounds->removeBackground($base64);
                        $bgRemovalMethod = $backgrounds->getLastMethod();
                        $bgRemovalEngine = $backgrounds->getEngineId();
                        try {
                            ApiUsageLog::record('replicate', 'remove_bg', 'remove_bg', $this->userId, $noBg !== null);
                        } catch (\Throwable) {}

                        if ($noBg) {
                            $dataUrl = str_starts_with($noBg, 'data:') ? $noBg : 'data:image/png;base64,' . $noBg;
                        } else {
                            $bgRemovalFailed = true;
                        }
                    }

                    // Flood-fill removes any residual near-white margin from either path.
                    // For scenes this only touches exterior white padding, never coloured pixels.
                    if (empty($this->backgroundColor)) {
                        $dataUrl = $backgrounds->removeWhiteBackground($dataUrl, 28);
                    }

                    $base64     = $dataUrl;
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
}
