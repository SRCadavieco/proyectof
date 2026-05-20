<?php

namespace App\Services;

use App\Models\EtsyListing;
use App\Models\TrendCluster;use App\Models\TrendItem;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * TrendProcessorService
 *
 * Pipeline:
 *   1. Load recent Etsy listings from DB
 *   2. Normalise titles → token bags
 *   3. Build TF-IDF vectors
 *   4. K-Means clustering
 *   5. Score each cluster (growth_rate, competition_score, trend_score)
 *   6. Use LLM to name clusters and generate summaries
 *   7. Persist TrendCluster + TrendItem records
 */
class TrendProcessorService
{

    // Stop words to strip from titles before vectorising
    private const STOP_WORDS = [
        'a','an','the','and','or','for','in','on','of','to','with','is','it',
        'this','that','are','was','be','by','at','as','from','my','your','our',
        'its','we','i','you','he','she','they','have','has','had','do','did',
        'will','can','not','no','if','so','up','out','but','about','into',
        'than','then','there','when','who','all','more','also','just','over',
        'back','after','use','two','new','old','great','good','best','cute',
        'cool','nice','love','funny','vintage','retro','custom','personalized',
        'gift','gifts','tshirt','shirt','tee','t-shirt','unisex','women','men',
        'kids','adult','plus','size','xs','sm','md','lg','xl','2xl','3xl',
    ];

    private const NUM_CLUSTERS    = 8;
    private const MAX_KMEANS_ITER = 20;
    // Only process listings from the last N days
    private const RECENCY_DAYS = 30;

    /** @var array<string, int> Vocabulary built during TF-IDF (term → index) */
    private array $vocab = [];

    public function __construct() {}

    /**
     * Run the full trend pipeline for an optional keyword filter.
     * Returns the number of clusters produced.
     */
    public function run(?string $keyword = null): int
    {
        $query = EtsyListing::query()
            ->where('created_at', '>=', Carbon::now()->subDays(self::RECENCY_DAYS));

        if ($keyword) {
            $query->where('keyword', $keyword);
        }

        /** @var Collection<int, EtsyListing> $listings */
        $listings = $query->get();

        if ($listings->isEmpty()) {
            Log::info('[TrendProcessor] No listings to process', ['keyword' => $keyword]);
            return 0;
        }

        Log::info('[TrendProcessor] Processing listings', ['count' => $listings->count()]);

        // Step 1 — Tokenise
        $tokenBags = $listings->map(fn ($l) => $this->tokenise($l->title));

        // Step 2 — TF-IDF
        $vectors = $this->buildTfIdfVectors($tokenBags);

        // Step 3 — K-Means clustering
        $k        = min(self::NUM_CLUSTERS, (int) ceil($listings->count() / 4));
        $clusters = $this->kMeans($vectors, $k);

        // Step 4 — Score + persist
        $saved = 0;
        foreach ($clusters as $clusterIndex => $memberIndices) {
            if (empty($memberIndices)) continue;

            $clusterListings = $listings->values()->only($memberIndices);
            $clusterVectors  = array_map(fn ($i) => $vectors[$i], $memberIndices);

            $centroid        = $this->centroid($clusterVectors);
            $topKeywords     = $this->topKeywordsFromCentroid($centroid, 8);
            $score           = $this->computeScore($clusterListings);
            $growthRate      = $this->computeGrowthRate($clusterListings);
            $competition     = $this->computeCompetition($clusterListings);

            // LLM naming (non-blocking — fall back on keyword list if LLM fails)
            [$name, $summary, $designPrompt] = $this->nameClusters($topKeywords, $clusterListings->take(5));

            $cluster = TrendCluster::create([
                'name'              => $name,
                'summary'           => $summary,
                'design_prompt'     => $designPrompt,
                'top_keywords'      => $topKeywords,
                'embedding_vector'  => array_slice($centroid, 0, 64), // store compact vector
                'score'             => round($score, 4),
                'growth_rate'       => round($growthRate, 4),
                'competition_score' => round($competition, 4),
                'listing_count'     => count($memberIndices),
                'keyword'           => $keyword,
            ]);

            foreach ($memberIndices as $idx) {
                $listing   = $listings->values()->get($idx);
                $sim       = $this->cosineSimilarity($vectors[$idx], $centroid);

                TrendItem::updateOrCreate(
                    ['cluster_id' => $cluster->id, 'listing_id' => $listing->id],
                    ['similarity_score' => round($sim, 4)]
                );
            }

            $saved++;
        }

        Log::info('[TrendProcessor] Finished', ['clusters_saved' => $saved]);
        return $saved;
    }

