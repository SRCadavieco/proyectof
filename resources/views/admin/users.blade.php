<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>FabricAI — Gestión de Usuarios</title>
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('adminUsers', () => ({
                tokenModal: false,
                editModal: false,
                deleteModal: false,
                currentUser: { id: null, name: '', plan: 'free', tokens: 0, tokensUrl: '', editUrl: '', deleteUrl: '' },
                init() {},
                openTokens(id, name, url) {
                    this.currentUser = { ...this.currentUser, id, name, tokensUrl: url };
                    this.tokenModal = true;
                },
                openEdit(id, name, plan, tokens, url) {
                    this.currentUser = { ...this.currentUser, id, name, plan, tokens, editUrl: url };
                    this.editModal = true;
                },
                openDelete(id, name, url) {
                    this.currentUser = { ...this.currentUser, id, name, deleteUrl: url };
                    this.deleteModal = true;
                },
            }));
        });
    </script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body class="bg-gray-950 text-white min-h-screen"
      x-data="adminUsers()"
      x-init="init()">

<div class="flex min-h-screen">
    {{-- Sidebar --}}
    <aside class="w-64 bg-gray-900 border-r border-gray-800 flex flex-col fixed h-full z-30">
        <div class="p-6 border-b border-gray-800 flex items-center gap-3">
            <a href="/"><img src="/images/logo.png" alt="Logo" class="h-12 w-12"></a>
            <div>
                <p class="font-bold text-sm">FabricAI</p>
                <p class="text-xs text-gray-500">Admin Panel</p>
            </div>
        </div>
        <nav class="flex-1 p-4 space-y-1">
            <a href="{{ route('admin.dashboard') }}"
               class="flex items-center gap-3 px-4 py-2.5 rounded-lg text-sm font-medium transition
                      {{ request()->routeIs('admin.dashboard') ? 'bg-purple-600/20 text-purple-400' : 'text-gray-400 hover:text-white hover:bg-gray-800' }}">
                <i class="fas fa-chart-pie w-5 text-center"></i> Dashboard
            </a>
            <a href="{{ route('admin.users') }}"
               class="flex items-center gap-3 px-4 py-2.5 rounded-lg text-sm font-medium transition
                      {{ request()->routeIs('admin.users') ? 'bg-purple-600/20 text-purple-400' : 'text-gray-400 hover:text-white hover:bg-gray-800' }}">
                <i class="fas fa-users w-5 text-center"></i> Usuarios
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

    {{-- Main --}}
    <main class="flex-1 ml-64 p-8">
        <div class="flex items-center justify-between mb-8">
            <div>
                <h1 class="text-2xl font-bold">Usuarios</h1>
                <p class="text-gray-500 text-sm mt-1">Gestión de cuentas de usuario</p>
            </div>
        </div>

        @if(session('success'))
            <div class="mb-6 px-4 py-3 rounded-lg bg-green-600/20 border border-green-600/30 text-green-400 text-sm">
                <i class="fas fa-check-circle mr-2"></i>{{ session('success') }}
            </div>
        @endif
        @if(session('error'))
            <div class="mb-6 px-4 py-3 rounded-lg bg-red-600/20 border border-red-600/30 text-red-400 text-sm">
                <i class="fas fa-exclamation-circle mr-2"></i>{{ session('error') }}
            </div>
        @endif

        {{-- Filters --}}
        <form method="GET" class="flex flex-wrap gap-4 mb-6">
            <div class="flex-1 min-w-[250px] relative">
                <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-500 text-sm"></i>
                <input type="text" name="search" value="{{ request('search') }}"
                       placeholder="Buscar por nombre o email..."
                       class="w-full pl-10 pr-4 py-2.5 rounded-lg bg-gray-900 border border-gray-800 text-sm text-white placeholder-gray-500 focus:outline-none focus:border-purple-500 transition">
            </div>
            <select name="plan"
                    class="px-4 py-2.5 rounded-lg bg-gray-900 border border-gray-800 text-sm text-white focus:outline-none focus:border-purple-500 transition">
                <option value="">Todos los planes</option>
                <option value="free" {{ request('plan') == 'free' ? 'selected' : '' }}>Free</option>
                <option value="pro" {{ request('plan') == 'pro' ? 'selected' : '' }}>Pro</option>
                <option value="studio" {{ request('plan') == 'studio' ? 'selected' : '' }}>Studio</option>
            </select>
            <button type="submit" class="px-6 py-2.5 rounded-lg bg-purple-600 hover:bg-purple-500 text-sm font-semibold transition">
                <i class="fas fa-filter mr-2"></i>Filtrar
            </button>
        </form>

        {{-- Table --}}
        <div class="bg-gray-900 border border-gray-800 rounded-xl overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-gray-800">
                            <th class="text-left px-6 py-4 text-xs font-semibold text-gray-400 uppercase tracking-wider">Usuario</th>
                            <th class="text-left px-6 py-4 text-xs font-semibold text-gray-400 uppercase tracking-wider">Plan</th>
                            <th class="text-left px-6 py-4 text-xs font-semibold text-gray-400 uppercase tracking-wider">Tokens</th>
                            <th class="text-left px-6 py-4 text-xs font-semibold text-gray-400 uppercase tracking-wider">Chats</th>
                            <th class="text-left px-6 py-4 text-xs font-semibold text-gray-400 uppercase tracking-wider">Registro</th>
                            <th class="text-right px-6 py-4 text-xs font-semibold text-gray-400 uppercase tracking-wider">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-800">
                        @forelse($users as $user)
                            <tr class="hover:bg-gray-800/50 transition">
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <div class="w-8 h-8 rounded-full bg-gradient-to-br from-purple-500 to-indigo-500 flex items-center justify-center text-xs font-bold shrink-0">
                                            {{ strtoupper(mb_substr($user->name, 0, 1)) }}
                                        </div>
                                        <div>
                                            <p class="font-medium text-white">
                                                {{ $user->name }}
                                                @if($user->is_admin)
                                                    <span class="ml-1 px-1.5 py-0.5 text-[10px] font-bold bg-red-600/20 text-red-400 rounded">ADMIN</span>
                                                @endif
                                            </p>
                                            <p class="text-xs text-gray-500">{{ $user->email }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    @php
                                        $planBadge = match($user->plan ?? 'free') {
                                            'pro'    => 'bg-purple-600/20 text-purple-400',
                                            'studio' => 'bg-amber-600/20 text-amber-400',
                                            default  => 'bg-gray-700/50 text-gray-400',
                                        };
                                    @endphp
                                    <span class="px-2.5 py-1 rounded-full text-xs font-semibold {{ $planBadge }}">
                                        {{ ucfirst($user->plan ?? 'free') }}
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="font-mono text-white">{{ $user->tokens ?? 0 }}</span>
                                </td>
                                <td class="px-6 py-4 text-gray-400">{{ $user->chats_count }}</td>
                                <td class="px-6 py-4 text-gray-400 text-xs">{{ $user->created_at->format('d/m/Y') }}</td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center justify-end gap-2">
                                        <button
                                            type="button"
                                            @click="openTokens({{ $user->id }}, '{{ addslashes($user->name) }}', '{{ route('admin.users.tokens', $user) }}')"
                                            class="p-2 rounded-lg text-gray-400 hover:text-green-400 hover:bg-green-600/10 transition"
                                            title="Añadir tokens">
                                            <i class="fas fa-coins text-xs"></i>
                                        </button>
                                        <button
                                            type="button"
                                            @click="openEdit({{ $user->id }}, '{{ addslashes($user->name) }}', '{{ $user->plan ?? 'free' }}', {{ $user->tokens ?? 0 }}, '{{ route('admin.users.update', $user) }}')"
                                            class="p-2 rounded-lg text-gray-400 hover:text-blue-400 hover:bg-blue-600/10 transition"
                                            title="Editar usuario">
                                            <i class="fas fa-pen text-xs"></i>
                                        </button>
                                        @if(!$user->is_admin)
                                            <button
                                                type="button"
                                                @click="openDelete({{ $user->id }}, '{{ addslashes($user->name) }}', '{{ route('admin.users.delete', $user) }}')"
                                                class="p-2 rounded-lg text-gray-400 hover:text-red-400 hover:bg-red-600/10 transition"
                                                title="Eliminar usuario">
                                                <i class="fas fa-trash text-xs"></i>
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-12 text-center text-gray-500">
                                    <i class="fas fa-users text-3xl mb-3 block"></i>
                                    No se encontraron usuarios.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($users->hasPages())
                <div class="px-6 py-4 border-t border-gray-800">{{ $users->links() }}</div>
            @endif
        </div>
    </main>
</div>

{{-- MODAL: ADD TOKENS --}}
<div x-show="tokenModal"
     x-transition:enter="transition ease-out duration-200"
     x-transition:enter-start="opacity-0"
     x-transition:enter-end="opacity-100"
     x-transition:leave="transition ease-in duration-150"
     x-transition:leave-start="opacity-100"
     x-transition:leave-end="opacity-0"
     class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-sm"
     style="display:none;"
     @click.self="tokenModal = false">
    <div class="bg-gray-900 border border-gray-800 rounded-2xl p-6 w-full max-w-sm shadow-2xl"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100">
        <div class="flex items-center justify-between mb-5">
            <h3 class="font-semibold text-white"><i class="fas fa-coins text-green-400 mr-2"></i>Añadir tokens</h3>
            <button @click="tokenModal = false" class="text-gray-500 hover:text-white transition"><i class="fas fa-times"></i></button>
        </div>
        <p class="text-sm text-gray-400 mb-4">Usuario: <span class="text-white font-medium" x-text="currentUser.name"></span></p>
        <form method="POST" :action="currentUser.tokensUrl">
            @csrf
            <div class="mb-4">
                <label class="block text-xs text-gray-500 uppercase mb-1">Tokens a añadir</label>
                <input type="number" name="amount" value="10" min="1" max="10000"
                       class="w-full px-4 py-2.5 rounded-lg bg-gray-800 border border-gray-700 text-white text-sm focus:outline-none focus:border-purple-500 transition">
            </div>
            <div class="flex gap-3">
                <button type="button" @click="tokenModal = false"
                        class="flex-1 px-4 py-2.5 rounded-lg border border-gray-700 text-sm text-gray-400 hover:text-white transition">Cancelar</button>
                <button type="submit"
                        class="flex-1 px-4 py-2.5 rounded-lg bg-green-600 hover:bg-green-500 text-sm font-semibold transition">
                    <i class="fas fa-plus mr-2"></i>Añadir
                </button>
            </div>
        </form>
    </div>
</div>

{{-- MODAL: EDIT USER --}}
<div x-show="editModal"
     x-transition:enter="transition ease-out duration-200"
     x-transition:enter-start="opacity-0"
     x-transition:enter-end="opacity-100"
     x-transition:leave="transition ease-in duration-150"
     x-transition:leave-start="opacity-100"
     x-transition:leave-end="opacity-0"
     class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-sm"
     style="display:none;"
     @click.self="editModal = false">
    <div class="bg-gray-900 border border-gray-800 rounded-2xl p-6 w-full max-w-sm shadow-2xl"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100">
        <div class="flex items-center justify-between mb-5">
            <h3 class="font-semibold text-white"><i class="fas fa-pen text-blue-400 mr-2"></i>Editar usuario</h3>
            <button @click="editModal = false" class="text-gray-500 hover:text-white transition"><i class="fas fa-times"></i></button>
        </div>
        <p class="text-sm text-gray-400 mb-4">Usuario: <span class="text-white font-medium" x-text="currentUser.name"></span></p>
        <form method="POST" :action="currentUser.editUrl">
            @csrf
            @method('PUT')
            <div class="mb-4">
                <label class="block text-xs text-gray-500 uppercase mb-1">Plan</label>
                <select name="plan" x-model="currentUser.plan"
                        class="w-full px-4 py-2.5 rounded-lg bg-gray-800 border border-gray-700 text-white text-sm focus:outline-none focus:border-purple-500 transition">
                    <option value="free">Free</option>
                    <option value="pro">Pro</option>
                    <option value="studio">Studio</option>
                </select>
            </div>
            <div class="mb-5">
                <label class="block text-xs text-gray-500 uppercase mb-1">Tokens (total)</label>
                <input type="number" name="tokens" x-model="currentUser.tokens" min="0"
                       class="w-full px-4 py-2.5 rounded-lg bg-gray-800 border border-gray-700 text-white text-sm focus:outline-none focus:border-purple-500 transition">
            </div>
            <div class="flex gap-3">
                <button type="button" @click="editModal = false"
                        class="flex-1 px-4 py-2.5 rounded-lg border border-gray-700 text-sm text-gray-400 hover:text-white transition">Cancelar</button>
                <button type="submit"
                        class="flex-1 px-4 py-2.5 rounded-lg bg-purple-600 hover:bg-purple-500 text-sm font-semibold transition">
                    <i class="fas fa-save mr-2"></i>Guardar
                </button>
            </div>
        </form>
    </div>
</div>

{{-- MODAL: DELETE USER --}}
<div x-show="deleteModal"
     x-transition:enter="transition ease-out duration-200"
     x-transition:enter-start="opacity-0"
     x-transition:enter-end="opacity-100"
     x-transition:leave="transition ease-in duration-150"
     x-transition:leave-start="opacity-100"
     x-transition:leave-end="opacity-0"
     class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-sm"
     style="display:none;"
     @click.self="deleteModal = false">
    <div class="bg-gray-900 border border-gray-800 rounded-2xl p-6 w-full max-w-sm shadow-2xl"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100">
        <div class="flex items-center justify-between mb-5">
            <h3 class="font-semibold text-white"><i class="fas fa-trash text-red-400 mr-2"></i>Eliminar usuario</h3>
            <button @click="deleteModal = false" class="text-gray-500 hover:text-white transition"><i class="fas fa-times"></i></button>
        </div>
        <p class="text-sm text-gray-400 mb-1">Vas a eliminar a:</p>
        <p class="text-white font-semibold mb-4" x-text="currentUser.name"></p>
        <p class="text-xs text-red-400 bg-red-600/10 border border-red-600/20 rounded-lg px-3 py-2 mb-5">
            <i class="fas fa-exclamation-triangle mr-2"></i>Se eliminarán todos sus chats y diseños. Es irreversible.
        </p>
        <form method="POST" :action="currentUser.deleteUrl">
            @csrf
            @method('DELETE')
            <div class="flex gap-3">
                <button type="button" @click="deleteModal = false"
                        class="flex-1 px-4 py-2.5 rounded-lg border border-gray-700 text-sm text-gray-400 hover:text-white transition">Cancelar</button>
                <button type="submit"
                        class="flex-1 px-4 py-2.5 rounded-lg bg-red-600 hover:bg-red-500 text-sm font-semibold transition">
                    <i class="fas fa-trash mr-2"></i>Eliminar
                </button>
            </div>
        </form>
    </div>
</div>

</body>
</html>
