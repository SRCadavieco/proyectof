<?php

namespace App\Console\Commands;

use App\Models\TrendCluster;
use Illuminate\Console\Command;

class NormalizeTrendNames extends Command
{
    private const GENERIC_NAME_WORDS = [
        'trend','trending','trendy','etsy','niche','cluster','screen','apparel','clothing',
        'shirt','shirts','tshirt','tshirts','tee','tees','hoodie','hoodies','sweatshirt','sweatshirts',
        'popular','loading','now','pick','bestseller','favorites','favorite',
    ];

    protected $signature = 'trends:normalize-names
                            {--days=30 : Normalize clusters created in the last N days}
                            {--dry-run : Preview changes without saving}';

    protected $description = 'Normalize and deduplicate trend cluster names (remove noisy prefixes like "Trending").';

    /** @var array<string,bool> */
    private array $seen = [];

    public function handle(): int
    {
        $days = max(0, (int) $this->option('days'));
        $dryRun = (bool) $this->option('dry-run');

        $query = TrendCluster::query()->orderBy('created_at')->orderBy('id');
        if ($days > 0) {
            $query->where('created_at', '>=', now()->subDays($days));
        }

        $rows = $query->get();
        if ($rows->isEmpty()) {
            $this->info('No clusters found to normalize.');
            return self::SUCCESS;
        }

        $updated = 0;
        /** @var TrendCluster $row */
        foreach ($rows as $row) {
            $original = (string) $row->name;
            $base = $this->sanitizeName($original);

            if ($base === '' || $this->isGenericName($base)) {
                $base = $this->buildKeywordDrivenName((array) ($row->top_keywords ?? []));
            }
            if ($base === '') {
                $base = 'Niche Cluster';
            }

            $final = $this->uniqueName($base);

            if ($final !== $original) {
                $updated++;
                $this->line("{$row->id}: {$original} -> {$final}");
                if (!$dryRun) {
                    $row->name = $final;
                    $row->save();
                }
            } else {
                $this->line("{$row->id}: {$original} (ok)");
            }
        }

        $this->newLine();
        $this->info('Normalization complete. Updated=' . $updated . ($dryRun ? ' (dry-run)' : ''));

        return self::SUCCESS;
    }

    private function sanitizeName(string $name): string
    {
        $s = trim($name);
        if ($s === '') {
            return '';
        }

        $s = trim($s, " \t\n\r\0\x0B\"'`“”‘’");
        $s = preg_replace('/^(?:trending|trend|trendy|etsy)\s+/i', '', $s);
        $s = preg_replace('/^(?:trending|trend|trendy|etsy)\s+/i', '', (string) $s);
        $s = preg_replace('/\b(?:loading|popular|now|pick|bestseller|favorite|favorites|trendy)\b/i', '', (string) $s);
        $s = preg_replace('/\s+/', ' ', (string) $s);
        $s = trim((string) $s, ' -_,.;:|/');

        $s = mb_convert_case((string) $s, MB_CASE_TITLE, 'UTF-8');

        $words = preg_split('/\s+/', $s) ?: [];
        if (count($words) > 5) {
            $s = implode(' ', array_slice($words, 0, 5));
        }

        return trim($s);
    }

    private function uniqueName(string $base): string
    {
        $key = mb_strtolower($base);
        if (!isset($this->seen[$key])) {
            $this->seen[$key] = true;
            return $base;
        }

        $i = 2;
        while (true) {
            $candidate = "{$base} {$i}";
            $candidateKey = mb_strtolower($candidate);
            if (!isset($this->seen[$candidateKey])) {
                $this->seen[$candidateKey] = true;
                return $candidate;
            }
            $i++;
        }
    }

    private function isGenericName(string $name): bool
    {
        $words = preg_split('/\s+/', mb_strtolower(trim($name))) ?: [];
        $words = array_values(array_filter($words, fn ($w) => $w !== ''));

        if (count($words) <= 1) {
            return true;
        }

        $informative = array_values(array_filter(
            $words,
            fn ($w) => !in_array($w, self::GENERIC_NAME_WORDS, true)
        ));

        return count($informative) < 2;
    }

    private function buildKeywordDrivenName(array $topKeywords): string
    {
        $filtered = array_values(array_filter(
            array_map(fn ($k) => mb_strtolower(trim((string) $k)), $topKeywords),
            fn ($k) => $k !== '' && !in_array($k, self::GENERIC_NAME_WORDS, true)
        ));

        if (count($filtered) === 0) {
            return '';
        }

        $chosen = array_slice(array_unique($filtered), 0, 3);
        $base = implode(' ', array_map(fn ($w) => mb_convert_case($w, MB_CASE_TITLE, 'UTF-8'), $chosen));
        return trim($base . ' Designs');
    }
}