    // ─── Tokenisation ────────────────────────────────────────────────────────

    private function tokenise(string $text): array
    {
        $text   = strtolower($text);
        $text   = preg_replace('/[^a-z\s]/', ' ', $text);
        $tokens = preg_split('/\s+/', trim($text));

        return array_values(array_filter(
            $tokens,
            fn ($t) => strlen($t) > 2 && !in_array($t, self::STOP_WORDS, true)
        ));
    }

    // ─── TF-IDF ──────────────────────────────────────────────────────────────

    /**
     * Build normalised TF-IDF vectors.
     * Returns a 2-D float array: [listingIndex][termIndex] = tfidf_weight.
     */
    private function buildTfIdfVectors(Collection $tokenBags): array
    {
        $n      = $tokenBags->count();
        $vocab  = [];
        $bagArr = $tokenBags->values()->toArray();

        // Build vocabulary
        foreach ($bagArr as $tokens) {
            foreach (array_unique($tokens) as $t) {
                if (!isset($vocab[$t])) {
                    $vocab[$t] = count($vocab);
                }
            }
        }
        $this->vocab = $vocab; // store for topKeywordsFromCentroid

        $vocabSize = count($vocab);
        $vectors   = array_fill(0, $n, array_fill(0, $vocabSize, 0.0));

        // Compute DF (document frequency)
        $df = array_fill(0, $vocabSize, 0);
        foreach ($bagArr as $tokens) {
            foreach (array_unique($tokens) as $t) {
                if (isset($vocab[$t])) {
                    $df[$vocab[$t]]++;
                }
            }
        }

        // Compute TF-IDF and L2-normalise each vector
        foreach ($bagArr as $docIdx => $tokens) {
            $counts = array_count_values($tokens);
            $len    = count($tokens) ?: 1;

            foreach ($counts as $t => $cnt) {
                if (!isset($vocab[$t])) continue;
                $tid      = $vocab[$t];
                $tf       = $cnt / $len;
                $idf      = log(($n + 1) / ($df[$tid] + 1)) + 1;
                $vectors[$docIdx][$tid] = $tf * $idf;
            }

            // L2 normalise
            $norm = sqrt(array_sum(array_map(fn ($v) => $v * $v, $vectors[$docIdx])));
            if ($norm > 0) {
                $vectors[$docIdx] = array_map(fn ($v) => $v / $norm, $vectors[$docIdx]);
            }
        }

        return $vectors;
    }

    // ─── K-Means ─────────────────────────────────────────────────────────────

