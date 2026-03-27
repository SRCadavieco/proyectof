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
<body class="bg-gray-950 text-white min-h-screen">

{{-- Sidebar --}}
<div class="flex min-h-screen">
    <aside class="w-64 bg-gray-900 border-r border-gray-800 flex flex-col fixed h-full z-30">
        <div class="p-6 border-b border-gray-800 flex items-center gap-3">
            <a href="/">
                <img src="/images/logo.png" alt="Logo" class="h-12 w-12">
            </a>
            <div>
                <p class="font-bold text-sm">FabricAI</p>
                <p class="text-xs text-gray-500">Admin Panel</p>
            </div>
        </div>
        <nav class="flex-1 p-4 space-y-1">
            <a href="{{ route('admin.dashboard') }}"
               class="flex items-center gap-3 px-4 py-2.5 rounded-lg text-sm font-medium transition
                      {{ request()->routeIs('admin.dashboard') ? 'bg-purple-600/20 text-purple-400' : 'text-gray-400 hover:text-white hover:bg-gray-800' }}">
                <i class="fas fa-chart-pie w-5 text-center"></i>
                Dashboard
            </a>
            <a href="{{ route('admin.users') }}"
               class="flex items-center gap-3 px-4 py-2.5 rounded-lg text-sm font-medium transition
                      {{ request()->routeIs('admin.users') ? 'bg-purple-600/20 text-purple-400' : 'text-gray-400 hover:text-white hover:bg-gray-800' }}">
                <i class="fas fa-users w-5 text-center"></i>
                Usuarios
            </a>
        </nav>
        <div class="p-4 border-t border-gray-800">
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-full bg-gradient-to-br from-purple-500 to-indigo-500 flex items-center justify-center text-xs font-bold">
                    {{ strtoupper(mb_substr(Auth::user()->name, 0, 1)) }}
                </div>
                <div class="text-sm">
                    <p class="text-white font-medium truncate">{{ Auth::user()->name }}</p>
                    <p class="text-xs text-gray-500 truncate">Admin</p>
                </div>
            </div>
            <a href="/" class="mt-3 flex items-center gap-2 text-xs text-gray-500 hover:text-gray-300 transition">
                <i class="fas fa-arrow-left"></i> Volver al sitio
            </a>
        </div>
    </aside>

    {{-- Main Content --}}
    <main class="flex-1 ml-64 p-8">
        {{-- Header --}}
        <div class="mb-8">
            <h1 class="text-2xl font-bold">Dashboard</h1>
            <p class="text-gray-500 text-sm mt-1">Resumen general de FabricAI</p>
        </div>

        {{-- Stats Cards --}}
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            {{-- Total Users --}}
            <div class="bg-gray-900 border border-gray-800 rounded-xl p-6">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-10 h-10 rounded-lg bg-purple-600/20 flex items-center justify-center">
                        <i class="fas fa-users text-purple-400"></i>
                    </div>
                </div>
                <p class="text-2xl font-bold">{{ number_format($totalUsers) }}</p>
                <p class="text-sm text-gray-500 mt-1">Usuarios totales</p>
            </div>

            {{-- Online Users --}}
            <div class="bg-gray-900 border border-gray-800 rounded-xl p-6">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-10 h-10 rounded-lg bg-green-600/20 flex items-center justify-center">
                        <i class="fas fa-circle text-green-400 text-xs"></i>
                    </div>
                </div>
                <p class="text-2xl font-bold">{{ number_format($onlineUsers) }}</p>
                <p class="text-sm text-gray-500 mt-1">Usuarios online (5 min)</p>
            </div>

            {{-- Total Chats --}}
            <div class="bg-gray-900 border border-gray-800 rounded-xl p-6">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-10 h-10 rounded-lg bg-blue-600/20 flex items-center justify-center">
                        <i class="fas fa-comments text-blue-400"></i>
                    </div>
                </div>
                <p class="text-2xl font-bold">{{ number_format($totalChats) }}</p>
                <p class="text-sm text-gray-500 mt-1">Chats creados</p>
            </div>

            {{-- Total Designs --}}
            <div class="bg-gray-900 border border-gray-800 rounded-xl p-6">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-10 h-10 rounded-lg bg-amber-600/20 flex items-center justify-center">
                        <i class="fas fa-palette text-amber-400"></i>
                    </div>
                </div>
                <p class="text-2xl font-bold">{{ number_format($totalMessages) }}</p>
                <p class="text-sm text-gray-500 mt-1">Mensajes totales</p>
            </div>
        </div>

        {{-- Charts Row --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
            {{-- Hourly Activity --}}
            <div class="bg-gray-900 border border-gray-800 rounded-xl p-6">
                <h3 class="text-sm font-semibold text-gray-300 mb-4">
                    <i class="fas fa-clock text-purple-400 mr-2"></i>Actividad por hora (últimos 7 días)
                </h3>
                <canvas id="hourlyChart" height="200"></canvas>
            </div>

            {{-- Daily Registrations --}}
            <div class="bg-gray-900 border border-gray-800 rounded-xl p-6">
                <h3 class="text-sm font-semibold text-gray-300 mb-4">
                    <i class="fas fa-user-plus text-green-400 mr-2"></i>Registros diarios (últimos 30 días)
                </h3>
                <canvas id="registrationsChart" height="200"></canvas>
            </div>
        </div>

        {{-- Plans Distribution --}}
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="bg-gray-900 border border-gray-800 rounded-xl p-6">
                <h3 class="text-sm font-semibold text-gray-300 mb-4">
                    <i class="fas fa-layer-group text-indigo-400 mr-2"></i>Distribución por plan
                </h3>
                <canvas id="plansChart" height="200"></canvas>
            </div>

            {{-- Quick Stats --}}
            <div class="bg-gray-900 border border-gray-800 rounded-xl p-6 lg:col-span-2">
                <h3 class="text-sm font-semibold text-gray-300 mb-4">
                    <i class="fas fa-info-circle text-cyan-400 mr-2"></i>Resumen de planes
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
                                <span class="text-sm font-medium text-gray-300">{{ $planLabels[$plan] }}</span>
                                <span class="text-xs text-gray-500">{{ $count }} usuarios ({{ $pct }}%)</span>
                            </div>
                            <div class="w-full bg-gray-800 rounded-full h-2">
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
        color: '#9ca3af',
        borderColor: '#374151',
    };
    Chart.defaults.color = chartDefaults.color;
    Chart.defaults.borderColor = chartDefaults.borderColor;

    // Hourly Activity Chart
    new Chart(document.getElementById('hourlyChart'), {
        type: 'bar',
        data: {
            labels: {!! json_encode(array_map(fn($h) => str_pad($h, 2, '0', STR_PAD_LEFT) . ':00', array_keys($hourlyData))) !!},
            datasets: [{
                label: 'Sesiones',
                data: {!! json_encode(array_values($hourlyData)) !!},
                backgroundColor: 'rgba(139, 92, 246, 0.5)',
                borderColor: 'rgb(139, 92, 246)',
                borderWidth: 1,
                borderRadius: 4,
            }]
        },
        options: {
            responsive: true,
            plugins: { legend: { display: false } },
            scales: {
                y: { beginAtZero: true, grid: { color: '#1f2937' } },
                x: { grid: { display: false } }
            }
        }
    });

    // Daily Registrations Chart
    new Chart(document.getElementById('registrationsChart'), {
        type: 'line',
        data: {
            labels: {!! json_encode(array_keys($dailyRegistrations)) !!},
            datasets: [{
                label: 'Registros',
                data: {!! json_encode(array_values($dailyRegistrations)) !!},
                borderColor: 'rgb(34, 197, 94)',
                backgroundColor: 'rgba(34, 197, 94, 0.1)',
                fill: true,
                tension: 0.4,
                pointRadius: 2,
            }]
        },
        options: {
            responsive: true,
            plugins: { legend: { display: false } },
            scales: {
                y: { beginAtZero: true, grid: { color: '#1f2937' } },
                x: { grid: { display: false }, ticks: { maxTicksToShow: 10 } }
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
                borderColor: '#111827',
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
