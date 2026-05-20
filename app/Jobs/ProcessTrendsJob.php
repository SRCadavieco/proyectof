<?php

namespace App\Jobs;

use App\Services\TrendProcessorService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessTrendsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 1;
    public int $timeout = 600;

    public function __construct(public readonly ?string $keyword = null) {}

    public function handle(TrendProcessorService $processor): void
    {
        Log::info('[ProcessTrendsJob] Starting', ['keyword' => $this->keyword]);
        $count = $processor->run($this->keyword);
        Log::info('[ProcessTrendsJob] Done', ['clusters' => $count]);
    }
}
