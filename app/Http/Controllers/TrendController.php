<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessTrendsJob;
use App\Jobs\ScrapeEtsyJob;
use App\Models\TrendCluster;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TrendController extends Controller
{
    private const GENERIC_NAME_WORDS = [
        'trend','trending','trendy','etsy','niche','cluster','screen','apparel','clothing',
        'shirt','shirts','tshirt','tshirts','tee','tees','hoodie','hoodies','sweatshirt','sweatshirts',
        'popular','loading','now','pick','bestseller','favorites','favorite',
    ];

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
        $query = TrendCluster::query()
            ->whereHas('listings', function ($q) {
                $q->whereNotNull('image')
                    ->where('image', '!=', '')
                    ->where('image', 'not like', '%loremflickr%')
                    ->where('image', 'not like', '%picsum%');
            })
            ->where('created_at', '>=', now()->subDays(7))
            ->orderByDesc('score');

        if ($request->filled('keyword')) {
            $query->where('keyword', $request->string('keyword'));
        }

        $allClusters = $query
            // Load a wider candidate set so we can prioritize listings with real images.
            ->with(['listings' => fn ($q) => $q->orderByPivot('similarity_score', 'desc')->limit(18)])
            ->get();

        // Build candidate cards with validated display names first.
        $namedClusters = $allClusters
            ->map(function (TrendCluster $c) {
                $displayKeywords = $this->buildDisplayKeywords($c);
                $display = $this->buildDisplayName($c, $displayKeywords);
                return ['cluster' => $c, 'display' => $display, 'keywords' => $displayKeywords];
            })
            ->filter(fn ($row) => $this->isValidDisplayName((string) $row['display']))
            ->values();

        // Prefer strict visual-evidence rows, but never leave the UI empty.
        $strictRows = $namedClusters
            ->filter(fn ($row) => $this->hasVisualEvidence($row['cluster'], (string) $row['display'], (array) ($row['keywords'] ?? [])))
            ->values();

        $rowsForFeed = $strictRows->isNotEmpty() ? $strictRows : $namedClusters;

        // Avoid duplicate cards with equivalent names across runs.
        $uniqueRows = $rowsForFeed
            ->unique(fn ($row) => mb_strtolower(trim((string) $row['display'])))
            ->values();

        $uniqueClusters = $uniqueRows->map(fn ($row) => [
            'cluster' => $row['cluster'],
            'display' => $row['display'],
            'keywords' => $row['keywords'],
        ])->values();

        $perPage = 20;
        $page = max(1, (int) $request->input('page', 1));
        $offset = ($page - 1) * $perPage;
        $pagedRows = $uniqueClusters->slice($offset, $perPage)->values();

        return response()->json([
            'data' => $pagedRows->map(fn ($row) => $this->formatCluster(
                $row['cluster'],
                detailed: false,
                displayName: (string) $row['display'],
                displayKeywords: (array) ($row['keywords'] ?? [])
            )),
            'meta' => [
                'current_page' => $page,
                'last_page'    => max(1, (int) ceil($uniqueClusters->count() / $perPage)),
                'total'        => $uniqueClusters->count(),
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
            'listings' => fn ($q) => $q->orderByPivot('similarity_score', 'desc')->limit(24),
        ])->findOrFail($id);

        $displayKeywords = $this->buildDisplayKeywords($cluster);
        $displayName = $this->buildDisplayName($cluster, $displayKeywords);

        return response()->json($this->formatCluster(
            $cluster,
            detailed: true,
            displayName: $displayName,
            displayKeywords: $displayKeywords
        ));
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

    private function formatCluster(
        TrendCluster $cluster,
        bool $detailed = false,
        ?string $displayName = null,
        ?array $displayKeywords = null
    ): array
    {
        $displayKeywords = $displayKeywords ?? $this->buildDisplayKeywords($cluster);
        $displayName = $displayName ?? $this->buildDisplayName($cluster, $displayKeywords);

        $sortedListings = $cluster->listings
            ->sort(function ($a, $b) {
                $aHasImage = $this->isRealImageUrl($a->image) ? 1 : 0;
                $bHasImage = $this->isRealImageUrl($b->image) ? 1 : 0;
                if ($aHasImage !== $bHasImage) {
                    return $bHasImage <=> $aHasImage;
                }

                $aSim = (float) ($a->pivot->similarity_score ?? 0);
                $bSim = (float) ($b->pivot->similarity_score ?? 0);
                return $bSim <=> $aSim;
            })
            ->values();

        $curatedImages = $this->buildCuratedImages($cluster, $sortedListings, $displayName, $displayKeywords);

        $data = [
            'id'                => $cluster->id,
            'name'              => $cluster->name,
            'display_name'      => $displayName,
            'summary'           => $cluster->summary,
            'design_prompt'     => $cluster->design_prompt,
            'top_keywords'      => $displayKeywords,
            'score'             => $cluster->score,
            'growth_rate'       => $cluster->growth_rate,
            'competition_score' => $cluster->competition_score,
            'listing_count'     => $cluster->listing_count,
            'keyword'           => $cluster->keyword,
            'created_at'        => $cluster->created_at?->toIso8601String(),
            'curated_images'    => $curatedImages,
            'sample_listings'   => $sortedListings->map(fn ($l) => [
                'id'    => $l->id,
                'title' => $l->title,
                'price' => $l->price,
                'url'   => $l->url,
                'image' => $l->image,
                'tags'  => $l->tags ?? [],
                'similarity_score' => (float) ($l->pivot->similarity_score ?? 0),
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

    private function isRealImageUrl(?string $url): bool
    {
        if (!$url) {
            return false;
        }

        $u = mb_strtolower(trim($url));
        if ($u === '') {
            return false;
        }

        if (str_contains($u, 'loremflickr') || str_contains($u, 'picsum')) {
            return false;
        }

        return str_starts_with($u, 'http://') || str_starts_with($u, 'https://');
    }

    private function buildDisplayName(TrendCluster $cluster, array $displayKeywords = []): string
    {
        $topKeywords = array_values(array_filter(
            array_map(fn ($k) => mb_strtolower(trim((string) $k)), $displayKeywords),
            fn ($k) => $k !== '' && !in_array($k, self::GENERIC_NAME_WORDS, true)
        ));

        if (count($topKeywords) >= 2) {
            $chosen = array_slice(array_unique($topKeywords), 0, 3);
            $base = implode(' ', array_map(fn ($w) => mb_convert_case($w, MB_CASE_TITLE, 'UTF-8'), $chosen));
            return trim($base . ' Designs');
        }

        $fallback = mb_strtolower(trim((string) $cluster->name));
        $fallback = preg_replace('/^(?:trending|trend|trendy|etsy)\s+/i', '', $fallback);
        $fallback = preg_replace('/\b(?:loading|popular|now|pick|bestseller|favorite|favorites|shirts?|tees?|t-?shirts?|hoodies?|sweatshirts?|apparel|clothing|niche|cluster)\b/i', '', (string) $fallback);
        $fallback = preg_replace('/\s+/', ' ', (string) $fallback);
        $fallback = trim((string) $fallback, ' -_,.;:|/');

        if ($fallback === '' || preg_match('/^\d+$/', $fallback)) {
            return '';
        }

        return mb_convert_case($fallback, MB_CASE_TITLE, 'UTF-8');
    }

    private function isValidDisplayName(string $name): bool
    {
        $n = trim($name);
        if ($n === '') {
            return false;
        }

        // Reject raw numeric labels like "7".
        if (preg_match('/^\d+$/', $n)) {
            return false;
        }

        // Require at least 2 alphabetic words to qualify as a niche label.
        preg_match_all('/[a-zA-Z]{2,}/', $n, $m);
        return count($m[0] ?? []) >= 2;
    }

    /**
     * Pick up to 3 real listing images most aligned with the niche keywords.
     *
     * @param \Illuminate\Support\Collection<int, mixed> $sortedListings
     * @return string[]
     */
    private function buildCuratedImages(TrendCluster $cluster, $sortedListings, string $displayName, array $displayKeywords = []): array
    {
        $keywordPool = array_values(array_unique(array_merge(
            $this->tokenizeForMatch($displayName),
            array_filter(array_map(fn ($k) => mb_strtolower(trim((string) $k)), $displayKeywords))
        )));

        $scored = $sortedListings
            ->filter(fn ($l) => $this->isRealImageUrl($l->image))
            ->map(function ($l) use ($keywordPool) {
                $titleTokens = $this->tokenizeForMatch((string) ($l->title ?? ''));
                $tagTokens = [];
                foreach ((array) ($l->tags ?? []) as $tag) {
                    $tagTokens = array_merge($tagTokens, $this->tokenizeForMatch((string) $tag));
                }

                $listingTokens = array_values(array_unique(array_merge($titleTokens, $tagTokens)));
                $overlap = count(array_intersect($keywordPool, $listingTokens));
                $similarity = (float) ($l->pivot->similarity_score ?? 0);

                // Strongly prioritize semantic overlap; similarity is a tiebreaker.
                $score = ($overlap * 100) + $similarity;

                return [
                    'image' => (string) $l->image,
                    'score' => $score,
                    'overlap' => $overlap,
                    'similarity' => $similarity,
                ];
            })
            ->sortByDesc('score')
            ->values();

        $picked = [];
        $seen = [];

        // First pass: keep only images with explicit keyword overlap.
        foreach ($scored as $row) {
            if (($row['overlap'] ?? 0) <= 0) {
                continue;
            }
            $img = (string) ($row['image'] ?? '');
            if ($img === '' || isset($seen[$img])) {
                continue;
            }
            $seen[$img] = true;
            $picked[] = $img;
            if (count($picked) >= 3) {
                break;
            }
        }

        // Second pass: if overlap is too strict for this cluster, use high-similarity real images.
        if (count($picked) < 3) {
            foreach ($scored as $row) {
                $img = (string) ($row['image'] ?? '');
                if ($img === '' || isset($seen[$img])) {
                    continue;
                }

                $similarity = (float) ($row['similarity'] ?? 0);
                if ($similarity < 0.35) {
                    continue;
                }

                $seen[$img] = true;
                $picked[] = $img;
                if (count($picked) >= 3) {
                    break;
                }
            }
        }

        return $picked;
    }

    /**
     * Tokenizer for niche-image matching.
     * Keeps only meaningful alphabetic terms and drops generic marketplace words.
     *
     * @return string[]
     */
    private function tokenizeForMatch(string $text): array
    {
        $s = mb_strtolower($text);
        $s = preg_replace('/[^a-z\s]/', ' ', $s);
        $parts = preg_split('/\s+/', trim((string) $s)) ?: [];

        return array_values(array_filter(
            $parts,
            fn ($w) => strlen($w) >= 3 && !in_array($w, self::GENERIC_NAME_WORDS, true)
        ));
    }

    /**
     * A cluster is publishable only if at least one real-image listing overlaps niche terms.
     */
    private function hasVisualEvidence(TrendCluster $cluster, string $displayName, array $displayKeywords = []): bool
    {
        $keywordPool = array_values(array_unique(array_merge(
            $this->tokenizeForMatch($displayName),
            array_filter(array_map(fn ($k) => mb_strtolower(trim((string) $k)), $displayKeywords))
        )));

        if (count($keywordPool) === 0) {
            return false;
        }

        foreach ($cluster->listings as $l) {
            if (!$this->isRealImageUrl($l->image)) {
                continue;
            }

            $titleTokens = $this->tokenizeForMatch((string) ($l->title ?? ''));
            $tagTokens = [];
            foreach ((array) ($l->tags ?? []) as $tag) {
                $tagTokens = array_merge($tagTokens, $this->tokenizeForMatch((string) $tag));
            }

            $listingTokens = array_values(array_unique(array_merge($titleTokens, $tagTokens)));
            $overlap = count(array_intersect($keywordPool, $listingTokens));

            if ($overlap > 0) {
                return true;
            }
        }

        return false;
    }

    /**
     * Build cleaner niche keywords from repeated real listing signals.
     *
     * @return string[]
     */
    private function buildDisplayKeywords(TrendCluster $cluster): array
    {
        $counts = [];

        // 1) Start with model keywords (light weight).
        foreach ((array) ($cluster->top_keywords ?? []) as $kw) {
            $token = mb_strtolower(trim((string) $kw));
            if ($token === '' || in_array($token, self::GENERIC_NAME_WORDS, true)) {
                continue;
            }
            $counts[$token] = ($counts[$token] ?? 0) + 1;
        }

        // 2) Reinforce with repeated title/tag tokens across listings.
        foreach ($cluster->listings as $l) {
            $tokens = $this->tokenizeForMatch((string) ($l->title ?? ''));
            foreach ((array) ($l->tags ?? []) as $tag) {
                $tokens = array_merge($tokens, $this->tokenizeForMatch((string) $tag));
            }

            foreach (array_unique($tokens) as $t) {
                if ($t === '' || in_array($t, self::GENERIC_NAME_WORDS, true)) {
                    continue;
                }
                $counts[$t] = ($counts[$t] ?? 0) + 1;
            }
        }

        if (empty($counts)) {
            return [];
        }

        arsort($counts);

        // Keep terms seen at least twice where possible to avoid one-off noisy words.
        $stable = array_keys(array_filter($counts, fn ($c) => $c >= 2));
        $pool = count($stable) >= 2 ? $stable : array_keys($counts);

        return array_slice(array_values($pool), 0, 8);
    }
}
