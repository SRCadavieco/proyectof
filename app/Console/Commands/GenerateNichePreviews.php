<?php

namespace App\Console\Commands;

use App\Models\TrendCluster;
use App\Services\ChutesService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class GenerateNichePreviews extends Command
{
    protected $signature = 'trends:generate-previews
                            {--force : Regenerate even if already cached}
                            {--days=7 : Only process clusters from the last N days (0 = all)}';

    protected $description = 'Pre-generate Z Image previews for trend clusters that have no real Etsy images';

    /** Must match the slot variants used by the frontend JS */
    private const SLOT_VARIANTS = ['', 'vintage style', 'colorful bold'];

    public function handle(ChutesService $chutes): int
    {
        $days  = (int) $this->option('days');
        $force = (bool) $this->option('force');

        $query = TrendCluster::query()->with(['listings'])->orderByDesc('score');
        if ($days > 0) {
            $query->where('created_at', '>=', now()->subDays($days));
        }

        $clusters = $query->get();

        if ($clusters->isEmpty()) {
            $this->warn('No clusters found.');
            return self::SUCCESS;
        }

        $this->info("Found {$clusters->count()} cluster(s).");

        $generated = 0;
        $skipped   = 0;
        $failed    = 0;

        foreach ($clusters as $cluster) {
            // Does this cluster already have real (non-mock) images?
            $hasRealImages = $cluster->listings->contains(fn ($l) =>
                $l->image
                && !str_contains((string) $l->image, 'loremflickr')
                && !str_contains((string) $l->image, 'picsum')
            );

            if ($hasRealImages && !$force) {
                $this->line("  <fg=gray>SKIP</> {$cluster->name} (has real Etsy images)");
                $skipped++;
                continue;
            }

            // Replicate frontend nicheDesc derivation exactly
            $nicheDesc = mb_strtolower($cluster->name ?? 'graphic design');
            $nicheDesc = (string) preg_replace('/\b(?:shirts?|tees?|t-shirts?|hoodies?|sweatshirts?|apparel)\b/i', '', $nicheDesc);
            $nicheDesc = trim((string) preg_replace('/\s+/', ' ', $nicheDesc));

            $this->line("  <info>GENERATE</info> \"{$cluster->name}\" → niche_desc=\"{$nicheDesc}\"");

            foreach (self::SLOT_VARIANTS as $slot => $zKw) {
                // Same cache key as TrendController::nichePreview()
                $cacheKey = 'trend-preview:' . md5("{$nicheDesc}:{$zKw}");

                if (!$force && Cache::has($cacheKey)) {
                    $this->line("    slot {$slot} [{$zKw}] → already cached ✓");
                    continue;
                }

                $prompt  = trim("{$nicheDesc} {$zKw}");
                $prompt .= ', graphic print design on white t-shirt, flat lay product mockup, '
                         . 'design is the focal point, clearly visible print, clean white background, '
                         . 'no model, professional product photo';
                $prompt  = mb_substr($prompt, 0, 350);

                $this->line("    slot {$slot} [{$zKw}] → generating…");
                $negativePrompt = 'text, letters, words, typography, writing, font, script, handwriting, calligraphy, watermark, caption, label, title, headline, tagline, slogan, inscription, readable text, legible text, comic panel border, panel frame, speech bubble, text box, manga panel, vignette border, white border frame, page layout, multiple panels, shield frame, badge frame, crest frame, hexagonal border, hexagon frame, diamond frame, shaped border, emblem frame, coat of arms frame, geometric frame, circular frame, oval frame, decorative border, ornamental frame, sigil frame, logo frame';
                $result = $chutes->generateDesign($prompt, null, 'z_image_turbo', $negativePrompt);

                if (!($result['success'] ?? false)) {
                    $this->warn("    slot {$slot} → FAILED: " . ($result['error'] ?? 'unknown'));
                    Log::warning('[GenerateNichePreviews] Failed', [
                        'cluster'    => $cluster->name,
                        'slot'       => $slot,
                        'niche_desc' => $nicheDesc,
                        'error'      => $result['error'] ?? 'unknown',
                    ]);
                    $failed++;
                    continue;
                }

                $image = $result['imageBase64'] ?? $result['image_base64'] ?? $result['base64']
                       ?? $result['imageUrl']   ?? $result['image_url']    ?? $result['url']
                       ?? null;

                if (!$image) {
                    $this->warn("    slot {$slot} → No image in response");
                    $failed++;
                    continue;
                }

                $url = $this->savePreviewImage((string) $image, $cacheKey);
                Cache::put($cacheKey, $url, now()->addDays(30));
                $this->line("    slot {$slot} → cached ✓");
                $generated++;
            }
        }

        $this->newLine();
        $this->info("Done. Generated={$generated} | Skipped (had real images)={$skipped} | Failed={$failed}");

        return $failed === 0 ? self::SUCCESS : self::FAILURE;
    }

    private function savePreviewImage(string $image, string $cacheKey): string
    {
        if (str_starts_with($image, 'http')) {
            return $image;
        }

        $hash = substr($cacheKey, strrpos($cacheKey, ':') + 1);
        $dir  = storage_path('app/public/trend-previews');

        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        if (str_starts_with($image, 'data:')) {
            $b64 = (string) preg_replace('/^data:[^;]+;base64,/', '', $image);
        } else {
            $b64 = $image;
        }

        file_put_contents($dir . '/' . $hash . '.png', base64_decode($b64));

        return '/storage/trend-previews/' . $hash . '.png';
    }
}
