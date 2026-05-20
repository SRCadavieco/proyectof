<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessTrendsJob;
use App\Jobs\ScrapeEtsyJob;
use App\Models\TrendCluster;
use App\Services\ChutesService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class TrendController extends Controller
{
    private const SCRAPE_KEYWORDS = [
        'tshirt funny',
        'retro shirt',
        'cat shirt',
        'camping shirt',
        'motivational quote shirt',
    ];

    /**
     * GET /api/trends
     * Returns clusters ordered by score descending.
     */
    public function index(Request $request): JsonResponse
    {
        $query = TrendCluster::query()->orderByDesc('score');

        if ($request->filled('keyword')) {
            $query->where('keyword', $request->string('keyword'));
        }

        $clusters = $query
            ->with(['listings' => fn ($q) => $q->orderByPivot('similarity_score', 'desc')->limit(6)])
            ->paginate(20);

        return response()->json([
            'data' => $clusters->map(fn ($c) => $this->formatCluster($c)),
            'meta' => [
                'current_page' => $clusters->currentPage(),
                'last_page'    => $clusters->lastPage(),
                'total'        => $clusters->total(),
            ],
        ]);
    }

    /**
     * GET /api/trends/{id}
     * Full cluster detail with sample listings.
     */
    public function show(int $id): JsonResponse
    {
        $cluster = TrendCluster::with([
            'listings' => fn ($q) => $q->orderByPivot('similarity_score', 'desc')->limit(12),
        ])->findOrFail($id);

        return response()->json($this->formatCluster($cluster, detailed: true));
    }

    /**
     * POST /api/trends/refresh
     * Dispatches scraping + processing pipeline.
     * Accepts optional { keywords: [...] } payload to override defaults.
     */
    public function refresh(Request $request): JsonResponse
    {
        $keywords = $request->input('keywords', self::SCRAPE_KEYWORDS);

        if (!is_array($keywords) || empty($keywords)) {
            return response()->json(['error' => 'keywords must be a non-empty array'], 422);
        }

        $keywords = array_map('strval', array_slice($keywords, 0, 20));

        foreach ($keywords as $keyword) {
            $kw = trim($keyword);
            if ($kw === '') continue;
            // Chain: scrape → process trends for that keyword
            ScrapeEtsyJob::dispatch($kw)->chain([
                new ProcessTrendsJob($kw),
            ]);
        }

        return response()->json([
            'status'   => 'queued',
            'keywords' => $keywords,
        ]);
    }

    // ─── Formatting ──────────────────────────────────────────────────────────

    /**
     * POST /api/trends/niche-preview
     * Generates a preview image for a trend niche using Z-Image (Chutes AI).
     * Results cached per cluster+slot for 30 days to avoid redundant generation.
     */
    public function nichePreview(Request $request, ChutesService $chutes): JsonResponse
    {
        $clusterId = (int) $request->input('cluster_id', 0);
        $slot      = max(0, min(2, (int) $request->input('slot', 0)));
        $nicheDesc = mb_substr((string) $request->input('niche_desc', 'graphic t-shirt design'), 0, 120);
        $keyword   = mb_substr((string) $request->input('keyword', ''), 0, 40);

        // Key by niche description + keyword (not cluster_id) so the cache survives
        // re-scraping runs that create new cluster records with different IDs.
        $cacheKey = 'trend-preview:' . md5("{$nicheDesc}:{$keyword}");
        $cached   = Cache::get($cacheKey);
        if ($cached) {
            return response()->json(['image' => $cached]);
        }

        $prompt  = trim("{$nicheDesc} {$keyword}");
        $prompt .= ', graphic print design on white t-shirt, flat lay product mockup, design is the focal point, clearly visible print, clean white background, no model, professional product photo';
        $prompt  = mb_substr($prompt, 0, 350);

        $negativePrompt = 'text, letters, words, typography, writing, font, script, handwriting, calligraphy, watermark, caption, label, title, headline, tagline, slogan, inscription, readable text, legible text, comic panel border, panel frame, speech bubble, text box, manga panel, vignette border, white border frame, page layout, multiple panels, shield frame, badge frame, crest frame, hexagonal border, hexagon frame, diamond frame, shaped border, emblem frame, coat of arms frame, geometric frame, circular frame, oval frame, decorative border, ornamental frame, sigil frame, logo frame';
        $result = $chutes->generateDesign($prompt, null, 'z_image_turbo', $negativePrompt);

        if (!($result['success'] ?? false)) {
            return response()->json(['error' => $result['error'] ?? 'Generation failed'], 500);
        }

        $image = $result['imageBase64'] ?? $result['image_base64'] ?? $result['base64']
               ?? $result['imageUrl']   ?? $result['image_url']    ?? $result['url']
               ?? null;

        if (!$image) {
            return response()->json(['error' => 'No image returned'], 500);
        }

        $url = $this->savePreviewImage((string) $image, $cacheKey);
        Cache::put($cacheKey, $url, now()->addDays(30));

        return response()->json(['image' => $url]);
    }

    private function formatCluster(TrendCluster $cluster, bool $detailed = false): array
    {
        $data = [
            'id'                => $cluster->id,
            'name'              => $cluster->name,
            'summary'           => $cluster->summary,
            'design_prompt'     => $cluster->design_prompt,
            'top_keywords'      => $cluster->top_keywords ?? [],
            'score'             => $cluster->score,
            'growth_rate'       => $cluster->growth_rate,
            'competition_score' => $cluster->competition_score,
            'listing_count'     => $cluster->listing_count,
            'keyword'           => $cluster->keyword,
            'created_at'        => $cluster->created_at?->toIso8601String(),
            'sample_listings'   => $cluster->listings->map(fn ($l) => [
                'id'    => $l->id,
                'title' => $l->title,
                'price' => $l->price,
                'url'   => $l->url,
                'image' => $l->image,
                'tags'  => $l->tags ?? [],
            ]),
            'preview_images'    => $this->resolvePreviewImages($cluster),
        ];

        if ($detailed) {
            $data['growth_indicator'] = match (true) {
                $cluster->growth_rate >= 0.6 => 'hot',
                $cluster->growth_rate >= 0.3 => 'rising',
                default                       => 'stable',
            };
        }

        return $data;
    }

    /**
     * Returns an array of up to 3 pre-cached Z Image URLs for clusters without
     * real Etsy images, so the browser can render them without extra fetch calls.
     * Returns an empty array when real scraper images are available (browser
     * will use those directly from sample_listings).
     *
     * @return string[]
     */
    private function resolvePreviewImages(TrendCluster $cluster): array
    {
        // If any listing has a real (non-mock) image, let the browser use those.
        $hasRealImages = $cluster->listings->contains(fn ($l) =>
            $l->image
            && !str_contains((string) $l->image, 'loremflickr')
            && !str_contains((string) $l->image, 'picsum')
        );

        if ($hasRealImages) {
            return [];
        }

        // Derive nicheDesc exactly as the frontend JS does.
        $nicheDesc = mb_strtolower($cluster->name ?? 'graphic design');
        $nicheDesc = (string) preg_replace('/\b(?:shirts?|tees?|t-shirts?|hoodies?|sweatshirts?|apparel)\b/i', '', $nicheDesc);
        $nicheDesc = trim((string) preg_replace('/\s+/', ' ', $nicheDesc));

        $variants = ['', 'vintage style', 'colorful bold'];
        $images   = [];

        foreach ($variants as $zKw) {
            $cacheKey = 'trend-preview:' . md5("{$nicheDesc}:{$zKw}");
            $cached   = Cache::get($cacheKey);
            // Skip legacy base64 blobs still in cache (they'd bloat the JSON response).
            // These will be replaced by file URLs when the command next runs.
            if ($cached !== null && strlen((string) $cached) > 500) {
                $cached = null;
            }
            $images[] = $cached;   // null = not yet generated
        }

        // Only return the array when at least one slot is available.
        $valid = array_values(array_filter($images));
        return count($valid) > 0 ? $images : [];
    }

    /**
     * Saves a generated image (base64 or data-URI) to the public storage directory
     * and returns a public URL path. If the input is already a URL, returns it as-is.
     */
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
