<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>FabricAI — Studio</title>
    @vite(['resources/css/app.css','resources/js/app.js'])
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        /* ── Scrollbar ── */
        .scrollbar-hide::-webkit-scrollbar { display: none; }
        .scrollbar-hide { -ms-overflow-style: none; scrollbar-width: none; }
        ::-webkit-scrollbar { width: 4px; height: 4px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: #d4cdc0; border-radius: 99px; }
        * { scrollbar-width: thin; scrollbar-color: #d4cdc0 transparent; }

        /* ── Animations ── */
        @keyframes msgIn {
            from { opacity: 0; transform: translateY(8px); }
            to   { opacity: 1; transform: translateY(0); }
        }
        .msg-enter { animation: msgIn 0.25s ease-out forwards; }

        @keyframes dotBounce {
            0%, 80%, 100% { transform: translateY(0); }
            40%            { transform: translateY(-4px); }
        }
        .dot-1 { animation: dotBounce 1.2s infinite 0s; }
        .dot-2 { animation: dotBounce 1.2s infinite 0.2s; }
        .dot-3 { animation: dotBounce 1.2s infinite 0.4s; }

        /* ── Sidebar ── */
        #sidebar { transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1); }
        @media (max-width: 767px) {
            #sidebar {
                position: fixed;
                inset: 0 auto 0 0;
                z-index: 40;
                height: 100dvh;
                transform: translateX(-100%);
            }
            #sidebar.sidebar-open {
                transform: translateX(0);
                box-shadow: 0 25px 50px -12px rgba(0,0,0,0.35);
            }
            #sidebar-backdrop { display: none; }
            #sidebar-backdrop.sidebar-open { display: block; }
        }
        @media (min-width: 768px) {
            #sidebar { position: relative; transform: none !important; flex-shrink: 0; }
            #sidebar-backdrop { display: none !important; }
        }

        /* ── Icon button ── */
        .icon-btn {
            width: 32px; height: 32px;
            border-radius: 8px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: #8a8a8a;
            transition: background 0.15s, color 0.15s;
            font-size: 13px;
            cursor: pointer;
            border: none;
            background: transparent;
            text-decoration: none;
            flex-shrink: 0;
        }
        .icon-btn:hover           { background: #f5f1ea; color: #1a1a1a; }
        .icon-btn.danger:hover    { background: #fef2f2; color: #ef4444; }
        .icon-btn.accent:hover    { background: #f5edff; color: #7c3ca0; }

        /* ── Chat thumbnail ── */
        .chat-thumb {
            width: 36px; height: 36px;
            border-radius: 8px;
            background: #f5f1ea;
            overflow: hidden;
            flex-shrink: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #d4cdc0;
            font-size: 13px;
        }
        .chat-thumb img { width: 100%; height: 100%; object-fit: cover; }

     

        /* ── Chat area background ── */
        #chat-container {
            background-image:
                linear-gradient(rgba(245,241,234,0.80), rgba(245,241,234,0.80)),
                url('/images/fitting-room.jpg');
            background-size: cover;
            background-position: center;
        }

        /* ── Provider / Model pills ── */
        .provider-btn, .model-pill {
            flex: 1;
            padding: 5px 4px;
            font-size: 9px;
            font-weight: 500;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            text-align: center;
            cursor: pointer;
            border: none;
            outline: none;
            transition: background 0.15s, color 0.15s;
        }
    </style>
</head>
<body class="bg-cream-100 text-ink h-[100dvh] overflow-hidden font-sans antialiased">

<div class="flex h-[100dvh]">

    <!-- Mobile sidebar backdrop -->
    <div id="sidebar-backdrop"
         onclick="closeSidebar()"
         class="fixed inset-0 bg-black/40 z-30 backdrop-blur-sm"></div>

    <!-- ═══════════════════════ SIDEBAR ═══════════════════════ -->
    <aside id="sidebar" class="w-64 bg-white border-r border-cream-300 flex flex-col h-[100dvh]">

        <!-- Logo + New Design button -->
        <div class="px-4 py-4 border-b border-cream-300 flex items-center justify-between">
            <a href="/" class="flex items-center gap-2.5 min-w-0">
                <img src="/images/logo.png" alt="FabricAI" class="h-8 w-8 object-contain shrink-0">
                <div class="min-w-0">
                    <span class="font-serif text-sm text-ink block leading-tight">fabricAI</span>
                    <span class="text-[9px] tracking-[0.22em] uppercase" style="color:#7c3ca0">atelier</span>
                </div>
            </a>
            <button onclick="newChat()" title="New design session"
                    class="w-8 h-8 flex items-center justify-center bg-ink text-white
                           hover:bg-[#7c3ca0] transition-colors rounded-full shrink-0 ml-2">
                <i class="fas fa-plus text-xs"></i>
            </button>
        </div>

        <!-- Design sessions list -->
        <div class="flex-1 overflow-y-auto py-3 scrollbar-hide">
            <p class="text-[9px] uppercase tracking-[0.2em] text-ink-muted px-4 mb-2">My Designs</p>
            <div id="chat-list" class="space-y-0.5 px-2"></div>
        </div>

        <!-- Footer -->
        <div class="border-t border-cream-300 p-4 space-y-3">

            <!-- AI Engine selector -->
            <div>
                <p class="text-[9px] uppercase tracking-[0.2em] text-ink-muted mb-1.5">AI Engine</p>
                <div class="flex border border-cream-300 rounded-lg overflow-hidden">
                    <button type="button" id="provider-btn-chutes" data-provider="chutes"
                            class="provider-btn"
                            style="background:#1a1a1a;color:#fff;">Chutes</button>
                    <button type="button" id="provider-btn-together" data-provider="together"
                            class="provider-btn"
                            style="background:transparent;color:#8a8a8a;border-left:1px solid #e8e3d9;">Together</button>
                </div>
                <div id="model-pills" class="flex border border-cream-300 rounded-lg overflow-hidden mt-1"></div>
                <input type="hidden" id="ai-provider" value="chutes">
                <input type="hidden" id="ai-model"    value="z_image_turbo">
            </div>

            <!-- Design credits -->
            <div class="flex items-center justify-between px-3 py-2.5 bg-cream-100 border border-cream-300 rounded-xl">
                <span class="text-xs text-ink-muted tracking-wide">Design Credits</span>
                <div class="flex items-center gap-1.5">
                    <span id="token-icon" class="text-sm" style="color:#7c3ca0">⚡</span>
                    <span id="token-count" class="text-sm font-semibold text-ink">{{ Auth::user()->tokens ?? 0 }}</span>
                </div>
            </div>

            <!-- User + logout -->
            <div class="flex items-center justify-between">
                <span class="text-xs text-ink-muted truncate pr-2">{{ Auth::user()->name }}</span>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" title="Log out" class="icon-btn danger">
                        <i class="fas fa-sign-out-alt"></i>
                    </button>
                </form>
            </div>

        </div>
    </aside>

    <!-- ═══════════════════════ MAIN ═══════════════════════ -->
    <main class="flex-1 flex flex-col overflow-hidden min-w-0">

        <!-- Header -->
        <header class="bg-white/80 backdrop-blur-sm border-b border-cream-300
                       px-4 py-3 flex items-center gap-3 z-10 relative shrink-0">
            <button onclick="toggleSidebar()"
                    class="md:hidden icon-btn shrink-0" aria-label="Open menu">
                <i class="fas fa-bars text-base"></i>
            </button>
            <h1 id="chat-title"
                class="font-medium text-sm text-ink truncate flex-1 text-center md:text-left">
                New Design
            </h1>
            <div class="w-8 shrink-0 md:hidden"></div>
        </header>

        <!-- Chat area -->
        <div id="chat-container" class="flex-1 overflow-y-auto">
            <div class="max-w-3xl mx-auto px-4 py-8">

                <!-- Welcome screen (hidden once messages exist) -->
                <div id="welcome-screen" class="flex flex-col items-center py-10 text-center">
                    <p class="text-[10px] uppercase tracking-[0.25em] text-ink-muted mb-3">Your personal atelier</p>
                    <h2 class="font-serif text-2xl md:text-3xl text-ink mb-8 leading-snug">
                        What shall we create today?&nbsp;<span style="color:#7c3ca0">✦</span>
                    </h2>
                    <p class="text-sm text-ink-muted max-w-xs leading-relaxed">
                        Describe your design — a style, a mood, a concept — and I'll bring it to life.
                    </p>
                    <p class="text-xs text-cream-400 mt-2 italic">
                        Try: "Minimalist botanical line art in earthy tones"
                    </p>
                </div>

                <!-- Dynamic messages -->
                <div id="messages" class="space-y-6"></div>

            </div>
        </div>

        <!-- Input area -->
        <div class="bg-white/90 backdrop-blur-sm border-t border-cream-300
                    px-4 py-3 relative z-10 shrink-0">
            <div id="error" class="hidden text-red-500 text-xs mb-2 px-1"></div>
            <div id="loader" class="hidden items-center gap-2 text-xs mb-2 px-1"
                 style="color:#7c3ca0">
                <div class="flex gap-1 items-end">
                    <span class="w-1.5 h-1.5 rounded-full dot-1" style="background:#7c3ca0"></span>
                    <span class="w-1.5 h-1.5 rounded-full dot-2" style="background:#7c3ca0"></span>
                    <span class="w-1.5 h-1.5 rounded-full dot-3" style="background:#7c3ca0"></span>
                </div>
                <span id="loader-text">Creating your design…</span>
            </div>
            <form id="design-form" class="max-w-3xl mx-auto">
                <!-- Edit mode banner -->
                <div id="edit-banner"
                     class="hidden mb-2 items-center gap-2 px-3 py-1.5
                            bg-amber-50 border border-amber-200 rounded-xl
                            text-amber-800 text-xs font-medium">
                    <i class="fas fa-magic text-amber-500"></i>
                    <span>Retouching — previous image is your base</span>
                    <button type="button" id="cancel-edit-btn"
                            class="ml-auto text-amber-600 hover:text-amber-900 transition-colors underline">
                        Cancel
                    </button>
                </div>
                <div class="flex gap-2 items-end">
                    <!-- Attach image -->
                    <label class="cursor-pointer icon-btn shrink-0" title="Attach image">
                        <i class="fas fa-paperclip text-base"></i>
                        <input type="file" id="image-upload" accept="image/*" class="hidden">
                    </label>
                    <!-- Image preview -->
                    <div id="image-preview" class="shrink-0"></div>
                    <!-- Prompt textarea -->
                    <textarea
                        id="prompt"
                        rows="1"
                        placeholder="Describe your idea…"
                        class="flex-1 bg-cream-100 border border-cream-300 rounded-xl
                               px-4 py-2.5 text-sm resize-none text-ink
                               focus:outline-none focus:border-[#7c3ca0] transition-colors
                               placeholder-ink-muted/60 max-h-32 scrollbar-hide leading-relaxed"></textarea>
                    <!-- Send button -->
                    <button type="submit" id="submit-btn"
                            class="w-10 h-10 flex items-center justify-center rounded-full
                                   text-white transition-colors disabled:opacity-40 shrink-0"
                            style="background:#5a2275"
                            onmouseover="this.style.background='#7c3ca0'"
                            onmouseout="this.style.background='#5a2275'">
                        <i class="fas fa-arrow-up text-sm"></i>
                    </button>
                </div>
            </form>
        </div>

    </main>
</div>

<!-- Hidden helpers -->
<img id="temp-image" class="hidden" crossorigin="anonymous">
<div id="debug-info" class="hidden"></div>

<!-- ═══════════ DELETE CONFIRMATION MODAL ═══════════ -->
<div id="delete-modal"
     class="fixed inset-0 z-50 hidden items-center justify-center bg-black/40 backdrop-blur-sm p-4">
    <div class="bg-white border border-cream-300 rounded-2xl shadow-2xl w-full max-w-sm p-6 text-center">
        <div class="w-12 h-12 bg-red-50 rounded-full flex items-center justify-center mx-auto mb-4">
            <i class="fas fa-trash text-red-400 text-lg"></i>
        </div>
        <h3 class="font-semibold text-ink text-base mb-1">Delete this session?</h3>
        <p class="text-sm text-ink-muted mb-6 leading-relaxed">
            All messages and generated designs in this session will be permanently removed.
        </p>
        <div class="flex gap-3">
            <button id="delete-cancel-btn"
                    class="flex-1 py-2.5 border border-cream-300 text-sm font-medium
                           text-ink hover:bg-cream-100 transition-colors rounded-xl">
                Cancel
            </button>
            <button id="delete-confirm-btn"
                    class="flex-1 py-2.5 bg-red-500 text-white text-sm font-medium
                           hover:bg-red-600 transition-colors rounded-xl">
                Delete
            </button>
        </div>
    </div>
</div>

<!-- ═══════════ GARMENT PREVIEW MODAL ═══════════ -->
<div id="preview-modal"
     class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50 backdrop-blur-sm p-4">
    <div class="bg-white border border-cream-300 shadow-2xl w-full max-w-2xl rounded-2xl overflow-hidden">
        <div class="flex items-center justify-between px-6 py-4 border-b border-cream-300">
            <h2 class="text-base font-semibold text-ink">Preview on Garment</h2>
            <button onclick="closePreviewModal()" class="icon-btn">
                <i class="fas fa-times text-lg"></i>
            </button>
        </div>
        <div class="p-5 overflow-y-auto max-h-[85dvh]">
            <div class="flex gap-4 mb-4 flex-wrap items-end">
                <div class="flex flex-col gap-1">
                    <label class="text-xs text-ink-muted uppercase tracking-wider">Garment</label>
                    <select id="garment-select" onchange="renderPreview()"
                            class="bg-cream-100 border border-cream-300 rounded-lg px-3 py-2 text-sm text-ink
                                   focus:outline-none focus:border-[#7c3ca0] transition-colors">
                        <option value="tshirt">Gildan 5000 — T-Shirt</option>
                        <option value="hoodie">Gildan 18500 — Hoodie</option>
                        <option value="tanktop">Bella+Canvas 3480 — Tank Top</option>
                        <option value="longsleeve">Gildan 5400 — Long Sleeve</option>
                        <option value="sweatshirt">Gildan 18000 — Sweatshirt</option>
                    </select>
                </div>
                <div class="flex flex-col gap-1">
                    <label class="text-xs text-ink-muted uppercase tracking-wider">Color</label>
                    <input type="color" id="garment-color" value="#ffffff"
                           oninput="renderPreview()"
                           class="w-10 h-10 border border-cream-300 rounded-lg cursor-pointer bg-transparent">
                </div>
            </div>

            <!-- Position controls -->
            <div class="grid grid-cols-3 gap-3 mb-3">
                <div class="flex flex-col gap-1">
                    <div class="flex justify-between">
                        <label class="text-xs text-ink-muted uppercase tracking-wider">Pos X</label>
                        <span id="pos-x-val" class="text-xs text-ink-muted">0</span>
                    </div>
                    <input type="range" id="design-pos-x" min="-1" max="1" step="0.01" value="0"
                           oninput="document.getElementById('pos-x-val').textContent=parseFloat(this.value).toFixed(2); renderPreview()"
                           class="w-full accent-purple-600">
                </div>
                <div class="flex flex-col gap-1">
                    <div class="flex justify-between">
                        <label class="text-xs text-ink-muted uppercase tracking-wider">Pos Y</label>
                        <span id="pos-y-val" class="text-xs text-ink-muted">0</span>
                    </div>
                    <input type="range" id="design-pos-y" min="-1" max="1" step="0.01" value="0"
                           oninput="document.getElementById('pos-y-val').textContent=parseFloat(this.value).toFixed(2); renderPreview()"
                           class="w-full accent-purple-600">
                </div>
                <div class="flex flex-col gap-1">
                    <div class="flex justify-between">
                        <label class="text-xs text-ink-muted uppercase tracking-wider">Scale</label>
                        <span id="scale-val" class="text-xs text-ink-muted">1.00</span>
                    </div>
                    <input type="range" id="design-scale" min="0.2" max="2" step="0.01" value="1"
                           oninput="document.getElementById('scale-val').textContent=parseFloat(this.value).toFixed(2); renderPreview()"
                           class="w-full accent-purple-600">
                </div>
            </div>

            <div class="flex justify-center bg-cream-100 rounded-xl p-4">
                <div id="canvas-wrapper" style="position:relative;display:inline-block;max-width:100%;line-height:0;">
                    <canvas id="garment-canvas" width="500" height="550"
                            class="max-w-full h-auto rounded-lg" style="max-height:420px;display:block;"></canvas>
                    <canvas id="design-canvas" style="position:absolute;left:0;top:0;cursor:grab;"></canvas>
                </div>
            </div>
            <div id="printify-spec" class="mt-2 text-xs text-ink-muted text-center"></div>

            <div class="flex justify-end gap-3 mt-4">
                <button onclick="downloadPreview()"
                        class="px-4 py-2 bg-ink text-white text-xs font-medium tracking-wide uppercase
                               rounded-lg hover:bg-ink-light transition-colors">
                    Download Preview
                </button>
                <button onclick="togglePrintifyPanel()"
                        class="px-4 py-2 border border-[#7c3ca0] text-[#7c3ca0] text-xs font-medium
                               tracking-wide uppercase rounded-lg hover:bg-[#7c3ca0] hover:text-white transition-colors">
                    Send to Printify
                </button>
            </div>

            <!-- Printify send panel -->
            <div id="printify-panel" class="hidden mt-4 border border-cream-300 bg-cream-50 rounded-xl p-4 space-y-3">
                <p class="text-xs font-medium tracking-widest uppercase text-ink-muted">Create product on Printify</p>

                <div id="printify-connect-notice"
                     class="hidden px-4 py-3 bg-yellow-50 border border-yellow-200 rounded-lg text-yellow-800 text-sm">
                    Your Printify account is not connected yet.
                    <a href="/profile" target="_blank" rel="noopener" class="underline font-medium">
                        Go to Profile → Connect Printify
                    </a>
                </div>

                <div class="flex flex-col gap-1">
                    <label class="text-xs text-ink-muted">Product name</label>
                    <input id="printify-title" type="text" value=""
                           class="bg-white border border-cream-300 rounded-lg px-3 py-2 text-sm text-ink
                                  focus:outline-none focus:border-[#7c3ca0] transition-colors">
                </div>

                <div class="flex flex-col gap-1">
                    <label class="text-xs text-ink-muted">Printify store</label>
                    <select id="printify-shop"
                            class="bg-white border border-cream-300 rounded-lg px-3 py-2 text-sm text-ink
                                   focus:outline-none focus:border-[#7c3ca0]">
                        <option value="">Loading stores…</option>
                    </select>
                </div>

                <div class="flex flex-col gap-1">
                    <label class="text-xs text-ink-muted">Garment color</label>
                    <div class="flex items-center gap-3">
                        <input type="color" id="printify-color-hex" value="#ffffff"
                               oninput="onPrintifyColorChange(this.value)"
                               class="w-10 h-10 border border-cream-300 rounded-lg cursor-pointer bg-transparent">
                        <span id="printify-color-name" class="text-xs text-ink font-medium">White</span>
                    </div>
                </div>

                <div id="printify-feedback" class="hidden text-sm py-1"></div>

                <div class="flex gap-2">
                    <button id="printify-send-btn" onclick="sendToPrintify()"
                            class="flex-1 py-2.5 bg-ink text-white text-xs font-medium tracking-widest uppercase
                                   rounded-lg hover:bg-ink-light transition-colors disabled:opacity-50">
                        Create Product
                    </button>
                    <button id="printify-bulk-btn" onclick="sendToAllPrintify()"
                            title="Upload design to all clothing types at once"
                            class="flex-1 py-2.5 bg-[#5a2275] text-white text-xs font-medium tracking-widest uppercase
                                   rounded-lg hover:bg-[#7c3ca0] transition-colors disabled:opacity-50">
                        Upload to All
                    </button>
                </div>
            </div>

        </div>
    </div>
</div>

<script>
    // User identity
    const userInitial   = '{{ strtoupper(mb_substr(Auth::user()->name, 0, 1)) }}';
    const userAvatarUrl = @json(Auth::user()->avatar);

    // ─── Token Manager ────────────────────────────────────────────────
    const TokenManager = {
        MAX: 10,
        _cache: {{ Auth::user()->tokens ?? 10 }},
        get()    { return this._cache; },
        set(n)   { this._cache = Math.max(0, n); this._render(this._cache); },
        deduct() {
            const cur = this.get();
            if (cur <= 0) return false;
            this.set(cur - 1);
            return true;
        },
        refill() { window.location.href = '/pricing'; },
        async sync() {
            try {
                const res = await fetch('/api/tokens', { headers: { 'Accept': 'application/json' } });
                if (res.ok) {
                    const data = await res.json();
                    this._cache = data.remaining;
                    this._render(data.remaining);
                }
            } catch (e) { /* silent */ }
        },
        _render(n) {
            const countEl = document.getElementById('token-count');
            const iconEl  = document.getElementById('token-icon');
            if (!countEl) return;
            countEl.textContent = n;
            if (iconEl) iconEl.textContent = '⚡';
        },
        init() { this._render(this.get()); }
    };

    // ─── State ────────────────────────────────────────────────────────
    let uploadedImageBase64 = null;
    let uploadedImageMime   = null;
    let currentChatId       = null;
    let isEditMode          = false;
    let isSubmitting        = false;
    let isCreatingChat      = false;
    let chats               = [];
    let pendingDeleteId     = null;

    const imageInput        = document.getElementById('image-upload');
    const form              = document.getElementById('design-form');
    const promptInput       = document.getElementById('prompt');
    const submitBtn         = document.getElementById('submit-btn');
    const loader            = document.getElementById('loader');
    const errorEl           = document.getElementById('error');
    const messagesContainer = document.getElementById('messages');
    const chatContainer     = document.getElementById('chat-container');
    const previewImageStore = [];

    const MODEL_OPTIONS = {
        chutes:   [{ value:'z_image_turbo', label:'Z-Image Turbo' }, { value:'flux_schnell', label:'FLUX Schnell' }],
        together: [{ value:'flux_dev', label:'FLUX.2 Dev' }],
    };
    const providerHidden = document.getElementById('ai-provider');
    const modelHidden    = document.getElementById('ai-model');
    const modelPillsEl   = document.getElementById('model-pills');

    // ─── Helpers ─────────────────────────────────────────────────────
    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
    function scrollToBottom() {
        setTimeout(() => { chatContainer.scrollTop = chatContainer.scrollHeight; }, 100);
    }
    function relativeDate(dateStr) {
        if (!dateStr) return '';
        const d    = new Date(dateStr);
        const diff = Date.now() - d.getTime();
        if (diff < 60000)     return 'Just now';
        if (diff < 3600000)   return Math.floor(diff / 60000) + 'm ago';
        if (diff < 86400000)  return Math.floor(diff / 3600000) + 'h ago';
        if (diff < 172800000) return 'Yesterday';
        if (diff < 604800000) return Math.floor(diff / 86400000) + 'd ago';
        return d.toLocaleDateString('en-US', { month:'short', day:'numeric' });
    }

    // ─── Welcome screen ───────────────────────────────────────────────
    function updateWelcomeScreen() {
        const ws = document.getElementById('welcome-screen');
        if (!ws) return;
        if (messagesContainer.children.length > 0) {
            ws.style.display = 'none';
        } else {
            ws.style.display       = 'flex';
            ws.style.flexDirection = 'column';
            ws.style.alignItems    = 'center';
        }
    }


    // ─── Delete modal ─────────────────────────────────────────────────
    function showDeleteModal() {
        const m = document.getElementById('delete-modal');
        m.classList.remove('hidden'); m.classList.add('flex');
    }
    function hideDeleteModal() {
        const m = document.getElementById('delete-modal');
        m.classList.add('hidden'); m.classList.remove('flex');
    }
    document.getElementById('delete-cancel-btn').addEventListener('click', () => {
        hideDeleteModal(); pendingDeleteId = null;
    });
    document.getElementById('delete-confirm-btn').addEventListener('click', async () => {
        if (pendingDeleteId !== null) {
            const id = pendingDeleteId;
            pendingDeleteId = null;
            hideDeleteModal();
            await deleteChat(id);
        }
    });
    document.getElementById('delete-modal').addEventListener('click', function (e) {
        if (e.target === this) { hideDeleteModal(); pendingDeleteId = null; }
    });

    // ─── AI Engine / Model ────────────────────────────────────────────
    function syncModelOptions(provider) {
        const options = MODEL_OPTIONS[provider] ?? MODEL_OPTIONS.gemini;
        modelPillsEl.innerHTML = options.map((m, i) => {
            const bl    = i > 0 ? 'border-left:1px solid #e8e3d9;' : '';
            const style = i === 0 ? 'background:#5a2275;color:#fff;' + bl : 'background:transparent;color:#8a8a8a;' + bl;
            return `<button type="button" data-model="${m.value}" class="model-pill" style="${style}">${m.label}</button>`;
        }).join('');
        modelHidden.value = options[0].value;
        modelPillsEl.querySelectorAll('.model-pill').forEach(btn => {
            btn.addEventListener('click', () => {
                modelPillsEl.querySelectorAll('.model-pill').forEach(b => {
                    b.style.background = 'transparent'; b.style.color = '#8a8a8a';
                });
                btn.style.background = '#5a2275'; btn.style.color = '#fff';
                modelHidden.value = btn.dataset.model;
            });
        });
    }
    function initProviderButtons() {
        document.querySelectorAll('.provider-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                document.querySelectorAll('.provider-btn').forEach(b => {
                    b.style.background = 'transparent'; b.style.color = '#8a8a8a';
                });
                btn.style.background = '#1a1a1a'; btn.style.color = '#fff';
                providerHidden.value = btn.dataset.provider;
                syncModelOptions(btn.dataset.provider);
            });
        });
        syncModelOptions('chutes');
    }

    // ─── Textarea auto-resize ─────────────────────────────────────────
    promptInput.addEventListener('input', function () {
        this.style.height = 'auto';
        this.style.height = Math.min(this.scrollHeight, 120) + 'px';
    });
    promptInput.addEventListener('keydown', function (e) {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            form.dispatchEvent(new Event('submit', { cancelable: true }));
        }
    });

    // ─── Image preview ────────────────────────────────────────────────
    function showImagePreview(dataUrl) {
        const preview = document.getElementById('image-preview');
        preview.innerHTML = '';
        const wrapper = document.createElement('div');
        wrapper.className = 'relative inline-block';
        const img = document.createElement('img');
        img.src = dataUrl;
        img.className = 'rounded-lg border border-cream-300 max-h-16 max-w-16 block';
        img.alt = 'Preview';
        const btn = document.createElement('button');
        btn.type = 'button'; btn.title = 'Remove image';
        btn.className = 'absolute -top-1.5 -right-1.5 w-4 h-4 bg-ink text-white rounded-full ' +
            'flex items-center justify-center text-xs leading-none hover:bg-red-600 transition-colors shadow';
        btn.innerHTML = '&times;';
        btn.addEventListener('click', clearImagePreview);
        wrapper.appendChild(img); wrapper.appendChild(btn); preview.appendChild(wrapper);
    }
    function clearImagePreview() {
        document.getElementById('image-preview').innerHTML = '';
        document.getElementById('image-upload').value = '';
        uploadedImageBase64 = null; uploadedImageMime = null;
    }
    promptInput.addEventListener('paste', function (e) {
        const items = (e.clipboardData || e.originalEvent.clipboardData).items;
        for (const item of items) {
            if (item.type.startsWith('image/')) {
                e.preventDefault();
                const file = item.getAsFile();
                if (!file) continue;
                uploadedImageMime = file.type;
                const reader = new FileReader();
                reader.onload = () => { uploadedImageBase64 = reader.result; showImagePreview(reader.result); };
                reader.readAsDataURL(file);
                break;
            }
        }
    });

    // ─── Loader ───────────────────────────────────────────────────────
    const ATELIER_MESSAGES = [
        'Creating your design…', 'Stitching your idea together…', 'Cutting the pattern…',
        'The atelier is at work…', 'Draping the fabric…', 'Fitting your design…',
        'Pinning the details…', 'Pressing the seams…',
    ];
    let _loaderInterval = null;
    function setLoading(loading) {
        submitBtn.disabled = loading;
        loader.classList.toggle('hidden', !loading);
        loader.classList.toggle('flex', loading);
        errorEl.classList.add('hidden');
        const textEl = document.getElementById('loader-text');
        if (loading && textEl) {
            let idx = Math.floor(Math.random() * ATELIER_MESSAGES.length);
            textEl.textContent = ATELIER_MESSAGES[idx];
            _loaderInterval = setInterval(() => {
                idx = (idx + 1) % ATELIER_MESSAGES.length;
                textEl.textContent = ATELIER_MESSAGES[idx];
            }, 2800);
        } else {
            clearInterval(_loaderInterval); _loaderInterval = null;
        }
    }
    function showError(msg) {
        errorEl.textContent = msg; errorEl.classList.remove('hidden');
    }

    // ─── Message renderers ────────────────────────────────────────────
    function addUserMessage(text, imageBase64 = null) {
        const avatarHtml = userAvatarUrl
            ? `<img src="${userAvatarUrl}" alt="" class="w-8 h-8 rounded-full object-cover flex-shrink-0">`
            : `<div class="w-8 h-8 rounded-full bg-ink flex items-center justify-center text-white text-xs font-semibold flex-shrink-0">${userInitial}</div>`;

        if (imageBase64) {
            const imgDiv = document.createElement('div');
            imgDiv.className = 'mb-1.5 flex flex-row-reverse items-end gap-3';
            imgDiv.innerHTML = `<div class="w-8 h-8 flex-shrink-0"></div>
                <img src="${imageBase64}" alt="Attached"
                     class="rounded-xl max-w-48 max-h-48 object-cover shadow-sm border border-cream-300">`;
            messagesContainer.appendChild(imgDiv);
        }

        const div = document.createElement('div');
        div.className = 'flex flex-row-reverse items-start gap-2.5 msg-enter';
        div.innerHTML = `${avatarHtml}
            <div class="bg-ink text-white px-4 py-3 rounded-2xl rounded-tr-sm text-sm leading-relaxed max-w-xs md:max-w-md">
                ${escapeHtml(text)}
            </div>`;
        messagesContainer.appendChild(div);
        updateWelcomeScreen();
        scrollToBottom();
    }

    function addBotResponse(imageUrl) {
        const div      = document.createElement('div');
        div.className  = 'flex items-start gap-3 msg-enter';
        const uniqueId = 'bg-' + Date.now();
        const idx      = previewImageStore.length;
        previewImageStore.push(imageUrl);

        div.innerHTML = `
            <div class="w-8 h-8 rounded-full bg-white border border-cream-300 shadow-sm
                        flex items-center justify-center flex-shrink-0 overflow-hidden p-1 mt-0.5">
                <img src="/images/logo.png" alt="FabricAI" class="w-full h-full object-contain">
            </div>
            <div class="bg-white border border-cream-200 rounded-2xl rounded-tl-sm shadow-sm overflow-hidden max-w-xs">
                <div id="${uniqueId}" class="bg-cream-100 p-2.5">
                    <img src="${imageUrl}" alt="Generated design" class="rounded-xl w-full block" crossorigin="anonymous">
                </div>
                <div class="px-3 py-2 border-t border-cream-200 flex items-center gap-1.5 flex-wrap">
                    <span class="text-[9px] text-ink-muted uppercase tracking-wider mr-1">BG</span>
                    <button type="button" onclick="changeBg('${uniqueId}','#faf8f4')"
                            class="w-4 h-4 rounded border border-cream-300 bg-cream-100 hover:border-ink-muted transition-colors" title="Cream"></button>
                    <button type="button" onclick="changeBg('${uniqueId}','#18181b')"
                            class="w-4 h-4 rounded border border-cream-300 bg-zinc-900 hover:border-ink-muted transition-colors" title="Dark"></button>
                    <button type="button" onclick="changeBg('${uniqueId}','#ffffff')"
                            class="w-4 h-4 rounded border border-cream-300 bg-white hover:border-ink-muted transition-colors" title="White"></button>
                    <button type="button" onclick="changeBg('${uniqueId}','#000000')"
                            class="w-4 h-4 rounded border border-cream-300 bg-black hover:border-ink-muted transition-colors" title="Black"></button>
                    <button type="button" onclick="changeBg('${uniqueId}','#7c3ca0')"
                            class="w-4 h-4 rounded border border-cream-300 bg-purple-700 hover:border-ink-muted transition-colors" title="Purple"></button>
                    <input type="color" onchange="changeBg('${uniqueId}',this.value)"
                           class="w-4 h-4 rounded border border-cream-300 cursor-pointer" title="Custom colour">
                </div>
                <div class="px-2 py-2 border-t border-cream-200 flex items-center gap-0.5">
                    <a href="${imageUrl}" download="design.png" title="Download" class="icon-btn">
                        <i class="fas fa-download"></i>
                    </a>
                    <button type="button" title="Retouch this design" class="icon-btn accent edit-btn">
                        <i class="fas fa-magic"></i>
                    </button>
                    <button type="button" title="Preview on garment" class="icon-btn preview-btn" data-preview-idx="${idx}">
                        <i class="fas fa-tshirt"></i>
                    </button>
                    <button type="button" title="Send to Printify" class="icon-btn accent printify-quick-btn" data-image-src="${imageUrl}">
                        <i class="fas fa-store"></i>
                    </button>
                </div>
            </div>`;

        messagesContainer.appendChild(div);
        updateWelcomeScreen();
        scrollToBottom();
    }

    function addBotError(msg) {
        const div = document.createElement('div');
        div.className = 'flex items-start gap-3 msg-enter';
        div.innerHTML = `
            <div class="w-8 h-8 rounded-full bg-white border border-cream-300 shadow-sm
                        flex items-center justify-center flex-shrink-0 overflow-hidden p-1 mt-0.5">
                <img src="/images/logo.png" alt="FabricAI" class="w-full h-full object-contain">
            </div>
            <div class="bg-red-50 border border-red-200 rounded-2xl rounded-tl-sm px-4 py-3
                        text-red-700 text-sm leading-relaxed max-w-xs md:max-w-md">
                ${escapeHtml(msg)}
            </div>`;
        messagesContainer.appendChild(div);
        updateWelcomeScreen();
        scrollToBottom();
    }

    window.changeBg = function (bgId, color) {
        const el = document.getElementById(bgId);
        if (el) el.style.backgroundColor = color;
    };

    // ─── Chat management ──────────────────────────────────────────────
    async function loadChats() {
        const chatList = document.getElementById('chat-list');
        chatList.innerHTML = '';
        try {
            const res = await fetch('/chats');
            chats = await res.json();
        } catch (err) {
            showError('Could not load sessions'); return;
        }

        chats.forEach(chat => {
            const wrapper = document.createElement('div');
            wrapper.className = 'group flex items-center gap-2.5 px-2 py-2 rounded-xl transition-colors ' +
                (chat.id === currentChatId ? 'bg-cream-200' : 'hover:bg-cream-100');

            // Thumbnail
            const thumb = document.createElement('div');
            thumb.className = 'chat-thumb';
            if (chat.thumbnail) {
                let thumbSrc = chat.thumbnail;
                if (!thumbSrc.startsWith('data:') && !thumbSrc.startsWith('http')) {
                    thumbSrc = 'data:image/png;base64,' + thumbSrc;
                }
                const img = document.createElement('img');
                img.src = thumbSrc; img.alt = '';
                thumb.appendChild(img);
            } else {
                thumb.innerHTML = '<i class="fas fa-image"></i>';
            }

            // Main content
            const content = document.createElement('div');
            content.className = 'flex-1 min-w-0 cursor-pointer';
            const nameEl = document.createElement('p');
            nameEl.className = 'text-xs font-medium text-ink truncate leading-tight';
            nameEl.textContent = chat.title || 'New Design';
            const dateEl = document.createElement('p');
            dateEl.className = 'text-[10px] text-ink-muted mt-0.5';
            dateEl.textContent = relativeDate(chat.created_at);
            content.appendChild(nameEl); content.appendChild(dateEl);
            content.onclick = () => { loadChat(chat.id); closeSidebar(); };

            // Inline rename input
            const input = document.createElement('input');
            input.type = 'text'; input.value = chat.title || 'New Design';
            input.className = 'hidden flex-1 min-w-0 px-1.5 py-0.5 text-xs rounded-lg border border-[#7c3ca0] bg-white text-ink focus:outline-none';

            let renameSaved = false;
            const saveRename = async () => {
                if (renameSaved) return; renameSaved = true;
                const t = input.value.trim();
                if (!t) return cancelRename();
                await fetch(`/chats/${chat.id}`, {
                    method: 'PATCH',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
                    body: JSON.stringify({ title: t }),
                });
                if (chat.id === currentChatId) document.getElementById('chat-title').textContent = t;
                await loadChats();
            };
            const cancelRename = () => {
                input.classList.add('hidden'); content.classList.remove('hidden');
                renameBtn.classList.remove('hidden'); delBtn.classList.remove('hidden');
            };
            input.addEventListener('keydown', e => {
                if (e.key === 'Enter')  { e.preventDefault(); saveRename(); }
                if (e.key === 'Escape') cancelRename();
            });
            input.addEventListener('blur', saveRename);

            // Action buttons
            const actions = document.createElement('div');
            actions.className = 'flex items-center gap-0.5 opacity-0 group-hover:opacity-100 transition-opacity shrink-0';

            const renameBtn = document.createElement('button');
            renameBtn.type = 'button'; renameBtn.title = 'Rename';
            renameBtn.className = 'icon-btn accent';
            renameBtn.innerHTML = '<i class="fas fa-pencil-alt" style="font-size:10px"></i>';
            renameBtn.onclick = e => {
                e.stopPropagation();
                content.classList.add('hidden'); renameBtn.classList.add('hidden'); delBtn.classList.add('hidden');
                input.classList.remove('hidden'); input.focus(); input.select();
            };

            const delBtn = document.createElement('button');
            delBtn.type = 'button'; delBtn.title = 'Delete session';
            delBtn.className = 'icon-btn danger';
            delBtn.innerHTML = '<i class="fas fa-trash" style="font-size:10px"></i>';
            delBtn.onclick = e => {
                e.stopPropagation();
                pendingDeleteId = chat.id;
                showDeleteModal();
            };

            actions.appendChild(renameBtn); actions.appendChild(delBtn);
            wrapper.appendChild(thumb); wrapper.appendChild(content);
            wrapper.appendChild(input); wrapper.appendChild(actions);
            chatList.appendChild(wrapper);
        });
    }

    async function deleteChat(chatId) {
        const res = await fetch(`/chats/${chatId}`, {
            method: 'DELETE',
            headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
        });
        if (res.ok) {
            if (chatId === currentChatId) {
                currentChatId = null;
                messagesContainer.innerHTML = '';
                document.getElementById('chat-title').textContent = 'New Design';
                updateWelcomeScreen();
            }
            await loadChats();
        } else {
            const data = await res.json().catch(() => ({}));
            showError(data.error || 'Could not delete the session');
        }
    }

    async function newChat() {
        if (isCreatingChat) return;
        isCreatingChat = true;
        const btn = document.querySelector('button[onclick="newChat()"]');
        if (btn) btn.disabled = true;
        try {
            const res  = await fetch('/chats', {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
            });
            const data = await res.json();
            if (!res.ok && data.error) { showError(data.error); return; }
            currentChatId = data.id;
            messagesContainer.innerHTML = '';
            document.getElementById('chat-title').textContent = 'New Design';
            updateWelcomeScreen();
            await loadChats();
            return data.id;
        } finally {
            isCreatingChat = false;
            if (btn) btn.disabled = false;
        }
    }

    async function loadChat(chatId) {
        const res  = await fetch(`/chats/${chatId}`);
        const data = await res.json();
        currentChatId = chatId;
        messagesContainer.innerHTML = '';
        document.getElementById('chat-title').textContent = data.chat?.title || 'New Design';
        data.messages.forEach(msg => {
            if (msg.role === 'user') {
                addUserMessage(msg.content);
            } else if (msg.image) {
                // Normalise: ensure it has a data URI prefix (old rows may be raw base64)
                let imgSrc = msg.image;
                if (imgSrc && !imgSrc.startsWith('data:') && !imgSrc.startsWith('http')) {
                    imgSrc = 'data:image/png;base64,' + imgSrc;
                }
                addBotResponse(imgSrc);
            } else if (msg.content) {
                addBotError(msg.content);
            }
        });
        updateWelcomeScreen();
        await loadChats();
    }

    // ─── Form submit ──────────────────────────────────────────────────
    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        if (isSubmitting) return;
        const prompt = promptInput.value.trim();
        if (!prompt) { showError('Please enter a prompt'); return; }

        isSubmitting = true;
        if (!currentChatId) currentChatId = await newChat();

        addUserMessage(prompt, uploadedImageBase64);
        promptInput.value = ''; promptInput.style.height = 'auto';

        const snapshotImage = uploadedImageBase64;
        const snapshotMime  = uploadedImageMime;
        clearImagePreview();
        setLoading(true);

        try {
            const csrf = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
            const res  = await fetch('/designs/generate', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
                body: JSON.stringify({
                    prompt, chat_id: currentChatId,
                    imageBase64: snapshotImage, mimeType: snapshotMime,
                    model: modelHidden.value, provider: providerHidden.value,
                    is_edit: isEditMode,
                }),
            });
            const data = await res.json().catch(() => ({ success: false, error: 'Invalid server response' }));
            if (!res.ok) throw new Error(data?.message || data?.error || `Error ${res.status}`);

            const imageUrl = data.imageUrl || data.image_url || data.url;
            const base64   = data.imageBase64 || data.image_base64 || data.base64;

            if (imageUrl) {
                addBotResponse(imageUrl); TokenManager.deduct();
            } else if (base64) {
                addBotResponse(base64.startsWith('data:') ? base64 : 'data:image/png;base64,' + base64);
                TokenManager.deduct();
            } else {
                throw new Error('No image in response');
            }
        } catch (err) {
            addBotError(err.message || 'Could not generate the image. Please check your prompt and try again.');
            await TokenManager.sync();
        } finally {
            isSubmitting = false; setLoading(false); exitEditMode(); clearImagePreview();
        }
    });

    document.addEventListener('visibilitychange', () => {
        if (document.visibilityState === 'visible') TokenManager.sync();
    });

    document.addEventListener('DOMContentLoaded', async () => {
        TokenManager.init();
        await TokenManager.sync();
        initProviderButtons();
        await loadChats();
        const res  = await fetch('/chats');
        const list = await res.json();
        if (list.length === 0) await newChat();
        updateWelcomeScreen();
    });

    imageInput.addEventListener('change', e => {
        const file = e.target.files[0];
        if (!file) { uploadedImageBase64 = null; uploadedImageMime = null; return; }
        uploadedImageMime = file.type;
        const reader = new FileReader();
        reader.onload = () => { uploadedImageBase64 = reader.result; showImagePreview(reader.result); };
        reader.readAsDataURL(file);
    });

    // ─── Edit mode ────────────────────────────────────────────────────
    function enterEditMode() {
        isEditMode = true;
        const banner = document.getElementById('edit-banner');
        banner.classList.remove('hidden'); banner.classList.add('flex');
        promptInput.classList.add('border-amber-400', 'bg-amber-50');
        promptInput.placeholder = 'Describe how you want to retouch this design…';
        promptInput.focus();
    }
    function exitEditMode() {
        isEditMode = false;
        const banner = document.getElementById('edit-banner');
        banner.classList.add('hidden'); banner.classList.remove('flex');
        promptInput.classList.remove('border-amber-400', 'bg-amber-50');
        promptInput.placeholder = 'Describe your idea…';
    }
    document.getElementById('cancel-edit-btn').addEventListener('click', () => exitEditMode());

    // ─── Click delegation ─────────────────────────────────────────────
    document.addEventListener('click', function (e) {
        // Edit
        const editBtn = e.target.closest ? e.target.closest('.edit-btn') : null;
        if (editBtn) enterEditMode();

        // Preview
        const prevBtn = e.target.closest ? e.target.closest('.preview-btn') : null;
        if (prevBtn) {
            const idx = parseInt(prevBtn.getAttribute('data-preview-idx'));
            if (!isNaN(idx) && previewImageStore[idx]) openPreviewModal(previewImageStore[idx]);
        }

        // Close preview modal on backdrop
        if (e.target.id === 'preview-modal') closePreviewModal();
    });

    // ─── Mobile sidebar ───────────────────────────────────────────────
    function toggleSidebar() {
        const sidebar  = document.getElementById('sidebar');
        const backdrop = document.getElementById('sidebar-backdrop');
        if (sidebar.classList.contains('sidebar-open')) {
            closeSidebar();
        } else {
            sidebar.classList.add('sidebar-open');
            backdrop.classList.add('sidebar-open');
        }
    }
    function closeSidebar() {
        document.getElementById('sidebar').classList.remove('sidebar-open');
        document.getElementById('sidebar-backdrop').classList.remove('sidebar-open');
    }

    // ═══════════════════════════════════════════════════════════════
    //  GARMENT PREVIEW SYSTEM
    // ═══════════════════════════════════════════════════════════════
    function shadeColor(hex, pct) {
        let r = parseInt(hex.slice(1,3),16), g = parseInt(hex.slice(3,5),16), b = parseInt(hex.slice(5,7),16);
        r = Math.min(255,Math.max(0,Math.round(r*(100+pct)/100)));
        g = Math.min(255,Math.max(0,Math.round(g*(100+pct)/100)));
        b = Math.min(255,Math.max(0,Math.round(b*(100+pct)/100)));
        return '#'+[r,g,b].map(x=>x.toString(16).padStart(2,'0')).join('');
    }

    const GARMENTS = {
        tshirt: {
            name:'T-Shirt', ref:'Gildan 5000', printPx:'3951 × 4919', printInches:'13.17" × 16.40"', dpi:300,
            printArea:{ x:184, y:178, w:132, h:193 },
            draw(ctx,color) {
                const dk=shadeColor(color,-20); ctx.fillStyle=color;
                ctx.beginPath(); ctx.moveTo(195,148); ctx.lineTo(108,170); ctx.lineTo(62,218);
                ctx.lineTo(82,262); ctx.lineTo(150,228); ctx.lineTo(150,495);
                ctx.lineTo(350,495); ctx.lineTo(350,228); ctx.lineTo(418,262);
                ctx.lineTo(438,218); ctx.lineTo(392,170); ctx.lineTo(305,148);
                ctx.quadraticCurveTo(250,122,195,148); ctx.closePath(); ctx.fill();
                ctx.strokeStyle=dk; ctx.lineWidth=2; ctx.stroke();
                ctx.beginPath(); ctx.moveTo(200,148); ctx.quadraticCurveTo(250,128,300,148);
                ctx.lineWidth=4; ctx.strokeStyle=dk; ctx.stroke();
            }
        },
        hoodie: {
            name:'Hoodie', ref:'Gildan 18500', printPx:'3543 × 4724', printInches:'11.81" × 15.75"', dpi:300,
            printArea:{ x:191, y:175, w:118, h:186 },
            draw(ctx,color) {
                const dk=shadeColor(color,-20); ctx.fillStyle=color;
                ctx.beginPath(); ctx.moveTo(175,155); ctx.quadraticCurveTo(155,65,250,55);
                ctx.quadraticCurveTo(345,65,325,155); ctx.quadraticCurveTo(250,130,175,155);
                ctx.closePath(); ctx.fill(); ctx.strokeStyle=dk; ctx.lineWidth=2; ctx.stroke();
                ctx.fillStyle=color; ctx.beginPath();
                ctx.moveTo(195,155); ctx.lineTo(100,178); ctx.lineTo(48,340); ctx.lineTo(88,350);
                ctx.lineTo(140,230); ctx.lineTo(140,498); ctx.lineTo(360,498); ctx.lineTo(360,230);
                ctx.lineTo(412,350); ctx.lineTo(452,340); ctx.lineTo(400,178); ctx.lineTo(305,155);
                ctx.quadraticCurveTo(250,138,195,155); ctx.closePath(); ctx.fill();
                ctx.strokeStyle=dk; ctx.lineWidth=2; ctx.stroke();
                ctx.beginPath(); ctx.moveTo(188,370); ctx.quadraticCurveTo(250,395,312,370);
                ctx.lineTo(312,420); ctx.quadraticCurveTo(250,430,188,420);
                ctx.closePath(); ctx.strokeStyle=dk; ctx.lineWidth=1.5; ctx.stroke();
                ctx.setLineDash([5,5]); ctx.beginPath(); ctx.moveTo(250,155); ctx.lineTo(250,495);
                ctx.strokeStyle=dk; ctx.lineWidth=1; ctx.stroke(); ctx.setLineDash([]);
                ctx.beginPath(); ctx.moveTo(235,155); ctx.lineTo(228,220); ctx.strokeStyle=dk; ctx.lineWidth=1.5; ctx.stroke();
                ctx.beginPath(); ctx.moveTo(265,155); ctx.lineTo(272,220); ctx.strokeStyle=dk; ctx.lineWidth=1.5; ctx.stroke();
            }
        },
        tanktop: {
            name:'Tank Top', ref:'Bella+Canvas 3480', printPx:'3000 × 4200', printInches:'10.00" × 14.00"', dpi:300,
            printArea:{ x:190, y:162, w:120, h:193 },
            draw(ctx,color) {
                const dk=shadeColor(color,-20); ctx.fillStyle=color; ctx.beginPath();
                ctx.moveTo(210,130); ctx.lineTo(180,130); ctx.lineTo(148,195);
                ctx.quadraticCurveTo(135,250,148,270); ctx.lineTo(148,498); ctx.lineTo(352,498);
                ctx.lineTo(352,270); ctx.quadraticCurveTo(365,250,352,195);
                ctx.lineTo(320,130); ctx.lineTo(290,130); ctx.quadraticCurveTo(250,155,210,130);
                ctx.closePath(); ctx.fill(); ctx.strokeStyle=dk; ctx.lineWidth=2; ctx.stroke();
            }
        },
        longsleeve: {
            name:'Long Sleeve', ref:'Gildan 5400', printPx:'3951 × 4919', printInches:'13.17" × 16.40"', dpi:300,
            printArea:{ x:184, y:178, w:132, h:193 },
            draw(ctx,color) {
                const dk=shadeColor(color,-20); ctx.fillStyle=color; ctx.beginPath();
                ctx.moveTo(195,148); ctx.lineTo(108,170); ctx.lineTo(42,380); ctx.lineTo(78,390);
                ctx.lineTo(148,230); ctx.lineTo(148,495); ctx.lineTo(352,495); ctx.lineTo(352,230);
                ctx.lineTo(422,390); ctx.lineTo(458,380); ctx.lineTo(392,170); ctx.lineTo(305,148);
                ctx.quadraticCurveTo(250,122,195,148); ctx.closePath(); ctx.fill();
                ctx.strokeStyle=dk; ctx.lineWidth=2; ctx.stroke();
                ctx.beginPath(); ctx.moveTo(200,148); ctx.quadraticCurveTo(250,128,300,148);
                ctx.lineWidth=4; ctx.strokeStyle=dk; ctx.stroke(); ctx.lineWidth=3;
                ctx.beginPath(); ctx.moveTo(42,378);  ctx.lineTo(78,388);  ctx.stroke();
                ctx.beginPath(); ctx.moveTo(422,388); ctx.lineTo(458,378); ctx.stroke();
            }
        },
        sweatshirt: {
            name:'Sweatshirt', ref:'Gildan 18000', printPx:'3543 × 4724', printInches:'11.81" × 15.75"', dpi:300,
            printArea:{ x:194, y:178, w:113, h:186 },
            draw(ctx,color) {
                const dk=shadeColor(color,-20); ctx.fillStyle=color; ctx.beginPath();
                ctx.moveTo(190,155); ctx.lineTo(105,175); ctx.lineTo(48,345); ctx.lineTo(85,355);
                ctx.lineTo(145,232); ctx.lineTo(145,498); ctx.lineTo(355,498); ctx.lineTo(355,232);
                ctx.lineTo(415,355); ctx.lineTo(452,345); ctx.lineTo(395,175); ctx.lineTo(310,155);
                ctx.quadraticCurveTo(250,128,190,155); ctx.closePath(); ctx.fill();
                ctx.strokeStyle=dk; ctx.lineWidth=2; ctx.stroke();
                ctx.beginPath(); ctx.moveTo(195,155); ctx.quadraticCurveTo(250,135,305,155);
                ctx.lineWidth=6; ctx.strokeStyle=dk; ctx.stroke();
                ctx.beginPath(); ctx.moveTo(145,490); ctx.lineTo(355,490); ctx.lineWidth=6; ctx.strokeStyle=dk; ctx.stroke();
                ctx.lineWidth=4;
                ctx.beginPath(); ctx.moveTo(48,343);  ctx.lineTo(85,353);  ctx.stroke();
                ctx.beginPath(); ctx.moveTo(415,353); ctx.lineTo(452,343); ctx.stroke();
            }
        },
    };

    let previewDesignSrc = null;

    function openPreviewModal(imageSrc) {
        previewDesignSrc = imageSrc;
        ['design-pos-x','design-pos-y','design-scale'].forEach((id,i) => {
            document.getElementById(id).value = i === 2 ? 1 : 0;
        });
        document.getElementById('pos-x-val').textContent  = '0';
        document.getElementById('pos-y-val').textContent  = '0';
        document.getElementById('scale-val').textContent  = '1.00';
        document.getElementById('preview-modal').classList.remove('hidden');
        document.getElementById('preview-modal').classList.add('flex');
        initDesignDrag();
        renderPreview();
    }
    function closePreviewModal() {
        document.getElementById('preview-modal').classList.add('hidden');
        document.getElementById('preview-modal').classList.remove('flex');
        previewDesignSrc = null;
    }

    function renderGarment() {
        const canvas  = document.getElementById('garment-canvas');
        const ctx     = canvas.getContext('2d');
        const garment = GARMENTS[document.getElementById('garment-select').value];
        const color   = document.getElementById('garment-color').value;
        const pa      = garment.printArea;
        ctx.clearRect(0, 0, canvas.width, canvas.height);
        const sz = 15;
        for (let y = 0; y < canvas.height; y += sz)
            for (let x = 0; x < canvas.width; x += sz) {
                ctx.fillStyle = ((x/sz + y/sz) % 2 === 0) ? '#1e1e2e' : '#252538';
                ctx.fillRect(x, y, sz, sz);
            }
        garment.draw(ctx, color);
        ctx.setLineDash([6,4]); ctx.strokeStyle='rgba(168,85,247,0.45)'; ctx.lineWidth=1;
        ctx.strokeRect(pa.x, pa.y, pa.w, pa.h); ctx.setLineDash([]);
        ctx.font='9px sans-serif'; ctx.fillStyle='rgba(168,85,247,0.55)';
        ctx.fillText('Print area', pa.x+2, pa.y-3);
        const specEl = document.getElementById('printify-spec');
        if (specEl) specEl.innerHTML = `<span class="text-purple-400 font-medium">${garment.ref}</span> — ${garment.printPx} px · ${garment.printInches} · ${garment.dpi} DPI`;
    }

    function positionDesignCanvas() {
        const pa = GARMENTS[document.getElementById('garment-select').value].printArea;
        const dc = document.getElementById('design-canvas');
        dc.width = pa.w; dc.height = pa.h;
        dc.style.left   = (pa.x/500*100)+'%'; dc.style.top    = (pa.y/550*100)+'%';
        dc.style.width  = (pa.w/500*100)+'%'; dc.style.height = (pa.h/550*100)+'%';
    }

    function renderDesign() {
        if (!previewDesignSrc) return;
        const dc  = document.getElementById('design-canvas');
        const ctx = dc.getContext('2d');
        const pa  = GARMENTS[document.getElementById('garment-select').value].printArea;
        ctx.clearRect(0, 0, dc.width, dc.height);
        const img = new Image(); img.crossOrigin = 'anonymous';
        img.onload = () => {
            const posX = parseFloat(document.getElementById('design-pos-x').value);
            const posY = parseFloat(document.getElementById('design-pos-y').value);
            const sc   = parseFloat(document.getElementById('design-scale').value);
            const ir = img.width/img.height; const pr = pa.w/pa.h;
            let dw, dh;
            if (ir > pr) { dw = pa.w; dh = pa.w/ir; } else { dh = pa.h; dw = pa.h*ir; }
            dw *= sc; dh *= sc;
            ctx.drawImage(img, (pa.w-dw)/2+posX*(pa.w/2), (pa.h-dh)/2+posY*(pa.h/2), dw, dh);
        };
        img.src = previewDesignSrc;
    }

    function renderPreview() { renderGarment(); positionDesignCanvas(); renderDesign(); }

    function downloadPreview() {
        const gc = document.getElementById('garment-canvas');
        const dc = document.getElementById('design-canvas');
        const pa = GARMENTS[document.getElementById('garment-select').value].printArea;
        const tmp = document.createElement('canvas');
        tmp.width = gc.width; tmp.height = gc.height;
        const ctx = tmp.getContext('2d');
        ctx.drawImage(gc, 0, 0); ctx.drawImage(dc, pa.x, pa.y, pa.w, pa.h);
        const link = document.createElement('a');
        link.download = 'garment-preview.png'; link.href = tmp.toDataURL('image/png'); link.click();
    }

    let _dragInitialized = false;
    function initDesignDrag() {
        if (_dragInitialized) return; _dragInitialized = true;
        const dc = document.getElementById('design-canvas');
        let isDragging=false, lastX=0, lastY=0;
        const dragStart = (cx,cy) => { isDragging=true; lastX=cx; lastY=cy; dc.style.cursor='grabbing'; };
        const dragMove  = (cx,cy) => {
            if (!isDragging) return;
            const rect = dc.getBoundingClientRect();
            const pa   = GARMENTS[document.getElementById('garment-select').value].printArea;
            const sx = pa.w/rect.width; const sy = pa.h/rect.height;
            const dx = (cx-lastX)*sx; const dy = (cy-lastY)*sy; lastX=cx; lastY=cy;
            const slX = document.getElementById('design-pos-x');
            const slY = document.getElementById('design-pos-y');
            slX.value = Math.max(-1,Math.min(1,parseFloat(slX.value)+dx*2/pa.w));
            slY.value = Math.max(-1,Math.min(1,parseFloat(slY.value)+dy*2/pa.h));
            document.getElementById('pos-x-val').textContent = parseFloat(slX.value).toFixed(2);
            document.getElementById('pos-y-val').textContent = parseFloat(slY.value).toFixed(2);
            renderDesign();
        };
        const dragEnd = () => { isDragging=false; dc.style.cursor='grab'; };
        dc.addEventListener('mousedown',  e => { e.preventDefault(); dragStart(e.clientX,e.clientY); });
        document.addEventListener('mousemove', e => dragMove(e.clientX,e.clientY));
        document.addEventListener('mouseup',   () => dragEnd());
        dc.addEventListener('touchstart', e => { e.preventDefault(); dragStart(e.touches[0].clientX,e.touches[0].clientY); }, {passive:false});
        document.addEventListener('touchmove', e => { if (isDragging) { e.preventDefault(); dragMove(e.touches[0].clientX,e.touches[0].clientY); } }, {passive:false});
        document.addEventListener('touchend', () => dragEnd());
    }

    // ═══════════════════════════════════════════════════════════════
    //  PRINTIFY INTEGRATION
    // ═══════════════════════════════════════════════════════════════
    let printifyShopsLoaded = false;

    async function loadPrintifyShops() {
        if (printifyShopsLoaded) return;
        const sel    = document.getElementById('printify-shop');
        const notice = document.getElementById('printify-connect-notice');
        sel.innerHTML = '<option value="">Loading…</option>';
        try {
            const statusRes = await fetch('/printify/status', { headers: { 'Accept': 'application/json' } });
            const status    = await statusRes.json();
            if (!status.connected) {
                sel.closest('.flex.flex-col').classList.add('hidden');
                if (notice) notice.classList.remove('hidden');
                return;
            }
            const res   = await fetch('/printify/shops', { headers: { 'Accept': 'application/json' } });
            const shops = await res.json();
            if (!res.ok) throw new Error(shops.error || 'Could not load shops');
            if (!Array.isArray(shops) || shops.length === 0) {
                sel.innerHTML = '<option value="">No shops found</option>'; return;
            }
            sel.innerHTML = shops.map(s => `<option value="${s.id}">${escapeHtml(s.name)}</option>`).join('');
            printifyShopsLoaded = true;
        } catch (err) {
            sel.innerHTML = `<option value="">Error: ${escapeHtml(err.message)}</option>`;
        }
    }

    function togglePrintifyPanel() {
        const panel    = document.getElementById('printify-panel');
        const isHidden = panel.classList.contains('hidden');
        panel.classList.toggle('hidden', !isHidden);
        if (isHidden) {
            const gs    = document.getElementById('garment-select');
            const label = gs.options[gs.selectedIndex]?.text ?? 'Custom Design';
            document.getElementById('printify-title').value = `FabricAI — ${label}`;
            resetPrintifyFeedback(); loadPrintifyShops();
            const hex    = document.getElementById('garment-color').value;
            const pch    = document.getElementById('printify-color-hex');
            const nameEl = document.getElementById('printify-color-name');
            if (pch) pch.value = hex;
            if (nameEl) nameEl.textContent = hexToColorName(hex);
        }
    }
    function resetPrintifyFeedback() {
        const fb = document.getElementById('printify-feedback');
        fb.className = 'hidden text-sm py-1'; fb.innerHTML = '';
    }
    function showPrintifyFeedback(html, type = 'error') {
        const fb = document.getElementById('printify-feedback');
        fb.className = `text-sm py-1 ${type === 'success' ? 'text-green-700' : 'text-red-600'}`;
        fb.innerHTML = html; fb.classList.remove('hidden');
    }

    async function sendToPrintify() {
        const shopId = document.getElementById('printify-shop').value;
        const title  = document.getElementById('printify-title').value.trim();
        const type   = document.getElementById('garment-select').value;
        const btn    = document.getElementById('printify-send-btn');
        if (!shopId)           { showPrintifyFeedback('Please select a Printify shop.'); return; }
        if (!title)            { showPrintifyFeedback('Please enter a product name.'); return; }
        if (!previewDesignSrc) { showPrintifyFeedback('No design loaded in preview.'); return; }
        const posX = parseFloat(document.getElementById('design-pos-x').value);
        const posY = parseFloat(document.getElementById('design-pos-y').value);
        const sc   = parseFloat(document.getElementById('design-scale').value);
        btn.disabled = true; btn.textContent = 'Creating product…'; resetPrintifyFeedback();
        try {
            const csrf = document.querySelector('meta[name="csrf-token"]').content;
            const res  = await fetch('/printify/products', {
                method:'POST',
                headers:{ 'Content-Type':'application/json','X-CSRF-TOKEN':csrf,'Accept':'application/json' },
                body: JSON.stringify({
                    shop_id:parseInt(shopId), garment_type:type, image_source:previewDesignSrc, title,
                    color:hexToColorName(document.getElementById('printify-color-hex').value),
                    pos_x:0.5+posX*0.5, pos_y:0.5+posY*0.5, design_scale:sc,
                }),
            });
            const data = await res.json();
            if (!res.ok || !data.success) throw new Error(data.error || `HTTP ${res.status}`);
            showPrintifyFeedback(`✓ Product created! <a href="${data.printify_url}" target="_blank" rel="noopener noreferrer" class="underline font-medium">Open in Printify →</a>`, 'success');
            btn.textContent = 'Create Another';
        } catch (err) {
            showPrintifyFeedback(`Failed: ${escapeHtml(err.message)}`);
            btn.textContent = 'Retry';
        } finally { btn.disabled = false; }
    }

    async function sendToAllPrintify() {
        const shopId = document.getElementById('printify-shop').value;
        const title  = document.getElementById('printify-title').value.trim();
        const btn    = document.getElementById('printify-bulk-btn');
        const send   = document.getElementById('printify-send-btn');
        if (!shopId || !title || !previewDesignSrc) {
            showPrintifyFeedback('Please fill in all fields and load a design.'); return;
        }
        const posX = parseFloat(document.getElementById('design-pos-x').value);
        const posY = parseFloat(document.getElementById('design-pos-y').value);
        const sc   = parseFloat(document.getElementById('design-scale').value);
        const garments = [
            {type:'tshirt',label:'T-Shirt'},{type:'hoodie',label:'Hoodie'},
            {type:'tanktop',label:'Tank Top'},{type:'longsleeve',label:'Long Sleeve'},
            {type:'sweatshirt',label:'Sweatshirt'},
        ];
        btn.disabled = true; send.disabled = true; resetPrintifyFeedback();
        const csrf = document.querySelector('meta[name="csrf-token"]').content;
        const resultLines = [];
        const renderProgress = (cur, curLabel) => {
            const pct = Math.round((cur/garments.length)*100);
            showPrintifyFeedback(`<div class="space-y-2">
                <div class="flex justify-between text-xs text-ink-muted mb-0.5">
                    <span>${cur < garments.length ? 'Uploading '+curLabel+'…' : 'Done'}</span>
                    <span>${cur}/${garments.length}</span>
                </div>
                <div class="w-full bg-cream-200 rounded h-1.5">
                    <div class="bg-purple-600 h-1.5 rounded transition-all duration-300" style="width:${pct}%"></div>
                </div>
                <div class="space-y-0.5 pt-1">${resultLines.join('')}</div></div>`);
        };
        for (let i = 0; i < garments.length; i++) {
            const {type,label} = garments[i];
            renderProgress(i, label);
            try {
                const res  = await fetch('/printify/products', {
                    method:'POST',
                    headers:{'Content-Type':'application/json','X-CSRF-TOKEN':csrf,'Accept':'application/json'},
                    body: JSON.stringify({
                        shop_id:parseInt(shopId), garment_type:type, image_source:previewDesignSrc,
                        title:title+' — '+label,
                        color:hexToColorName(document.getElementById('printify-color-hex').value),
                        pos_x:0.5+posX*0.5, pos_y:0.5+posY*0.5, design_scale:sc,
                    }),
                });
                const data = await res.json();
                if (!res.ok || !data.success) throw new Error(data.error || `HTTP ${res.status}`);
                resultLines.push(`<div class="text-green-700 text-xs">✓ ${label} — <a href="${data.printify_url}" target="_blank" rel="noopener noreferrer" class="underline font-medium">Open →</a></div>`);
            } catch (err) {
                resultLines.push(`<div class="text-red-600 text-xs">✗ ${label}: ${escapeHtml(err.message)}</div>`);
            }
        }
        renderProgress(garments.length, '');
        btn.disabled = false; send.disabled = false; btn.textContent = 'Upload to All';
    }

    const PRINTIFY_PALETTE = [
        {name:'Black',hex:'#18181b'},{name:'Dark Heather',hex:'#4b5563'},
        {name:'Sport Grey',hex:'#9ca3af'},{name:'White',hex:'#f9f9f9'},
        {name:'Navy',hex:'#1e3a5f'},{name:'Royal',hex:'#1d4ed8'},
        {name:'Sky',hex:'#7dd3fc'},{name:'Red',hex:'#dc2626'},
        {name:'Maroon',hex:'#7f1d1d'},{name:'Orange',hex:'#ea580c'},
        {name:'Gold',hex:'#ca8a04'},{name:'Forest Green',hex:'#15803d'},
        {name:'Olive',hex:'#4d7c0f'},{name:'Purple',hex:'#7e22ce'},
        {name:'Heliconia',hex:'#db2777'},
    ];
    function hexToRgb(hex) { const n=parseInt(hex.replace('#',''),16); return [(n>>16)&255,(n>>8)&255,n&255]; }
    function colorDist([r1,g1,b1],[r2,g2,b2]) { return Math.sqrt((r1-r2)**2+(g1-g2)**2+(b1-b2)**2); }
    function hexToColorName(hex) {
        const rgb = hexToRgb(hex); let best=PRINTIFY_PALETTE[0], bd=Infinity;
        for (const p of PRINTIFY_PALETTE) { const d=colorDist(rgb,hexToRgb(p.hex)); if (d<bd){bd=d;best=p;} }
        return best.name;
    }
    function onPrintifyColorChange(hex) {
        const gc=document.getElementById('garment-color'); if (gc){gc.value=hex;renderPreview();}
        const n=document.getElementById('printify-color-name'); if (n) n.textContent=hexToColorName(hex);
    }
    document.addEventListener('DOMContentLoaded', () => {
        const gc=document.getElementById('garment-color');
        if (gc) gc.addEventListener('input', e => {
            const p=document.getElementById('printify-color-hex');
            const n=document.getElementById('printify-color-name');
            if (p) p.value=e.target.value;
            if (n) n.textContent=hexToColorName(e.target.value);
        });
    });
    document.addEventListener('click', function (e) {
        const qb = e.target.closest ? e.target.closest('.printify-quick-btn') : null;
        if (!qb) return;
        const src = qb.getAttribute('data-image-src');
        if (src) openPreviewModal(src);
        const panel = document.getElementById('printify-panel');
        if (panel.classList.contains('hidden')) togglePrintifyPanel();
    });
</script>

</body>
</html>
