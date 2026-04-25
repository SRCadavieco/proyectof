@extends('layouts.admin')

@section('title', 'Dashboard')

@push('head')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4"></script>
@endpush

@section('content')

{{-- Header --}}
<div class="mb-8">
    <h1 class="text-2xl font-serif">Dashboard</h1>
    <p class="text-ink-muted text-sm mt-1">FabricAI at a glance</p>
</div>

{{-- Stats cards --}}
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
    <div class="bg-white border border-cream-200 rounded-xl p-6">
        <div class="w-10 h-10 rounded-lg bg-purple-50 flex items-center justify-center mb-4">
            <i class="fas fa-users text-purple-600"></i>
        </div>
        <p class="text-2xl font-bold">{{ number_format($totalUsers) }}</p>
        <p class="text-sm text-ink-muted mt-1">Total users</p>
    </div>
    <div class="bg-white border border-cream-200 rounded-xl p-6">
        <div class="w-10 h-10 rounded-lg bg-green-50 flex items-center justify-center mb-4">
            <i class="fas fa-circle text-green-600 text-xs"></i>
        </div>
        <p class="text-2xl font-bold">{{ number_format($onlineUsers) }}</p>
        <p class="text-sm text-ink-muted mt-1">Online users (last 5 min)</p>
    </div>
    <div class="bg-white border border-cream-200 rounded-xl p-6">
        <div class="w-10 h-10 rounded-lg bg-blue-50 flex items-center justify-center mb-4">
            <i class="fas fa-comments text-blue-600"></i>
        </div>
        <p class="text-2xl font-bold">{{ number_format($totalChats) }}</p>
        <p class="text-sm text-ink-muted mt-1">Total chats</p>
    </div>
    <div class="bg-white border border-cream-200 rounded-xl p-6">
        <div class="w-10 h-10 rounded-lg bg-amber-50 flex items-center justify-center mb-4">
            <i class="fas fa-palette text-amber-600"></i>
        </div>
        <p class="text-2xl font-bold">{{ number_format($totalMessages) }}</p>
        <p class="text-sm text-ink-muted mt-1">Total messages</p>
    </div>
</div>

{{-- Charts row --}}
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
    <div class="bg-white border border-cream-200 rounded-xl p-6">
        <h3 class="text-sm font-semibold text-ink mb-1">
            <i class="fas fa-clock text-purple-600 mr-2"></i>Active users by hour
        </h3>
        <p class="text-xs text-ink-muted mb-4">Unique active users per hour — last 7 days</p>
        <div style="position:relative;height:200px">
            <canvas id="hourlyChart"></canvas>
        </div>
    </div>
    <div class="bg-white border border-cream-200 rounded-xl p-6">
        <h3 class="text-sm font-semibold text-ink mb-1">
            <i class="fas fa-user-plus text-green-600 mr-2"></i>New registrations
        </h3>
        <p class="text-xs text-ink-muted mb-4">Accounts created per day — last 30 days</p>
        <div style="position:relative;height:200px">
            <canvas id="registrationsChart"></canvas>
        </div>
    </div>
</div>

{{-- Plans --}}
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <div class="bg-white border border-cream-200 rounded-xl p-6">
        <h3 class="text-sm font-semibold text-ink mb-4">
            <i class="fas fa-layer-group text-purple-600 mr-2"></i>Plan breakdown
        </h3>
        <div style="position:relative;height:200px">
            <canvas id="plansChart"></canvas>
        </div>
    </div>
    <div class="bg-white border border-cream-200 rounded-xl p-6 lg:col-span-2">
        <h3 class="text-sm font-semibold text-ink mb-4">
            <i class="fas fa-info-circle text-blue-600 mr-2"></i>Plans summary
        </h3>
        <div class="space-y-4">
            @php
                $planColors = ['free' => 'gray', 'pro' => 'purple', 'studio' => 'amber'];
            @endphp
            @foreach(['free', 'pro', 'studio'] as $plan)
                @php
                    $count = $usersByPlan[$plan] ?? 0;
                    $pct   = $totalUsers > 0 ? round($count / $totalUsers * 100, 1) : 0;
                    $color = $planColors[$plan];
                @endphp
                <div>
                    <div class="flex justify-between items-center mb-1">
                        <span class="text-sm font-medium text-ink">{{ ucfirst($plan) }}</span>
                        <span class="text-xs text-ink-muted">{{ $count }} users ({{ $pct }}%)</span>
                    </div>
                    <div class="w-full bg-cream-200 rounded-full h-2">
                        <div class="h-2 rounded-full bg-{{ $color }}-500 transition-all"
                             style="width: {{ $pct }}%"></div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
    Chart.defaults.color       = '#6b6b6b';
    Chart.defaults.borderColor = '#e8e5e0';

    new Chart(document.getElementById('hourlyChart'), {
        type: 'bar',
        data: {
            labels: {!! json_encode(array_map(fn($h) => str_pad($h, 2, '0', STR_PAD_LEFT) . ':00', array_keys($hourlyData))) !!},
            datasets: [{
                label: 'Users',
                data: {!! json_encode(array_values($hourlyData)) !!},
                backgroundColor: 'rgba(139, 92, 246, 0.3)',
                borderColor: 'rgb(139, 92, 246)',
                borderWidth: 1, borderRadius: 4,
            }],
        },
        options: {
            responsive: true, maintainAspectRatio: false,
            plugins: { legend: { display: false }, tooltip: { callbacks: { label: ctx => ctx.parsed.y + ' user(s)' } } },
            scales: {
                y: { beginAtZero: true, grid: { color: '#e8e5e0' }, ticks: { stepSize: 1, callback: v => Number.isInteger(v) ? v : '' } },
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
                backgroundColor: 'rgba(34, 197, 94, 0.3)',
                borderColor: 'rgb(34, 197, 94)',
                borderWidth: 1, borderRadius: 4,
            }],
        },
        options: {
            responsive: true, maintainAspectRatio: false,
            plugins: { legend: { display: false }, tooltip: { callbacks: { title: ctx => regLabels[ctx[0].dataIndex], label: ctx => ctx.parsed.y + ' registration(s)' } } },
            scales: {
                y: { beginAtZero: true, grid: { color: '#e8e5e0' }, ticks: { stepSize: 1, callback: v => Number.isInteger(v) ? v : '' } },
                x: { grid: { display: false }, ticks: { maxRotation: 45, autoSkip: true, maxTicksLimit: 15 } },
            },
        },
    });

    new Chart(document.getElementById('plansChart'), {
        type: 'doughnut',
        data: {
            labels: ['Free', 'Pro', 'Studio'],
            datasets: [{
                data: [{{ $usersByPlan['free'] ?? 0 }}, {{ $usersByPlan['pro'] ?? 0 }}, {{ $usersByPlan['studio'] ?? 0 }}],
                backgroundColor: ['rgba(107,114,128,0.7)', 'rgba(139,92,246,0.7)', 'rgba(245,158,11,0.7)'],
                borderColor: '#ffffff', borderWidth: 2,
            }],
        },
        options: {
            responsive: true, maintainAspectRatio: false,
            plugins: { legend: { position: 'bottom', labels: { padding: 20, usePointStyle: true } } },
        },
    });
</script>
@endpush
