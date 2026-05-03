@extends('layouts.admin')

@section('title', 'Dashboard')

@push('head')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4"></script>
@endpush

@section('content')

<div class="mb-8">
    <h1 class="text-2xl font-serif text-white">Dashboard</h1>
    <p class="text-white/40 text-sm mt-1">FabricAI control center</p>
</div>

<div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4 mb-8">
    <div class="rounded-2xl p-5" style="background:#111;border:1px solid rgba(255,255,255,0.08)">
        <div class="w-10 h-10 rounded-xl flex items-center justify-center mb-4" style="background:rgba(124,60,160,0.2);color:#c084fc">
            <i class="fas fa-users"></i>
        </div>
        <p class="text-2xl font-bold text-white">{{ number_format($totalUsers) }}</p>
        <p class="text-xs uppercase tracking-widest text-white/35 mt-2">Total users</p>
    </div>
    <div class="rounded-2xl p-5" style="background:#111;border:1px solid rgba(255,255,255,0.08)">
        <div class="w-10 h-10 rounded-xl flex items-center justify-center mb-4" style="background:rgba(34,197,94,0.18);color:#4ade80">
            <i class="fas fa-circle text-[10px]"></i>
        </div>
        <p class="text-2xl font-bold text-white">{{ number_format($onlineUsers) }}</p>
        <p class="text-xs uppercase tracking-widest text-white/35 mt-2">Online (5m)</p>
    </div>
    <div class="rounded-2xl p-5" style="background:#111;border:1px solid rgba(255,255,255,0.08)">
        <div class="w-10 h-10 rounded-xl flex items-center justify-center mb-4" style="background:rgba(59,130,246,0.18);color:#60a5fa">
            <i class="fas fa-comments"></i>
        </div>
        <p class="text-2xl font-bold text-white">{{ number_format($totalChats) }}</p>
        <p class="text-xs uppercase tracking-widest text-white/35 mt-2">Total chats</p>
    </div>
    <div class="rounded-2xl p-5" style="background:#111;border:1px solid rgba(255,255,255,0.08)">
        <div class="w-10 h-10 rounded-xl flex items-center justify-center mb-4" style="background:rgba(245,158,11,0.18);color:#fbbf24">
            <i class="fas fa-message"></i>
        </div>
        <p class="text-2xl font-bold text-white">{{ number_format($totalMessages) }}</p>
        <p class="text-xs uppercase tracking-widest text-white/35 mt-2">Total messages</p>
    </div>
</div>

