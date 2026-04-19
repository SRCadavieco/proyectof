<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>FabricAI — The Fitting Room</title>
    @vite(['resources/css/app.css','resources/js/app.js'])
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
            .scrollbar-hide::-webkit-scrollbar { display: none; }
            .scrollbar-hide { -ms-overflow-style: none; scrollbar-width: none; }
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        .spinner {
            animation: spin 0.8s linear infinite;
        }
        /* Custom scrollbar */
        ::-webkit-scrollbar { width: 6px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb {
            background: #d4cdc0;
            border-radius: 99px;
        }
        ::-webkit-scrollbar-thumb:hover { background: #7c3ca0; }
        * { scrollbar-width: thin; scrollbar-color: #d4cdc0 transparent; }

        /* Message entrance */
        @keyframes msgIn {
            from { opacity: 0; transform: translateY(8px); }
            to   { opacity: 1; transform: translateY(0); }
        }
        .msg-enter { animation: msgIn 0.3s ease-out forwards; }

        /* Loading dots */
        @keyframes dotBounce {
            0%, 80%, 100% { transform: translateY(0); }
            40%            { transform: translateY(-5px); }
        }
        .dot-1 { animation: dotBounce 1.2s infinite 0s; }
        .dot-2 { animation: dotBounce 1.2s infinite 0.2s; }
        .dot-3 { animation: dotBounce 1.2s infinite 0.4s; }

        /* Sidebar accent stripe */
        .sidebar-accent {
            height: 3px;
            background: linear-gradient(90deg, #5a2275 0%, #7c3ca0 40%, #c2704f 70%, #5a2275 100%);
        }
    </style>
</head>
<body class="bg-cream-50 text-ink h-screen overflow-hidden font-sans">

<div class="flex h-screen">
    <!-- ================= SIDEBAR ================= -->
    <aside class="w-72 bg-white border-r border-cream-300 flex flex-col">
        <div class="sidebar-accent"></div>
        <div class="p-6 border-b border-cream-300">
            <a href="/" class="flex items-center gap-2">
                <img src="/images/logo.png" alt="Logo" class="h-10 w-10">
                <div>
                    <span class="font-serif text-base text-ink block leading-tight">FabricAI</span>
                    <span class="text-[9px] font-medium tracking-[0.25em] uppercase" style="color:#7c3ca0">The Fitting Room</span>
                </div>
            </a>
        </div>
        <div class="flex-1 overflow-y-auto p-4 text-sm text-ink-muted">
            <!-- Future chat history -->
            <div class="flex-1 overflow-y-auto p-4 text-sm text-ink-muted">
    <button
        onclick="newChat()"
        class="w-full mb-3 px-4 py-2.5 bg-ink text-white text-xs font-medium tracking-widest uppercase
               hover:bg-purple-900 transition-colors">
        + New Look
    </button>
    <p class="text-[9px] uppercase tracking-widest text-ink-muted mb-2 px-1">Your Sessions</p>
    <div id="chat-list" class="space-y-1"></div>
</div>
        </div>

        <!-- Token counter -->
        <div class="p-4 border-t border-cream-300">
            <div class="bg-cream-100 border border-cream-300 px-4 py-3 flex flex-col gap-2">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-medium text-ink-muted uppercase tracking-wider">Design Credits</span>
                    <div class="flex items-center gap-1.5">
                        <span id="token-icon" class="text-base">&#9889;</span>
                        <span id="token-count" class="text-sm font-bold text-ink">{{ Auth::user()->tokens ?? 0 }}</span>
                        <span class="text-xs text-ink-muted">/ 10</span>
                    </div>
                </div>
                <div class="w-full bg-cream-300 rounded-full h-1.5">
                    <div id="token-bar" class="h-1.5 rounded-full transition-all duration-500 bg-purple-600" style="width:{{ ((Auth::user()->tokens ?? 0) / 10) * 100 }}%"></div>
                </div>
                <button id="refill-btn"
                        onclick="TokenManager.refill()"
                        class="{{ (Auth::user()->tokens ?? 0) > 0 ? 'hidden' : '' }} w-full mt-1 py-2 bg-ink text-white text-xs font-medium tracking-wide uppercase hover:bg-ink-light transition-colors">
                    Want more credits?
                </button>
            </div>
        </div>
    </aside>

    <!-- ================= MAIN ================= -->
    <main class="flex-1 flex flex-col relative overflow-hidden">
        <!-- Background fitting-room at main level — visible behind every panel -->
        <div class="absolute inset-0 z-0" style="
            background-image: url('/images/fitting-room.jpg');
            background-size: 270%;
            background-position: center 60%;
            background-repeat: no-repeat;
        "></div>
        <div class="absolute inset-0 z-0" style="background: rgba(245, 240, 232, 0.55);"></div>

        <!-- HEADER -->
        <header class="relative z-10 px-8 py-4 border-b border-cream-300
                       bg-white flex items-center">
            <!-- Left spacer -->
            <div class="flex-1"></div>
            <!-- Centered chat title -->
            <h1 id="chat-title" class="font-medium text-ink text-sm truncate max-w-xs text-center">New Look</h1>
            <!-- Right: user + logout -->
            <div class="flex-1 flex justify-end items-center gap-3">
                <span class="text-sm text-ink-muted">{{ Auth::user()->name }}</span>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit"
                            class="px-3 py-1.5 border border-cream-300 text-xs text-ink-muted hover:text-ink hover:border-ink transition-colors">
                        Log out
                    </button>
                </form>
            </div>
        </header>

        <!-- ================= CHAT AREA ================= -->
        <div class="flex-1 relative overflow-hidden">

        <div id="chat-container" class="absolute inset-0 overflow-y-auto p-8 pb-4 z-10" style="background:transparent;">
            <div class="max-w-4xl mx-auto space-y-8">
                <!-- Welcome Message -->
                <div class="flex items-start gap-4 msg-enter">
                    <div class="w-10 h-10 rounded-lg bg-white/80 flex items-center justify-center flex-shrink-0 overflow-hidden p-1 shadow-sm">
                        <img src="/images/logo.png" alt="FabricAI" class="w-full h-full object-contain">
                    </div>
                    <div class="bg-white/90 backdrop-blur-sm border border-cream-300 rounded-lg px-6 py-5 text-ink-light max-w-2xl shadow-sm space-y-2">
                        <p class="text-[10px] font-medium tracking-[0.2em] uppercase" style="color:#7c3ca0">Welcome to The Fitting Room</p>
                        <p class="font-serif text-xl text-ink leading-snug">Your personal atelier, open 24 hours.</p>
                        <p class="text-sm leading-relaxed">Tell me what you have in mind — a silhouette, a mood, a fabric — and I’ll tailor it into a design. Refine each look as many times as you like.</p>
                        <p class="text-xs text-ink-muted border-t border-cream-200 pt-3">Try: <em>“A structured blazer in ivory linen with oversized lapels”</em></p>
                    </div>
                </div>
                <div id="messages" class="space-y-8"></div>
            </div>
        </div>
        </div><!-- /fitting-room wrapper -->

        <!-- ================= INPUT AREA ================= -->
        <div class="relative z-10 border-t border-cream-300/60 p-6"
             style="background: rgba(250, 247, 242, 0.82); backdrop-filter: blur(14px); -webkit-backdrop-filter: blur(14px);">
            <div id="error" class="hidden text-red-600 text-sm mb-3"></div>
            <div id="loader" class="hidden items-center gap-3 text-sm mb-4" style="color:#7c3ca0">
                <div class="flex gap-1 items-end">
                    <span class="w-1.5 h-1.5 rounded-full bg-purple-700 dot-1"></span>
                    <span class="w-1.5 h-1.5 rounded-full bg-purple-700 dot-2"></span>
                    <span class="w-1.5 h-1.5 rounded-full bg-purple-700 dot-3"></span>
                </div>
                <span id="loader-text">Tailoring your look…</span>
            </div>
            <form id="design-form" class="max-w-4xl mx-auto">
                <!-- AI selector: provider tabs + model pills -->
                <div class="mb-3 flex justify-end items-center gap-2.5">

                    <!-- Provider segmented control -->
                    <div class="inline-flex" style="border:1px solid #d4cdc0;">
                        <button type="button" id="provider-btn-gemini" data-provider="gemini"
                            class="provider-btn px-4 py-2 text-[10px] font-medium tracking-[0.14em] uppercase transition-colors"
                            style="background:#2a2520;color:#fff;">
                            Gemini
                        </button>
                        <button type="button" id="provider-btn-chutes" data-provider="chutes"
                            class="provider-btn px-4 py-2 text-[10px] font-medium tracking-[0.14em] uppercase transition-colors"
                            style="background:transparent;color:#88827a;border-left:1px solid #d4cdc0;">
                            Chutes AI
                        </button>
                    </div>

                    <span class="text-cream-400 text-xs select-none">&rsaquo;</span>

                    <!-- Model pills -->
                    <div id="model-pills" class="inline-flex" style="border:1px solid #d4cdc0;"></div>

                    <!-- Hidden inputs used when submitting -->
                    <input type="hidden" id="ai-provider" value="gemini">
                    <input type="hidden" id="ai-model"    value="fabric_light">
                </div>
                <!-- Banner modo edición -->
                <div id="edit-banner" class="hidden mb-3 flex items-center gap-2 px-4 py-2 bg-purple-50 border border-purple-200 text-purple-800 text-sm font-medium">
                    <span>&#9986; Retouching your look — the previous image is your base</span>
                    <button type="button" id="cancel-edit-btn" class="ml-auto text-purple-600 hover:text-purple-900 transition-colors text-xs underline">Cancel</button>
                </div>
                <div class="flex gap-3 items-end">
    <!-- Upload image -->
    <label class="cursor-pointer px-4 py-4 bg-cream-200 hover:bg-cream-300 transition-colors">
        <i class="fas fa-paperclip text-ink-muted"></i>
        <input type="file" id="image-upload" accept="image/*" class="hidden">
    </label>
    <div id="image-preview" class="ml-2"></div>

    <textarea
        id="prompt"
        rows="1"
        placeholder="Describe your vision — silhouette, fabric, mood, reference…"
        class="flex-1 bg-cream-100 border border-cream-300 px-5 py-4 text-sm resize-none text-ink
               focus:outline-none focus:border-purple-400 transition-colors
               placeholder-ink-muted/50 max-h-40 scrollbar-hide"></textarea>

    <button
        type="submit"
        id="submit-btn"
        class="px-6 py-4 font-medium text-sm tracking-widest uppercase
               text-white transition-colors disabled:opacity-50"
        style="background:#5a2275;"
        onmouseover="this.style.background='#7c3ca0'" onmouseout="this.style.background='#5a2275'">
        Create Look
    </button>
</div>
            </form>
        </div>
    </main>
</div>
    
    <!-- Hidden elements para procesar imagen -->
    <img id="temp-image" class="hidden" crossorigin="anonymous" />
    <div id="debug-info" class="hidden"></div>

    <script>
        // User identity (ready for profile photos)
        const userInitial = '{{ strtoupper(mb_substr(Auth::user()->name, 0, 1)) }}';
        const userAvatarUrl = null; // set to profile photo URL when available

        // ─── Token Manager (server-backed) ─────────────────────────────
        const TokenManager = {
            MAX: 10,
            _cache: {{ Auth::user()->tokens ?? 10 }},

            get() {
                return this._cache;
            },

            set(n) {
                this._cache = Math.max(0, n);
                this._render(this._cache);
            },

            deduct() {
                const cur = this.get();
                if (cur <= 0) return false;
                this.set(cur - 1);
                return true;
            },

            refill() {
                window.location.href = '/pricing';
            },

            async sync() {
                try {
                    const res = await fetch('/api/tokens', {
                        headers: { 'Accept': 'application/json' }
                    });
                    if (res.ok) {
                        const data = await res.json();
                        this._cache = data.remaining;
                        this._render(data.remaining);
                    }
                } catch (e) { /* silent */ }
            },

            _render(n) {
                const countEl = document.getElementById('token-count');
                const barEl   = document.getElementById('token-bar');
                const btnEl   = document.getElementById('refill-btn');
                const iconEl  = document.getElementById('token-icon');
                if (!countEl) return;
                countEl.textContent = n;
                barEl.style.width = ((n / this.MAX) * 100) + '%';
                if (n === 0) {
                    barEl.className = 'h-1.5 rounded-full transition-all duration-500 bg-red-500';
                    iconEl.textContent = String.fromCodePoint(0x1FABA);
                    btnEl.classList.remove('hidden');
                    if (submitBtn) submitBtn.disabled = true;
                } else {
                    barEl.className = n <= 3
                        ? 'h-1.5 rounded-full transition-all duration-500 bg-orange-400'
                        : 'h-1.5 rounded-full transition-all duration-500 bg-purple-600';
                    iconEl.textContent = '\u26A1';
                    btnEl.classList.add('hidden');
                    if (submitBtn) submitBtn.disabled = false;
                }
            },

            init() { this._render(this.get()); }
        };
        // ─────────────────────────────────────────────────────────────────

        // Referencias a elementos
        let uploadedImageBase64 = null;
        let uploadedImageMime = null;
        let currentChatId = null;
        let isEditMode = false;
        let isSubmitting = false;
        let isCreatingChat = false;
        let chats = [];
        const imageInput = document.getElementById('image-upload');
        const form = document.getElementById('design-form');

        const MODEL_OPTIONS = {
            gemini: [
                { value: 'fabric_light', label: 'Fabric Light' },
                { value: 'fabric_pro',   label: 'Fabric Pro'   },
            ],
            chutes: [
                { value: 'z_image_turbo', label: 'Z-Image Turbo' },
                { value: 'flux_schnell',  label: 'FLUX Schnell'  },
            ],
        };

        const providerHidden = document.getElementById('ai-provider');
        const modelHidden    = document.getElementById('ai-model');
        const modelPillsEl   = document.getElementById('model-pills');

        function syncModelOptions(provider) {
            const options = MODEL_OPTIONS[provider] ?? MODEL_OPTIONS.gemini;
            modelPillsEl.innerHTML = options.map((m, i) => {
                const border = i > 0 ? 'border-left:1px solid #d4cdc0;' : '';
                const style  = i === 0
                    ? `background:#5a2275;color:#fff;${border}`
                    : `background:transparent;color:#88827a;${border}`;
                return `<button type="button" data-model="${m.value}"
                    class="model-pill px-3.5 py-2 text-[10px] font-medium tracking-[0.12em] uppercase transition-colors"
                    style="${style}">${m.label}</button>`;
            }).join('');

            modelHidden.value = options[0].value;

            modelPillsEl.querySelectorAll('.model-pill').forEach(btn => {
                btn.addEventListener('click', () => {
                    modelPillsEl.querySelectorAll('.model-pill').forEach(b => {
                        b.style.background = 'transparent';
                        b.style.color = '#88827a';
                    });
                    btn.style.background = '#5a2275';
                    btn.style.color = '#fff';
                    modelHidden.value = btn.dataset.model;
                });
            });
        }

        function initProviderButtons() {
            document.querySelectorAll('.provider-btn').forEach(btn => {
                btn.addEventListener('click', () => {
                    document.querySelectorAll('.provider-btn').forEach(b => {
                        b.style.background = 'transparent';
                        b.style.color = '#88827a';
                        b.style.borderLeft = b.dataset.provider === 'chutes' ? '1px solid #d4cdc0' : '';
                    });
                    btn.style.background = '#2a2520';
                    btn.style.color = '#fff';
                    providerHidden.value = btn.dataset.provider;
                    syncModelOptions(btn.dataset.provider);
                });
            });
            syncModelOptions('gemini');
        }
        const promptInput = document.getElementById('prompt');
        const submitBtn = document.getElementById('submit-btn');
        const loader = document.getElementById('loader');
        const errorEl = document.getElementById('error');
        const messagesContainer = document.getElementById('messages');
        const chatContainer = document.getElementById('chat-container');
        const previewImageStore = [];
        
        // Auto-resize textarea
        promptInput.addEventListener('input', function() {
            this.style.height = 'auto';
            this.style.height = Math.min(this.scrollHeight, 120) + 'px';
        });

        // Enter = submit, Shift+Enter = nueva línea
        promptInput.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                form.dispatchEvent(new Event('submit', { cancelable: true }));
            }
        });
        
        // Rotating fitting-room loader messages
        const ATELIER_MESSAGES = [
            'Tailoring your look…',
            'Stitching your idea together…',
            'Cutting the pattern…',
            'The atelier is at work…',
            'Draping the fabric…',
            'Fitting your design…',
            'Pinning the details…',
            'Pressing the seams…',
        ];
        let _loaderInterval = null;

        // Estado de carga
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
                clearInterval(_loaderInterval);
                _loaderInterval = null;
            }
        }
        
        // Mostrar error
        function showError(msg) {
            errorEl.textContent = msg;
            errorEl.classList.remove('hidden');
        }
        
        // Agregar mensaje del usuario
        function addUserMessage(text, imageBase64 = null) {
            const avatarHtml = userAvatarUrl
                ? `<img src="${userAvatarUrl}" alt="" class="w-8 h-8 rounded-full object-cover flex-shrink-0">`
                : `<div class="w-8 h-8 rounded-full bg-ink flex items-center justify-center text-white text-sm font-bold flex-shrink-0">${userInitial}</div>`;

            // Image as a separate bubble above the text
            if (imageBase64) {
                const imgDiv = document.createElement('div');
                imgDiv.className = 'mb-2 flex flex-row-reverse items-end gap-3';
                imgDiv.innerHTML = `
                    <div class="w-8 h-8 flex-shrink-0"></div>
                    <img src="${imageBase64}" alt="Attached image"
                         class="rounded-lg max-w-xs max-h-56 object-cover shadow-sm">
                `;
                messagesContainer.appendChild(imgDiv);
            }

            const messageDiv = document.createElement('div');
            messageDiv.className = 'mb-5 flex flex-row-reverse items-start gap-3 msg-enter';
            messageDiv.innerHTML = `
                ${avatarHtml}
                <div class="bg-ink text-white px-4 py-3.5 rounded-lg text-sm leading-relaxed max-w-2xl">${escapeHtml(text)}</div>
            `;
            messagesContainer.appendChild(messageDiv);
            scrollToBottom();
        }
        
        // Agregar respuesta del bot con imagen
        function addBotResponse(imageUrl) {
            const messageDiv = document.createElement('div');
            messageDiv.className = 'mb-5 flex items-start gap-4 msg-enter';
            
            const uniqueId = 'bg-' + Date.now();
            const previewIdx = previewImageStore.length;
            previewImageStore.push(imageUrl);
            
            messageDiv.innerHTML = `
                <div class="w-10 h-10 rounded-lg bg-white/80 flex items-center justify-center flex-shrink-0 self-start overflow-hidden p-1 shadow-sm">
                    <img src="/images/logo.png" alt="FabricAI" class="w-full h-full object-contain">
                </div>
                <div class="flex flex-col gap-3 max-w-2xl bg-white/90 backdrop-blur-sm border border-cream-300 rounded-lg p-3 shadow-sm">
                    <div id="${uniqueId}" class="rounded-lg overflow-hidden bg-cream-100 p-2 transition-colors">
                        <img src="${imageUrl}" alt="Generated design" class="rounded-lg shadow-sm w-full block" crossorigin="anonymous">
                    </div>
                    <div class="flex gap-2 items-center flex-wrap px-1">
                        <span class="text-xs text-ink-muted mr-1">Background:</span>
                        <button type="button" onclick="changeBg('${uniqueId}', '#18181b')" class="w-7 h-7 border border-cream-300 bg-gray-900 hover:border-ink transition-colors" title="Dark"></button>
                        <button type="button" onclick="changeBg('${uniqueId}', '#ffffff')" class="w-7 h-7 border border-cream-300 bg-white hover:border-ink transition-colors" title="White"></button>
                        <button type="button" onclick="changeBg('${uniqueId}', '#000000')" class="w-7 h-7 border border-cream-300 bg-black hover:border-ink transition-colors" title="Black"></button>
                        <button type="button" onclick="changeBg('${uniqueId}', '#a78bfa')" class="w-7 h-7 border border-cream-300 bg-purple-400 hover:border-ink transition-colors" title="Purple"></button>
                        <button type="button" onclick="changeBg('${uniqueId}', '#6366f1')" class="w-7 h-7 border border-cream-300 bg-indigo-500 hover:border-ink transition-colors" title="Indigo"></button>
                        <input type="color" onchange="changeBg('${uniqueId}', this.value)" class="w-7 h-7 border border-cream-300 cursor-pointer" title="Custom">
                    </div>
                    <div class="flex gap-3 px-1">
                        <a href="${imageUrl}" download="design.png"
                           class="px-4 py-2 bg-ink text-white text-sm font-medium tracking-wide uppercase hover:bg-ink-light transition-colors">
                            Download
                        </a>
                        <button type="button" class="px-4 py-2 border border-ink text-ink text-sm font-medium tracking-wide uppercase hover:bg-ink hover:text-white transition-colors edit-btn">
                            Edit image
                        </button>
                        <button type="button" class="px-4 py-2 border border-ink text-ink text-sm font-medium tracking-wide uppercase hover:bg-ink hover:text-white transition-colors preview-btn" data-preview-idx="${previewIdx}">
                            Preview
                        </button>
                        <button type="button" class="px-4 py-2 border border-purple-600 text-purple-700 text-sm font-medium tracking-wide uppercase hover:bg-purple-700 hover:text-white transition-colors printful-quick-btn" data-image-src="${imageUrl}">
                            Printful
                        </button>
                    </div>
                </div>
                        `;
                        // Edit image button delegation
                       
            
            messagesContainer.appendChild(messageDiv);
            scrollToBottom();
        }
        
        // Función global para cambiar el fondo
        window.changeBg = function(bgId, color) {
            const bgElement = document.getElementById(bgId);
            if (bgElement) {
                bgElement.style.backgroundColor = color;
            }
        };
        
        // Mensaje de error del bot
        function addBotError(msg) {
            const messageDiv = document.createElement('div');
            messageDiv.className = 'mb-5 flex items-start gap-4 msg-enter';
            messageDiv.innerHTML = `
                <div class="w-10 h-10 rounded-lg bg-white/80 flex items-center justify-center flex-shrink-0 overflow-hidden p-1 shadow-sm">
                    <img src="/images/logo.png" alt="FabricAI" class="w-full h-full object-contain">
                </div>
                <div class="bg-red-50/90 backdrop-blur-sm border border-red-200 rounded-lg px-6 py-4 max-w-2xl text-red-700 text-sm leading-relaxed">
                    ${escapeHtml(msg)}
                </div>
            `;
            messagesContainer.appendChild(messageDiv);
            scrollToBottom();
        }

        // Scroll al final del chat
        function scrollToBottom() {
            setTimeout(() => {
                chatContainer.scrollTop = chatContainer.scrollHeight;
            }, 100);
        }
        
        // Escape HTML
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
        async function loadChats() {
    

    const chatList = document.getElementById('chat-list');
    chatList.innerHTML = '';
    // Obtener chats desde backend
    try {
        const res = await fetch('/chats');
        chats = await res.json();
    } catch (err) {
        showError('Could not load chats');
        return;
    }
    chats.forEach(chat => {
        const wrapper = document.createElement('div');
        wrapper.className = 'flex items-center group';

        // Nombre del chat (texto normal)
        const div = document.createElement('div');
        div.className =
            'flex-1 px-3 py-2 rounded-lg cursor-pointer truncate ' +
            (chat.id === currentChatId
                ? 'bg-cream-200 text-ink'
                : 'hover:bg-cream-100 text-ink-muted');
        div.textContent = chat.title ?? 'New Look';
        div.onclick = () => loadChat(chat.id);

        // Input inline para renombrar (oculto por defecto)
        const input = document.createElement('input');
        input.type = 'text';
        input.value = chat.title ?? 'New Look';
        input.className = 'hidden flex-1 px-3 py-1.5 rounded-lg bg-cream-100 text-ink text-sm border border-purple-600 focus:outline-none';

        let renameSaved = false;
        const saveRename = async () => {
            if (renameSaved) return;
            renameSaved = true;
            const newTitle = input.value.trim();
            if (!newTitle) return cancelRename();
            await fetch(`/chats/${chat.id}`, {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({ title: newTitle })
            });
            if (chat.id === currentChatId) {
                document.getElementById('chat-title').textContent = newTitle;
            }
            await loadChats();
        };

        const cancelRename = () => {
            input.classList.add('hidden');
            div.classList.remove('hidden');
            renameBtn.classList.remove('hidden');
            delBtn.classList.remove('hidden');
        };

        input.addEventListener('keydown', (e) => {
            if (e.key === 'Enter') { e.preventDefault(); saveRename(); }
            if (e.key === 'Escape') cancelRename();
        });
        input.addEventListener('blur', saveRename);

        // Botón renombrar (lápiz)
        const renameBtn = document.createElement('button');
        renameBtn.className = 'ml-1 text-ink-muted hover:text-purple-700 text-sm px-1 focus:outline-none opacity-0 group-hover:opacity-100 transition-opacity';
        renameBtn.title = 'Rename chat';
        renameBtn.innerHTML = '<i class="fas fa-pencil-alt"></i>';
        renameBtn.onclick = (e) => {
            e.stopPropagation();
            div.classList.add('hidden');
            renameBtn.classList.add('hidden');
            delBtn.classList.add('hidden');
            input.classList.remove('hidden');
            input.focus();
            input.select();
        };

        // Botón borrar
        const delBtn = document.createElement('button');
        delBtn.className = 'ml-1 text-ink-muted hover:text-red-600 text-sm px-1 focus:outline-none opacity-0 group-hover:opacity-100 transition-opacity';
        delBtn.title = 'Delete chat';
        delBtn.innerHTML = '<i class="fas fa-trash"></i>';
        delBtn.onclick = async (e) => {
            e.stopPropagation();
            if (confirm('Are you sure you want to delete this chat?')) {
                await deleteChat(chat.id);
            }
        };

        wrapper.appendChild(div);
        wrapper.appendChild(input);
        wrapper.appendChild(renameBtn);
        wrapper.appendChild(delBtn);
        chatList.appendChild(wrapper);
    });
}
async function deleteChat(chatId) {
    const res = await fetch(`/chats/${chatId}`, {
        method: 'DELETE',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        }
    });
    if (res.ok) {
        // Si el chat borrado era el actual, limpiar mensajes y chatId
        if (chatId === currentChatId) {
            currentChatId = null;
            messagesContainer.innerHTML = '';
        }
        await loadChats();
    } else {
        const data = await res.json().catch(() => ({}));
        showError(data.error || 'Could not delete the chat');
    }
}


