@extends('layouts.admin')

@section('title', 'API Costs')

@push('head')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4"></script>
@endpush

@section('content')

{{-- Header --}}
<div class="mb-8 flex items-center justify-between">
    <div>
        <h1 class="text-2xl font-serif">API Costs</h1>
        <p class="text-sm text-ink-muted mt-1">Fixed price per generated image — no estimation needed</p>
    </div>
    <span class="text-xs bg-blue-100 text-blue-700 px-3 py-1.5 rounded-full font-medium">
        <i class="fas fa-circle-check mr-1"></i> Fixed price per image
    </span>
</div>

{{-- Summary cards --}}
<div class="grid grid-cols-4 gap-4 mb-8">
    <div class="bg-white rounded-xl border border-cream-200 p-5">
        <p class="text-xs text-ink-muted uppercase tracking-wider mb-1">Total to date</p>
        <p class="text-2xl font-serif font-bold">${{ number_format($totalCost, 4) }}</p>
        <p class="text-xs text-ink-muted mt-1">{{ number_format($totalCalls) }} calls</p>
    </div>
    <div class="bg-white rounded-xl border border-cream-200 p-5">
        <p class="text-xs text-ink-muted uppercase tracking-wider mb-1">This month</p>
        <p class="text-2xl font-serif font-bold text-purple-700">${{ number_format($thisMonthCost, 4) }}</p>
        <p class="text-xs text-ink-muted mt-1">{{ now()->format('F Y') }}</p>
    </div>
    <div class="bg-white rounded-xl border border-cream-200 p-5">
        <p class="text-xs text-ink-muted uppercase tracking-wider mb-1">Today</p>
        <p class="text-2xl font-serif font-bold text-green-700">${{ number_format($todayCost, 4) }}</p>
        <p class="text-xs text-ink-muted mt-1">{{ now()->format('M d, Y') }}</p>
    </div>
    <div class="bg-white rounded-xl border border-cream-200 p-5">
        <p class="text-xs text-ink-muted uppercase tracking-wider mb-1">Avg. cost / call</p>
        <p class="text-2xl font-serif font-bold">
            ${{ $totalCalls > 0 ? number_format($totalCost / $totalCalls, 5) : '0.00000' }}
        </p>
        <p class="text-xs text-ink-muted mt-1">All services</p>
    </div>
</div>

<div class="grid grid-cols-3 gap-6 mb-6">
    {{-- Cost by service --}}
    <div class="col-span-1 bg-white rounded-xl border border-cream-200 p-6">
        <h2 class="text-sm font-semibold mb-4">By service</h2>
        @forelse($byService as $row)
            @php
                $serviceColors = [
                    'together'    => 'bg-purple-100 text-purple-700',
                    'chutes'      => 'bg-blue-100 text-blue-700',
                    'gemini'      => 'bg-green-100 text-green-700',
                    'rnbulktools' => 'bg-amber-100 text-amber-700',
                ];
                $badgeClass = $serviceColors[$row->service] ?? 'bg-cream-100 text-ink';
            @endphp
            <div class="flex items-center justify-between py-3 border-b border-cream-100 last:border-0">
                <div class="flex items-center gap-2">
                    <span class="text-xs font-mono px-2 py-0.5 rounded {{ $badgeClass }}">
                        {{ $row->service }}
                    </span>
                    <span class="text-xs text-ink-muted">{{ number_format($row->calls) }} calls</span>
                </div>
                <span class="text-sm font-semibold">${{ number_format($row->total_cost, 4) }}</span>
            </div>
        @empty
            <p class="text-sm text-ink-muted py-4 text-center">No data yet</p>
        @endforelse
    </div>

    {{-- Daily chart --}}
    <div class="col-span-2 bg-white rounded-xl border border-cream-200 p-6">
        <h2 class="text-sm font-semibold mb-4">Daily cost (last 30 days)</h2>
        <div style="position:relative;height:220px">
            <canvas id="dailyCostChart"></canvas>
        </div>
    </div>
</div>

<div class="grid grid-cols-2 gap-6">
    {{-- By model --}}
    <div class="bg-white rounded-xl border border-cream-200 p-6">
        <h2 class="text-sm font-semibold mb-4">By model</h2>
        <table class="w-full text-sm">
            <thead>
                <tr class="text-xs text-ink-muted border-b border-cream-100">
                    <th class="text-left pb-2">Service / Model</th>
                    <th class="text-right pb-2">Calls</th>
                    <th class="text-right pb-2">Cost</th>
                </tr>
            </thead>
            <tbody>
                @forelse($byModel as $row)
                    <tr class="border-b border-cream-50 hover:bg-cream-50">
                        <td class="py-2">
                            <span class="text-xs text-ink-muted">{{ $row->service }}</span>
                            <span class="mx-1 text-ink-muted">/</span>
                            <span class="font-mono text-xs">{{ $row->model ?? '—' }}</span>
                        </td>
                        <td class="text-right py-2 text-ink-muted">{{ number_format($row->calls) }}</td>
                        <td class="text-right py-2 font-semibold">${{ number_format($row->total_cost, 4) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="3" class="py-4 text-center text-ink-muted">No data yet</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Top users --}}
    <div class="bg-white rounded-xl border border-cream-200 p-6">
        <h2 class="text-sm font-semibold mb-4">Top users by spend</h2>
        <table class="w-full text-sm">
            <thead>
                <tr class="text-xs text-ink-muted border-b border-cream-100">
                    <th class="text-left pb-2">User</th>
                    <th class="text-right pb-2">Calls</th>
                    <th class="text-right pb-2">Cost</th>
                </tr>
            </thead>
            <tbody>
                @forelse($topUsers as $row)
                    <tr class="border-b border-cream-50 hover:bg-cream-50">
                        <td class="py-2">
                            <p class="font-medium">{{ $row->user?->name ?? 'Deleted' }}</p>
                            <p class="text-xs text-ink-muted">{{ $row->user?->email }}</p>
                        </td>
                        <td class="text-right py-2 text-ink-muted">{{ number_format($row->calls) }}</td>
                        <td class="text-right py-2 font-semibold">${{ number_format($row->total_cost, 4) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="3" class="py-4 text-center text-ink-muted">No data yet</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>


@endsection

@push('scripts')
<script>
    const dailyLabels = @json(array_keys($dailyCost));
    const dailyValues = @json(array_values($dailyCost));

    new Chart(document.getElementById('dailyCostChart'), {
        type: 'bar',
        data: {
            labels: dailyLabels,
            datasets: [{
                label: 'Cost USD',
                data: dailyValues,
                backgroundColor: 'rgba(147, 51, 234, 0.15)',
                borderColor: 'rgba(147, 51, 234, 0.7)',
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
                y: { ticks: { callback: v => '$' + v.toFixed(4), font: { size: 10 } }, grid: { color: 'rgba(0,0,0,0.04)' } },
            },
        },
    });
</script>
@endpush