    /**
     * Basic K-Means over pre-normalised float vectors.
     * Returns array of clusters, each an array of listing indices.
     */
    private function kMeans(array $vectors, int $k): array
    {
        $n = count($vectors);
        if ($n === 0) return [];

        $k = min($k, $n);

        // Initialise centroids (spread across dataset)
        $step      = (int) max(1, floor($n / $k));
        $centroids = [];
        for ($i = 0; $i < $k; $i++) {
            $centroids[] = $vectors[$i * $step];
        }

        $assignments = array_fill(0, $n, 0);

        for ($iter = 0; $iter < self::MAX_KMEANS_ITER; $iter++) {
            $changed = false;

            // Assignment step
            foreach ($vectors as $idx => $vec) {
                $bestCluster = 0;
                $bestDist    = PHP_FLOAT_MAX;
                foreach ($centroids as $ci => $centroid) {
                    $dist = $this->euclideanDistance($vec, $centroid);
                    if ($dist < $bestDist) {
                        $bestDist    = $dist;
                        $bestCluster = $ci;
                    }
                }
                if ($assignments[$idx] !== $bestCluster) {
                    $assignments[$idx] = $bestCluster;
                    $changed           = true;
                }
            }

            if (!$changed) break;

            // Update step — recompute centroids
            $newCentroids = array_fill(0, $k, null);
            $counts       = array_fill(0, $k, 0);

            foreach ($assignments as $idx => $ci) {
                $counts[$ci]++;
                if ($newCentroids[$ci] === null) {
                    $newCentroids[$ci] = $vectors[$idx];
                } else {
                    foreach ($vectors[$idx] as $dim => $val) {
                        $newCentroids[$ci][$dim] = ($newCentroids[$ci][$dim] ?? 0) + $val;
                    }
                }
            }

            foreach ($newCentroids as $ci => $sum) {
                if ($sum === null) {
                    // Empty cluster — reassign to a random vector to avoid collapse
                    $newCentroids[$ci] = $vectors[array_rand($vectors)];
                    continue;
                }
                $cnt = $counts[$ci] ?: 1;
                $newCentroids[$ci] = array_map(fn ($v) => $v / $cnt, $sum);
            }

            $centroids = $newCentroids;
        }

        // Group indices by cluster
        $result = array_fill(0, $k, []);
        foreach ($assignments as $idx => $ci) {
            $result[$ci][] = $idx;
        }

        return $result;
    }

    // ─── Scoring ─────────────────────────────────────────────────────────────

    /**
     * trend_score = growth_rate / (competition_score + 0.1) * engagement_proxy
     */
    private function computeScore(Collection $listings): float
    {
        $growth  = $this->computeGrowthRate($listings);
        $comp    = $this->computeCompetition($listings);
        $engage  = $this->engagementProxy($listings);
        return $growth / ($comp + 0.1) * $engage;
    }

    /**
     * Growth rate = fraction of listings created in last 7 days.
     */
    private function computeGrowthRate(Collection $listings): float
    {
        $recent = $listings->filter(
            fn ($l) => $l->created_at->gte(Carbon::now()->subDays(7))
        )->count();
        return $listings->count() > 0 ? $recent / $listings->count() : 0;
    }

    /**
     * Competition score = normalised listing count (0–1 scale).
     * More listings → higher competition.
     */
    private function computeCompetition(Collection $listings): float
    {
        // Scale: 50 listings → score 1.0
        return min(1.0, $listings->count() / 50);
    }

    /**
     * Engagement proxy = proportion of listings with images (data quality signal).
     */
    private function engagementProxy(Collection $listings): float
    {
        $withImages = $listings->filter(fn ($l) => !empty($l->image))->count();
        return $listings->count() > 0 ? $withImages / $listings->count() : 0.5;
    }

    // ─── Helpers ─────────────────────────────────────────────────────────────

    private function centroid(array $vectors): array
    {
        $n    = count($vectors);
        if ($n === 0) return [];
        $dims = count($vectors[0]);
        $sum  = array_fill(0, $dims, 0.0);
        foreach ($vectors as $v) {
            foreach ($v as $d => $val) {
                $sum[$d] += $val;
            }
        }
        return array_map(fn ($s) => $s / $n, $sum);
    }

    private function cosineSimilarity(array $a, array $b): float
    {
        $dot = 0.0;
        $na  = 0.0;
        $nb  = 0.0;
        foreach ($a as $i => $va) {
            $vb   = $b[$i] ?? 0;
            $dot += $va * $vb;
            $na  += $va * $va;
            $nb  += $vb * $vb;
        }
        $denom = sqrt($na) * sqrt($nb);
        return $denom > 0 ? $dot / $denom : 0.0;
    }

