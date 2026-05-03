@extends('layouts.admin')

@section('title', 'Users')

@push('head')
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
@endpush

@section('content')
<div x-data="adminUsers()" x-init="init()">

<div class="flex items-center justify-between mb-8">
    <div>
        <h1 class="text-2xl font-serif text-white">Users</h1>
        <p class="text-white/40 text-sm mt-1">Manage accounts, plans, tokens and Printify status</p>
    </div>
</div>

@if(session('success'))
    <div class="mb-6 px-4 py-3 rounded-xl text-sm" style="background:rgba(34,197,94,0.12);border:1px solid rgba(34,197,94,0.35);color:#86efac">
        <i class="fas fa-check-circle mr-2"></i>{{ session('success') }}
    </div>
@endif
@if(session('error'))
    <div class="mb-6 px-4 py-3 rounded-xl text-sm" style="background:rgba(239,68,68,0.12);border:1px solid rgba(239,68,68,0.35);color:#fca5a5">
        <i class="fas fa-exclamation-circle mr-2"></i>{{ session('error') }}
    </div>
@endif

<form method="GET" class="flex flex-wrap gap-3 mb-6">
    <div class="flex-1 min-w-[260px] relative">
        <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-white/35 text-sm"></i>
        <input type="text" name="search" value="{{ request('search') }}"
               placeholder="Search by name or email..."
               class="w-full pl-10 pr-4 py-2.5 rounded-xl text-sm text-white placeholder-white/25 focus:outline-none transition"
               style="background:#111;border:1px solid rgba(255,255,255,0.1)"
               onfocus="this.style.borderColor='rgba(124,60,160,0.6)'"
               onblur="this.style.borderColor='rgba(255,255,255,0.1)'">
    </div>
    <select name="plan"
            class="px-4 py-2.5 rounded-xl text-sm text-white focus:outline-none transition"
            style="background:#111;border:1px solid rgba(255,255,255,0.1)">
        <option value="">All plans</option>
        <option value="free" {{ request('plan') == 'free' ? 'selected' : '' }}>Free</option>
        <option value="starter" {{ request('plan') == 'starter' ? 'selected' : '' }}>Starter</option>
        <option value="pro" {{ request('plan') == 'pro' ? 'selected' : '' }}>Pro</option>
        <option value="business" {{ request('plan') == 'business' ? 'selected' : '' }}>Business</option>
    </select>
    <button type="submit" class="px-6 py-2.5 rounded-xl text-sm font-semibold text-white transition" style="background:#7c3ca0">
        <i class="fas fa-filter mr-2"></i>Filter
    </button>
</form>

