<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessTrendsJob;
use App\Jobs\ScrapeEtsyJob;
use App\Models\TrendCluster;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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
}
