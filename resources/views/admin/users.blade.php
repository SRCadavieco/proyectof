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

{{-- Header --}}
<div class="flex items-center justify-between mb-8">
    <div>
        <h1 class="text-2xl font-serif">Users</h1>
        <p class="text-ink-muted text-sm mt-1">Manage user accounts</p>
    </div>
</div>

@if(session('success'))
    <div class="mb-6 px-4 py-3 rounded-lg bg-green-50 border border-green-200 text-green-700 text-sm">
        <i class="fas fa-check-circle mr-2"></i>{{ session('success') }}
    </div>
@endif
@if(session('error'))
    <div class="mb-6 px-4 py-3 rounded-lg bg-red-50 border border-red-200 text-red-700 text-sm">
        <i class="fas fa-exclamation-circle mr-2"></i>{{ session('error') }}
    </div>
@endif

{{-- Filters --}}
<form method="GET" class="flex flex-wrap gap-4 mb-6">
    <div class="flex-1 min-w-[250px] relative">
        <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-500 text-sm"></i>
        <input type="text" name="search" value="{{ request('search') }}"
               placeholder="Search by name or email..."
               class="w-full pl-10 pr-4 py-2.5 rounded-lg bg-white border border-cream-200 text-sm text-ink placeholder-ink-muted focus:outline-none focus:border-purple-500 transition">
    </div>
    <select name="plan"
            class="px-4 py-2.5 rounded-lg bg-white border border-cream-200 text-sm text-ink focus:outline-none focus:border-purple-500 transition">
        <option value="">All plans</option>
        <option value="free" {{ request('plan') == 'free' ? 'selected' : '' }}>Free</option>
        <option value="pro" {{ request('plan') == 'pro' ? 'selected' : '' }}>Pro</option>
        <option value="studio" {{ request('plan') == 'studio' ? 'selected' : '' }}>Studio</option>
    </select>
    <button type="submit" class="px-6 py-2.5 rounded-lg bg-ink text-white hover:bg-ink-light text-sm font-semibold transition">
        <i class="fas fa-filter mr-2"></i>Filter
    </button>
</form>