<div class="rounded-2xl overflow-hidden" style="background:#111;border:1px solid rgba(255,255,255,0.08)">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr style="border-bottom:1px solid rgba(255,255,255,0.08)">
                    <th class="text-left px-6 py-4 text-[11px] font-semibold text-white/35 uppercase tracking-wider">User</th>
                    <th class="text-left px-6 py-4 text-[11px] font-semibold text-white/35 uppercase tracking-wider">Plan</th>
                    <th class="text-left px-6 py-4 text-[11px] font-semibold text-white/35 uppercase tracking-wider">Tokens</th>
                    <th class="text-left px-6 py-4 text-[11px] font-semibold text-white/35 uppercase tracking-wider">Chats</th>
                    <th class="text-left px-6 py-4 text-[11px] font-semibold text-white/35 uppercase tracking-wider">Has Printify</th>
                    <th class="text-left px-6 py-4 text-[11px] font-semibold text-white/35 uppercase tracking-wider">Joined</th>
                    <th class="text-right px-6 py-4 text-[11px] font-semibold text-white/35 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($users as $user)
                    @php
                        $currentPlan = match ($user->plan) {
                            'pro' => 'pro',
                            'studio' => 'business',
                            'starter', 'business', 'free' => $user->plan,
                            default => 'free',
                        };
                        $planBadge = match($currentPlan) {
                            'pro' => 'background:rgba(124,60,160,0.2);border:1px solid rgba(124,60,160,0.35);color:#c084fc',
                            'starter' => 'background:rgba(59,130,246,0.2);border:1px solid rgba(59,130,246,0.35);color:#93c5fd',
                            'business' => 'background:rgba(16,185,129,0.2);border:1px solid rgba(16,185,129,0.35);color:#6ee7b7',
                            default => 'background:rgba(255,255,255,0.08);border:1px solid rgba(255,255,255,0.1);color:rgba(255,255,255,0.6)',
                        };
                    @endphp
                    <tr class="transition" style="border-bottom:1px solid rgba(255,255,255,0.06)">
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-3">
                                @if($user->avatar)
                                    <img src="{{ $user->avatar }}" alt="{{ $user->name }}" class="w-8 h-8 rounded-full object-cover shrink-0">
                                @else
                                    <div class="w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold text-white shrink-0" style="background:#7c3ca0">
                                        {{ strtoupper(mb_substr($user->name, 0, 1)) }}
                                    </div>
                                @endif
                                <div>
                                    <p class="font-medium text-white/85">
                                        {{ $user->name }}
                                        @if($user->is_admin)
                                            <span class="ml-1 px-1.5 py-0.5 text-[10px] font-bold rounded" style="background:rgba(239,68,68,0.16);color:#fca5a5;border:1px solid rgba(239,68,68,0.35)">ADMIN</span>
                                        @endif
                                    </p>
                                    <p class="text-xs text-white/40">{{ $user->email }}</p>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <span class="px-2.5 py-1 rounded-full text-xs font-semibold" style="{{ $planBadge }}">
                                {{ ucfirst($currentPlan) }}
                            </span>
                        </td>
                        <td class="px-6 py-4">
                            <span class="font-mono text-white/75">{{ $user->tokens ?? 0 }}</span>
                        </td>
                        <td class="px-6 py-4 text-white/45">{{ $user->chats_count }}</td>
                        <td class="px-6 py-4">
                            @if($user->printifyConnection)
                                <span class="inline-flex items-center justify-center w-6 h-6 rounded-full" style="background:rgba(16,185,129,0.18);border:1px solid rgba(16,185,129,0.35)">
                                    <i class="fas fa-check text-[11px]" style="color:#6ee7b7"></i>
                                </span>
                            @else
                                <span class="inline-flex items-center justify-center w-6 h-6 rounded-full" style="background:rgba(255,255,255,0.06);border:1px solid rgba(255,255,255,0.1)">
                                    <i class="fas fa-minus text-[10px] text-white/25"></i>
                                </span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-white/40 text-xs">{{ $user->created_at->format('M d, Y') }}</td>
                        <td class="px-6 py-4">
                            <div class="flex items-center justify-end gap-2">
                                <button type="button"
                                    @click="openTokens({{ $user->id }}, '{{ addslashes($user->name) }}', '{{ route('admin.users.tokens', $user) }}')"
                                    class="p-2 rounded-lg text-white/35 transition" style="border:1px solid rgba(255,255,255,0.08)" title="Add tokens">
                                    <i class="fas fa-coins text-xs"></i>
                                </button>
                                <button type="button"
                                    @click="openEdit({{ $user->id }}, '{{ addslashes($user->name) }}', '{{ $currentPlan }}', {{ $user->tokens ?? 0 }}, '{{ route('admin.users.update', $user) }}')"
                                    class="p-2 rounded-lg text-white/35 transition" style="border:1px solid rgba(255,255,255,0.08)" title="Edit user">
                                    <i class="fas fa-pen text-xs"></i>
                                </button>
                                @if(!$user->is_admin)
                                    <button type="button"
                                        @click="openDelete({{ $user->id }}, '{{ addslashes($user->name) }}', '{{ route('admin.users.delete', $user) }}')"
                                        class="p-2 rounded-lg text-white/35 transition" style="border:1px solid rgba(255,255,255,0.08)" title="Delete user">
                                        <i class="fas fa-trash text-xs"></i>
                                    </button>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-6 py-12 text-center text-white/35">
                            <i class="fas fa-users text-3xl mb-3 block"></i>
                            No users found.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($users->hasPages())
        <div class="px-6 py-4" style="border-top:1px solid rgba(255,255,255,0.08)">{{ $users->links() }}</div>
    @endif
</div>

<div x-show="tokenModal" x-transition class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-sm" style="display:none;" @click.self="tokenModal = false">
    <div class="rounded-2xl p-6 w-full max-w-sm shadow-2xl" style="background:#111;border:1px solid rgba(255,255,255,0.09)">
        <div class="flex items-center justify-between mb-5">
            <h3 class="font-semibold text-white"><i class="fas fa-coins mr-2" style="color:#6ee7b7"></i>Add tokens</h3>
            <button @click="tokenModal = false" class="text-white/35 hover:text-white transition"><i class="fas fa-times"></i></button>
        </div>
        <p class="text-sm text-white/40 mb-4">User: <span class="text-white/80 font-medium" x-text="currentUser.name"></span></p>
        <form method="POST" :action="currentUser.tokensUrl">
            @csrf
            <div class="mb-4">
                <label class="block text-xs text-white/35 uppercase mb-1">Tokens to add</label>
                <input type="number" name="amount" value="10" min="1" max="10000"
                       class="w-full px-4 py-2.5 rounded-xl text-white text-sm focus:outline-none transition"
                       style="background:rgba(255,255,255,0.06);border:1px solid rgba(255,255,255,0.1)">
            </div>
            <div class="flex gap-3">
                <button type="button" @click="tokenModal = false" class="flex-1 px-4 py-2.5 rounded-xl text-sm text-white/55 hover:text-white transition" style="border:1px solid rgba(255,255,255,0.12)">Cancel</button>
                <button type="submit" class="flex-1 px-4 py-2.5 rounded-xl text-sm font-semibold text-white transition" style="background:#16a34a">
                    <i class="fas fa-plus mr-2"></i>Add
                </button>
            </div>
        </form>
    </div>