async function newChat() {
    if (isCreatingChat) return;
    isCreatingChat = true;
    const newChatBtn = document.querySelector('button[onclick="newChat()"]');
    if (newChatBtn) newChatBtn.disabled = true;
    try {
        const res = await fetch('/chats', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            }
        });
        const data = await res.json();
        if (!res.ok && data.error) {
            showError(data.error);
            return;
        }
        currentChatId = data.id;
        messagesContainer.innerHTML = '';
        document.getElementById('chat-title').textContent = 'New Look';
        await loadChats();
        return data.id;
    } finally {
        isCreatingChat = false;
        if (newChatBtn) newChatBtn.disabled = false;
    }
}

async function loadChat(chatId) {
    const res = await fetch(`/chats/${chatId}`);
    const data = await res.json();
    currentChatId = chatId;
    messagesContainer.innerHTML = '';
    document.getElementById('chat-title').textContent = data.chat?.title ?? 'New Look';
    data.messages.forEach(msg => {
        if (msg.role === 'user') {
            addUserMessage(msg.content);
        } else if (msg.image) {
            addBotResponse(msg.image);
        } else if (msg.content) {
            addBotError(msg.content);
        }
    });
    await loadChats();
}

        // Enviar formulario
        form.addEventListener('submit', async (e) => {
            e.preventDefault();

            if (isSubmitting) return;

            const prompt = promptInput.value.trim();
            if (!prompt) {
                showError('Please enter a prompt');
                return;
            }

            if (TokenManager.get() <= 0) {
                // Sincronizar con servidor por si el admin añadió tokens
                await TokenManager.sync();
                if (TokenManager.get() <= 0) {
                    showError('You have no tokens left. Get more to keep designing!');
                    return;
                }
            }

            isSubmitting = true;

            // Si no hay chat actual, crear uno antes de enviar
            if (!currentChatId) {
                currentChatId = await newChat();
            }

            // Agregar mensaje del usuario
            addUserMessage(prompt, uploadedImageBase64);
            promptInput.value = '';
            promptInput.style.height = 'auto';

            // Snapshot image values before clearing the UI
            const snapshotImage = uploadedImageBase64;
            const snapshotMime  = uploadedImageMime;

            // Clear image input immediately after sending
            imageInput.value = '';
            document.getElementById('image-preview').innerHTML = '';
            uploadedImageBase64 = null;
            uploadedImageMime = null;

            setLoading(true);

            try {
                const aiModel    = modelHidden.value;
                const aiProvider = providerHidden.value;
                const csrf = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
                console.log('Enviando datos:', {
                    prompt,
                    chat_id: currentChatId,
                    imageBase64: snapshotImage,
                    mimeType: snapshotMime,
                    model: aiModel,
                    provider: aiProvider
                });
                const res = await fetch('/designs/generate', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrf,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        prompt,
                        chat_id: currentChatId,
                        imageBase64: snapshotImage,
                        mimeType: snapshotMime,
                        model: aiModel,
                        provider: aiProvider,
                        is_edit: isEditMode
                    })
                });
                const data = await res.json().catch(() => ({
                    success: false,
                    error: 'Respuesta inválida del servidor'
                }));

                if (!res.ok) {
                    throw new Error(data?.message || data?.error || `Error ${res.status}`);
                }

                const imageUrl = data.imageUrl || data.image_url || data.url;
                const base64 = data.imageBase64 || data.image_base64 || data.base64;

                if (imageUrl) {
                    addBotResponse(imageUrl);
                    TokenManager.deduct();
                } else if (base64) {
                    const fullBase64 = base64.startsWith('data:') ? base64 : 'data:image/png;base64,' + base64;
                    addBotResponse(fullBase64);
                    TokenManager.deduct();
                } else {
                    throw new Error('No image in response');
                }
            } catch (err) {
                const errMsg = err.message || 'Could not generate the image. Please check your prompt and try again.';
                addBotError(errMsg);
                console.error('Error en submit:', err);
                // Sincronizar tokens con el servidor: el backend puede haber descontado
                // un token aunque la generación haya fallado (ej. timeout en Chutes)
                await TokenManager.sync();
            } finally {
                isSubmitting = false;
                setLoading(false);
                exitEditMode();
                uploadedImageBase64 = null;
                uploadedImageMime = null;
                imageInput.value = '';
                document.getElementById('image-preview').innerHTML = '';
            }
        });
        // Sincronizar tokens cuando el usuario vuelve a la pestaña
        document.addEventListener('visibilitychange', () => {
            if (document.visibilityState === 'visible') {
                TokenManager.sync();
            }
        });

        document.addEventListener('DOMContentLoaded', async () => {
    TokenManager.init();
    await TokenManager.sync();
    initProviderButtons();
    await loadChats();

    // Auto-crear chat si no hay ninguno
    const res = await fetch('/chats');
    const chats = await res.json();

    if (chats.length === 0) {
        await newChat();
    }
});


