<?php

namespace App\Console\Commands;

use App\Jobs\ProcessTrendsJob;
use App\Jobs\ScrapeEtsyJob;
use Illuminate\Console\Command;

class ScrapeAndProcessTrends extends Command
{
    protected $signature = 'trends:scrape-and-process
                            {--keywords= : Comma-separated keyword list (overrides defaults)}';

    protected $description = 'Dispatch Etsy scraping + trend processing for configured keywords';

    private const DEFAULT_KEYWORDS = [
        'tshirt funny',
        'retro shirt',
        'cat shirt',
        'camping shirt',
        'motivational quote shirt',
    ];

    public function handle(): int
    {
        $keywords = $this->option('keywords')
            ? array_map('trim', explode(',', (string) $this->option('keywords')))
            : self::DEFAULT_KEYWORDS;

        $keywords = array_filter($keywords, fn ($k) => $k !== '');

        if (empty($keywords)) {
            $this->error('No keywords provided.');
            return self::FAILURE;
        }

        foreach ($keywords as $keyword) {
            ScrapeEtsyJob::dispatch($keyword)->chain([
                new ProcessTrendsJob($keyword),
            ]);
            $this->line("Queued: <info>{$keyword}</info>");
        }

        $this->info('All jobs dispatched.');
        return self::SUCCESS;
    }
}