</div>

<div x-show="editModal" x-transition class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-sm" style="display:none;" @click.self="editModal = false">
    <div class="rounded-2xl p-6 w-full max-w-sm shadow-2xl" style="background:#111;border:1px solid rgba(255,255,255,0.09)">
        <div class="flex items-center justify-between mb-5">
            <h3 class="font-semibold text-white"><i class="fas fa-pen mr-2" style="color:#60a5fa"></i>Edit user</h3>
            <button @click="editModal = false" class="text-white/35 hover:text-white transition"><i class="fas fa-times"></i></button>
        </div>
        <p class="text-sm text-white/40 mb-4">User: <span class="text-white/80 font-medium" x-text="currentUser.name"></span></p>
        <form method="POST" :action="currentUser.editUrl">
            @csrf
            @method('PUT')
            <div class="mb-4">
                <label class="block text-xs text-white/35 uppercase mb-1">Plan</label>
                <select name="plan" x-model="currentUser.plan"
                        class="w-full px-4 py-2.5 rounded-xl text-white text-sm focus:outline-none transition"
                        style="background:rgba(255,255,255,0.06);border:1px solid rgba(255,255,255,0.1)">
                    <option value="free">Free</option>
                    <option value="starter">Starter</option>
                    <option value="pro">Pro</option>
                    <option value="business">Business</option>
                </select>
            </div>
            <div class="mb-5">
                <label class="block text-xs text-white/35 uppercase mb-1">Tokens (total)</label>
                <input type="number" name="tokens" x-model="currentUser.tokens" min="0"
                       class="w-full px-4 py-2.5 rounded-xl text-white text-sm focus:outline-none transition"
                       style="background:rgba(255,255,255,0.06);border:1px solid rgba(255,255,255,0.1)">
            </div>
            <div class="flex gap-3">
                <button type="button" @click="editModal = false" class="flex-1 px-4 py-2.5 rounded-xl text-sm text-white/55 hover:text-white transition" style="border:1px solid rgba(255,255,255,0.12)">Cancel</button>
                <button type="submit" class="flex-1 px-4 py-2.5 rounded-xl text-sm font-semibold text-white transition" style="background:#7c3ca0">
                    <i class="fas fa-save mr-2"></i>Save
                </button>
            </div>
        </form>
    </div>
</div>

<div x-show="deleteModal" x-transition class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-sm" style="display:none;" @click.self="deleteModal = false">
    <div class="rounded-2xl p-6 w-full max-w-sm shadow-2xl" style="background:#111;border:1px solid rgba(255,255,255,0.09)">
        <div class="flex items-center justify-between mb-5">
            <h3 class="font-semibold text-white"><i class="fas fa-trash mr-2 text-red-400"></i>Delete user</h3>
            <button @click="deleteModal = false" class="text-white/35 hover:text-white transition"><i class="fas fa-times"></i></button>
        </div>
        <p class="text-sm text-white/40 mb-1">You are about to delete:</p>
        <p class="text-white font-semibold mb-4" x-text="currentUser.name"></p>
        <p class="text-xs rounded-lg px-3 py-2 mb-5" style="background:rgba(239,68,68,0.12);border:1px solid rgba(239,68,68,0.35);color:#fca5a5">
            <i class="fas fa-exclamation-triangle mr-2"></i>All their chats and designs will be permanently deleted.
        </p>
        <form method="POST" :action="currentUser.deleteUrl">
            @csrf
            @method('DELETE')
            <div class="flex gap-3">
                <button type="button" @click="deleteModal = false" class="flex-1 px-4 py-2.5 rounded-xl text-sm text-white/55 hover:text-white transition" style="border:1px solid rgba(255,255,255,0.12)">Cancel</button>
                <button type="submit" class="flex-1 px-4 py-2.5 rounded-xl bg-red-600 hover:bg-red-500 text-sm font-semibold text-white transition">
                    <i class="fas fa-trash mr-2"></i>Delete
                </button>
            </div>
        </form>
    </div>
</div>

</div>
@endsection
