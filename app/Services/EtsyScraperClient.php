<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * HTTP client for the Node.js scraper microservice.
 *
 * Config keys (config/services.php → .env):
 *   SCRAPER_SERVICE_URL     Base URL of the Node scraper  (e.g. http://localhost:3100)
 *   SCRAPER_SERVICE_SECRET  Bearer token (matches scraper-service SCRAPER_SECRET)
 */
class EtsyScraperClient
{
    private string $baseUrl;
    private string $secret;

    public function __construct()
    {
        $this->baseUrl = rtrim((string) config('services.scraper.url', 'http://localhost:3100'), '/');
        $this->secret  = (string) config('services.scraper.secret', '');
    }

    /**
     * Scrape Etsy for the given keyword.
     *
     * @return array{keyword: string, count: int, listings: array<int, array>}
     * @throws \RuntimeException on HTTP or scrape failure
     */
    public function scrape(string $keyword): array
    {
        $headers = ['Accept' => 'application/json'];
        if ($this->secret !== '') {
            $headers['Authorization'] = "Bearer {$this->secret}";
        }

        $response = Http::withHeaders($headers)
            ->timeout(120)
            ->get("{$this->baseUrl}/scrape", ['q' => $keyword]);

        if ($response->failed()) {
            // The scraper returns { error: "Scrape failed", message: "<real cause>" }.
            // Prefer the detailed `message` so the root cause surfaces in logs/UI.
            $detail = $response->json('message') ?? $response->json('error') ?? $response->body();
            Log::error('[EtsyScraperClient] Scrape request failed', [
                'keyword' => $keyword,
                'status'  => $response->status(),
                'error'   => $detail,
            ]);
            throw new \RuntimeException("Scraper returned HTTP {$response->status()}: {$detail}");
        }

        return $response->json();
    }

    /**
     * Check the scraper service health endpoint.
     */
    public function isHealthy(): bool
    {
        try {
            $response = Http::timeout(5)->get("{$this->baseUrl}/health");
            return $response->successful();
        } catch (\Throwable) {
            return false;
        }
    }
}