{{-- Table --}}
<div class="bg-white border border-cream-200 rounded-xl overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-cream-200">
                    <th class="text-left px-6 py-4 text-xs font-semibold text-ink-muted uppercase tracking-wider">User</th>
                    <th class="text-left px-6 py-4 text-xs font-semibold text-ink-muted uppercase tracking-wider">Plan</th>
                    <th class="text-left px-6 py-4 text-xs font-semibold text-ink-muted uppercase tracking-wider">Tokens</th>
                    <th class="text-left px-6 py-4 text-xs font-semibold text-ink-muted uppercase tracking-wider">Chats</th>
                    <th class="text-left px-6 py-4 text-xs font-semibold text-ink-muted uppercase tracking-wider">Joined</th>
                    <th class="text-right px-6 py-4 text-xs font-semibold text-ink-muted uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-cream-200">
                @forelse($users as $user)
                    <tr class="hover:bg-cream-50 transition">
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-3">
                                @if($user->avatar)
                                    <img src="{{ $user->avatar }}" alt="{{ $user->name }}"
                                         class="w-8 h-8 rounded-full object-cover shrink-0">
                                @else
                                    <div class="w-8 h-8 rounded-full bg-ink flex items-center justify-center text-xs font-bold text-white shrink-0">
                                        {{ strtoupper(mb_substr($user->name, 0, 1)) }}
                                    </div>
                                @endif
                                <div>
                                    <p class="font-medium text-ink">
                                        {{ $user->name }}
                                        @if($user->is_admin)
                                            <span class="ml-1 px-1.5 py-0.5 text-[10px] font-bold bg-red-50 text-red-600 rounded">ADMIN</span>
                                        @endif
                                    </p>
                                    <p class="text-xs text-ink-muted">{{ $user->email }}</p>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            @php
                                $planBadge = match($user->plan ?? 'free') {
                                    'pro'    => 'bg-purple-50 text-purple-700',
                                    'studio' => 'bg-amber-50 text-amber-700',
                                    default  => 'bg-cream-100 text-ink-muted',
                                };
                            @endphp
                            <span class="px-2.5 py-1 rounded-full text-xs font-semibold {{ $planBadge }}">
                                {{ ucfirst($user->plan ?? 'free') }}
                            </span>
                        </td>
                        <td class="px-6 py-4">
                            <span class="font-mono text-ink">{{ $user->tokens ?? 0 }}</span>
                        </td>
                        <td class="px-6 py-4 text-ink-muted">{{ $user->chats_count }}</td>
                        <td class="px-6 py-4 text-ink-muted text-xs">{{ $user->created_at->format('M d, Y') }}</td>
                        <td class="px-6 py-4">
                            <div class="flex items-center justify-end gap-2">
                                <button type="button"
                                    @click="openTokens({{ $user->id }}, '{{ addslashes($user->name) }}', '{{ route('admin.users.tokens', $user) }}')"
                                    class="p-2 rounded-lg text-ink-muted hover:text-green-600 hover:bg-green-50 transition" title="Add tokens">
                                    <i class="fas fa-coins text-xs"></i>
                                </button>
                                <button type="button"
                                    @click="openEdit({{ $user->id }}, '{{ addslashes($user->name) }}', '{{ $user->plan ?? 'free' }}', {{ $user->tokens ?? 0 }}, '{{ route('admin.users.update', $user) }}')"
                                    class="p-2 rounded-lg text-ink-muted hover:text-blue-600 hover:bg-blue-50 transition" title="Edit user">
                                    <i class="fas fa-pen text-xs"></i>
                                </button>
                                @if(!$user->is_admin)
                                    <button type="button"
                                        @click="openDelete({{ $user->id }}, '{{ addslashes($user->name) }}', '{{ route('admin.users.delete', $user) }}')"
                                        class="p-2 rounded-lg text-ink-muted hover:text-red-600 hover:bg-red-50 transition" title="Delete user">
                                        <i class="fas fa-trash text-xs"></i>
                                    </button>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-6 py-12 text-center text-ink-muted">
                            <i class="fas fa-users text-3xl mb-3 block"></i>
                            No users found.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($users->hasPages())
        <div class="px-6 py-4 border-t border-cream-200">{{ $users->links() }}</div>
    @endif
</div>

{{-- MODAL: ADD TOKENS --}}
<div x-show="tokenModal" x-transition
     class="fixed inset-0 z-50 flex items-center justify-center bg-ink/40 backdrop-blur-sm"
     style="display:none;" @click.self="tokenModal = false">
    <div class="bg-white border border-cream-200 rounded-2xl p-6 w-full max-w-sm shadow-2xl"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100">
        <div class="flex items-center justify-between mb-5">
            <h3 class="font-semibold text-ink"><i class="fas fa-coins text-green-600 mr-2"></i>Add tokens</h3>
            <button @click="tokenModal = false" class="text-ink-muted hover:text-ink transition"><i class="fas fa-times"></i></button>
        </div>
        <p class="text-sm text-ink-muted mb-4">User: <span class="text-ink font-medium" x-text="currentUser.name"></span></p>
        <form method="POST" :action="currentUser.tokensUrl">
            @csrf
            <div class="mb-4">
                <label class="block text-xs text-ink-muted uppercase mb-1">Tokens to add</label>
                <input type="number" name="amount" value="10" min="1" max="10000"
                       class="w-full px-4 py-2.5 rounded-lg bg-cream-50 border border-cream-200 text-ink text-sm focus:outline-none focus:border-purple-500 transition">
            </div>
            <div class="flex gap-3">
                <button type="button" @click="tokenModal = false"
                        class="flex-1 px-4 py-2.5 rounded-lg border border-cream-300 text-sm text-ink-muted hover:text-ink transition">Cancel</button>
                <button type="submit"
                        class="flex-1 px-4 py-2.5 rounded-lg bg-green-600 hover:bg-green-500 text-sm font-semibold text-white transition">
                    <i class="fas fa-plus mr-2"></i>Add
                </button>
            </div>
        </form>
    </div>
