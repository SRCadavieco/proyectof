<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>FabricAI — Admin Panel</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body class="bg-cream-50 text-ink min-h-screen">

{{-- Sidebar --}}
<div class="flex min-h-screen">
    <aside class="w-64 bg-white border-r border-cream-200 flex flex-col fixed h-full z-30">
        <div class="p-6 border-b border-cream-200 flex items-center gap-3">
            <a href="/">
                <img src="/images/logo.png" alt="Logo" class="h-12 w-12">
            </a>
            <div>
                <p class="font-serif font-bold text-sm">FabricAI</p>
                <p class="text-xs text-ink-muted uppercase tracking-wider">Admin Panel</p>
            </div>
        </div>
        <nav class="flex-1 p-4 space-y-1">
            <a href="{{ route('admin.dashboard') }}"
               class="flex items-center gap-3 px-4 py-2.5 rounded-lg text-sm font-medium transition
                      {{ request()->routeIs('admin.dashboard') ? 'bg-purple-50 text-purple-700' : 'text-ink-muted hover:text-ink hover:bg-cream-100' }}">
                <i class="fas fa-chart-pie w-5 text-center"></i>
                Dashboard
            </a>
            <a href="{{ route('admin.users') }}"
               class="flex items-center gap-3 px-4 py-2.5 rounded-lg text-sm font-medium transition
                      {{ request()->routeIs('admin.users') ? 'bg-purple-50 text-purple-700' : 'text-ink-muted hover:text-ink hover:bg-cream-100' }}">
                <i class="fas fa-users w-5 text-center"></i>
                Usuarios
            </a>
        </nav>
        <div class="p-4 border-t border-cream-200">
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-full bg-ink flex items-center justify-center text-xs font-bold text-white">
                    {{ strtoupper(mb_substr(Auth::user()->name, 0, 1)) }}
                </div>
                <div class="text-sm">
                    <p class="text-ink font-medium truncate">{{ Auth::user()->name }}</p>
                    <p class="text-xs text-ink-muted truncate">Admin</p>
                </div>
            </div>
            <a href="/" class="mt-3 flex items-center gap-2 text-xs text-ink-muted hover:text-ink transition">
                <i class="fas fa-arrow-left"></i> Volver al sitio
            </a>
        </div>
    </aside>

    {{-- Main Content --}}
    <main class="flex-1 ml-64 p-8">
        {{-- Header --}}
        <div class="mb-8">
            <h1 class="text-2xl font-serif">Dashboard</h1>
            <p class="text-ink-muted text-sm mt-1">Resumen general de FabricAI</p>
        </div>

        {{-- Stats Cards --}}
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            {{-- Total Users --}}
            <div class="bg-white border border-cream-200 rounded-xl p-6">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-10 h-10 rounded-lg bg-purple-50 flex items-center justify-center">
                        <i class="fas fa-users text-purple-600"></i>
                    </div>
                </div>
                <p class="text-2xl font-bold">{{ number_format($totalUsers) }}</p>
                <p class="text-sm text-ink-muted mt-1">Usuarios totales</p>
            </div>

            {{-- Online Users --}}
            <div class="bg-white border border-cream-200 rounded-xl p-6">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-10 h-10 rounded-lg bg-green-50 flex items-center justify-center">
                        <i class="fas fa-circle text-green-600 text-xs"></i>
                    </div>
                </div>
                <p class="text-2xl font-bold">{{ number_format($onlineUsers) }}</p>
                <p class="text-sm text-ink-muted mt-1">Usuarios online (5 min)</p>
            </div>

            {{-- Total Chats --}}
            <div class="bg-white border border-cream-200 rounded-xl p-6">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-10 h-10 rounded-lg bg-blue-50 flex items-center justify-center">
                        <i class="fas fa-comments text-blue-600"></i>
                    </div>
                </div>
                <p class="text-2xl font-bold">{{ number_format($totalChats) }}</p>
                <p class="text-sm text-ink-muted mt-1">Chats creados</p>
            </div>

            {{-- Total Designs --}}
            <div class="bg-white border border-cream-200 rounded-xl p-6">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-10 h-10 rounded-lg bg-amber-50 flex items-center justify-center">
                        <i class="fas fa-palette text-amber-600"></i>
                    </div>
                </div>
                <p class="text-2xl font-bold">{{ number_format($totalMessages) }}</p>
                <p class="text-sm text-ink-muted mt-1">Mensajes totales</p>
            </div>
        </div>

        {{-- Charts Row --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
            {{-- Hourly Activity --}}
            <div class="bg-white border border-cream-200 rounded-xl p-6">
                <h3 class="text-sm font-semibold text-ink mb-4">
                    <i class="fas fa-clock text-purple-600 mr-2"></i>Usuarios conectados por hora (últimos 7 días)
                </h3>
                <p class="text-xs text-ink-muted -mt-2 mb-3">Usuarios únicos activos en cada franja horaria</p>
                <canvas id="hourlyChart" height="200"></canvas>
            </div>

            {{-- Daily Registrations --}}
            <div class="bg-white border border-cream-200 rounded-xl p-6">
                <h3 class="text-sm font-semibold text-ink mb-4">
                    <i class="fas fa-user-plus text-green-600 mr-2"></i>Nuevos registros (últimos 30 días)
                </h3>
                <p class="text-xs text-ink-muted -mt-2 mb-3">Cuentas creadas por día</p>
                <canvas id="registrationsChart" height="200"></canvas>
            </div>
        </div>

        {{-- Plans Distribution --}}
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="bg-white border border-cream-200 rounded-xl p-6">
                <h3 class="text-sm font-semibold text-ink mb-4">
                    <i class="fas fa-layer-group text-purple-600 mr-2"></i>Distribución por plan
                </h3>
                <canvas id="plansChart" height="200"></canvas>
            </div>

            {{-- Quick Stats --}}
            <div class="bg-white border border-cream-200 rounded-xl p-6 lg:col-span-2">
                <h3 class="text-sm font-semibold text-ink mb-4">
                    <i class="fas fa-info-circle text-blue-600 mr-2"></i>Resumen de planes
                </h3>
                <div class="space-y-4">
                    @php
                        $planColors = ['free' => 'gray', 'pro' => 'purple', 'studio' => 'amber'];
                        $planLabels = ['free' => 'Free', 'pro' => 'Pro', 'studio' => 'Studio'];
                    @endphp
                    @foreach(['free', 'pro', 'studio'] as $plan)
                        @php
                            $count = $usersByPlan[$plan] ?? 0;
                            $pct = $totalUsers > 0 ? round($count / $totalUsers * 100, 1) : 0;
                            $color = $planColors[$plan];
                        @endphp
                        <div>
                            <div class="flex justify-between items-center mb-1">
                                <span class="text-sm font-medium text-ink">{{ $planLabels[$plan] }}</span>
                                <span class="text-xs text-ink-muted">{{ $count }} usuarios ({{ $pct }}%)</span>
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
    </main>
</div>

<script>
    const chartDefaults = {
        color: '#6b6b6b',
        borderColor: '#e8e5e0',
    };
    Chart.defaults.color = chartDefaults.color;
    Chart.defaults.borderColor = chartDefaults.borderColor;

    // Hourly Activity Chart — usuarios únicos concurrentes
    new Chart(document.getElementById('hourlyChart'), {
        type: 'bar',
        data: {
            labels: {!! json_encode(array_map(fn($h) => str_pad($h, 2, '0', STR_PAD_LEFT) . ':00', array_keys($hourlyData))) !!},
            datasets: [{
                label: 'Usuarios',
                data: {!! json_encode(array_values($hourlyData)) !!},
                backgroundColor: 'rgba(139, 92, 246, 0.3)',
                borderColor: 'rgb(139, 92, 246)',
                borderWidth: 1,
                borderRadius: 4,
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: ctx => ctx.parsed.y + (ctx.parsed.y === 1 ? ' usuario' : ' usuarios')
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: { color: '#e8e5e0' },
                    ticks: {
                        stepSize: 1,
                        callback: v => Number.isInteger(v) ? v : ''
                    }
                },
                x: { grid: { display: false } }
            }
        }
    });

    // Daily Registrations Chart
    const regLabels = {!! json_encode(array_keys($dailyRegistrations)) !!};
    // Mostrar solo día/mes en el eje X
    const regLabelsShort = regLabels.map(d => {
        const parts = d.split('-');
        return parts[2] + '/' + parts[1];
    });
    new Chart(document.getElementById('registrationsChart'), {
        type: 'bar',
        data: {
            labels: regLabelsShort,
            datasets: [{
                label: 'Registros',
                data: {!! json_encode(array_values($dailyRegistrations)) !!},
                backgroundColor: 'rgba(34, 197, 94, 0.3)',
                borderColor: 'rgb(34, 197, 94)',
                borderWidth: 1,
                borderRadius: 4,
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        title: ctx => regLabels[ctx[0].dataIndex],
                        label: ctx => ctx.parsed.y + (ctx.parsed.y === 1 ? ' registro' : ' registros')
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: { color: '#e8e5e0' },
                    ticks: {
                        stepSize: 1,
                        callback: v => Number.isInteger(v) ? v : ''
                    }
                },
                x: {
                    grid: { display: false },
                    ticks: { maxRotation: 45, autoSkip: true, maxTicksLimit: 15 }
                }
            }
        }
    });

    // Plans Distribution Doughnut
    new Chart(document.getElementById('plansChart'), {
        type: 'doughnut',
        data: {
            labels: ['Free', 'Pro', 'Studio'],
            datasets: [{
                data: [
                    {{ $usersByPlan['free'] ?? 0 }},
                    {{ $usersByPlan['pro'] ?? 0 }},
                    {{ $usersByPlan['studio'] ?? 0 }}
                ],
                backgroundColor: [
                    'rgba(107, 114, 128, 0.7)',
                    'rgba(139, 92, 246, 0.7)',
                    'rgba(245, 158, 11, 0.7)',
                ],
                borderColor: '#ffffff',
                borderWidth: 2,
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: { padding: 20, usePointStyle: true }
                }
            }
        }
    });
</script>

</body>
</html>
