<?php

namespace App\Jobs;

use App\Models\EtsyListing;
use App\Services\EtsyScraperClient;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ScrapeEtsyJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 2;
    public int $timeout = 180;

    public function __construct(public readonly string $keyword) {}

    public function handle(EtsyScraperClient $client): void
    {
        Log::info('[ScrapeEtsyJob] Starting', ['keyword' => $this->keyword]);

        try {
            $result   = $client->scrape($this->keyword);
            $listings = $result['listings'] ?? [];

            $saved   = 0;
            $skipped = 0;

            foreach ($listings as $item) {
                if (empty($item['title']) || empty($item['url'])) {
                    $skipped++;
                    continue;
                }

                EtsyListing::updateOrCreate(
                    ['url' => $item['url']],
                    [
                        'keyword'  => $this->keyword,
                        'title'    => $item['title'],
                        'price'    => $item['price'] ?? null,
                        'image'    => $item['image'] ?? null,
                        'tags'     => $item['tags'] ?? [],
                        'raw_json' => $item,
                    ]
                );
                $saved++;
            }

            Log::info('[ScrapeEtsyJob] Done', [
                'keyword' => $this->keyword,
                'saved'   => $saved,
                'skipped' => $skipped,
            ]);
        } catch (\Throwable $e) {
            Log::error('[ScrapeEtsyJob] Failed', [
                'keyword' => $this->keyword,
                'error'   => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}