</div>

{{-- MODAL: EDIT USER --}}
<div x-show="editModal" x-transition
     class="fixed inset-0 z-50 flex items-center justify-center bg-ink/40 backdrop-blur-sm"
     style="display:none;" @click.self="editModal = false">
    <div class="bg-white border border-cream-200 rounded-2xl p-6 w-full max-w-sm shadow-2xl"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100">
        <div class="flex items-center justify-between mb-5">
            <h3 class="font-semibold text-ink"><i class="fas fa-pen text-blue-600 mr-2"></i>Edit user</h3>
            <button @click="editModal = false" class="text-ink-muted hover:text-ink transition"><i class="fas fa-times"></i></button>
        </div>
        <p class="text-sm text-ink-muted mb-4">User: <span class="text-ink font-medium" x-text="currentUser.name"></span></p>
        <form method="POST" :action="currentUser.editUrl">
            @csrf
            @method('PUT')
            <div class="mb-4">
                <label class="block text-xs text-ink-muted uppercase mb-1">Plan</label>
                <select name="plan" x-model="currentUser.plan"
                        class="w-full px-4 py-2.5 rounded-lg bg-cream-50 border border-cream-200 text-ink text-sm focus:outline-none focus:border-purple-500 transition">
                    <option value="free">Free</option>
                    <option value="pro">Pro</option>
                    <option value="studio">Studio</option>
                </select>
            </div>
            <div class="mb-5">
                <label class="block text-xs text-ink-muted uppercase mb-1">Tokens (total)</label>
                <input type="number" name="tokens" x-model="currentUser.tokens" min="0"
                       class="w-full px-4 py-2.5 rounded-lg bg-cream-50 border border-cream-200 text-ink text-sm focus:outline-none focus:border-purple-500 transition">
            </div>
            <div class="flex gap-3">
                <button type="button" @click="editModal = false"
                        class="flex-1 px-4 py-2.5 rounded-lg border border-cream-300 text-sm text-ink-muted hover:text-ink transition">Cancel</button>
                <button type="submit"
                        class="flex-1 px-4 py-2.5 rounded-lg bg-purple-600 hover:bg-purple-500 text-sm font-semibold text-white transition">
                    <i class="fas fa-save mr-2"></i>Save
                </button>
            </div>
        </form>
    </div>
</div>

{{-- MODAL: DELETE USER --}}
<div x-show="deleteModal" x-transition
     class="fixed inset-0 z-50 flex items-center justify-center bg-ink/40 backdrop-blur-sm"
     style="display:none;" @click.self="deleteModal = false">
    <div class="bg-white border border-cream-200 rounded-2xl p-6 w-full max-w-sm shadow-2xl"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100">
        <div class="flex items-center justify-between mb-5">
            <h3 class="font-semibold text-ink"><i class="fas fa-trash text-red-600 mr-2"></i>Delete user</h3>
            <button @click="deleteModal = false" class="text-ink-muted hover:text-ink transition"><i class="fas fa-times"></i></button>
        </div>
        <p class="text-sm text-ink-muted mb-1">You are about to delete:</p>
        <p class="text-ink font-semibold mb-4" x-text="currentUser.name"></p>
        <p class="text-xs text-red-600 bg-red-50 border border-red-200 rounded-lg px-3 py-2 mb-5">
            <i class="fas fa-exclamation-triangle mr-2"></i>All their chats and designs will be permanently deleted.
        </p>
        <form method="POST" :action="currentUser.deleteUrl">
            @csrf
            @method('DELETE')
            <div class="flex gap-3">
                <button type="button" @click="deleteModal = false"
                        class="flex-1 px-4 py-2.5 rounded-lg border border-cream-300 text-sm text-ink-muted hover:text-ink transition">Cancel</button>
                <button type="submit"
                        class="flex-1 px-4 py-2.5 rounded-lg bg-red-600 hover:bg-red-500 text-sm font-semibold text-white transition">
                    <i class="fas fa-trash mr-2"></i>Delete
                </button>
            </div>
        </form>
    </div>
</div>

</div>
@endsection