@extends('layouts.admin')

@section('title', 'API Costs')

@push('head')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4"></script>
@endpush

@section('content')
<div class="mb-8 flex flex-wrap items-start gap-3 justify-between">
    <div>
        <h1 class="text-2xl font-serif text-white">API Costs</h1>
        <p class="text-sm text-white/40 mt-1">Fixed cost telemetry per image/service</p>
    </div>
    <span class="text-xs px-3 py-1.5 rounded-full font-medium shrink-0" style="background:rgba(59,130,246,0.18);border:1px solid rgba(59,130,246,0.35);color:#93c5fd">
        <i class="fas fa-circle-check mr-1"></i> Fixed price tracking
    </span>
</div>

<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
    <div class="rounded-2xl p-5" style="background:#111;border:1px solid rgba(255,255,255,0.08)">
        <p class="text-xs text-white/35 uppercase tracking-wider mb-1">Total to date</p>
        <p class="text-2xl font-serif font-bold text-white">${{ number_format($totalCost, 4) }}</p>
        <p class="text-xs text-white/30 mt-1">{{ number_format($totalCalls) }} calls</p>
    </div>
    <div class="rounded-2xl p-5" style="background:#111;border:1px solid rgba(255,255,255,0.08)">
        <p class="text-xs text-white/35 uppercase tracking-wider mb-1">This month</p>
        <p class="text-2xl font-serif font-bold" style="color:#c084fc">${{ number_format($thisMonthCost, 4) }}</p>
        <p class="text-xs text-white/30 mt-1">{{ now()->format('F Y') }}</p>
    </div>
    <div class="rounded-2xl p-5" style="background:#111;border:1px solid rgba(255,255,255,0.08)">
        <p class="text-xs text-white/35 uppercase tracking-wider mb-1">Today</p>
        <p class="text-2xl font-serif font-bold" style="color:#6ee7b7">${{ number_format($todayCost, 4) }}</p>
        <p class="text-xs text-white/30 mt-1">{{ now()->format('M d, Y') }}</p>
    </div>
    <div class="rounded-2xl p-5" style="background:#111;border:1px solid rgba(255,255,255,0.08)">
        <p class="text-xs text-white/35 uppercase tracking-wider mb-1">Avg. cost / call</p>
        <p class="text-2xl font-serif font-bold text-white">${{ $totalCalls > 0 ? number_format($totalCost / $totalCalls, 5) : '0.00000' }}</p>
        <p class="text-xs text-white/30 mt-1">All services</p>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
    <div class="col-span-1 rounded-2xl p-6" style="background:#111;border:1px solid rgba(255,255,255,0.08)">
        <h2 class="text-sm font-semibold text-white mb-4">By service</h2>
        @forelse($byService as $row)
            @php
                $serviceColors = [
                    'together'    => 'background:rgba(124,60,160,0.2);color:#c084fc;border:1px solid rgba(124,60,160,0.35)',
                    'chutes'      => 'background:rgba(59,130,246,0.2);color:#93c5fd;border:1px solid rgba(59,130,246,0.35)',
                    'gemini'      => 'background:rgba(16,185,129,0.2);color:#6ee7b7;border:1px solid rgba(16,185,129,0.35)',
                    'nanogpt'     => 'background:rgba(244,114,182,0.2);color:#f9a8d4;border:1px solid rgba(244,114,182,0.35)',
                    'rnbulktools' => 'background:rgba(245,158,11,0.2);color:#fcd34d;border:1px solid rgba(245,158,11,0.35)',
                ];
                $badgeStyle = $serviceColors[$row->service] ?? 'background:rgba(255,255,255,0.08);color:rgba(255,255,255,0.7);border:1px solid rgba(255,255,255,0.12)';
            @endphp
            <div class="flex items-center justify-between py-3" style="border-bottom:1px solid rgba(255,255,255,0.06)">
                <div class="flex items-center gap-2">
                    <span class="text-xs font-mono px-2 py-0.5 rounded" style="{{ $badgeStyle }}">{{ $row->service }}</span>
                    <span class="text-xs text-white/40">{{ number_format($row->calls) }} calls</span>
                </div>
                <span class="text-sm font-semibold text-white/80">${{ number_format($row->total_cost, 4) }}</span>
            </div>
        @empty
            <p class="text-sm text-white/35 py-4 text-center">No data yet</p>
        @endforelse
    </div>

    <div class="lg:col-span-2 rounded-2xl p-6" style="background:#111;border:1px solid rgba(255,255,255,0.08)">
        <h2 class="text-sm font-semibold text-white mb-4">Daily cost (last 30 days)</h2>
        <div style="position:relative;height:220px">
            <canvas id="dailyCostChart"></canvas>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    <div class="rounded-2xl p-6" style="background:#111;border:1px solid rgba(255,255,255,0.08)">
        <h2 class="text-sm font-semibold text-white mb-4">By model</h2>
        <table class="w-full text-sm">
            <thead>
                <tr style="border-bottom:1px solid rgba(255,255,255,0.08)">
                    <th class="text-left pb-2 text-white/35">Service / Model</th>
                    <th class="text-right pb-2 text-white/35">Calls</th>
                    <th class="text-right pb-2 text-white/35">Cost</th>
                </tr>
            </thead>
            <tbody>
                @forelse($byModel as $row)
                    <tr style="border-bottom:1px solid rgba(255,255,255,0.05)">
                        <td class="py-2">
                            <span class="text-xs text-white/35">{{ $row->service }}</span>
                            <span class="mx-1 text-white/20">/</span>
                            <span class="font-mono text-xs text-white/70">{{ $row->model ?? '—' }}</span>
                        </td>
                        <td class="text-right py-2 text-white/45">{{ number_format($row->calls) }}</td>
                        <td class="text-right py-2 font-semibold text-white/80">${{ number_format($row->total_cost, 4) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="3" class="py-4 text-center text-white/35">No data yet</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="rounded-2xl p-6" style="background:#111;border:1px solid rgba(255,255,255,0.08)">
        <h2 class="text-sm font-semibold text-white mb-4">Top users by spend</h2>
        <table class="w-full text-sm">
            <thead>
                <tr style="border-bottom:1px solid rgba(255,255,255,0.08)">
                    <th class="text-left pb-2 text-white/35">User</th>
                    <th class="text-right pb-2 text-white/35">Calls</th>
                    <th class="text-right pb-2 text-white/35">Cost</th>
                </tr>
            </thead>
            <tbody>
                @forelse($topUsers as $row)
                    <tr style="border-bottom:1px solid rgba(255,255,255,0.05)">
                        <td class="py-2">
                            <p class="font-medium text-white/80">{{ $row->user?->name ?? 'Deleted' }}</p>
                            <p class="text-xs text-white/35">{{ $row->user?->email }}</p>
                        </td>
                        <td class="text-right py-2 text-white/45">{{ number_format($row->calls) }}</td>
                        <td class="text-right py-2 font-semibold text-white/80">${{ number_format($row->total_cost, 4) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="3" class="py-4 text-center text-white/35">No data yet</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="mt-8 rounded-2xl p-6" style="background:#111;border:1px solid rgba(255,255,255,0.08)">
    <div class="flex flex-wrap items-center gap-3 mb-5">
        <h2 class="text-sm font-semibold text-white">Pricing reference</h2>
        <span class="text-xs px-2 py-0.5 rounded-full font-medium" style="background:rgba(245,158,11,0.2);border:1px solid rgba(245,158,11,0.35);color:#fcd34d">
            <i class="fas fa-triangle-exclamation mr-1"></i>Prices verified Apr 2025
        </span>
        <span class="text-xs text-white/30 ml-auto">* = official price not publicly listed (estimate)</span>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-4">
        <div class="rounded-xl p-4 flex flex-col gap-2" style="background:rgba(124,60,160,0.08);border:1px solid rgba(124,60,160,0.24)">
            <div class="flex items-center justify-between">
                <span class="text-xs font-mono px-2 py-0.5 rounded" style="background:rgba(124,60,160,0.18);color:#c084fc">together</span>
                <span class="text-lg font-serif font-bold" style="color:#c084fc">$0.0154 <span class="text-xs font-sans text-white/35">/ img</span></span>
            </div>
            <p class="text-xs font-semibold text-white/80">FLUX.2 [dev]</p>
            <p class="text-xs text-white/40 leading-relaxed">Text-to-image and img2img (reference photo edits). Together's highest-quality image model. 1024 × 1024 · 28 steps.</p>
        </div>

        <div class="rounded-xl p-4 flex flex-col gap-2" style="background:rgba(59,130,246,0.08);border:1px solid rgba(59,130,246,0.24)">
            <div class="flex items-center justify-between">
                <span class="text-xs font-mono px-2 py-0.5 rounded" style="background:rgba(59,130,246,0.18);color:#93c5fd">chutes</span>
                <span class="text-sm font-serif font-bold" style="color:#93c5fd">variable</span>
            </div>
            <p class="text-xs font-semibold text-white/80">z_image_turbo and flux_schnell</p>
            <p class="text-xs text-white/40 leading-relaxed">Fast text-to-image only. No reference-image support.</p>
            <p class="text-xs text-white/40"><span class="font-mono font-semibold" style="color:#93c5fd">z_image_turbo</span> — $0.0005 / sec</p>
            <p class="text-xs text-white/40"><span class="font-mono font-semibold" style="color:#93c5fd">flux_schnell</span> — $0.0010 / step</p>
        </div>

        <div class="rounded-xl p-4 flex flex-col gap-2" style="background:rgba(245,158,11,0.08);border:1px solid rgba(245,158,11,0.24)">
            <div class="flex items-center justify-between">
                <span class="text-xs font-mono px-2 py-0.5 rounded" style="background:rgba(245,158,11,0.18);color:#fcd34d">rnbulktools</span>
                <span class="text-lg font-serif font-bold" style="color:#fcd34d">~$0.01 <span class="text-xs font-sans text-white/35">/ op *</span></span>
            </div>
            <p class="text-xs font-semibold text-white/80">remove_bg</p>
            <p class="text-xs text-white/40 leading-relaxed">Background removal called automatically on each generated image.</p>
        </div>

        <div class="rounded-xl p-4 flex flex-col gap-2" style="background:rgba(244,114,182,0.08);border:1px solid rgba(244,114,182,0.24)">
            <div class="flex items-center justify-between">
                <span class="text-xs font-mono px-2 py-0.5 rounded" style="background:rgba(244,114,182,0.18);color:#f9a8d4">nanogpt</span>
                <span class="text-lg font-serif font-bold" style="color:#f9a8d4">$0.066 <span class="text-xs font-sans text-white/35">/ img</span></span>
            </div>
            <p class="text-xs font-semibold text-white/80">gpt_image_2 · juggernaut_z</p>
            <p class="text-xs text-white/40 leading-relaxed">High-quality text-to-image generation for business plan (NanoGPT model selector).</p>
        </div>
    </div>

    <p class="text-xs text-white/30 mt-4 text-right">
        Costs tracked via <span class="font-mono px-1 rounded" style="background:rgba(255,255,255,0.08)">api_usage_logs</span>
        and <span class="font-mono px-1 rounded" style="background:rgba(255,255,255,0.08)">ApiUsageLog::COST_MAP</span>
    </p>
</div>
@endsection

@push('scripts')
<script>
    Chart.defaults.color = 'rgba(255,255,255,0.65)';
    Chart.defaults.borderColor = 'rgba(255,255,255,0.08)';

    const dailyLabels = @json(array_keys($dailyCost));
    const dailyValues = @json(array_values($dailyCost));

    new Chart(document.getElementById('dailyCostChart'), {
        type: 'bar',
        data: {
            labels: dailyLabels,
            datasets: [{
                label: 'Cost USD',
                data: dailyValues,
                backgroundColor: 'rgba(147, 51, 234, 0.2)',
                borderColor: 'rgba(192, 132, 252, 0.85)',
                borderWidth: 1,
                borderRadius: 4,
            }],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: { callbacks: { label: ctx => '$' + ctx.parsed.y.toFixed(5) } },
            },
            scales: {
                x: { ticks: { maxTicksLimit: 10, font: { size: 10 } }, grid: { display: false } },
                y: { ticks: { callback: v => '$' + v.toFixed(4), font: { size: 10 } }, grid: { color: 'rgba(255,255,255,0.08)' } },
            },
        },
    });
</script>
@endpush