<div class="grid grid-cols-1 xl:grid-cols-2 gap-6 mb-8">
    <div class="rounded-2xl p-6" style="background:#111;border:1px solid rgba(255,255,255,0.08)">
        <h3 class="text-sm font-semibold text-white mb-1">
            <i class="fas fa-clock mr-2" style="color:#c084fc"></i>Active users by hour
        </h3>
        <p class="text-xs text-white/35 mb-4">Unique active users per hour · last 7 days</p>
        <div style="position:relative;height:220px">
            <canvas id="hourlyChart"></canvas>
        </div>
    </div>
    <div class="rounded-2xl p-6" style="background:#111;border:1px solid rgba(255,255,255,0.08)">
        <h3 class="text-sm font-semibold text-white mb-1">
            <i class="fas fa-user-plus mr-2" style="color:#4ade80"></i>New registrations
        </h3>
        <p class="text-xs text-white/35 mb-4">Accounts created per day · last 30 days</p>
        <div style="position:relative;height:220px">
            <canvas id="registrationsChart"></canvas>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
    <div class="rounded-2xl p-6" style="background:#111;border:1px solid rgba(255,255,255,0.08)">
        <h3 class="text-sm font-semibold text-white mb-4">
            <i class="fas fa-layer-group mr-2" style="color:#c084fc"></i>Plan breakdown
        </h3>
        <div style="position:relative;height:220px">
            <canvas id="plansChart"></canvas>
        </div>
    </div>
    <div class="rounded-2xl p-6 xl:col-span-2" style="background:#111;border:1px solid rgba(255,255,255,0.08)">
        <h3 class="text-sm font-semibold text-white mb-4">
            <i class="fas fa-info-circle mr-2" style="color:#60a5fa"></i>Plans summary
        </h3>
        <div class="space-y-4">
            @php
                $planColors = [
                    'free' => '#94a3b8',
                    'starter' => '#60a5fa',
                    'pro' => '#c084fc',
                    'business' => '#34d399',
                ];
            @endphp
            @foreach(['free', 'starter', 'pro', 'business'] as $plan)
                @php
                    $count = $usersByPlan[$plan] ?? 0;
                    $pct   = $totalUsers > 0 ? round($count / $totalUsers * 100, 1) : 0;
                @endphp
                <div>
                    <div class="flex justify-between items-center mb-1.5">
                        <span class="text-sm font-medium text-white/75">{{ ucfirst($plan) }}</span>
                        <span class="text-xs text-white/40">{{ $count }} users ({{ $pct }}%)</span>
                    </div>
                    <div class="w-full rounded-full h-2" style="background:rgba(255,255,255,0.08)">
                        <div class="h-2 rounded-full transition-all" style="width: {{ $pct }}%;background:{{ $planColors[$plan] }}"></div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
    Chart.defaults.color = 'rgba(255,255,255,0.65)';
    Chart.defaults.borderColor = 'rgba(255,255,255,0.09)';

    new Chart(document.getElementById('hourlyChart'), {
        type: 'bar',
        data: {
            labels: {!! json_encode(array_map(fn($h) => str_pad($h, 2, '0', STR_PAD_LEFT) . ':00', array_keys($hourlyData))) !!},
            datasets: [{
                label: 'Users',
                data: {!! json_encode(array_values($hourlyData)) !!},
                backgroundColor: 'rgba(139, 92, 246, 0.24)',
                borderColor: 'rgba(192, 132, 252, 0.9)',
                borderWidth: 1, borderRadius: 4,
            }],
        },
        options: {
            responsive: true, maintainAspectRatio: false,
            plugins: { legend: { display: false }, tooltip: { callbacks: { label: ctx => ctx.parsed.y + ' user(s)' } } },
            scales: {
                y: { beginAtZero: true, grid: { color: 'rgba(255,255,255,0.08)' }, ticks: { stepSize: 1, callback: v => Number.isInteger(v) ? v : '' } },
                x: { grid: { display: false } },
            },
        },
    });

    const regLabels = {!! json_encode(array_keys($dailyRegistrations)) !!};
    const regLabelsShort = regLabels.map(d => { const p = d.split('-'); return p[2] + '/' + p[1]; });
    new Chart(document.getElementById('registrationsChart'), {
        type: 'bar',
        data: {
            labels: regLabelsShort,
            datasets: [{
                label: 'Registrations',
                data: {!! json_encode(array_values($dailyRegistrations)) !!},
                backgroundColor: 'rgba(34, 197, 94, 0.24)',
                borderColor: 'rgba(74, 222, 128, 0.9)',
                borderWidth: 1, borderRadius: 4,
            }],
        },
        options: {
            responsive: true, maintainAspectRatio: false,
            plugins: { legend: { display: false }, tooltip: { callbacks: { title: ctx => regLabels[ctx[0].dataIndex], label: ctx => ctx.parsed.y + ' registration(s)' } } },
            scales: {
                y: { beginAtZero: true, grid: { color: 'rgba(255,255,255,0.08)' }, ticks: { stepSize: 1, callback: v => Number.isInteger(v) ? v : '' } },
                x: { grid: { display: false }, ticks: { maxRotation: 45, autoSkip: true, maxTicksLimit: 15 } },
            },
        },
    });

    new Chart(document.getElementById('plansChart'), {
        type: 'doughnut',
        data: {
            labels: ['Free', 'Starter', 'Pro', 'Business'],
            datasets: [{
                data: [{{ $usersByPlan['free'] ?? 0 }}, {{ $usersByPlan['starter'] ?? 0 }}, {{ $usersByPlan['pro'] ?? 0 }}, {{ $usersByPlan['business'] ?? 0 }}],
                backgroundColor: ['rgba(148,163,184,0.75)', 'rgba(96,165,250,0.75)', 'rgba(192,132,252,0.75)', 'rgba(52,211,153,0.75)'],
                borderColor: '#111', borderWidth: 2,
            }],
        },
        options: {
            responsive: true, maintainAspectRatio: false,
            plugins: { legend: { position: 'bottom', labels: { padding: 20, usePointStyle: true } } },
        },
    });
</script>
@endpush