imageInput.addEventListener('change', async (e) => {
    const file = e.target.files[0];
    const preview = document.getElementById('image-preview');
    preview.innerHTML = '';
    if (!file) {
        uploadedImageBase64 = null;
        uploadedImageMime = null;
        return;
    }
    uploadedImageMime = file.type;
    const reader = new FileReader();
    reader.onload = () => {
        uploadedImageBase64 = reader.result;
        // Mostrar miniatura
        const img = document.createElement('img');
        img.src = reader.result;
        img.className = 'rounded-lg border border-cream-300 max-h-20 max-w-20 mt-2';
        img.alt = 'Preview';
        preview.appendChild(img);
        // Log para depuración
        console.log('Imagen subida:', uploadedImageBase64);
    };
    reader.readAsDataURL(file);
});
        function enterEditMode() {
            isEditMode = true;
            const banner = document.getElementById('edit-banner');
            banner.classList.remove('hidden');
            banner.classList.add('flex');
            promptInput.classList.remove('border-cream-300', 'focus:border-ink');
            promptInput.classList.add('border-orange-500', 'focus:border-orange-400', 'bg-orange-50');
            promptInput.placeholder = 'Describe how you want to retouch this look…';
            promptInput.focus();
        }

        function exitEditMode() {
            isEditMode = false;
            const banner = document.getElementById('edit-banner');
            banner.classList.add('hidden');
            banner.classList.remove('flex');
            promptInput.classList.add('border-cream-300', 'focus:border-ink');
            promptInput.classList.remove('border-orange-500', 'focus:border-orange-400', 'bg-orange-50');
            promptInput.placeholder = 'Describe your vision — silhouette, fabric, mood, reference…';
        }

        document.getElementById('cancel-edit-btn').addEventListener('click', () => exitEditMode());

        document.addEventListener('click', function(e) {
            if (e.target && e.target.classList.contains('edit-btn')) {
                enterEditMode();
            }
            const prevBtn = e.target.closest ? e.target.closest('.preview-btn') : null;
            if (e.target.classList.contains('preview-btn') || prevBtn) {
                const btn = prevBtn || e.target;
                const idx = parseInt(btn.getAttribute('data-preview-idx'));
                if (!isNaN(idx) && previewImageStore[idx]) openPreviewModal(previewImageStore[idx]);
            }
            if (e.target.id === 'preview-modal') closePreviewModal();
        });

        // ─── Garment Preview System ──────────────────────────────────────
        let previewDesignSrc = null;

        function shadeColor(hex, pct) {
            let r = parseInt(hex.slice(1,3),16), g = parseInt(hex.slice(3,5),16), b = parseInt(hex.slice(5,7),16);
            r = Math.min(255, Math.max(0, Math.round(r*(100+pct)/100)));
            g = Math.min(255, Math.max(0, Math.round(g*(100+pct)/100)));
            b = Math.min(255, Math.max(0, Math.round(b*(100+pct)/100)));
            return '#'+[r,g,b].map(x=>x.toString(16).padStart(2,'0')).join('');
        }

        // ─── Printify real print areas ─────────────────────────────────
        // Canvas = 500 × 550.  Garment proportions mapped from real inches.
        // Sources: Printify product editor → "Download design template" per SKU.
        //
        //  Product (SKU)                Print area (px @300dpi)   Inches      Canvas rect
        //  ─────────────────────────────────────────────────────────────────────────────────
        //  Gildan 5000  T-Shirt         3951 × 4919              13.17 × 16.40
        //  Gildan 18500 Hoodie          3543 × 4724              11.81 × 15.75
        //  Bella+Canvas 3480 Tank Top   3000 × 4200              10.00 × 14.00
        //  Gildan 5400  Long Sleeve     3951 × 4919              13.17 × 16.40
        //  Gildan 18000 Sweatshirt      3543 × 4724              11.81 × 15.75
        // ──────────────────────────────────────────────────────────────────

        const GARMENTS = {
            tshirt: {
                name: 'T-Shirt',
                ref: 'Gildan 5000',
                printPx: '3951 × 4919',
                printInches: '13.17" × 16.40"',
                dpi: 300,
                // Body on canvas: x 150-350 (w200), y 165-495 (h330)
                // Ratios: 13.17/20=0.659 → 132px  |  16.40/28=0.586 → 193px
                printArea: { x: 184, y: 178, w: 132, h: 193 },
                draw(ctx, color) {
                    const dk = shadeColor(color, -20);
                    ctx.fillStyle = color;
                    ctx.beginPath();
                    ctx.moveTo(195, 148);
                    ctx.lineTo(108, 170); ctx.lineTo(62, 218);
                    ctx.lineTo(82, 262); ctx.lineTo(150, 228);
                    ctx.lineTo(150, 495); ctx.lineTo(350, 495);
                    ctx.lineTo(350, 228); ctx.lineTo(418, 262);
                    ctx.lineTo(438, 218); ctx.lineTo(392, 170);
                    ctx.lineTo(305, 148);
                    ctx.quadraticCurveTo(250, 122, 195, 148);
                    ctx.closePath(); ctx.fill();
                    ctx.strokeStyle = dk; ctx.lineWidth = 2; ctx.stroke();
                    ctx.beginPath();
                    ctx.moveTo(200, 148);
                    ctx.quadraticCurveTo(250, 128, 300, 148);
                    ctx.lineWidth = 4; ctx.strokeStyle = dk; ctx.stroke();
                }
            },
            hoodie: {
                name: 'Hoodie',
                ref: 'Gildan 18500',
                printPx: '3543 × 4724',
                printInches: '11.81" × 15.75"',
                dpi: 300,
                // Body on canvas: x 140-360 (w220), y 155-498 (h343)
                // Ratios: 11.81/22=0.537 → 118px  |  15.75/29=0.543 → 186px
                // Print area stops above kangaroo pocket (~y360)
                printArea: { x: 191, y: 175, w: 118, h: 186 },
                draw(ctx, color) {
                    const dk = shadeColor(color, -20);
                    ctx.fillStyle = color;
                    ctx.beginPath();
                    ctx.moveTo(175, 155);
                    ctx.quadraticCurveTo(155, 65, 250, 55);
                    ctx.quadraticCurveTo(345, 65, 325, 155);
                    ctx.quadraticCurveTo(250, 130, 175, 155);
                    ctx.closePath(); ctx.fill();
                    ctx.strokeStyle = dk; ctx.lineWidth = 2; ctx.stroke();
                    ctx.fillStyle = color;
                    ctx.beginPath();
                    ctx.moveTo(195, 155); ctx.lineTo(100, 178);
                    ctx.lineTo(48, 340); ctx.lineTo(88, 350);
                    ctx.lineTo(140, 230); ctx.lineTo(140, 498);
                    ctx.lineTo(360, 498); ctx.lineTo(360, 230);
                    ctx.lineTo(412, 350); ctx.lineTo(452, 340);
                    ctx.lineTo(400, 178); ctx.lineTo(305, 155);
                    ctx.quadraticCurveTo(250, 138, 195, 155);
                    ctx.closePath(); ctx.fill();
                    ctx.strokeStyle = dk; ctx.lineWidth = 2; ctx.stroke();
                    ctx.beginPath();
                    ctx.moveTo(188, 370);
                    ctx.quadraticCurveTo(250, 395, 312, 370);
                    ctx.lineTo(312, 420);
                    ctx.quadraticCurveTo(250, 430, 188, 420);
                    ctx.closePath(); ctx.strokeStyle = dk; ctx.lineWidth = 1.5; ctx.stroke();
                    ctx.setLineDash([5,5]);
                    ctx.beginPath(); ctx.moveTo(250, 155); ctx.lineTo(250, 495);
                    ctx.strokeStyle = dk; ctx.lineWidth = 1; ctx.stroke();
                    ctx.setLineDash([]);
                    ctx.beginPath(); ctx.moveTo(235, 155); ctx.lineTo(228, 220);
                    ctx.strokeStyle = dk; ctx.lineWidth = 1.5; ctx.stroke();
                    ctx.beginPath(); ctx.moveTo(265, 155); ctx.lineTo(272, 220);
                    ctx.strokeStyle = dk; ctx.lineWidth = 1.5; ctx.stroke();
                }
            },
            tanktop: {
                name: 'Tank Top',
                ref: 'Bella+Canvas 3480',
                printPx: '3000 × 4200',
                printInches: '10.00" × 14.00"',
                dpi: 300,
                // Body on canvas: x 148-352 (w204), y 140-498 (h358)
                // Ratios: 10/17=0.588 → 120px  |  14/26=0.538 → 193px
                printArea: { x: 190, y: 162, w: 120, h: 193 },
                draw(ctx, color) {
                    const dk = shadeColor(color, -20);
                    ctx.fillStyle = color;
                    ctx.beginPath();
                    ctx.moveTo(210, 130); ctx.lineTo(180, 130);
                    ctx.lineTo(148, 195);
                    ctx.quadraticCurveTo(135, 250, 148, 270);
                    ctx.lineTo(148, 498); ctx.lineTo(352, 498);
                    ctx.lineTo(352, 270);
                    ctx.quadraticCurveTo(365, 250, 352, 195);
                    ctx.lineTo(320, 130); ctx.lineTo(290, 130);
                    ctx.quadraticCurveTo(250, 155, 210, 130);
                    ctx.closePath(); ctx.fill();
                    ctx.strokeStyle = dk; ctx.lineWidth = 2; ctx.stroke();
                }
            },
            longsleeve: {
                name: 'Long Sleeve',
                ref: 'Gildan 5400',
                printPx: '3951 × 4919',
                printInches: '13.17" × 16.40"',
                dpi: 300,
                // Same body proportions as T-Shirt
                printArea: { x: 184, y: 178, w: 132, h: 193 },
                draw(ctx, color) {
                    const dk = shadeColor(color, -20);
                    ctx.fillStyle = color;
                    ctx.beginPath();
                    ctx.moveTo(195, 148); ctx.lineTo(108, 170);
                    ctx.lineTo(42, 380); ctx.lineTo(78, 390);
                    ctx.lineTo(148, 230); ctx.lineTo(148, 495);
                    ctx.lineTo(352, 495); ctx.lineTo(352, 230);
                    ctx.lineTo(422, 390); ctx.lineTo(458, 380);
                    ctx.lineTo(392, 170); ctx.lineTo(305, 148);
                    ctx.quadraticCurveTo(250, 122, 195, 148);
                    ctx.closePath(); ctx.fill();
                    ctx.strokeStyle = dk; ctx.lineWidth = 2; ctx.stroke();
                    ctx.beginPath(); ctx.moveTo(200, 148);
                    ctx.quadraticCurveTo(250, 128, 300, 148);
                    ctx.lineWidth = 4; ctx.strokeStyle = dk; ctx.stroke();
                    ctx.lineWidth = 3;
                    ctx.beginPath(); ctx.moveTo(42, 378); ctx.lineTo(78, 388); ctx.stroke();
                    ctx.beginPath(); ctx.moveTo(422, 388); ctx.lineTo(458, 378); ctx.stroke();
                }
            },
            sweatshirt: {
                name: 'Sweatshirt',
                ref: 'Gildan 18000',
                printPx: '3543 × 4724',
                printInches: '11.81" × 15.75"',
                dpi: 300,
                // Body on canvas: x 145-355 (w210), y 155-498 (h343)
                // Ratios: 11.81/22=0.537 → 113px  |  15.75/29=0.543 → 186px
                printArea: { x: 194, y: 178, w: 113, h: 186 },
                draw(ctx, color) {
                    const dk = shadeColor(color, -20);
                    ctx.fillStyle = color;
                    ctx.beginPath();
                    ctx.moveTo(190, 155); ctx.lineTo(105, 175);
                    ctx.lineTo(48, 345); ctx.lineTo(85, 355);
                    ctx.lineTo(145, 232); ctx.lineTo(145, 498);
                    ctx.lineTo(355, 498); ctx.lineTo(355, 232);
                    ctx.lineTo(415, 355); ctx.lineTo(452, 345);
                    ctx.lineTo(395, 175); ctx.lineTo(310, 155);
                    ctx.quadraticCurveTo(250, 128, 190, 155);
                    ctx.closePath(); ctx.fill();
                    ctx.strokeStyle = dk; ctx.lineWidth = 2; ctx.stroke();
                    ctx.beginPath(); ctx.moveTo(195, 155);
                    ctx.quadraticCurveTo(250, 135, 305, 155);
                    ctx.lineWidth = 6; ctx.strokeStyle = dk; ctx.stroke();
                    ctx.beginPath(); ctx.moveTo(145, 490); ctx.lineTo(355, 490);
                    ctx.lineWidth = 6; ctx.strokeStyle = dk; ctx.stroke();
                    ctx.lineWidth = 4;
                    ctx.beginPath(); ctx.moveTo(48, 343); ctx.lineTo(85, 353); ctx.stroke();
                    ctx.beginPath(); ctx.moveTo(415, 353); ctx.lineTo(452, 343); ctx.stroke();
                }
            }
        };

        function openPreviewModal(imageSrc) {
            previewDesignSrc = imageSrc;
            document.getElementById('preview-modal').classList.remove('hidden');
            document.getElementById('preview-modal').classList.add('flex');
            renderPreview();
        }

        function closePreviewModal() {
            document.getElementById('preview-modal').classList.add('hidden');
            document.getElementById('preview-modal').classList.remove('flex');
            previewDesignSrc = null;
        }

        function renderPreview() {
            if (!previewDesignSrc) return;
            const canvas = document.getElementById('preview-canvas');
            const ctx = canvas.getContext('2d');
            const garmentType = document.getElementById('garment-select').value;
            const garmentColor = document.getElementById('garment-color').value;
            const garment = GARMENTS[garmentType];

            ctx.clearRect(0, 0, canvas.width, canvas.height);

            const sz = 15;
            for (let y = 0; y < canvas.height; y += sz) {
                for (let x = 0; x < canvas.width; x += sz) {
                    ctx.fillStyle = ((x/sz + y/sz) % 2 === 0) ? '#1e1e2e' : '#252538';
                    ctx.fillRect(x, y, sz, sz);
                }
            }

            garment.draw(ctx, garmentColor);

            // Draw print area boundary (Printify safe zone)
            const pa = garment.printArea;
            ctx.setLineDash([6, 4]);
            ctx.strokeStyle = 'rgba(168, 85, 247, 0.45)';
            ctx.lineWidth = 1;
            ctx.strokeRect(pa.x, pa.y, pa.w, pa.h);
            ctx.setLineDash([]);
            ctx.font = '9px sans-serif';
            ctx.fillStyle = 'rgba(168, 85, 247, 0.55)';
            ctx.fillText('Print area', pa.x + 2, pa.y - 3);

            // Update spec info below canvas
            const specEl = document.getElementById('printify-spec');
            if (specEl) {
                specEl.innerHTML = `<span class="text-purple-400 font-medium">${garment.ref}</span> &mdash; ${garment.printPx} px &middot; ${garment.printInches} &middot; ${garment.dpi} DPI`;
            }

            const img = new Image();
            img.crossOrigin = 'anonymous';
            img.onload = () => {
                const ir = img.width / img.height;
                const pr = pa.w / pa.h;
                let dw, dh;
                if (ir > pr) { dw = pa.w; dh = pa.w / ir; }
                else { dh = pa.h; dw = pa.h * ir; }
                const dx = pa.x + (pa.w - dw) / 2;
                const dy = pa.y + (pa.h - dh) / 2;
                ctx.drawImage(img, dx, dy, dw, dh);
            };
            img.src = previewDesignSrc;
        }

        function downloadPreview() {
            const canvas = document.getElementById('preview-canvas');
            const link = document.createElement('a');
            link.download = 'garment-preview.png';
            link.href = canvas.toDataURL('image/png');
            link.click();
        }

        // ─── Printful integration ────────────────────────────────────────
        let printfulStoresLoaded = false;

        async function loadPrintfulStores() {
            if (printfulStoresLoaded) return;
            const sel     = document.getElementById('printful-store');
            const connect = document.getElementById('printful-connect-notice');
            sel.innerHTML = '<option value="">Loading…</option>';
            try {
                // Check connection status first
                const statusRes = await fetch('/printful/status', { headers: { 'Accept': 'application/json' } });
                const status    = await statusRes.json();
                if (!status.connected) {
                    sel.closest('.flex.flex-col').classList.add('hidden');
                    if (connect) connect.classList.remove('hidden');
                    return;
                }
                const res    = await fetch('/printful/stores', { headers: { 'Accept': 'application/json' } });
                const stores = await res.json();
                if (!res.ok) throw new Error(stores.error || 'Could not load stores');
                if (!Array.isArray(stores) || stores.length === 0) {
                    sel.innerHTML = '<option value="">No stores found</option>';
                    return;
                }
                sel.innerHTML = stores.map(s =>
                    `<option value="${s.id}">${escapeHtml(s.name)}</option>`
                ).join('');
                printfulStoresLoaded = true;
            } catch (err) {
                sel.innerHTML = `<option value="">Error: ${escapeHtml(err.message)}</option>`;
            }
        }

        function togglePrintfulPanel() {
            const panel = document.getElementById('printful-panel');
            const isHidden = panel.classList.contains('hidden');
            panel.classList.toggle('hidden', !isHidden);
            if (isHidden) {
                const garmentSel = document.getElementById('garment-select');
                const garmentLabel = garmentSel.options[garmentSel.selectedIndex]?.text ?? 'Custom Design';
                document.getElementById('printful-title').value = `FabricAI — ${garmentLabel}`;
                resetPrintfulFeedback();
                loadPrintfulStores();
            }
        }

        function resetPrintfulFeedback() {
            const fb = document.getElementById('printful-feedback');
            fb.className = 'hidden text-sm py-2';
            fb.innerHTML = '';
        }

        function showPrintfulFeedback(html, type = 'error') {
            const fb = document.getElementById('printful-feedback');
            fb.className = `text-sm py-2 ${type === 'success' ? 'text-green-700' : 'text-red-600'}`;
            fb.innerHTML = html;
            fb.classList.remove('hidden');
        }

        async function sendToPrintful() {
            const storeId     = document.getElementById('printful-store').value;
            const title       = document.getElementById('printful-title').value.trim();
            const garmentType = document.getElementById('garment-select').value;
            const btn         = document.getElementById('printful-send-btn');

            if (!storeId) { showPrintfulFeedback('Please select a Printful store.'); return; }
            if (!title)   { showPrintfulFeedback('Please enter a product name.'); return; }
            if (!previewDesignSrc) { showPrintfulFeedback('No design loaded in preview.'); return; }

            btn.disabled = true;
            btn.textContent = 'Creating product…';
            resetPrintfulFeedback();

            try {
                const csrf = document.querySelector('meta[name="csrf-token"]').content;
                const res  = await fetch('/printful/products', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrf,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({
                        store_id:     parseInt(storeId),
                        garment_type: garmentType,
                        image_source: previewDesignSrc,
                        title,
                    }),
                });
                const data = await res.json();
                if (!res.ok || !data.success) {
                    throw new Error(data.error || `HTTP ${res.status}`);
                }
                showPrintfulFeedback(
                    `✓ Product created! <a href="${data.printful_url}" target="_blank" rel="noopener noreferrer"
                        class="underline font-medium">Open in Printful →</a>`,
                    'success'
                );
                btn.textContent = 'Create Another';
            } catch (err) {
                showPrintfulFeedback(`Failed: ${escapeHtml(err.message)}`);
                btn.textContent = 'Retry';
            } finally {
                btn.disabled = false;
            }
        }

        // "Printful" quick-button on each bot response card
        document.addEventListener('click', function(e) {
            const quickBtn = e.target.closest ? e.target.closest('.printful-quick-btn') : null;
            if (!quickBtn) return;
            const src = quickBtn.getAttribute('data-image-src');
            if (src) openPreviewModal(src);
            // Open the Printful panel automatically
            const panel = document.getElementById('printful-panel');
            if (panel.classList.contains('hidden')) togglePrintfulPanel();
        });
        // ─────────────────────────────────────────────────────────────────
    </script>

    <!-- ═══════════ GARMENT PREVIEW MODAL ═══════════ -->
    <div id="preview-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50 backdrop-blur-sm">
        <div class="bg-white border border-cream-300 shadow-2xl w-full max-w-2xl mx-4 overflow-hidden">
            <div class="flex items-center justify-between px-6 py-4 border-b border-cream-300">
                <h2 class="text-lg font-serif text-ink">Preview on Garment</h2>
                <button onclick="closePreviewModal()" class="text-ink-muted hover:text-ink transition-colors">
                    <i class="fas fa-times text-lg"></i>
                </button>
            </div>
            <div class="p-6">
                <div class="flex gap-4 mb-4 flex-wrap items-end">
                    <div class="flex flex-col gap-1">
                        <label class="text-xs text-ink-muted uppercase tracking-wider">Garment</label>
                        <select id="garment-select" onchange="renderPreview()" class="bg-cream-100 border border-cream-300 px-3 py-2 text-sm text-ink focus:outline-none focus:border-ink">
                            <option value="tshirt">Gildan 5000 — T-Shirt</option>
                            <option value="hoodie">Gildan 18500 — Hoodie</option>
                            <option value="tanktop">Bella+Canvas 3480 — Tank Top</option>
                            <option value="longsleeve">Gildan 5400 — Long Sleeve</option>
                            <option value="sweatshirt">Gildan 18000 — Sweatshirt</option>
                        </select>
                    </div>
                    <div class="flex flex-col gap-1">
                        <label class="text-xs text-ink-muted uppercase tracking-wider">Color</label>
                        <input type="color" id="garment-color" value="#ffffff" onchange="renderPreview()" class="w-10 h-10 border border-cream-300 cursor-pointer bg-transparent">
                    </div>
                </div>
                <div class="flex justify-center bg-cream-100 p-4">
                    <canvas id="preview-canvas" width="500" height="550" class="max-w-full h-auto" style="max-height: 450px;"></canvas>
                </div>
                <div id="printify-spec" class="mt-2 text-xs text-ink-muted text-center"></div>
                <div class="flex justify-end gap-3 mt-3">
                    <button onclick="downloadPreview()" class="px-4 py-2 bg-ink text-white text-sm font-medium tracking-wide uppercase hover:bg-ink-light transition-colors">
                        Download Preview
                    </button>
                    <button onclick="togglePrintfulPanel()" class="px-4 py-2 border border-ink text-ink text-sm font-medium tracking-wide uppercase hover:bg-ink hover:text-white transition-colors">
                        Send to Printful
                    </button>
                </div>

                <!-- ─── Printful send panel ─── -->
                <div id="printful-panel" class="hidden mt-4 border border-cream-300 bg-cream-50 p-4 space-y-3">
                    <p class="text-xs font-medium tracking-widest uppercase text-ink-muted">Create product on Printful</p>

                    <!-- Not connected notice -->
                    <div id="printful-connect-notice" class="hidden px-4 py-3 bg-yellow-50 border border-yellow-200 text-yellow-800 text-sm">
                        Your Printful account is not connected yet.
                        <a href="/profile" target="_blank" class="underline font-medium">Go to Profile → Connect Printful</a>
                    </div>

                    <!-- Product title -->
                    <div class="flex flex-col gap-1">
                        <label class="text-xs text-ink-muted">Product name</label>
                        <input id="printful-title" type="text" value=""
                               class="bg-white border border-cream-300 px-3 py-2 text-sm text-ink focus:outline-none focus:border-purple-400 transition-colors">
                    </div>

                    <!-- Store selector -->
                    <div class="flex flex-col gap-1">
                        <label class="text-xs text-ink-muted">Printful store</label>
                        <select id="printful-store" class="bg-white border border-cream-300 px-3 py-2 text-sm text-ink focus:outline-none focus:border-purple-400">
                            <option value="">Loading stores…</option>
                        </select>
                    </div>

                    <!-- Feedback -->
                    <div id="printful-feedback" class="hidden text-sm py-2"></div>

                    <!-- Action -->
                    <button id="printful-send-btn" onclick="sendToPrintful()"
                            class="w-full py-2.5 bg-ink text-white text-xs font-medium tracking-widest uppercase
                                   hover:bg-purple-900 transition-colors disabled:opacity-50">
                        Create Product
                    </button>
                </div>
            </div>
        </div>
    </div>

</body>
</html>