    private function euclideanDistance(array $a, array $b): float
    {
        $sum = 0.0;
        foreach ($a as $i => $va) {
            $diff = $va - ($b[$i] ?? 0);
            $sum += $diff * $diff;
        }
        return sqrt($sum);
    }

    /**
     * Extract top N keywords from a centroid vector using term indices.
     * Returns plain keyword strings.
     */
    private function topKeywordsFromCentroid(array $centroid, int $n): array
    {
        arsort($centroid);
        $topIndices = array_keys(array_slice($centroid, 0, $n, true));
        // Map numeric indices back to term strings using the stored vocab
        $indexToTerm = array_flip($this->vocab);
        return array_values(array_filter(
            array_map(fn ($idx) => $indexToTerm[$idx] ?? null, $topIndices)
        ));
    }

    // ─── LLM cluster naming ──────────────────────────────────────────────────

    /**
     * Ask the LLM to name a cluster, produce a market insight, and generate a design prompt.
     * Falls back to joining the top keywords if the LLM call fails.
     *
     * @param  array<string> $topKeywords
     * @param  Collection<int, EtsyListing> $sampleListings
     * @return array{0: string, 1: string, 2: string}  [name, summary, design_prompt]
     */
    private function nameClusters(array $topKeywords, Collection $sampleListings): array
    {
        $titles  = $sampleListings->pluck('title')->implode(' | ');
        $kwList  = implode(', ', array_slice($topKeywords, 0, 10));

        $prompt = <<<PROMPT
        You are a print-on-demand creative director.
        Given these Etsy listing titles: [{$titles}]
        And extracted keywords: [{$kwList}]

        1. Give a concise niche cluster name (max 5 words, e.g. "Funny Cat Lover Shirts").
        2. Write a 1-sentence market insight for this cluster.
        3. Write a style_context: a short style descriptor (max 20 words) for t-shirt artwork in this niche.
           Rules for style_context:
           - Describe ONLY the artistic style, color palette (2-3 specific colors), texture/finish, and aesthetic mood.
           - Do NOT include specific subjects, characters, objects, animals, or text content.
           - It will be appended after the user's own subject, so keep it style-pure.
           - Example: "bold vintage illustration, deep red and mustard yellow, distressed screen-print, 1970s Americana aesthetic"

        Respond in JSON: {"name": "...", "summary": "...", "design_prompt": "..."}
        PROMPT;

        $apiKey   = (string) config('services.nanogpt.key', '');
        $llmModel = (string) config('services.nanogpt.llm_model', 'deepseek/deepseek-v4-flash');

        try {
            $response = Http::withHeaders(['x-api-key' => $apiKey])
                ->withToken($apiKey)
                ->timeout(30)
                ->post('https://nano-gpt.com/v1/chat/completions', [
                    'model'       => $llmModel,
                    'messages'    => [['role' => 'user', 'content' => $prompt]],
                    'max_tokens'  => 400,
                    'temperature' => 0.5,
                ]);

            if ($response->successful()) {
                $content = $response->json('choices.0.message.content', '');
                // Extract JSON from the response (may be wrapped in markdown)
                if (preg_match('/\{.*\}/s', $content, $matches)) {
                    $parsed = json_decode($matches[0], true);
                    if (is_array($parsed) && isset($parsed['name'])) {
                        return [
                            (string) $parsed['name'],
                            (string) ($parsed['summary'] ?? ''),
                            (string) ($parsed['design_prompt'] ?? ''),
                        ];
                    }
                }
            }
        } catch (\Throwable $e) {
            Log::warning('[TrendProcessor] LLM naming failed', ['error' => $e->getMessage()]);
        }

        // Fallback
        $fallbackName = implode(' ', array_map('ucfirst', array_slice((array) $topKeywords, 0, 3)));
        return [$fallbackName ?: 'Unnamed Cluster', '', ''];
    }
}
