<?php

namespace App\Console\Commands;

use App\Models\EtsyListing;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class BackfillEtsyImages extends Command
{
    protected $signature = 'trends:backfill-etsy-images
                            {--limit=500 : Maximum listings to process}
                            {--delay-ms=250 : Delay between requests in milliseconds}
                            {--dry-run : Detect candidates without saving}';

    protected $description = 'Backfill missing Etsy listing images from listing detail pages (og:image/twitter:image).';

    public function handle(): int
    {
        $limit = max(1, (int) $this->option('limit'));
        $delayMs = max(0, (int) $this->option('delay-ms'));
        $dryRun = (bool) $this->option('dry-run');

        $query = EtsyListing::query()
            ->where(function ($q) {
                $q->whereNull('image')
                    ->orWhere('image', '')
                    ->orWhere('image', 'like', '%loremflickr%')
                    ->orWhere('image', 'like', '%picsum%');
            })
            ->where('url', 'like', '%etsy.com/listing/%')
            ->orderByDesc('id')
            ->limit($limit);

        $rows = $query->get();

        if ($rows->isEmpty()) {
            $this->info('No listings need image backfill.');
            return self::SUCCESS;
        }

        $this->info("Processing {$rows->count()} listing(s) for image backfill...");

        $updated = 0;
        $missing = 0;
        $failed = 0;

        /** @var EtsyListing $row */
        foreach ($rows as $row) {
            try {
                $image = $this->fetchImageFromListingUrl((string) $row->url);

                if (!$image) {
                    $missing++;
                    $this->line("MISS  #{$row->id} {$row->url}");
                    if ($delayMs > 0) {
                        usleep($delayMs * 1000);
                    }
                    continue;
                }

                if (!$dryRun) {
                    $row->image = $image;
                    $row->save();
                }

                $updated++;
                $this->line("OK    #{$row->id} {$image}");
            } catch (\Throwable $e) {
                $failed++;
                $this->line("FAIL  #{$row->id} {$e->getMessage()}");
            }

            if ($delayMs > 0) {
                usleep($delayMs * 1000);
            }
        }

        $this->newLine();
        $this->info("Backfill done. Updated={$updated} Missing={$missing} Failed={$failed}" . ($dryRun ? ' (dry-run)' : ''));

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }

    private function fetchImageFromListingUrl(string $url): ?string
    {
        $resp = Http::withHeaders([
            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/125.0.0.0 Safari/537.36',
            'Accept-Language' => 'en-US,en;q=0.9',
            'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,*/*;q=0.8',
        ])->timeout(20)->get($url);

        if (!$resp->successful()) {
            return null;
        }

        $html = (string) $resp->body();

        $candidates = [
            $this->matchMeta($html, 'property', 'og:image'),
            $this->matchMeta($html, 'name', 'twitter:image'),
            $this->matchMeta($html, 'property', 'twitter:image'),
        ];

        // Fallback: find first Etsy static image URL in HTML.
        if (preg_match('/https:\/\/[^"\'\s>]*etsystatic\.com[^"\'\s>]*/i', $html, $m)) {
            $candidates[] = $m[0] ?? null;
        }

        foreach ($candidates as $candidate) {
            $normalized = $this->normalizeImageUrl($candidate);
            if ($normalized) {
                return $normalized;
            }
        }

        return null;
    }

    private function matchMeta(string $html, string $attr, string $value): ?string
    {
        $pattern = '/<meta[^>]*' . preg_quote($attr, '/') . '=["\']' . preg_quote($value, '/') . '["\'][^>]*content=["\']([^"\']+)["\'][^>]*>/i';
        if (preg_match($pattern, $html, $m)) {
            return $m[1] ?? null;
        }

        // Some pages place content before property/name attributes.
        $patternAlt = '/<meta[^>]*content=["\']([^"\']+)["\'][^>]*' . preg_quote($attr, '/') . '=["\']' . preg_quote($value, '/') . '["\'][^>]*>/i';
        if (preg_match($patternAlt, $html, $m2)) {
            return $m2[1] ?? null;
        }

        return null;
    }

    private function normalizeImageUrl(?string $url): ?string
    {
        if (!$url) {
            return null;
        }

        $u = trim($url);
        if ($u === '') {
            return null;
        }

        if (str_starts_with($u, '//')) {
            $u = 'https:' . $u;
        }

        if (!str_starts_with($u, 'http://') && !str_starts_with($u, 'https://')) {
            return null;
        }

        $lu = strtolower($u);
        if (str_contains($lu, 'loremflickr') || str_contains($lu, 'picsum')) {
            return null;
        }

        return $u;
    }
}
