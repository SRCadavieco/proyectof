<!DOCTYPE html>
<html lang="en" style="background:#0d0d0d;overflow:hidden">
<head>
    <link rel="icon" href="{{ asset('images/logo.png') }}?v={{ filemtime(public_path('images/logo.png')) }}" type="image/png">
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
        ::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.12); border-radius: 99px; }
        * { scrollbar-width: thin; scrollbar-color: rgba(255,255,255,0.12) transparent; }

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
        .icon-btn:hover           { background: rgba(255,255,255,0.08); color: #fff; }
        .icon-btn.danger:hover    { background: rgba(239,68,68,0.12); color: #f87171; }
        .icon-btn.accent:hover    { background: rgba(124,60,160,0.18); color: #c084fc; }

        /* ── Chat thumbnail ── */
        .chat-thumb {
            width: 36px; height: 36px;
            border-radius: 8px;
            background: rgba(255,255,255,0.07);
            overflow: hidden;
            flex-shrink: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            color: rgba(255,255,255,0.2);
            font-size: 13px;
        }
        .chat-thumb img { width: 100%; height: 100%; object-fit: cover; }

        /* ── Generating placeholder animations ── */
        @keyframes gen-pulse {
            0%,100% { opacity: 0.45; }
            50%      { opacity: 1; }
        }
        @keyframes gen-bar {
            0%   { width: 6%; }
            80%  { width: 92%; }
            100% { width: 92%; }
        }
        @keyframes gen-pen {
            0%   { left: 6%; }
            80%  { left: 90%; }
            100% { left: 90%; }
        }
        .gen-placeholder { animation: gen-pulse 2s ease-in-out infinite; }
        .gen-bar-fill    { animation: gen-bar 20s cubic-bezier(0.1,0.4,0.3,1) forwards; }
        .gen-pen-icon    { animation: gen-pen 20s cubic-bezier(0.1,0.4,0.3,1) forwards; }
        @keyframes shimmer {
            0%   { transform: translateX(-100%); }
            100% { transform: translateX(250%); }
        }
        .gen-shimmer { animation: shimmer 1.6s linear infinite; }

        /* ── Chat area background ── */
        main {
            background-image:
                linear-gradient(rgba(13,13,13,0.82), rgba(13,13,13,0.82)),
                url('/images/fitting-room.webp');
            background-size: cover;
            background-position: center;
        }


        /* ── Saved designs panel list: horizontal on mobile, vertical on desktop ── */
        #saved-designs-list {
            max-height: 110px;
        }
        @media (min-width: 640px) {
            #saved-designs-list {
                max-height: none;
                flex: 1;
            }
        }

        @media (min-width: 768px) { #sidebar-toggle-btn { display: none; } }
    </style>
</head>
<body class="bg-[#0d0d0d] text-white h-[100dvh] overflow-hidden font-sans antialiased">

<div class="flex h-[100dvh]">

    <!-- Mobile sidebar backdrop -->
    <div id="sidebar-backdrop"
         onclick="closeSidebar()"
         class="fixed inset-0 bg-black/40 z-30 backdrop-blur-sm"></div>

    <!-- ═══════════════════════ SIDEBAR ═══════════════════════ -->
    <aside id="sidebar" class="w-64 flex flex-col h-[100dvh]" style="background:#111;border-right:1px solid rgba(255,255,255,0.07)">

        <!-- Logo + New Design button -->
        <div class="px-4 py-4 flex items-center justify-between" style="border-bottom:1px solid rgba(255,255,255,0.07)">
            <a href="/" class="flex items-center gap-2.5 min-w-0">
                <img src="{{ asset('images/logo.png') }}?v={{ filemtime(public_path('images/logo.png')) }}" alt="FabricAI" class="h-8 w-8 object-contain shrink-0">
                <div class="min-w-0">
                    <span class="font-serif text-sm text-white block leading-tight">fabricAI</span>
                    <span class="text-[9px] tracking-[0.22em] uppercase" style="color:#9d5bc7">atelier</span>
                </div>
            </a>
        </div>

        <!-- Design sessions list -->
        <div class="flex-1 overflow-y-auto py-3 scrollbar-hide">
            <div class="flex items-center justify-between px-4 mb-2">
                <p class="text-[9px] uppercase tracking-[0.2em] text-white/30">My Designs</p>
                <div class="flex items-center gap-1">
                    <button onclick="showDeleteAllModal()" title="Delete all sessions"
                            class="w-5 h-5 flex items-center justify-center text-white/30
                                   hover:text-red-400 transition-colors rounded-full shrink-0">
                        <i class="fas fa-trash-alt" style="font-size:8px"></i>
                    </button>
                    <button onclick="newChat()" title="New design session"
                            class="w-5 h-5 flex items-center justify-center bg-ink text-white
                                   hover:bg-[#7c3ca0] transition-colors rounded-full shrink-0">
                        <i class="fas fa-plus" style="font-size:8px"></i>
                    </button>
                </div>
            </div>
            <div id="chat-list" class="space-y-0.5 px-2"></div>
        </div>

        <!-- Footer -->
        <div class="px-4 pt-4 pb-6 space-y-3" style="border-top:1px solid rgba(255,255,255,0.07)">

            <!-- Spools -->
            <div class="flex items-center justify-between px-3 py-2.5 rounded-xl" style="background:rgba(255,255,255,0.05);border:1px solid rgba(255,255,255,0.08)">
                <div class="flex items-center gap-1.5">
                    <img src="/images/spool.webp" class="w-6 h-6 object-contain opacity-70" alt="Spools">
                    <span class="text-xs text-white/40 tracking-wide">Spools</span>
                </div>
                <div class="flex items-center gap-2">
                    <div class="flex items-center gap-1.5">
                        <span id="token-count" class="text-sm font-semibold text-white">{{ Auth::user()->tokens ?? 0 }}</span>
                    </div>
                    <button onclick="openCreditPacksModal()" title="Buy more Spools"
                            class="text-[10px] px-2 py-0.5 rounded-full font-semibold leading-none transition-colors"
                            style="background:rgba(124,60,160,0.2);border:1px solid rgba(124,60,160,0.35);color:#c084fc"
                            onmouseover="this.style.background='rgba(124,60,160,0.35)'" onmouseout="this.style.background='rgba(124,60,160,0.2)'">+</button>
                </div>
            </div>

            <!-- User menu -->
            <div class="relative" id="user-menu-wrapper">
                <button onclick="toggleUserMenu()"
                        class="flex items-center gap-2 w-full px-3 py-2 rounded-xl transition-colors text-left" style="--tw-hover:rgba(255,255,255,0.06)" onmouseover="this.style.background='rgba(255,255,255,0.06)'" onmouseout="this.style.background='transparent'">
                    <div class="w-7 h-7 rounded-full flex items-center justify-center text-white text-xs font-semibold shrink-0"
                         style="background:#7c3ca0">
                        {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
                    </div>
                    <div class="flex flex-col min-w-0 flex-1">
                        <span class="text-xs text-white/80 truncate leading-tight">{{ Auth::user()->name }}</span>
                        <span class="text-[9px] font-bold uppercase tracking-widest mt-0.5" style="color:#c084fc">{{ ucfirst(Auth::user()->plan ?? 'free') }}</span>
                    </div>
                    <i class="fas fa-chevron-up text-white/30 shrink-0 transition-transform duration-200" id="user-menu-chevron" style="font-size:9px"></i>
                </button>

                <!-- Dropdown (opens upward) -->
                <div id="user-menu-dropdown"
                     class="hidden absolute bottom-full left-0 right-0 mb-1 rounded-xl shadow-lg overflow-hidden z-50" style="background:#1a1a1a;border:1px solid rgba(255,255,255,0.1)">
                    <a href="/profile"
                       class="flex items-center gap-2.5 px-3 py-2.5 text-sm text-white/70 hover:text-white transition-colors" style="--hover:1" onmouseover="this.style.background='rgba(255,255,255,0.06)'" onmouseout="this.style.background='transparent'">
                        <i class="fas fa-user text-white/30 w-4 text-center" style="font-size:11px"></i>
                        Profile
                    </a>
                    @if(Auth::user()->is_admin)
                    <a href="/admin"
                       class="flex items-center gap-2.5 px-3 py-2.5 text-sm text-white/70 hover:text-white transition-colors" onmouseover="this.style.background='rgba(255,255,255,0.06)'" onmouseout="this.style.background='transparent'">
                        <i class="fas fa-shield-alt text-white/30 w-4 text-center" style="font-size:11px"></i>
                        Admin Panel
                    </a>
                    @endif
                    <button onclick="openMyDesignsModal(); closeUserMenu()"
                            class="flex items-center gap-2.5 px-3 py-2.5 text-sm text-white/70 hover:text-white transition-colors w-full text-left" onmouseover="this.style.background='rgba(255,255,255,0.06)'" onmouseout="this.style.background='transparent'">
                        <i class="fas fa-bookmark text-white/30 w-4 text-center" style="font-size:11px"></i>
                        My Saved Designs
                    </button>
                    <div class="mx-2" style="border-top:1px solid rgba(255,255,255,0.07)"></div>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit"
                                class="flex items-center gap-2.5 px-3 py-2.5 text-sm text-red-400 hover:text-red-300 transition-colors w-full text-left" onmouseover="this.style.background='rgba(239,68,68,0.08)'" onmouseout="this.style.background='transparent'">
                            <i class="fas fa-sign-out-alt w-4 text-center" style="font-size:11px"></i>
                            Log out
                        </button>
                    </form>
                </div>
            </div>

        </div>
    </aside>

    <!-- ═══════════════════════ MAIN ═══════════════════════ -->
    <main class="flex-1 flex flex-col overflow-hidden min-w-0">

        <!-- Header -->
        <header class="backdrop-blur-sm px-4 py-3 flex items-center gap-3 z-10 relative shrink-0" style="background:rgba(17,17,17,0.9);border-bottom:1px solid rgba(255,255,255,0.07)">
            <button id="sidebar-toggle-btn" onclick="toggleSidebar()"
                    class="md:hidden icon-btn shrink-0" aria-label="Open menu">
                <i class="fas fa-bars text-base"></i>
            </button>
            <h1 id="chat-title"
                class="font-medium text-sm text-white/80 truncate flex-1 text-left">
                New Design
            </h1>
        </header>

        <!-- Chat area -->
        <div id="chat-container" class="flex-1 overflow-y-auto">
            <div class="max-w-2xl mx-auto px-4 pb-8 pt-2">

                <!-- Welcome screen (hidden once messages exist) -->
                <div id="welcome-screen" class="flex flex-col items-center py-10 text-center">
                    <p class="text-[10px] uppercase tracking-[0.25em] text-white/30 mb-3">Your personal atelier</p>
                    <h2 class="font-serif text-2xl md:text-3xl text-white mb-8 leading-snug">
                        What shall we create today?&nbsp;<span style="color:#7c3ca0">✦</span>
                    </h2>
                    <p class="text-sm text-white/40 max-w-xs leading-relaxed">
                        Describe your design — a style, a mood, a concept — and I'll bring it to life.
                    </p>
                    <p class="text-xs text-white/20 mt-2 italic">
                        Try: "Retro sun with mountains, bold outlines, earthy tones"
                    </p>
                    @php $printifyConnected = Auth::check() && Auth::user()->printifyConnection; @endphp
                    @if(!$printifyConnected)
                    <div class="mt-6 flex items-center gap-2 px-4 py-2.5 rounded-xl text-xs text-yellow-300/80" style="background:rgba(234,179,8,0.08);border:1px solid rgba(234,179,8,0.2)">
                        <i class="fas fa-plug text-yellow-400/60"></i>
                        <span>Printify not connected. You won't be able to upload your designs.</span>
                        <a href="/profile" class="underline ml-1 text-yellow-300/60 hover:text-yellow-300 transition-colors">Connect →</a>
                    </div>
                    @endif

                </div>

                <!-- Dynamic messages -->
                <div id="messages" class="space-y-6 pb-4"></div>

            </div>
        </div>

        <!-- Input area -->
        <div class="backdrop-blur-sm px-4 pt-3 pb-5 relative z-10 shrink-0" style="background:rgba(17,17,17,0.50);border-top:1px solid rgba(255,255,255,0.07)">
            <div id="error" class="hidden text-red-500 text-xs mb-2 px-1"></div>
            <!-- No credits banner -->
            <div id="no-credits-banner" class="hidden mb-3">
                <div class="flex items-center justify-between gap-3 px-4 py-3 rounded-xl"
                     style="background:rgba(124,60,160,0.08);border:1px solid rgba(124,60,160,0.25);">
                    <div class="flex items-center gap-2">
                        <img src="/images/spool.webp" class="w-4 h-4 object-contain" style="opacity:0.8" alt="Spools">
                        <span class="text-sm font-medium" style="color:#5a2275;">You've used all your Spools</span>
                    </div>
                    <a href="#" onclick="openCreditPacksModal(); return false;"
                       class="shrink-0 px-4 py-1.5 rounded-full text-xs font-semibold text-white transition-colors"
                       style="background:#7c3ca0;"
                       onmouseover="this.style.background='#5a2275'"
                       onmouseout="this.style.background='#7c3ca0'">
                        Get more Spools
                    </a>
                </div>
            </div>
            <div id="loader" class="hidden items-center gap-2 text-xs mb-2 px-1"
                 style="color:#7c3ca0">
                <div class="flex gap-1 items-end">
                    <span class="w-1.5 h-1.5 rounded-full dot-1" style="background:#7c3ca0"></span>
                    <span class="w-1.5 h-1.5 rounded-full dot-2" style="background:#7c3ca0"></span>
                    <span class="w-1.5 h-1.5 rounded-full dot-3" style="background:#7c3ca0"></span>
                </div>

            </div>
            <form id="design-form" class="max-w-2xl mx-auto">
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
                <div class="flex gap-2 items-center">
                    <!-- Attach image -->
                    <label class="cursor-pointer icon-btn shrink-0" title="Attach image" style="color:#c084fc">
                        <i class="fas fa-paperclip text-base"></i>
                        <input type="file" id="image-upload" accept="image/*" class="hidden">
                    </label>
                    <!-- Image preview -->
                    <div id="image-preview" class="shrink-0"></div>
                    <!-- Prompt textarea -->
                    <textarea
                        id="prompt"
                        rows="1"
                        maxlength="270"
                        placeholder="Describe the graphic decoration: motif, style, mood, colors…"
                        class="flex-1 rounded-xl px-4 py-2.5 text-sm resize-none text-white focus:outline-none transition-colors max-h-32 scrollbar-hide leading-relaxed placeholder-white/25"
                        style="background:rgba(255,255,255,0.07);border:1px solid rgba(255,255,255,0.1)" onfocus="this.style.borderColor='rgba(124,60,160,0.6)'" onblur="this.style.borderColor='rgba(255,255,255,0.1)'"></textarea>
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
                <div class="mt-2 flex items-center justify-between gap-2">
                    @if(
                        in_array(strtolower(Auth::user()->plan ?? 'free'), ['pro', 'business', 'studio'])
                        || (Auth::user()->is_admin && strtolower(Auth::user()->plan ?? 'free') === 'admin')
                    )
                    <div class="flex items-center gap-2">
                        <span class="text-[10px] uppercase tracking-wider" style="color:rgba(255,255,255,0.55)">Model</span>
                        <select id="model-selector" class="text-[11px] rounded px-2 py-1 outline-none cursor-pointer font-medium" style="background:#3b1a54;color:#e9d5ff;border:1px solid rgba(192,132,252,0.6)">
                            <option value="flash">Fabric Flash · 1 spool</option>
                            <option value="max">Fabric Max · 2 spools</option>
                        </select>
                    </div>
                    @endif
                    <div class="ml-auto flex items-center gap-2">
                        @if(Auth::user()->is_admin && strtolower(Auth::user()->plan ?? 'free') === 'admin')
                        <span class="text-[10px] uppercase tracking-wider" style="color:rgba(255,255,255,0.55)">Design</span>
                        <select id="style-selector" class="text-[11px] rounded px-2 py-1 outline-none cursor-pointer font-medium" style="background:#1f2b4d;color:#dbeafe;border:1px solid rgba(147,197,253,0.45)">
                            <option value="default">No style (Default)</option>
                            <option value="realistic_drawing">Realistic Drawing</option>
                            <option value="cartoon_drawing">Cartoon Drawing</option>
                            <option value="vector_art">Vector Art</option>
                            <option value="photorealistic">Photorealistic</option>
                            <option value="ghibli">Ghibli-inspired</option>
                            <option value="manga">Manga</option>
                        </select>
                        @endif
                        <!-- Char counter -->
                        <div id="char-counter" class="text-[10px] pr-1" style="color:rgba(255,255,255,0.2)">0 / 270</div>
                    </div>
                </div>
            </form>
        </div>

    </main>
</div>

<!-- Hidden helpers -->
<img id="temp-image" class="hidden" crossorigin="anonymous">
<div id="debug-info" class="hidden"></div>

<!-- ═══════════ DELETE CONFIRMATION MODAL ═══════════ -->
<!-- ═══════════ MY SAVED DESIGNS MODAL ═══════════ -->
<div id="my-designs-modal"
     class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50 backdrop-blur-sm p-4">
    <div class="shadow-2xl w-full max-w-3xl rounded-2xl overflow-hidden flex flex-col" style="max-height:90dvh;background:#111;border:1px solid rgba(255,255,255,0.09)">
        <div class="flex items-center justify-between px-6 py-4 flex-shrink-0" style="border-bottom:1px solid rgba(255,255,255,0.07)">
            <div>
                <h2 class="text-base font-semibold text-white">My Saved Designs</h2>
                <p class="text-xs text-white/40 mt-0.5">Click a design to use it in a new session</p>
            </div>
            <button onclick="closeMyDesignsModal()" class="icon-btn">
                <i class="fas fa-times text-lg"></i>
            </button>
        </div>
        <div class="flex-1 overflow-y-auto p-6">
            <div id="my-designs-grid" class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-4">
                <!-- populated by JS -->
            </div>
            <div id="my-designs-empty" class="hidden flex-col items-center justify-center py-16 text-center">
                <div class="w-14 h-14 rounded-full flex items-center justify-center mb-4" style="background:rgba(255,255,255,0.07)">
                    <i class="fas fa-bookmark text-white/20 text-xl"></i>
                </div>
                <p class="text-sm text-white/40">No saved designs yet.</p>
                <p class="text-xs text-white/20 mt-1">Bookmark a generated image to save it here.</p>
            </div>
            <div id="my-designs-loading" class="flex items-center justify-center py-16">
                <i class="fas fa-spinner fa-spin text-white/30 text-xl"></i>
            </div>
        </div>
    </div>
</div>

<!-- ═══════════ IMAGE LIGHTBOX ═══════════ -->
<div id="lightbox-modal"
     class="fixed inset-0 z-[60] hidden items-center justify-center bg-black/50 backdrop-blur-2xl p-6"
     onclick="closeLightbox()">

    <!-- Close -->
    <button onclick="closeLightbox()" aria-label="Close"
            class="absolute top-5 right-5 w-9 h-9 flex items-center justify-center rounded-full
                   bg-white/15 hover:bg-white/30 text-white transition-colors z-10">
        <i class="fas fa-times"></i>
    </button>

    <!-- Card -->
    <div class="flex flex-col items-center gap-5 max-w-xl w-full" onclick="event.stopPropagation()">
        <img id="lightbox-img" src="" alt=""
             class="max-w-full bg-white rounded-2xl shadow-2xl object-contain"
             style="max-height:72dvh;">

        <!-- Action buttons -->
        <div class="flex flex-wrap justify-center gap-2">
            <a id="lightbox-download-btn" href="#" download="design.png" target="_blank"
               class="px-4 py-2 bg-white/15 hover:bg-white/25 text-white text-xs font-medium rounded-xl
                      transition-colors flex items-center gap-1.5 border border-white/20 backdrop-blur-sm">
                <i class="fas fa-download text-[10px]"></i> Download
            </a>
            <button onclick="_closeLightboxThen(() => openPreviewModal(_lightboxSrc))"
                    class="px-4 py-2 bg-white/15 hover:bg-white/25 text-white text-xs font-medium rounded-xl
                           transition-colors flex items-center gap-1.5 border border-white/20 backdrop-blur-sm">
                <i class="fas fa-tshirt text-[10px]"></i> Preview
            </button>
            <button onclick="_closeLightboxThen(() => openBulkUploadModal(_lightboxSrc))"
                    class="px-4 py-2 bg-[#7c3ca0]/80 hover:bg-[#7c3ca0] text-white text-xs font-medium rounded-xl
                           transition-colors flex items-center gap-1.5 border border-purple-400/30 backdrop-blur-sm">
                <i class="fas fa-cloud-upload-alt text-[10px]"></i> Upload to Printify
            </button>
        </div>
    </div>
</div>

<div id="delete-modal"
     class="fixed inset-0 z-50 hidden items-center justify-center bg-black/40 backdrop-blur-sm p-4">
    <div class="rounded-2xl shadow-2xl w-full max-w-sm p-6 text-center" style="background:#1a1a1a;border:1px solid rgba(255,255,255,0.1)">
        <div class="w-12 h-12 rounded-full flex items-center justify-center mx-auto mb-4" style="background:rgba(239,68,68,0.1)">
            <i class="fas fa-trash text-red-400 text-lg"></i>
        </div>
        <h3 class="font-semibold text-white text-base mb-1">Delete this session?</h3>
        <p class="text-sm text-white/50 mb-6 leading-relaxed">
            All messages and generated designs in this session will be permanently removed.
        </p>
        <div class="flex gap-3">
            <button id="delete-cancel-btn"
                    class="flex-1 py-2.5 text-sm font-medium text-white/60 hover:text-white transition-colors rounded-xl" style="border:1px solid rgba(255,255,255,0.12)" onmouseover="this.style.background='rgba(255,255,255,0.06)'" onmouseout="this.style.background='transparent'">
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

<!-- ═══════════ DELETE ALL MODAL ═══════════ -->
<div id="delete-all-modal"
     class="fixed inset-0 z-50 hidden items-center justify-center bg-black/40 backdrop-blur-sm p-4">
    <div class="rounded-2xl shadow-2xl w-full max-w-sm p-6 text-center" style="background:#1a1a1a;border:1px solid rgba(255,255,255,0.1)">
        <div class="w-12 h-12 rounded-full flex items-center justify-center mx-auto mb-4" style="background:rgba(239,68,68,0.1)">
            <i class="fas fa-trash text-red-400 text-lg"></i>
        </div>
        <h3 class="font-semibold text-white text-base mb-1">Delete all sessions?</h3>
        <p class="text-sm text-white/50 mb-6 leading-relaxed">
            All design sessions and their messages will be permanently removed. This cannot be undone.
        </p>
        <div class="flex gap-3">
            <button id="delete-all-cancel-btn"
                    class="flex-1 py-2.5 text-sm font-medium text-white/60 hover:text-white transition-colors rounded-xl" style="border:1px solid rgba(255,255,255,0.12)" onmouseover="this.style.background='rgba(255,255,255,0.06)'" onmouseout="this.style.background='transparent'">
                Cancel
            </button>
            <button id="delete-all-confirm-btn"
                    class="flex-1 py-2.5 bg-red-500 text-white text-sm font-medium
                           hover:bg-red-600 transition-colors rounded-xl">
                Delete All
            </button>
        </div>
    </div>
</div>

<!-- ═══════════ BULK UPLOAD MODAL ═══════════ -->
<div id="bulk-upload-modal"
     class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50 backdrop-blur-sm p-4">
    <div class="shadow-2xl w-full max-w-md rounded-2xl overflow-hidden flex flex-col" style="background:#111;border:1px solid rgba(255,255,255,0.09)">
        <!-- Header -->
        <div class="flex items-center justify-between px-5 py-4" style="border-bottom:1px solid rgba(255,255,255,0.07)">
            <div class="flex items-center gap-2">
                <i class="fas fa-cloud-upload-alt text-[#c084fc] text-sm"></i>
                <h2 class="text-sm font-semibold text-white">Upload to All Garments</h2>
            </div>
            <button onclick="closeBulkUploadModal()" id="bulk-modal-close-btn" class="icon-btn">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <!-- Body -->
        <div class="px-5 py-4 space-y-4 overflow-y-auto">
            <div id="bulk-form-section">
                <div class="flex flex-col gap-1 mb-3">
                    <label class="text-xs text-white/40">Product name</label>
                    <input id="bulk-title" type="text" placeholder="FabricAI — My Design"
                           class="rounded-lg px-3 py-2 text-sm text-white focus:outline-none transition-colors"
                           style="background:rgba(255,255,255,0.07);border:1px solid rgba(255,255,255,0.1)" onfocus="this.style.borderColor='rgba(124,60,160,0.6)'" onblur="this.style.borderColor='rgba(255,255,255,0.1)'">
                </div>
                <div class="flex flex-col gap-1 mb-3">
                    <label class="text-xs text-white/40">Printify store</label>
                    <select id="bulk-shop"
                            class="rounded-lg px-3 py-2 text-sm text-white focus:outline-none"
                            style="background:rgba(255,255,255,0.07);border:1px solid rgba(255,255,255,0.1)">
                        <option value="">Loading stores…</option>
                    </select>
                </div>
                <div class="flex flex-col gap-1">
                    <label class="text-xs text-white/40">Garment color</label>
                    <div class="flex items-center gap-3">
                        <input type="color" id="bulk-color-hex" value="#ffffff"
                               oninput="document.getElementById('bulk-color-name').textContent=hexToColorName(this.value)"
                               class="w-10 h-10 rounded-lg cursor-pointer bg-transparent" style="border:1px solid rgba(255,255,255,0.12)">
                        <span id="bulk-color-name" class="text-xs text-white/70 font-medium">White</span>
                    </div>
                </div>
                <div class="mt-4 pt-3" style="border-top:1px solid rgba(255,255,255,0.07)">
                    @if(
                        in_array(strtolower(Auth::user()->plan ?? 'free'), ['pro', 'studio', 'business'])
                        || (Auth::user()->is_admin && strtolower(Auth::user()->plan ?? 'free') === 'admin')
                    )
                    <label class="flex items-center gap-2.5 cursor-pointer group">
                        <div class="relative">
                            <input type="checkbox" id="bulk-all-colors" class="sr-only peer">
                            <div class="w-9 h-5 rounded-full transition-colors duration-200 peer-checked:bg-[#a855f7]" style="background:rgba(255,255,255,0.12)"></div>
                            <div class="absolute top-0.5 left-0.5 w-4 h-4 bg-white rounded-full shadow transition-transform duration-200 peer-checked:translate-x-4"></div>
                        </div>
                        <div>
                            <span class="text-xs text-white/70 font-medium group-hover:text-white transition-colors">Upload in all available colors</span>
                            <span class="ml-1.5 text-[10px] text-[#c084fc] font-semibold uppercase tracking-wider">Turbo</span>
                        </div>
                    </label>
                    <p id="bulk-all-colors-note" class="text-[10px] text-white/30 mt-1.5 ml-11 hidden">
                        Creates one product per garment with every available color variant enabled.
                    </p>
                    @else
                    <div class="flex items-center gap-2.5 opacity-50 cursor-not-allowed" title="Available from Pro plan">
                        <div class="w-9 h-5 rounded-full" style="background:rgba(255,255,255,0.12)">
                            <div class="w-4 h-4 bg-white/40 rounded-full mt-0.5 ml-0.5"></div>
                        </div>
                        <div>
                            <span class="text-xs text-white/40 font-medium">Upload in all available colors</span>
                            <a href="/pricing" class="ml-1.5 text-[10px] text-[#c084fc] font-semibold uppercase tracking-wider hover:underline">Upgrade</a>
                        </div>
                    </div>
                    @endif
                </div>
                <div class="mt-3 pt-3" style="border-top:1px solid rgba(255,255,255,0.07)">
                    <label class="flex items-center gap-2.5 cursor-pointer group">
                        <div class="relative">
                            <input type="checkbox" id="bulk-publish" class="sr-only peer">
                            <div class="w-9 h-5 rounded-full transition-colors duration-200 peer-checked:bg-[#7c3ca0]" style="background:rgba(255,255,255,0.12)"></div>
                            <div class="absolute top-0.5 left-0.5 w-4 h-4 bg-white rounded-full shadow transition-transform duration-200 peer-checked:translate-x-4"></div>
                        </div>
                        <span class="text-xs text-white/70 font-medium group-hover:text-white transition-colors">Publish directly to store</span>
                    </label>
                    <p class="text-[10px] text-white/30 mt-1.5 ml-11">Makes products visible in your store immediately after creation.</p>
                </div>
            </div>
            <div id="bulk-progress-section" class="hidden space-y-3">
                <div class="flex justify-between text-xs text-white/40 mb-1">
                    <span id="bulk-progress-label">Uploading…</span>
                    <span id="bulk-progress-count">0/5</span>
                </div>
                <div class="w-full rounded-full h-2" style="background:rgba(255,255,255,0.08)">
                    <div id="bulk-progress-bar" class="bg-[#7c3ca0] h-2 rounded-full transition-all duration-300" style="width:0%"></div>
                </div>
                <div id="bulk-progress-results" class="space-y-1 pt-1 text-xs max-h-40 overflow-y-auto"></div>
            </div>
        </div>
        <!-- Footer -->
        <div class="px-5 py-4 flex gap-2" style="border-top:1px solid rgba(255,255,255,0.07)">
            <button onclick="closeBulkUploadModal()" id="bulk-cancel-btn"
                    class="flex-1 py-2.5 text-white/50 hover:text-white text-xs font-medium tracking-wide uppercase rounded-xl transition-colors" style="border:1px solid rgba(255,255,255,0.12)" onmouseover="this.style.background='rgba(255,255,255,0.06)'" onmouseout="this.style.background='transparent'">
                Cancel
            </button>
            <button onclick="startBulkUpload()" id="bulk-start-btn"
                    class="flex-1 py-2.5 bg-[#7c3ca0] text-white text-xs font-medium tracking-wide
                           uppercase rounded-xl hover:bg-[#5a2275] transition-colors disabled:opacity-50">
                <i class="fas fa-cloud-upload-alt mr-1"></i> Upload All
            </button>
        </div>
    </div>
</div>

<!-- ═══════════ GARMENT PREVIEW MODAL ═══════════ -->
<div id="preview-modal"
     class="fixed inset-0 z-50 hidden items-end sm:items-center justify-center bg-black/50 backdrop-blur-sm sm:p-4">
    <div class="shadow-2xl w-full sm:max-w-4xl rounded-t-2xl sm:rounded-2xl overflow-hidden flex flex-col" style="max-height:97dvh;background:#111;border:1px solid rgba(255,255,255,0.09)">
        <div class="flex items-center justify-between px-4 sm:px-6 py-3 sm:py-4 flex-shrink-0" style="border-bottom:1px solid rgba(255,255,255,0.07)">
            <h2 class="text-sm sm:text-base font-semibold text-white">Preview on Garment</h2>
            <button onclick="closePreviewModal()" class="icon-btn">
                <i class="fas fa-times text-lg"></i>
            </button>
        </div>
        <div class="flex flex-col sm:flex-row flex-1 min-h-0 overflow-hidden">

            <!-- ── Saved Designs panel ── -->
            <!-- Mobile: horizontal scrollable strip at top; Desktop: vertical left sidebar -->
            <div id="saved-designs-panel" class="flex-shrink-0 flex flex-col sm:w-44" style="border-bottom:1px solid rgba(255,255,255,0.07);background:rgba(255,255,255,0.03)">
                <div class="px-3 py-2 flex items-center justify-between" style="border-bottom:1px solid rgba(255,255,255,0.07)">
                    <p class="text-[9px] uppercase tracking-[0.2em] text-white/30 font-medium">Saved Designs</p>
                    <!-- Mobile: Add button inline -->
                    <button id="add-to-canvas-btn-mobile" disabled onclick="addSelectedToCanvas()"
                            class="sm:hidden px-3 py-1 bg-[#5a2275] text-white text-[9px] font-medium uppercase tracking-widest
                                   rounded-lg hover:bg-[#7c3ca0] transition-colors disabled:opacity-40">
                        + Add
                    </button>
                </div>
                <!-- Mobile: horizontal scroll; Desktop: vertical scroll -->
                <div id="saved-designs-list"
                     class="flex sm:flex-col gap-2 overflow-x-auto sm:overflow-x-hidden overflow-y-hidden sm:overflow-y-auto
                            p-2 flex-row">
                    <p class="text-[10px] text-white/30 text-center py-6 leading-relaxed whitespace-nowrap sm:whitespace-normal">Loading…</p>
                </div>
                <div class="p-2 flex-shrink-0 hidden sm:block" style="border-top:1px solid rgba(255,255,255,0.07)">
                    <button id="add-to-canvas-btn" disabled onclick="addSelectedToCanvas()"
                            class="w-full py-2 bg-[#5a2275] text-white text-[10px] font-medium uppercase tracking-widest
                                   rounded-lg hover:bg-[#7c3ca0] transition-colors disabled:opacity-40">
                        + Add to Canvas
                    </button>
                </div>
            </div>

            <!-- ── Editor ── -->
            <div class="flex-1 flex flex-col overflow-hidden min-w-0">

            <!-- ─ Toolbar ─ -->
            <div class="flex items-center gap-2 px-3 py-2.5 flex-shrink-0" style="border-bottom:1px solid rgba(255,255,255,0.07);background:rgba(255,255,255,0.03)">
                <select id="garment-select" onchange="renderPreview()"
                        class="flex-1 min-w-0 rounded-lg px-2.5 py-1.5 text-xs text-white focus:outline-none transition-colors"
                        style="background:rgba(255,255,255,0.08);border:1px solid rgba(255,255,255,0.1)">
                    <option value="tshirt">T-Shirt</option>
                    <option value="hoodie">Hoodie</option>
                    <option value="tanktop">Tank Top</option>
                    <option value="longsleeve">Long Sleeve</option>
                    <option value="sweatshirt">Sweatshirt</option>
                    <option value="leggings">Leggings</option>
                    <option value="joggers">Joggers</option>
                    <option value="shorts">Shorts</option>
                    <option value="dresses">Vestidos</option>
                    <option value="skirts">Faldas</option>
                    <option value="bikinis">Bikinis / Swimwear</option>
                    <option value="socks">Calcetines</option>
                    <option value="underwear">Ropa interior</option>
                    <option value="pajamas">Pijamas</option>
                    <option value="caps">Gorras</option>
                    <option value="beanies">Beanies</option>
                    <option value="tote_bags">Tote Bags</option>
                    <option value="scarves">Bufandas</option>
                </select>
                <input type="color" id="garment-color" value="#ffffff" oninput="renderPreview()"
                       title="Garment color"
                       class="w-8 h-8 rounded-lg cursor-pointer flex-shrink-0" style="border:1px solid rgba(255,255,255,0.12)">
                <div class="flex rounded-lg overflow-hidden flex-shrink-0" style="border:1px solid rgba(255,255,255,0.12)">
                    <button id="side-front-btn" type="button" onclick="switchSide('front')"
                            class="px-3 py-1.5 text-[11px] font-medium transition-colors bg-[#7c3ca0] text-white">Front</button>
                    <button id="side-back-btn" type="button" onclick="switchSide('back')"
                            class="px-3 py-1.5 text-[11px] font-medium transition-colors text-white/40" style="background:rgba(255,255,255,0.04)">Back</button>
                </div>
            </div>

            <!-- ─ Canvas ─ -->
            <div class="flex-1 flex flex-col items-center justify-center overflow-hidden" style="background:#0a0a0a">
                <div id="canvas-wrapper" style="position:relative;display:inline-block;max-width:100%;line-height:0;">
                    <canvas id="garment-canvas" width="500" height="550"
                            class="max-w-full h-auto rounded-xl block"
                            style="max-height:clamp(260px,46dvh,460px);"></canvas>
                    <canvas id="design-canvas" style="position:absolute;left:0;top:0;pointer-events:none;"></canvas>
                    <canvas id="handle-canvas" style="position:absolute;left:0;top:0;cursor:grab;"></canvas>
                </div>
                <p class="text-[10px] text-white/20 mt-2 select-none pointer-events-none">
                    <i class="fas fa-arrows-alt mr-1"></i>Drag to move &nbsp;·&nbsp; <i class="fas fa-search-plus mr-1"></i>Scroll to zoom
                </p>
                <p class="text-[9px] text-white/15 mt-1 select-none pointer-events-none italic">
                    <i class="fas fa-info-circle mr-1"></i>Print areas shown are approximate — final placement may vary slightly.
                </p>
            </div>

            <!-- ─ Controls + Actions ─ -->
            <div class="flex-shrink-0 px-4 py-3 space-y-3 overflow-y-auto" style="border-top:1px solid rgba(255,255,255,0.07);background:rgba(17,17,17,0.95);max-height:44dvh">

                <div id="printify-spec" class="text-[10px] text-white/30 text-center leading-relaxed empty:hidden"></div>

                <!-- Sliders -->
                <div class="grid grid-cols-2 gap-x-5 gap-y-2.5">
                    <label class="flex items-center gap-2 min-w-0">
                        <span class="text-[10px] text-white/30 shrink-0 w-4">X</span>
                        <input type="range" id="design-pos-x" min="-1" max="1" step="0.01" value="0"
                               oninput="document.getElementById('pos-x-val').textContent=parseFloat(this.value).toFixed(2); syncSelectedLayerFromControls(); renderPreview()"
                               class="flex-1 accent-purple-600 min-w-0">
                        <span id="pos-x-val" class="text-[10px] text-white/70 tabular-nums w-7 text-right shrink-0">0</span>
                    </label>
                    <label class="flex items-center gap-2 min-w-0">
                        <span class="text-[10px] text-white/30 shrink-0 w-4">Y</span>
                        <input type="range" id="design-pos-y" min="-1" max="1" step="0.01" value="0"
                               oninput="document.getElementById('pos-y-val').textContent=parseFloat(this.value).toFixed(2); syncSelectedLayerFromControls(); renderPreview()"
                               class="flex-1 accent-purple-600 min-w-0">
                        <span id="pos-y-val" class="text-[10px] text-white/70 tabular-nums w-7 text-right shrink-0">0</span>
                    </label>
                    <label class="flex items-center gap-2 min-w-0">
                        <span class="text-[10px] text-white/30 shrink-0 w-4">⊕</span>
                        <input type="range" id="design-scale" min="0.2" max="2" step="0.01" value="1"
                               oninput="document.getElementById('scale-val').textContent=parseFloat(this.value).toFixed(2); syncSelectedLayerFromControls(); renderPreview()"
                               class="flex-1 accent-purple-600 min-w-0">
                        <span id="scale-val" class="text-[10px] text-white/70 tabular-nums w-7 text-right shrink-0">1.00</span>
                    </label>
                    <label class="flex items-center gap-2 min-w-0">
                        <span class="text-[10px] text-white/30 shrink-0 w-4">↻</span>
                        <input type="range" id="design-rotation" min="-180" max="180" step="1" value="0"
                               oninput="document.getElementById('rotation-val').textContent=parseInt(this.value)+'°'; syncSelectedLayerFromControls(); renderPreview()"
                               class="flex-1 accent-purple-600 min-w-0">
                        <span id="rotation-val" class="text-[10px] text-white/70 tabular-nums w-7 text-right shrink-0">0°</span>
                    </label>
                </div>

                <!-- Layers -->
                <div id="layers-container" class="hidden rounded-xl p-2.5" style="border:1px solid rgba(255,255,255,0.07);background:rgba(255,255,255,0.04)">
                    <p class="text-[9px] uppercase tracking-[0.15em] text-white/30 mb-2">Layers</p>
                    <div id="layers-list" class="space-y-1.5"></div>
                </div>

                <!-- Actions -->
                <div class="flex gap-2">
                    <button onclick="downloadPreview()"
                            class="flex-1 py-2 text-white/60 hover:text-white text-xs font-medium rounded-xl
                                   transition-colors flex items-center justify-center gap-1.5" style="border:1px solid rgba(255,255,255,0.12)" onmouseover="this.style.background='rgba(255,255,255,0.06)'" onmouseout="this.style.background='transparent'">
                        <i class="fas fa-download text-[10px]"></i> Download
                    </button>
                    <button onclick="togglePrintifyPanel()"
                            class="flex-1 py-2 bg-[#7c3ca0] text-white text-xs font-medium rounded-xl
                                   hover:bg-[#5a2275] transition-colors flex items-center justify-center gap-1.5">
                        <i class="fas fa-store text-[10px]"></i> Create Product
                    </button>
                </div>

                <!-- Printify panel -->
                <div id="printify-panel" class="hidden rounded-xl overflow-hidden" style="border:1px solid rgba(255,255,255,0.07)">
                    <div class="px-3 py-2" style="background:rgba(255,255,255,0.04);border-bottom:1px solid rgba(255,255,255,0.07)">
                        <p class="text-[10px] font-medium tracking-widest uppercase text-white/30">New Printify product</p>
                    </div>
                    <div class="px-3 py-3 space-y-2.5">
                        <div id="printify-connect-notice"
                             class="hidden px-3 py-2.5 rounded-lg text-yellow-300 text-xs" style="background:rgba(234,179,8,0.1);border:1px solid rgba(234,179,8,0.2)">
                            Account not connected.
                            <a href="/profile" target="_blank" rel="noopener" class="underline font-medium">Connect in Profile →</a>
                        </div>
                        <input id="printify-title" type="text" placeholder="Product name"
                               class="w-full rounded-lg px-3 py-2 text-sm text-white focus:outline-none transition-colors"
                               style="background:rgba(255,255,255,0.07);border:1px solid rgba(255,255,255,0.1)" onfocus="this.style.borderColor='rgba(124,60,160,0.6)'" onblur="this.style.borderColor='rgba(255,255,255,0.1)'">
                        <select id="printify-shop"
                                class="w-full rounded-lg px-3 py-2 text-sm text-white focus:outline-none"
                                style="background:rgba(255,255,255,0.07);border:1px solid rgba(255,255,255,0.1)">
                            <option value="">Loading stores…</option>
                        </select>
                        <div class="flex items-center gap-2.5">
                            <input type="color" id="printify-color-hex" value="#ffffff"
                                   oninput="onPrintifyColorChange(this.value)"
                                   class="w-8 h-8 rounded-lg cursor-pointer flex-shrink-0" style="border:1px solid rgba(255,255,255,0.12)">
                            <span id="printify-color-name" class="text-xs text-white/60">White</span>
                        </div>
                        <div class="pt-2.5" style="border-top:1px solid rgba(255,255,255,0.07);margin-top:4px">
                            <label class="flex items-center gap-2.5 cursor-pointer group">
                                <div class="relative">
                                    <input type="checkbox" id="printify-publish" class="sr-only peer">
                                    <div class="w-9 h-5 rounded-full transition-colors duration-200 peer-checked:bg-[#7c3ca0]" style="background:rgba(255,255,255,0.12)"></div>
                                    <div class="absolute top-0.5 left-0.5 w-4 h-4 bg-white rounded-full shadow transition-transform duration-200 peer-checked:translate-x-4"></div>
                                </div>
                                <span class="text-xs text-white/70 font-medium group-hover:text-white transition-colors">Publish directly to store</span>
                            </label>
                            <p class="text-[10px] text-white/30 mt-1.5 ml-11">Makes the product visible in your store immediately.</p>
                        </div>
                        <div id="printify-feedback" class="hidden text-xs py-1"></div>
                        <div class="flex gap-2">
                            <button id="printify-send-btn" onclick="sendToPrintify()"
                                    class="flex-1 py-2.5 bg-ink text-white text-xs font-medium rounded-xl
                                           hover:bg-ink-light transition-colors disabled:opacity-50">
                                Create Product
                            </button>
                            <button id="printify-bulk-btn" onclick="sendToAllPrintify()"
                                    title="Upload design to all clothing types at once"
                                    class="flex-1 py-2.5 bg-[#5a2275] text-white text-xs font-medium rounded-xl
                                           hover:bg-[#7c3ca0] transition-colors disabled:opacity-50">
                                Upload to All
                            </button>
                        </div>
                    </div>
                </div>

            </div><!-- /.controls -->
            </div><!-- /.editor -->
        </div><!-- /.two-col -->
    </div><!-- /.modal-inner -->
</div>

<script>
    // User identity
    const userInitial   = '{{ strtoupper(mb_substr(Auth::user()->name, 0, 1)) }}';
    const userAvatarUrl = @json(Auth::user()->avatar);
    const userPlan      = @json(Auth::user()->plan ?? 'free');
    const userIsAdmin   = @json((bool) Auth::user()->is_admin);
    const isAdminPlan   = userIsAdmin && String(userPlan || '').toLowerCase() === 'admin';

    const adminConsole = {
        info(...args) { if (isAdminPlan) console.info(...args); },
        warn(...args) { if (isAdminPlan) console.warn(...args); },
        error(...args) { if (isAdminPlan) console.error(...args); },
    };


    // ─── Token Manager ────────────────────────────────────────────────
    const TokenManager = {
        MAX: 10,
        _cache: {{ Auth::user()->tokens ?? 5 }},
        get()    { return this._cache; },
        set(n)   { this._cache = Math.max(0, n); this._render(this._cache); },
        deduct(amount = 1) {
            const cur = this.get();
            if (cur < amount) return false;
            this.set(cur - amount);
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
            const countEl  = document.getElementById('token-count');
            const iconEl   = document.getElementById('token-icon');
            const banner   = document.getElementById('no-credits-banner');
            const textarea = document.getElementById('prompt');
            const sendBtn  = document.getElementById('submit-btn');
            if (!countEl) return;
            countEl.textContent = n;
            if (iconEl) iconEl.innerHTML = '<img src="/images/spool.webp" class="w-4 h-4 object-contain opacity-70" alt="Spools">';
            if (banner) banner.classList.toggle('hidden', n > 0);
            if (textarea) textarea.disabled = n <= 0;
            if (sendBtn)  sendBtn.disabled  = n <= 0;
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
    const savedImgKeys      = new Set(); // fingerprints of already-saved images
    const savedImgIds       = new Map(); // fingerprint → DB id (for unsaving)

    const imageInput        = document.getElementById('image-upload');
    const form              = document.getElementById('design-form');
    const promptInput       = document.getElementById('prompt');
    const submitBtn         = document.getElementById('submit-btn');
    const loader            = document.getElementById('loader');
    const errorEl           = document.getElementById('error');
    const messagesContainer = document.getElementById('messages');
    const chatContainer     = document.getElementById('chat-container');
    const previewImageStore = [];



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

    // ─── Delete All modal ────────────────────────────────────────────
    function showDeleteAllModal() {
        const m = document.getElementById('delete-all-modal');
        m.classList.remove('hidden'); m.classList.add('flex');
    }
    function hideDeleteAllModal() {
        const m = document.getElementById('delete-all-modal');
        m.classList.add('hidden'); m.classList.remove('flex');
    }
    document.getElementById('delete-all-cancel-btn').addEventListener('click', () => hideDeleteAllModal());
    document.getElementById('delete-all-confirm-btn').addEventListener('click', async () => {
        hideDeleteAllModal();
        const btn = document.getElementById('delete-all-confirm-btn');
        try {
            const res = await fetch('/chats', {
                method: 'DELETE',
                headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content }
            });
            if (!res.ok) throw new Error('HTTP ' + res.status);
            // Clear everything and start fresh
            currentChatId = null;
            chats = [];
            document.getElementById('chat-list').innerHTML = '';
            document.getElementById('chat-title').textContent = 'New Design';
            document.getElementById('messages').innerHTML = '';
            document.getElementById('welcome-screen').classList.remove('hidden');
            await newChat();
        } catch (e) {
            adminConsole.error('Delete all failed', e);
        }
    });
    document.getElementById('delete-all-modal').addEventListener('click', function (e) {
        if (e.target === this) hideDeleteAllModal();
    });



    // ─── Char counter & dynamic maxlength ───────────────────────────
    const LIMIT_DIFFUSION = 270;
    const charCounter = document.getElementById('char-counter');

    function updateCharCounter() {
        const max = parseInt(promptInput.getAttribute('maxlength') || LIMIT_DIFFUSION);
        const len = promptInput.value.length;
        const remaining = max - len;
        charCounter.textContent = len + ' / ' + max;
        charCounter.style.color = remaining <= 20 ? '#ef4444' : remaining <= 60 ? '#f59e0b' : '#8a8a8a';
    }

    function syncPromptLimit() {
        promptInput.setAttribute('maxlength', LIMIT_DIFFUSION);
        updateCharCounter();
    }

    function resolveGenerationEngine(snapshotImage) {
        if (snapshotImage || isEditMode) {
            return { provider: 'together', model: 'flux_dev', cost: 1 };
        }

        const plan = String(userPlan || '').toLowerCase();
        if (isAdminPlan || ['pro', 'business', 'studio'].includes(plan)) {
            const sel = document.getElementById('model-selector')?.value || 'flash';
            if (sel === 'max') {
                return { provider: 'nanogpt', model: 'juggernaut_z', cost: 2 };
            }
        }

        return { provider: 'chutes', model: 'fabric_pro', cost: 1 };
    }

    // ─── Textarea auto-resize ─────────────────────────────────────────
    promptInput.addEventListener('input', function () {
        this.style.height = 'auto';
        this.style.height = Math.min(this.scrollHeight, 120) + 'px';
        updateCharCounter();
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
    let _loaderInterval = null;
    function setLoading(loading) {
        submitBtn.disabled = loading;
        loader.classList.toggle('hidden', !loading);
        loader.classList.toggle('flex', loading);
        errorEl.classList.add('hidden');
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
                     class="rounded-xl max-w-48 max-h-48 object-cover shadow-sm border border-cream-300 cursor-zoom-in chat-lightbox-img">`;
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
            <div class="w-8 h-8 rounded-full shadow-sm
                        flex items-center justify-center flex-shrink-0 overflow-hidden p-1 mt-0.5" style="background:#1a1a1a;border:1px solid rgba(255,255,255,0.1)">
                <img src="{{ asset('images/logo.png') }}?v={{ filemtime(public_path('images/logo.png')) }}" alt="FabricAI" class="w-full h-full object-contain">
            </div>
            <div class="rounded-2xl rounded-tl-sm shadow-sm overflow-hidden max-w-sm" style="background:#1a1a1a;border:1px solid rgba(255,255,255,0.09)">
                <div id="${uniqueId}" class="relative" style="background:#ffffff">
                    <img src="${imageUrl}" alt="Generated design" class="w-full block cursor-zoom-in chat-lightbox-img" crossorigin="anonymous">
                    <button type="button" title="Save design"
                            class="save-design-btn absolute top-2 right-2 w-7 h-7 rounded-full
                                   backdrop-blur-sm shadow-sm
                                   flex items-center justify-center transition-all hover:scale-110"
                            style="background:rgba(0,0,0,0.45);border:1px solid rgba(0,0,0,0.15)"
                            data-image-src="${imageUrl}">
                        <i class="fas fa-bookmark text-xs text-white"></i>
                    </button>
                </div>
                <div class="px-3 py-2 flex items-center gap-1.5 flex-wrap" style="border-top:1px solid rgba(255,255,255,0.07)">
                    <span class="text-[9px] text-white/30 uppercase tracking-wider mr-1">BG</span>
                    <button type="button" onclick="changeBg('${uniqueId}','#faf8f4')"
                            class="w-4 h-4 rounded hover:opacity-80 transition-opacity" style="background:#faf8f4;border:1px solid rgba(255,255,255,0.15)" title="Cream"></button>
                    <button type="button" onclick="changeBg('${uniqueId}','#18181b')"
                            class="w-4 h-4 rounded hover:opacity-80 transition-opacity" style="background:#18181b;border:1px solid rgba(255,255,255,0.15)" title="Dark"></button>
                    <button type="button" onclick="changeBg('${uniqueId}','#ffffff')"
                            class="w-4 h-4 rounded hover:opacity-80 transition-opacity" style="background:#ffffff;border:1px solid rgba(255,255,255,0.15)" title="White"></button>
                    <button type="button" onclick="changeBg('${uniqueId}','#000000')"
                            class="w-4 h-4 rounded hover:opacity-80 transition-opacity" style="background:#000000;border:1px solid rgba(255,255,255,0.15)" title="Black"></button>
                    <button type="button" onclick="changeBg('${uniqueId}','#7c3ca0')"
                            class="w-4 h-4 rounded hover:opacity-80 transition-opacity" style="background:#7c3ca0;border:1px solid rgba(255,255,255,0.15)" title="Purple"></button>
                    <input type="color" onchange="changeBg('${uniqueId}',this.value)"
                           class="w-4 h-4 rounded cursor-pointer" style="border:1px solid rgba(255,255,255,0.15)" title="Custom colour">
                </div>
                <div class="px-2 py-2 flex items-center justify-center gap-1" style="border-top:1px solid rgba(255,255,255,0.07)">
                    <a href="${imageUrl}" download="design.png" title="Download"
                       class="icon-btn flex-col gap-0.5" style="width:52px;height:44px;font-size:14px;color:#e2e8f0;background:rgba(255,255,255,0.1);border-radius:10px">
                        <i class="fas fa-download"></i>
                        <span style="font-size:8px;opacity:0.6">Save</span>
                    </a>
                    <button type="button" title="Retouch this design" class="icon-btn accent edit-btn flex-col gap-0.5" style="width:52px;height:44px;font-size:14px;color:#c084fc;background:rgba(124,60,160,0.2);border-radius:10px">
                        <i class="fas fa-magic"></i>
                        <span style="font-size:8px;opacity:0.7">Edit</span>
                    </button>
                    <button type="button" title="Preview on garment" class="icon-btn preview-btn flex-col gap-0.5" data-preview-idx="${idx}" style="width:52px;height:44px;font-size:14px;color:#e2e8f0;background:rgba(255,255,255,0.1);border-radius:10px">
                        <i class="fas fa-tshirt"></i>
                        <span style="font-size:8px;opacity:0.6">Try on</span>
                    </button>
                    <button type="button" title="Turbo upload to all garments" onclick="openBulkUploadModal('${imageUrl}')"
                            class="icon-btn flex-col gap-0.5" style="width:52px;height:44px;font-size:14px;color:#c084fc;background:rgba(124,60,160,0.2);border-radius:10px">
                        <i class="fas fa-bolt"></i>
                        <span style="font-size:8px;opacity:0.75">Upload</span>
                    </button>
                </div>
            </div>`;

        messagesContainer.appendChild(div);

        updateWelcomeScreen();
        scrollToBottom();
    }

    function addGeneratingPlaceholder() {
        const id  = 'ph-' + Date.now();
        const div = document.createElement('div');
        div.id        = id;
        div.className = 'flex items-start gap-3 msg-enter';
        div.innerHTML = `
            <div style="width:32px;height:32px;border-radius:50%;background:#1a1a1a;border:1px solid rgba(255,255,255,0.1);
                        display:flex;align-items:center;
                        justify-content:center;flex-shrink:0;overflow:hidden;padding:4px;margin-top:2px;">
                <img src="{{ asset('images/logo.png') }}?v={{ filemtime(public_path('images/logo.png')) }}" alt="FabricAI" style="width:100%;height:100%;object-fit:contain;">
            </div>
            <div style="background:#1a1a1a;border:1px solid rgba(255,255,255,0.09);border-radius:0 16px 16px 16px;
                        box-shadow:0 4px 16px rgba(0,0,0,0.3);overflow:hidden;width:260px;">
                <!-- Animated image area -->
                <div style="position:relative;width:260px;height:260px;background:#111;
                            border-radius:0 12px 0 0;overflow:hidden;">
                    <!-- Grid overlay -->
                    <div style="position:absolute;inset:0;
                         background-image:linear-gradient(rgba(124,60,160,0.08) 1px,transparent 1px),
                                          linear-gradient(90deg,rgba(124,60,160,0.08) 1px,transparent 1px);
                         background-size:28px 28px;"></div>
                    <!-- Shimmer sweep -->
                    <div class="gen-shimmer" style="position:absolute;inset:0;width:40%;
                         background:linear-gradient(90deg,transparent,rgba(255,255,255,0.55),transparent);
                         pointer-events:none;"></div>
                    <!-- Purple orb -->
                    <div style="position:absolute;width:160px;height:160px;border-radius:50%;
                         background:radial-gradient(circle,rgba(124,60,160,0.2) 0%,transparent 70%);
                         filter:blur(24px);top:50%;left:50%;transform:translate(-50%,-50%);"></div>
                    <!-- Pencil -->
                    <div class="gen-placeholder" style="position:absolute;inset:0;display:flex;
                         align-items:center;justify-content:center;">
                        <span style="font-size:36px;color:rgba(124,60,160,0.6);transform:scaleX(-1);display:inline-block;">✎</span>
                    </div>
                </div>
            </div>`;
        messagesContainer.appendChild(div);
        updateWelcomeScreen();
        scrollToBottom();
        return id;
    }

    function addBotError(msg) {
        const div = document.createElement('div');
        div.className = 'flex items-start gap-3 msg-enter';
        div.innerHTML = `
            <div class="w-8 h-8 rounded-full shadow-sm
                        flex items-center justify-center flex-shrink-0 overflow-hidden p-1 mt-0.5" style="background:#1a1a1a;border:1px solid rgba(255,255,255,0.1)">
                <img src="{{ asset('images/logo.png') }}?v={{ filemtime(public_path('images/logo.png')) }}" alt="FabricAI" class="w-full h-full object-contain">
            </div>
            <div class="rounded-2xl rounded-tl-sm px-4 py-3
                        text-red-400 text-sm leading-relaxed max-w-xs md:max-w-md" style="background:rgba(239,68,68,0.08);border:1px solid rgba(239,68,68,0.2)">
                ${escapeHtml(msg)}
            </div>`;
        messagesContainer.appendChild(div);
        updateWelcomeScreen();
        scrollToBottom();
    }

    // ─── BG Removal warning toast ────────────────────────────────────
    let _bgRemovalToastTimer = null;
    function showBgRemovalWarning() {
        let toast = document.getElementById('bg-removal-toast');
        if (!toast) {
            toast = document.createElement('div');
            toast.id = 'bg-removal-toast';
            toast.style.cssText = 'position:fixed;bottom:80px;left:50%;transform:translateX(-50%);z-index:9999;'
                + 'padding:8px 14px;border-radius:10px;font-size:11px;pointer-events:none;transition:opacity 0.4s;'
                + 'background:rgba(30,20,40,0.92);border:1px solid rgba(168,85,247,0.3);color:rgba(200,170,220,0.9);'
                + 'white-space:nowrap;box-shadow:0 4px 16px rgba(0,0,0,0.4);';
            toast.innerHTML = '<i class="fas fa-magic mr-1.5" style="color:rgba(168,85,247,0.7)"></i>'
                + 'Background removal failed — we\'re working on it.';
            document.body.appendChild(toast);
        }
        toast.style.opacity = '1';
        clearTimeout(_bgRemovalToastTimer);
        _bgRemovalToastTimer = setTimeout(() => { toast.style.opacity = '0'; }, 5000);
    }

    // ─── Image lightbox ───────────────────────────────────────────────
    let _lightboxSrc = null;
    function openLightbox(src) {
        _lightboxSrc = src;
        const modal = document.getElementById('lightbox-modal');
        document.getElementById('lightbox-img').src = src;
        const dlBtn = document.getElementById('lightbox-download-btn');
        if (dlBtn) dlBtn.href = src;
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    }
    function closeLightbox() {
        const modal = document.getElementById('lightbox-modal');
        modal.classList.add('hidden');
        modal.classList.remove('flex');
        document.getElementById('lightbox-img').src = '';
    }
    function _closeLightboxThen(fn) {
        closeLightbox();
        // Small delay so the lightbox closes before the next modal opens
        setTimeout(fn, 120);
    }
    // Delegate click on any .chat-lightbox-img inside the messages container
    document.addEventListener('click', e => {
        if (e.target.classList.contains('chat-lightbox-img')) {
            openLightbox(e.target.src);
        }
    });
    document.addEventListener('keydown', e => {
        if (e.key === 'Escape') closeLightbox();
    });

    window.changeBg = function (bgId, color) {
        const el = document.getElementById(bgId);
        if (el) el.style.backgroundColor = color;
    };

    // ─── Chat management ──────────────────────────────────────────────
    async function loadChats() {
        const chatList = document.getElementById('chat-list');
        try {
            const res = await fetch('/chats');
            chats = await res.json();
        } catch (err) {
            showError('Could not load sessions'); return;
        }

        // Build into a fragment first to avoid flash
        const frag = document.createDocumentFragment();

        chats.forEach(chat => {
            const wrapper = document.createElement('div');
            wrapper.className = 'group flex items-center gap-2.5 px-2 py-2 rounded-xl transition-colors ' +
                (chat.id === currentChatId ? 'bg-white/10' : 'hover:bg-white/[0.06]');

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
            nameEl.className = 'text-xs font-medium text-white/80 truncate leading-tight';
            nameEl.textContent = chat.title || 'New Design';
            const dateEl = document.createElement('p');
            dateEl.className = 'text-[10px] text-white/30 mt-0.5';
            dateEl.textContent = relativeDate(chat.created_at);
            content.appendChild(nameEl); content.appendChild(dateEl);
            content.onclick = () => { loadChat(chat.id); closeSidebar(); };

            // Inline rename input
            const input = document.createElement('input');
            input.type = 'text'; input.value = chat.title || 'New Design';
            input.className = 'hidden flex-1 min-w-0 px-1.5 py-0.5 text-xs rounded-lg border border-[#7c3ca0] text-white focus:outline-none' + ' bg-[#111]';

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
            frag.appendChild(wrapper);
        });

        // Single DOM swap — no visible flash
        chatList.innerHTML = '';
        chatList.appendChild(frag);
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
            if (!res.ok && data.error) {
                showError(data.error);
                return;
            }
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
        const peekCost = resolveGenerationEngine(uploadedImageBase64).cost;
        if (TokenManager.get() < peekCost) {
            showError(peekCost > 1 ? `You need ${peekCost} Spools to use advanced features` : 'No Spools available');
            return;
        }

        isSubmitting = true;
        if (!currentChatId) currentChatId = await newChat();

        addUserMessage(prompt, uploadedImageBase64);
        promptInput.value = ''; promptInput.style.height = 'auto';

        const snapshotImage = uploadedImageBase64;
        const snapshotMime  = uploadedImageMime;
        const generationEngine = resolveGenerationEngine(snapshotImage);
        clearImagePreview();
        setLoading(true);

        const placeholderId = addGeneratingPlaceholder();

        try {
            const csrf = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
            const res  = await fetch('/designs/generate', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
                body: JSON.stringify({
                    prompt, chat_id: currentChatId,
                    imageBase64: snapshotImage, mimeType: snapshotMime,
                    model: generationEngine.model,
                    provider: generationEngine.provider,
                    imageStyle: document.getElementById('style-selector')?.value || 'default',
                    is_edit: isEditMode,
                }),
            });
            const data = await res.json().catch(() => ({ success: false, error: 'Invalid server response' }));
            if (!res.ok) throw new Error(data?.message || data?.error || `Error ${res.status}`);

            // NanoGPT returns immediately with a generation_id — poll for the result
            if (data.status === 'generating' && data.generation_id) {
                await pollGeneration(data.generation_id, placeholderId, generationEngine.cost);
                return; // finally block handled inside pollGeneration
            }

            const imageUrl = data.imageUrl || data.image_url || data.url;
            const base64   = data.imageBase64 || data.image_base64 || data.base64;

            adminConsole.info('[FabricAI] Generation engine', {
                plan: userPlan,
                provider: data.provider || 'unknown',
                model: data.model || 'unknown',
                usedFallback: (data.provider || '') !== generationEngine.provider
            });

            if (data.bg_removal_method) {
                const bgRoute = data.bg_removal_method === 'api'
                    ? 'replicate_primary'
                    : (data.bg_removal_method === 'laravel_local' ? 'laravel_local_fallback' : 'unknown');
                adminConsole.info('[FabricAI] Background removal (sync)', {
                    method: data.bg_removal_method,
                    route: bgRoute,
                    engine: data.bg_removal_engine || 'unknown',
                    provider: data.provider || 'unknown',
                    model: data.model || 'unknown',
                });
            }

            if (data.bg_removal_failed) {
                adminConsole.warn('[FabricAI] Background removal failed for this generation. The raw image is being shown instead.', {
                    provider: data.provider || 'unknown',
                    model:    data.model    || 'unknown',
                    detail:   data.bg_removal_error || 'No additional detail — check server logs.',
                });
                showBgRemovalWarning();
            }

            if (imageUrl) {
                const ph = document.getElementById(placeholderId); if (ph) ph.remove();
                addBotResponse(imageUrl); TokenManager.deduct(generationEngine.cost);
            } else if (base64) {
                const ph = document.getElementById(placeholderId); if (ph) ph.remove();
                addBotResponse(base64.startsWith('data:') ? base64 : 'data:image/png;base64,' + base64);
                TokenManager.deduct(generationEngine.cost);
            } else {
                throw new Error('No image in response');
            }
        } catch (err) {
            const ph = document.getElementById(placeholderId); if (ph) ph.remove();
            addBotError(err.message || 'Could not generate the image. Please check your prompt and try again.');
            await TokenManager.sync();
        } finally {
            isSubmitting = false; setLoading(false); exitEditMode(); clearImagePreview();
        }
    });

    // ─── NanoGPT async polling ────────────────────────────────────────
    async function pollGeneration(generationId, placeholderId, cost = 1) {
        const POLL_INTERVAL = 4000; // 4 seconds between polls
        const MAX_POLLS     = 60;   // max 4 min wait
        let   polls         = 0;

        return new Promise((resolve) => {
            const interval = setInterval(async () => {
                polls++;
                try {
                    const res  = await fetch(`/designs/generation/${generationId}`, {
                        headers: { 'Accept': 'application/json' }
                    });
                    const data = await res.json().catch(() => ({}));

                    if (data.status === 'done') {
                        clearInterval(interval);
                        const ph = document.getElementById(placeholderId); if (ph) ph.remove();

                        const imageUrl = data.imageUrl || data.image_url || data.url;
                        const base64   = data.imageBase64 || data.image_base64 || data.base64;

                        adminConsole.info('[FabricAI] Generation engine (async)', {
                            plan: userPlan, provider: data.provider || 'nanogpt', model: data.model || 'juggernaut_z',
                        });

                        if (data.bg_removal_method) {
                            const bgRoute = data.bg_removal_method === 'api'
                                ? 'replicate_primary'
                                : (data.bg_removal_method === 'laravel_local' ? 'laravel_local_fallback' : 'unknown');
                            adminConsole.info('[FabricAI] Background removal (async)', {
                                method: data.bg_removal_method,
                                route: bgRoute,
                                engine: data.bg_removal_engine || 'unknown',
                                provider: data.provider || 'nanogpt',
                                model: data.model || 'juggernaut_z',
                            });
                        }

                        if (data.bg_removal_failed) showBgRemovalWarning();

                        if (imageUrl) {
                            addBotResponse(imageUrl); TokenManager.deduct(cost);
                        } else if (base64) {
                            addBotResponse(base64.startsWith('data:') ? base64 : 'data:image/png;base64,' + base64);
                            TokenManager.deduct(cost);
                        } else {
                            addBotError('No image in response');
                            await TokenManager.sync();
                        }

                        isSubmitting = false; setLoading(false); exitEditMode(); clearImagePreview();
                        resolve();
                    } else if (data.status === 'error') {
                        clearInterval(interval);
                        const ph = document.getElementById(placeholderId); if (ph) ph.remove();
                        addBotError(data.error || 'Generation failed');
                        await TokenManager.sync();
                        isSubmitting = false; setLoading(false); exitEditMode(); clearImagePreview();
                        resolve();
                    } else if (polls >= MAX_POLLS) {
                        clearInterval(interval);
                        const ph = document.getElementById(placeholderId); if (ph) ph.remove();
                        addBotError('Generation timed out. Please try again.');
                        await TokenManager.sync();
                        isSubmitting = false; setLoading(false); exitEditMode(); clearImagePreview();
                        resolve();
                    }
                    // status === 'pending' → keep polling
                } catch (err) {
                    // Network error during polling — keep trying unless max reached
                    if (polls >= MAX_POLLS) {
                        clearInterval(interval);
                        const ph = document.getElementById(placeholderId); if (ph) ph.remove();
                        addBotError('Network error during generation polling.');
                        await TokenManager.sync();
                        isSubmitting = false; setLoading(false); exitEditMode(); clearImagePreview();
                        resolve();
                    }
                }
            }, POLL_INTERVAL);
        });
    }

    document.addEventListener('visibilitychange', () => {
        if (document.visibilityState === 'visible') TokenManager.sync();
    });

    document.addEventListener('DOMContentLoaded', async () => {
        TokenManager.init();
        await TokenManager.sync();
        syncPromptLimit();
        await loadChats();
        if (chats.length === 0) {
            // Create the first chat without re-rendering the sidebar (it's empty anyway)
            try {
                const res = await fetch('/chats', {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
                });
                const data = await res.json();
                if (res.ok && data.id) {
                    currentChatId = data.id;
                    chats = [data];
                    await loadChats();
                }
            } catch(e) { /* non-critical */ }
        }
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

        // Save design (bookmark button)
        const saveBtn = e.target.closest ? e.target.closest('.save-design-btn') : null;
        if (saveBtn) {
            const src = saveBtn.getAttribute('data-image-src');
            if (src) saveDesign(src, saveBtn);
        }

        // Close preview modal on backdrop
        if (e.target.id === 'preview-modal') closePreviewModal();
    });

    // ─── User menu dropdown ───────────────────────────────────────────
    function toggleUserMenu() {
        const dd = document.getElementById('user-menu-dropdown');
        const chevron = document.getElementById('user-menu-chevron');
        const isOpen = !dd.classList.contains('hidden');
        if (isOpen) {
            closeUserMenu();
        } else {
            dd.classList.remove('hidden');
            dd.classList.add('flex', 'flex-col');
            chevron.style.transform = 'rotate(180deg)';
            // close on outside click
            setTimeout(() => document.addEventListener('click', _closeUserMenuOutside), 0);
        }
    }
    function closeUserMenu() {
        const dd = document.getElementById('user-menu-dropdown');
        const chevron = document.getElementById('user-menu-chevron');
        dd.classList.add('hidden');
        dd.classList.remove('flex', 'flex-col');
        chevron.style.transform = '';
        document.removeEventListener('click', _closeUserMenuOutside);
    }
    function _closeUserMenuOutside(e) {
        const wrapper = document.getElementById('user-menu-wrapper');
        if (!wrapper.contains(e.target)) closeUserMenu();
    }

    // ─── My Saved Designs modal ───────────────────────────────────────
    function openMyDesignsModal() {
        const modal = document.getElementById('my-designs-modal');
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        _loadMyDesigns();
    }
    function closeMyDesignsModal() {
        const modal = document.getElementById('my-designs-modal');
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }
    async function _loadMyDesigns() {
        const grid    = document.getElementById('my-designs-grid');
        const empty   = document.getElementById('my-designs-empty');
        const loading = document.getElementById('my-designs-loading');
        grid.innerHTML = '';
        empty.classList.add('hidden');   empty.classList.remove('flex');
        loading.classList.remove('hidden');

        try {
            const res  = await fetch('/designs/saved');
            if (!res.ok) throw new Error('HTTP ' + res.status);
            const data = await res.json();
            loading.classList.add('hidden');
            if (!data.length) {
                empty.classList.remove('hidden');
                empty.classList.add('flex');
                return;
            }
            data.forEach(d => {
                const wrap = document.createElement('div');
                wrap.dataset.designId = d.id;
                wrap.className = 'group relative rounded-xl overflow-hidden bg-transparent hover:border-[#7c3ca0] transition-colors';
                wrap.style.cssText = 'border:1px solid rgba(255,255,255,0.09)';

                // Bottom bar: inline editable title + action buttons
                wrap.innerHTML = `
                    <img src="${d.image_data}" alt="${d.title || 'design'}"
                         class="w-full aspect-square object-contain bg-white cursor-pointer" loading="lazy">
                    <div class="px-2 py-1.5 flex items-center gap-1">
                        <span class="design-title-label flex-1 text-[10px] text-white/30 truncate cursor-text"
                              title="Click to rename">${d.title || 'Design'}</span>
                        <input type="text" value="${(d.title || 'Design').replace(/"/g,'&quot;')}"
                               class="design-title-input hidden flex-1 text-[10px] text-white border border-[#7c3ca0] rounded px-1 py-0.5 outline-none min-w-0" style="background:#111">
                        <button class="rename-btn text-white/20 hover:text-[#c084fc] transition-colors shrink-0" title="Rename">
                            <i class="fas fa-pencil-alt" style="font-size:9px"></i>
                        </button>
                        <button class="delete-btn text-white/20 hover:text-red-400 transition-colors shrink-0" title="Delete">
                            <i class="fas fa-trash" style="font-size:10px"></i>
                        </button>
                    </div>
                    <!-- inline confirm bar (hidden by default) -->
                    <div class="confirm-bar hidden items-center justify-between px-2 py-1.5 text-[10px]" style="background:rgba(239,68,68,0.1);border-top:1px solid rgba(239,68,68,0.2)">
                        <span class="text-red-400">Delete?</span>
                        <div class="flex gap-1">
                            <button class="confirm-yes px-2 py-0.5 bg-red-500 text-white rounded-md hover:bg-red-600 transition-colors">Yes</button>
                            <button class="confirm-no px-2 py-0.5 text-white/50 rounded-md hover:bg-white/10 transition-colors" style="border:1px solid rgba(255,255,255,0.15)">No</button>
                        </div>
                    </div>`;

                // Use design image → open in garment preview
                wrap.querySelector('img').addEventListener('click', () => {
                    _useDesignFromModal(d.image_data);
                });

                // Delete: show inline confirm bar instead of browser confirm()
                wrap.querySelector('.delete-btn').addEventListener('click', e => {
                    e.stopPropagation();
                    const bar = wrap.querySelector('.confirm-bar');
                    bar.classList.remove('hidden');
                    bar.classList.add('flex');
                });
                wrap.querySelector('.confirm-no').addEventListener('click', e => {
                    e.stopPropagation();
                    const bar = wrap.querySelector('.confirm-bar');
                    bar.classList.add('hidden');
                    bar.classList.remove('flex');
                });
                wrap.querySelector('.confirm-yes').addEventListener('click', e => {
                    e.stopPropagation();
                    _deleteSavedDesign(d.id, wrap);
                });

                // Rename: toggle label ↔ input
                const label  = wrap.querySelector('.design-title-label');
                const input  = wrap.querySelector('.design-title-input');
                const renBtn = wrap.querySelector('.rename-btn');
                const startRename = () => {
                    label.classList.add('hidden');
                    input.classList.remove('hidden');
                    renBtn.querySelector('i').className = 'fas fa-check';
                    input.focus(); input.select();
                };
                const commitRename = () => {
                    const newTitle = input.value.trim() || label.textContent;
                    label.textContent = newTitle;
                    label.classList.remove('hidden');
                    input.classList.add('hidden');
                    renBtn.querySelector('i').className = 'fas fa-pencil-alt';
                    _renameSavedDesign(d.id, newTitle, label);
                };
                label.addEventListener('click', startRename);
                renBtn.addEventListener('click', e => {
                    e.stopPropagation();
                    if (!input.classList.contains('hidden')) { commitRename(); } else { startRename(); }
                });
                input.addEventListener('keydown', e => {
                    if (e.key === 'Enter') { e.preventDefault(); commitRename(); }
                    if (e.key === 'Escape') { input.value = label.textContent; commitRename(); }
                });
                input.addEventListener('blur', commitRename);

                grid.appendChild(wrap);
            });
        } catch(e) {
            loading.classList.add('hidden');
            grid.innerHTML = '<p class="col-span-4 text-sm text-red-400 text-center py-8">Could not load designs.</p>';
        }
    }
    function _useDesignFromModal(src) {
        closeMyDesignsModal();
        // Open preview modal with this design pre-loaded
        openPreviewModal(src);
    }
    async function _deleteSavedDesign(id, card) {
        try {
            const res = await fetch(`/designs/saved/${id}`, {
                method: 'DELETE',
                headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content }
            });
            if (res.ok) {
                card.remove();
                const grid = document.getElementById('my-designs-grid');
                if (!grid.children.length) {
                    const empty = document.getElementById('my-designs-empty');
                    empty.classList.remove('hidden');
                    empty.classList.add('flex');
                }
            }
        } catch(e) { adminConsole.error(e); }
    }

    async function _renameSavedDesign(id, title, labelEl) {
        try {
            await fetch(`/designs/saved/${id}`, {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content
                },
                body: JSON.stringify({ title })
            });
        } catch(e) { adminConsole.error(e); }
    }

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

    function _drawGenericGarment(ctx, color) {
        const dk = shadeColor(color, -20);
        ctx.fillStyle = color;
        ctx.beginPath();
        ctx.roundRect(120, 60, 260, 440, 24);
        ctx.fill();
        ctx.strokeStyle = dk;
        ctx.lineWidth = 2;
        ctx.stroke();
    }

    // Returns the print area rectangle (canvas pixels) for the given garment.
    function _computePrintArea(garmentKey) {
        const g = GARMENTS[garmentKey];
        return (_activeSide === 'back' && g.printAreaBack) ? g.printAreaBack : g.printArea;
    }

    // ── Printify SVG garment system ───────────────────────────────────────────
    // SVGs fetched once from our server, colored on the fly, drawn on canvas.
    // Print area coordinates derived from the exact SVG viewBox/translate values
    // (obtained via Printify API product views endpoint).
    const _garmentSvgCache = new Map();  // garmentKey → raw SVG text
    const _garmentImgCache = new Map();  // 'key|#color' → HTMLImageElement

    async function _loadGarmentSvgText(garmentKey, side = 'front') {
        const cKey = garmentKey + '|' + side;
        if (_garmentSvgCache.has(cKey)) return _garmentSvgCache.get(cKey);
        const url = side === 'back' ? GARMENTS[garmentKey]?.svgUrlBack : GARMENTS[garmentKey]?.svgUrl;
        if (!url) return null;
        try {
            const resp = await fetch(url);
            if (!resp.ok) return null;
            const text = await resp.text();
            _garmentSvgCache.set(cKey, text);
            return text;
        } catch(e) { return null; }
    }

    async function _getColoredGarmentImg(garmentKey, color, side = 'front') {
        const cKey = garmentKey + '|' + side + '|' + color;
        if (_garmentImgCache.has(cKey)) return _garmentImgCache.get(cKey);
        const svgText = await _loadGarmentSvgText(garmentKey, side);
        if (!svgText) return null;
        // Replace the fill of the garment body group (id="color_first" fill="#fff")
        const colored = svgText.replace(/(id="color_first"[^>]+fill=")[^"]*(")/g, '$1' + color + '$2');
        return new Promise(resolve => {
            const img = new Image();
            img.onload = () => { _garmentImgCache.set(cKey, img); resolve(img); };
            img.onerror = () => resolve(null);
            img.src = 'data:image/svg+xml;charset=utf-8,' + encodeURIComponent(colored);
        });
    }
    // ─────────────────────────────────────────────────────────────────────────

    const GARMENTS = {
        tshirt: {
            name:'T-Shirt', ref:'Gildan 5000', printPx:'3951 × 4919', printInches:'13.17" × 16.40"', dpi:300,
            printW:3951, printH:4919,
            // SVG from Printify API (viewBox 3527.65×3527.66, translate 1044.01 747.9, rect 1439.64×1823.54)
            // Canvas 500×550: SVG letterboxed to 500×500 (yOffset=25)
            svgUrl:'/images/garments/tshirt.svg',
            printArea:{ x:148, y:131, w:204, h:258 },
            svgUrlBack:'/images/garments/tshirt-back.svg',
            printAreaBack:{ x:155, y:84, w:190, h:218 },
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
            printW:3543, printH:4724,
            // SVG from Printify API (viewBox 4159.82×4159.82, translate 1468.06 1428.49, rect 1223.69×1035.56)
            // Print area widened & extended vertically to match actual chest safe zone (SVG rect was landscape/too short)
            svgUrl:'/images/garments/hoodie.svg',
            printArea:{ x:163, y:197, w:175, h:190 },
            svgUrlBack:'/images/garments/hoodie-back.svg',
            printAreaBack:{ x:165, y:182, w:170, h:185 },
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
            printW:3000, printH:4200,
            // SVG from Printify API (viewBox 3968.84×3968.84, translate 1264.6 1313.37, rect 1439.64×1823.54)
            svgUrl:'/images/garments/tanktop.svg',
            printArea:{ x:159, y:190, w:181, h:230 },
            svgUrlBack:'/images/garments/tanktop-back.svg',
            printAreaBack:{ x:159, y:135, w:181, h:230 },
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
            printW:3951, printH:4919,
            // SVG from Printify API (viewBox 3570.14×3570.14, translate 1066.94 646.78, rect 1439.64×1823.54)
            svgUrl:'/images/garments/longsleeve.svg',
            printArea:{ x:149, y:116, w:202, h:255 },
            svgUrlBack:'/images/garments/longsleeve-back.svg',
            printAreaBack:{ x:149, y:82, w:202, h:255 },
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
            printW:3543, printH:4724,
            // SVG from Printify API (viewBox 4156.44×4156.44, translate 1161.87 1199.03, rect 1832.61×1889.29)
            svgUrl:'/images/garments/sweatshirt.svg',
            printArea:{ x:140, y:169, w:220, h:227 },
            svgUrlBack:'/images/garments/sweatshirt-back.svg',
            printAreaBack:{ x:139, y:131, w:223, h:264 },
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
        leggings: {
            name:'Leggings', ref:'AOP Leggings', printPx:'3600 × 4800', printInches:'12.00" × 16.00"', dpi:300,
            printW:3600, printH:4800,
            svgUrl:'/images/garments/leggings.svg',
            printArea:{ x:145, y:92, w:210, h:360 },
            svgUrlBack:'/images/garments/leggings-back.svg',
            printAreaBack:{ x:145, y:92, w:210, h:360 },
            draw(ctx,color) { _drawGenericGarment(ctx, color); }
        },
        joggers: {
            name:'Joggers', ref:'Athletic Joggers (AOP)', printPx:'3600 × 4800', printInches:'12.00" × 16.00"', dpi:300,
            printW:3600, printH:4800,
            svgUrl:'/images/garments/joggers.svg',
            printArea:{ x:135, y:122, w:230, h:330 },
            svgUrlBack:'/images/garments/joggers-back.svg',
            printAreaBack:{ x:135, y:122, w:230, h:330 },
            draw(ctx,color) { _drawGenericGarment(ctx, color); }
        },
        shorts: {
            name:'Shorts', ref:'Shorts (AOP)', printPx:'3000 × 2400', printInches:'10.00" × 8.00"', dpi:300,
            printW:3000, printH:2400,
            svgUrl:'/images/garments/shorts.svg',
            printArea:{ x:130, y:200, w:240, h:210 },
            svgUrlBack:'/images/garments/shorts-back.svg',
            printAreaBack:{ x:130, y:200, w:240, h:210 },
            draw(ctx,color) { _drawGenericGarment(ctx, color); }
        },
        dresses: {
            name:'Vestidos', ref:'Racerback Dress (AOP)', printPx:'4200 × 5400', printInches:'14.00" × 18.00"', dpi:300,
            printW:4200, printH:5400,
            svgUrl:'/images/garments/dresses.svg',
            printArea:{ x:138, y:96, w:224, h:360 },
            svgUrlBack:'/images/garments/dresses-back.svg',
            printAreaBack:{ x:138, y:96, w:224, h:360 },
            draw(ctx,color) { _drawGenericGarment(ctx, color); }
        },
        skirts: {
            name:'Faldas', ref:'Skater Skirt (AOP)', printPx:'3600 × 3000', printInches:'12.00" × 10.00"', dpi:300,
            printW:3600, printH:3000,
            svgUrl:'/images/garments/skirts.svg',
            printArea:{ x:118, y:210, w:264, h:190 },
            svgUrlBack:'/images/garments/skirts-back.svg',
            printAreaBack:{ x:118, y:210, w:264, h:190 },
            draw(ctx,color) { _drawGenericGarment(ctx, color); }
        },
        bikinis: {
            name:'Bikinis / Swimwear', ref:'Bikini Swimsuit (AOP)', printPx:'3000 × 3000', printInches:'10.00" × 10.00"', dpi:300,
            printW:3000, printH:3000,
            svgUrl:'/images/garments/bikinis.svg',
            printArea:{ x:145, y:142, w:210, h:240 },
            svgUrlBack:'/images/garments/bikinis-back.svg',
            printAreaBack:{ x:145, y:142, w:210, h:240 },
            draw(ctx,color) { _drawGenericGarment(ctx, color); }
        },
        socks: {
            name:'Calcetines', ref:'Crew Socks', printPx:'1500 × 3000', printInches:'5.00" × 10.00"', dpi:300,
            printW:1500, printH:3000,
            svgUrl:'/images/garments/socks.svg',
            printArea:{ x:180, y:110, w:140, h:320 },
            svgUrlBack:'/images/garments/socks-back.svg',
            printAreaBack:{ x:180, y:110, w:140, h:320 },
            draw(ctx,color) { _drawGenericGarment(ctx, color); }
        },
        underwear: {
            name:'Ropa interior', ref:'Boxer Briefs (AOP)', printPx:'3000 × 2400', printInches:'10.00" × 8.00"', dpi:300,
            printW:3000, printH:2400,
            svgUrl:'/images/garments/underwear.svg',
            printArea:{ x:140, y:190, w:220, h:180 },
            svgUrlBack:'/images/garments/underwear-back.svg',
            printAreaBack:{ x:140, y:190, w:220, h:180 },
            draw(ctx,color) { _drawGenericGarment(ctx, color); }
        },
        pajamas: {
            name:'Pijamas', ref:'Satin Pajamas (AOP)', printPx:'4200 × 5400', printInches:'14.00" × 18.00"', dpi:300,
            printW:4200, printH:5400,
            svgUrl:'/images/garments/pajamas.svg',
            printArea:{ x:130, y:92, w:240, h:360 },
            svgUrlBack:'/images/garments/pajamas-back.svg',
            printAreaBack:{ x:130, y:92, w:240, h:360 },
            draw(ctx,color) { _drawGenericGarment(ctx, color); }
        },
        caps: {
            name:'Gorras', ref:'Low Profile Baseball Cap', printPx:'1800 × 1200', printInches:'6.00" × 4.00"', dpi:300,
            printW:1800, printH:1200,
            svgUrl:'/images/garments/caps.svg',
            printArea:{ x:185, y:175, w:130, h:90 },
            svgUrlBack:'/images/garments/caps-back.svg',
            printAreaBack:{ x:190, y:165, w:120, h:85 },
            draw(ctx,color) { _drawGenericGarment(ctx, color); }
        },
        beanies: {
            name:'Beanies', ref:'Cuff Beanie', printPx:'1800 × 1800', printInches:'6.00" × 6.00"', dpi:300,
            printW:1800, printH:1800,
            svgUrl:'/images/garments/beanies.svg',
            printArea:{ x:170, y:135, w:160, h:130 },
            svgUrlBack:'/images/garments/beanies-back.svg',
            printAreaBack:{ x:170, y:135, w:160, h:130 },
            draw(ctx,color) { _drawGenericGarment(ctx, color); }
        },
        tote_bags: {
            name:'Tote Bags', ref:'Cotton Tote Bag', printPx:'3600 × 4200', printInches:'12.00" × 14.00"', dpi:300,
            printW:3600, printH:4200,
            svgUrl:'/images/garments/tote_bags.svg',
            printArea:{ x:130, y:160, w:240, h:260 },
            svgUrlBack:'/images/garments/tote_bags-back.svg',
            printAreaBack:{ x:130, y:160, w:240, h:260 },
            draw(ctx,color) { _drawGenericGarment(ctx, color); }
        },
        scarves: {
            name:'Bufandas', ref:'Poly Scarf', printPx:'4800 × 1800', printInches:'16.00" × 6.00"', dpi:300,
            printW:4800, printH:1800,
            svgUrl:'/images/garments/scarves.svg',
            printArea:{ x:110, y:220, w:280, h:110 },
            svgUrlBack:'/images/garments/scarves-back.svg',
            printAreaBack:{ x:110, y:220, w:280, h:110 },
            draw(ctx,color) { _drawGenericGarment(ctx, color); }
        },
    };

    const _layers        = { front: [], back: [] }; // {side: [{id, src, posX, posY, scale, rotation, imgW, imgH}]}
    let _activeSide      = 'front';
    let _selectedLayerId = null;
    const _imgCache      = new Map(); // src → HTMLImageElement (already loaded)

    function _getOrLoadImage(src) {
        if (_imgCache.has(src)) return Promise.resolve(_imgCache.get(src));
        return new Promise((resolve, reject) => {
            const img = new Image();
            img.crossOrigin = 'anonymous';
            img.onload  = () => { _imgCache.set(src, img); resolve(img); };
            img.onerror = reject;
            img.src = src;
        });
    }

    function getSelectedLayer() {
        const layers = _layers[_activeSide];
        return layers.find(l => l.id === _selectedLayerId) || layers[0] || null;
    }

    function syncSelectedLayerFromControls() {
        const layer = getSelectedLayer();
        if (!layer) return;
        layer.posX     = parseFloat(document.getElementById('design-pos-x').value);
        layer.posY     = parseFloat(document.getElementById('design-pos-y').value);
        layer.scale    = parseFloat(document.getElementById('design-scale').value);
        layer.rotation = parseFloat(document.getElementById('design-rotation').value);
    }

    function updateControlsFromSelected() {
        const layer = getSelectedLayer();
        if (!layer) {
            ['design-pos-x','design-pos-y'].forEach(id => document.getElementById(id).value = 0);
            document.getElementById('design-scale').value    = 1;
            document.getElementById('design-rotation').value = 0;
            document.getElementById('pos-x-val').textContent    = '0';
            document.getElementById('pos-y-val').textContent    = '0';
            document.getElementById('scale-val').textContent    = '1.00';
            document.getElementById('rotation-val').textContent = '0°';
            return;
        }
        document.getElementById('design-pos-x').value    = layer.posX;
        document.getElementById('design-pos-y').value    = layer.posY;
        document.getElementById('design-scale').value    = layer.scale;
        document.getElementById('design-rotation').value = layer.rotation || 0;
        document.getElementById('pos-x-val').textContent    = parseFloat(layer.posX).toFixed(2);
        document.getElementById('pos-y-val').textContent    = parseFloat(layer.posY).toFixed(2);
        document.getElementById('scale-val').textContent    = parseFloat(layer.scale).toFixed(2);
        document.getElementById('rotation-val').textContent = Math.round(layer.rotation || 0) + '°';
    }

    function openPreviewModal(imageSrc) {
        const id = Date.now();
        _activeSide      = 'front';
        _layers.front    = [{id, src: imageSrc, posX: 0, posY: 0, scale: 1, rotation: 0, imgW: null, imgH: null}];
        _layers.back     = [];
        _selectedLayerId = id;
        document.getElementById('pos-x-val').textContent    = '0';
        document.getElementById('pos-y-val').textContent    = '0';
        document.getElementById('scale-val').textContent    = '1.00';
        document.getElementById('rotation-val').textContent = '0°';
        ['design-pos-x','design-pos-y','design-scale','design-rotation'].forEach((eid,i) => {
            document.getElementById(eid).value = i === 2 ? 1 : 0;
        });
        document.getElementById('preview-modal').classList.remove('hidden');
        document.getElementById('preview-modal').classList.add('flex');
        initDesignDrag();
        renderPreview();
        renderLayersList();
        loadSavedDesigns();
    }

    function closePreviewModal() {
        document.getElementById('preview-modal').classList.add('hidden');
        document.getElementById('preview-modal').classList.remove('flex');
        _layers.front    = [];
        _layers.back     = [];
        _activeSide      = 'front';
        _selectedLayerId = null;
    }

    function addLayerToCanvas(src) {
        const id = Date.now();
        _layers[_activeSide].push({id, src, posX: 0, posY: 0, scale: 1, rotation: 0, imgW: null, imgH: null});
        _selectedLayerId = id;
        updateControlsFromSelected();
        renderPreview();
        renderLayersList();
    }

    function selectLayer(id) {
        _selectedLayerId = id;
        updateControlsFromSelected();
        renderLayersList();
        renderHandles();
    }

    function removeLayer(id) {
        _layers[_activeSide] = _layers[_activeSide].filter(l => l.id !== id);
        if (_selectedLayerId === id) {
            const al = _layers[_activeSide];
            _selectedLayerId = al.length > 0 ? al[al.length - 1].id : null;
        }
        updateControlsFromSelected();
        renderPreview();
        renderLayersList();
    }

    function renderLayersList() {
        const container = document.getElementById('layers-list');
        const wrapper   = document.getElementById('layers-container');
        if (!container || !wrapper) return;
        const layers = _layers[_activeSide];
        if (layers.length <= 1) { wrapper.classList.add('hidden'); return; }
        wrapper.classList.remove('hidden');
        container.innerHTML = '';
        layers.forEach((layer, idx) => {
            const isSelected = layer.id === _selectedLayerId;
            const item = document.createElement('div');
            item.className = 'flex items-center gap-2 p-1.5 rounded-lg cursor-pointer border transition-all ' +
                (isSelected ? 'border-purple-400' : 'hover:border-purple-600/40') + (isSelected ? ' bg-purple-900/20' : '');
            item.style.borderColor = isSelected ? '' : 'rgba(255,255,255,0.08)';
            const thumb = document.createElement('img');
            thumb.src = layer.src; thumb.alt = '';
            thumb.className = 'w-8 h-8 rounded object-contain flex-shrink-0' + ' bg-white/[0.07]';
            const label = document.createElement('span');
            label.className = 'text-xs text-white/60 flex-1 min-w-0 truncate';
            label.textContent = 'Layer ' + (idx + 1);
            const del = document.createElement('button');
            del.type = 'button';
            del.className = 'w-5 h-5 text-red-400 hover:text-red-600 flex items-center justify-center flex-shrink-0 text-xs';
            del.innerHTML = '<i class="fas fa-times"></i>';
            del.onclick = (e) => { e.stopPropagation(); removeLayer(layer.id); };
            item.appendChild(thumb); item.appendChild(label); item.appendChild(del);
            item.onclick = () => selectLayer(layer.id);
            container.appendChild(item);
        });
    }

    async function getFlattenedSrc(layers) {
        layers = layers ?? _layers[_activeSide];
        if (!layers.length) return null;
        // Single non-rotated layer with HTTP URL: pass directly, Printify will fetch it
        if (layers.length === 1 && !layers[0].rotation && !layers[0].src.startsWith('data:')) return layers[0].src;
        const garment = GARMENTS[document.getElementById('garment-select').value];
        // Cap at 2000 px on the longest side to keep the POST body under PHP limits
        const MAX_DIM = 2000;
        const origW = garment.printW, origH = garment.printH;
        const dimScale = Math.min(1, MAX_DIM / Math.max(origW, origH));
        const pw = Math.round(origW * dimScale);
        const ph = Math.round(origH * dimScale);
        const flat = document.createElement('canvas');
        flat.width = pw; flat.height = ph;
        const ctx = flat.getContext('2d');
        for (const layer of layers) {
            await new Promise(resolve => {
                const img = new Image(); img.crossOrigin = 'anonymous';
                img.onload = () => {
                    const ir = img.width/img.height; const pr = pw/ph;
                    let dw, dh;
                    if (ir > pr) { dw = pw; dh = pw/ir; } else { dh = ph; dw = ph*ir; }
                    dw *= layer.scale; dh *= layer.scale;
                    const cx = pw/2 + layer.posX*(pw/2);
                    const cy = ph/2 + layer.posY*(ph/2);
                    ctx.save();
                    ctx.translate(cx, cy);
                    ctx.rotate((layer.rotation || 0) * Math.PI / 180);
                    ctx.drawImage(img, -dw/2, -dh/2, dw, dh);
                    ctx.restore();
                    resolve();
                };
                img.onerror = () => resolve();
                img.src = layer.src;
            });
        }
        return flat.toDataURL('image/png');
    }

    function renderGarment() {
        const canvas     = document.getElementById('garment-canvas');
        const ctx        = canvas.getContext('2d');
        const garmentKey = document.getElementById('garment-select').value;
        const garment    = GARMENTS[garmentKey];
        const color      = document.getElementById('garment-color').value;
        ctx.clearRect(0, 0, canvas.width, canvas.height);
        const sz = 15;
        for (let y = 0; y < canvas.height; y += sz)
            for (let x = 0; x < canvas.width; x += sz) {
                ctx.fillStyle = ((x/sz + y/sz) % 2 === 0) ? '#1e1e2e' : '#252538';
                ctx.fillRect(x, y, sz, sz);
            }
        // Draw garment: Printify SVG (colored) if cached, else vector fallback
        const cKey = garmentKey + '|' + _activeSide + '|' + color;
        if (_garmentImgCache.has(cKey)) {
            // SVG is square — letterbox to fit 500×500 centered in 500×550 (yOffset=25)
            const garmentSz = Math.min(canvas.width, canvas.height);
            const xOff = Math.round((canvas.width  - garmentSz) / 2);
            const yOff = Math.round((canvas.height - garmentSz) / 2);
            ctx.drawImage(_garmentImgCache.get(cKey), xOff, yOff, garmentSz, garmentSz);
        } else {
            garment.draw(ctx, color); // vector fallback while SVG loads
            _getColoredGarmentImg(garmentKey, color, _activeSide).then(img => {
                if (img) renderGarment(); // re-render with the loaded SVG
            });
        }
        // Print area overlay (coordinates from Printify SVG analysis)
        const pa = _computePrintArea(garmentKey);
        ctx.setLineDash([6,4]); ctx.strokeStyle='rgba(168,85,247,0.7)'; ctx.lineWidth=1.5;
        ctx.strokeRect(pa.x, pa.y, pa.w, pa.h); ctx.setLineDash([]);
        ctx.font='9px sans-serif'; ctx.fillStyle='rgba(168,85,247,0.8)';
        ctx.fillText('Print area', pa.x+2, pa.y-3);
        const specEl = document.getElementById('printify-spec');
        if (specEl) specEl.innerHTML = `<span class="text-purple-400 font-medium">${garment.ref}</span> — ${garment.printPx} px · ${garment.printInches} · ${garment.dpi} DPI`;
    }

    function positionDesignCanvas() {
        const garmentKey = document.getElementById('garment-select').value;
        const canvas     = document.getElementById('garment-canvas');
        const pa = _computePrintArea(garmentKey);
        const dc = document.getElementById('design-canvas');
        const hc = document.getElementById('handle-canvas');
        dc.width = pa.w; dc.height = pa.h;
        dc.style.left   = (pa.x / canvas.width  * 100) + '%';
        dc.style.top    = (pa.y / canvas.height * 100) + '%';
        dc.style.width  = (pa.w / canvas.width  * 100) + '%';
        dc.style.height = (pa.h / canvas.height * 100) + '%';
        if (hc) {
            hc.width = pa.w; hc.height = pa.h;
            hc.style.left   = (pa.x / canvas.width  * 100) + '%';
            hc.style.top    = (pa.y / canvas.height * 100) + '%';
            hc.style.width  = (pa.w / canvas.width  * 100) + '%';
            hc.style.height = (pa.h / canvas.height * 100) + '%';
        }
    }

    function renderDesign() {
        const layers = _layers[_activeSide];
        if (!layers.length) return;
        const dc  = document.getElementById('design-canvas');
        const ctx = dc.getContext('2d');
        const pa  = _computePrintArea(document.getElementById('garment-select').value);

        // If all images are already cached, draw synchronously (no flicker during drag)
        const allCached = layers.every(l => _imgCache.has(l.src));
        if (allCached) {
            ctx.clearRect(0, 0, dc.width, dc.height);
            layers.forEach(layer => {
                const img = _imgCache.get(layer.src);
                layer.imgW = img.width; layer.imgH = img.height;
                const ir = img.width / img.height; const pr = pa.w / pa.h;
                let dw, dh;
                if (ir > pr) { dw = pa.w; dh = pa.w/ir; } else { dh = pa.h; dw = pa.h*ir; }
                dw *= layer.scale; dh *= layer.scale;
                const cx = pa.w/2 + layer.posX*(pa.w/2);
                const cy = pa.h/2 + layer.posY*(pa.h/2);
                ctx.save();
                ctx.translate(cx, cy);
                ctx.rotate((layer.rotation || 0) * Math.PI / 180);
                ctx.drawImage(img, -dw/2, -dh/2, dw, dh);
                ctx.restore();
            });
            renderHandles();
            return;
        }

        // First render or new layer: load uncached images then redraw
        const drawLayer = (layer) => _getOrLoadImage(layer.src).then(img => {
            layer.imgW = img.width; layer.imgH = img.height;
            const ir = img.width/img.height; const pr = pa.w/pa.h;
            let dw, dh;
            if (ir > pr) { dw = pa.w; dh = pa.w/ir; } else { dh = pa.h; dw = pa.h*ir; }
            dw *= layer.scale; dh *= layer.scale;
            const cx = pa.w/2 + layer.posX*(pa.w/2);
            const cy = pa.h/2 + layer.posY*(pa.h/2);
            ctx.save();
            ctx.translate(cx, cy);
            ctx.rotate((layer.rotation || 0) * Math.PI / 180);
            ctx.drawImage(img, -dw/2, -dh/2, dw, dh);
            ctx.restore();
        });
        ctx.clearRect(0, 0, dc.width, dc.height);
        layers.reduce((p, layer) => p.then(() => drawLayer(layer)), Promise.resolve())
            .then(() => renderHandles());
    }

    function renderPreview() { renderGarment(); positionDesignCanvas(); renderDesign(); }

    function downloadPreview() {
        const gc = document.getElementById('garment-canvas');
        const dc = document.getElementById('design-canvas');
        const pa = _computePrintArea(document.getElementById('garment-select').value);
        const tmp = document.createElement('canvas');
        tmp.width = gc.width; tmp.height = gc.height;
        const ctx = tmp.getContext('2d');
        try { ctx.drawImage(gc, 0, 0); } catch(e) { /* tainted canvas: skip garment */ }
        ctx.drawImage(dc, pa.x, pa.y, pa.w, pa.h);
        const link = document.createElement('a');
        link.download = 'garment-preview.png'; link.href = tmp.toDataURL('image/png'); link.click();
    }

    const ROTATE_HANDLE_OFFSET = 22;
    const ROTATE_HANDLE_RADIUS = 6;

    function getLayerHandlePos(layer) {
        const pa = _computePrintArea(document.getElementById('garment-select').value);
        const ir = (layer.imgW && layer.imgH) ? layer.imgW / layer.imgH : 1;
        const pr = pa.w / pa.h;
        let dw, dh;
        if (ir > pr) { dw = pa.w; dh = pa.w/ir; } else { dh = pa.h; dw = pa.h*ir; }
        dw *= layer.scale; dh *= layer.scale;
        const cx  = pa.w/2 + layer.posX*(pa.w/2);
        const cy  = pa.h/2 + layer.posY*(pa.h/2);
        const rot = (layer.rotation || 0) * Math.PI / 180;
        const dist = dh/2 + ROTATE_HANDLE_OFFSET;
        // Rotate local (0, -dist) by rot around (cx, cy)
        const hx = cx + dist * Math.sin(rot);
        const hy = cy - dist * Math.cos(rot);
        return { cx, cy, dw, dh, rot, hx, hy };
    }

    function renderHandles() {
        const hc  = document.getElementById('handle-canvas');
        if (!hc) return;
        const ctx = hc.getContext('2d');
        ctx.clearRect(0, 0, hc.width, hc.height);
        const layer = getSelectedLayer();
        if (!layer || !layer.imgW) return;

        const { cx, cy, dw, dh, rot, hx, hy } = getLayerHandlePos(layer);

        ctx.save();
        ctx.translate(cx, cy);
        ctx.rotate(rot);

        // Dashed bounding box
        ctx.strokeStyle = 'rgba(124,60,160,0.85)';
        ctx.lineWidth = 1.5;
        ctx.setLineDash([5, 3]);
        ctx.strokeRect(-dw/2, -dh/2, dw, dh);
        ctx.setLineDash([]);

        // Corner handles
        [[-dw/2,-dh/2],[dw/2,-dh/2],[dw/2,dh/2],[-dw/2,dh/2]].forEach(([x,y]) => {
            ctx.beginPath(); ctx.arc(x, y, 4, 0, Math.PI*2);
            ctx.fillStyle = '#fff'; ctx.fill();
            ctx.strokeStyle = '#7c3ca0'; ctx.lineWidth = 1.5; ctx.stroke();
        });

        // Stem line to rotation handle
        ctx.strokeStyle = 'rgba(124,60,160,0.7)';
        ctx.lineWidth = 1.5;
        ctx.beginPath();
        ctx.moveTo(0, -dh/2);
        ctx.lineTo(0, -dh/2 - ROTATE_HANDLE_OFFSET);
        ctx.stroke();

        // Rotation handle circle
        ctx.beginPath();
        ctx.arc(0, -dh/2 - ROTATE_HANDLE_OFFSET, ROTATE_HANDLE_RADIUS, 0, Math.PI*2);
        ctx.fillStyle = '#7c3ca0'; ctx.fill();
        ctx.strokeStyle = '#fff'; ctx.lineWidth = 1.5; ctx.stroke();

        // Rotation arrow inside circle
        ctx.strokeStyle = '#fff';
        ctx.lineWidth = 1.3;
        ctx.beginPath();
        ctx.arc(0, -dh/2 - ROTATE_HANDLE_OFFSET, 3.2, -2.3, 0.9);
        ctx.stroke();
        // Small arrowhead at arc end
        const ae = 0.9;
        const ax = 3.2 * Math.cos(ae), ay = (-dh/2 - ROTATE_HANDLE_OFFSET) + 3.2 * Math.sin(ae);
        ctx.beginPath();
        ctx.moveTo(ax, ay);
        ctx.lineTo(ax + 2.5*Math.cos(ae + Math.PI*0.55), ay + 2.5*Math.sin(ae + Math.PI*0.55));
        ctx.stroke();
        ctx.beginPath();
        ctx.moveTo(ax, ay);
        ctx.lineTo(ax + 2.5*Math.cos(ae - Math.PI*0.55), ay + 2.5*Math.sin(ae - Math.PI*0.55));
        ctx.stroke();

        ctx.restore();
    }

    let _dragInitialized = false;
    function initDesignDrag() {
        if (_dragInitialized) return; _dragInitialized = true;
        const hc = document.getElementById('handle-canvas');
        if (!hc) return;

        let mode          = null; // 'drag' | 'rotate'
        let lastX = 0, lastY = 0;
        let rotStartAngle = 0, rotStartRot = 0;
        let _rafPending   = false;

        function getCanvasPt(clientX, clientY) {
            const pa   = _computePrintArea(document.getElementById('garment-select').value);
            const rect = hc.getBoundingClientRect();
            return {
                x: (clientX - rect.left) * (pa.w / rect.width),
                y: (clientY - rect.top)  * (pa.h / rect.height),
            };
        }

        function nearRotHandle(mx, my) {
            const layer = getSelectedLayer();
            if (!layer || !layer.imgW) return false;
            const { hx, hy } = getLayerHandlePos(layer);
            return Math.sqrt((mx - hx)**2 + (my - hy)**2) <= ROTATE_HANDLE_RADIUS + 6;
        }

        const onStart = (clientX, clientY) => {
            const {x, y} = getCanvasPt(clientX, clientY);
            const layer = getSelectedLayer();
            if (!layer) return;
            if (nearRotHandle(x, y)) {
                mode = 'rotate';
                const { cx, cy } = getLayerHandlePos(layer);
                rotStartAngle = Math.atan2(y - cy, x - cx);
                rotStartRot   = layer.rotation || 0;
                hc.style.cursor = 'crosshair';
            } else {
                mode = 'drag';
                lastX = clientX; lastY = clientY;
                hc.style.cursor = 'grabbing';
            }
        };

        const onMove = (clientX, clientY) => {
            const {x, y} = getCanvasPt(clientX, clientY);
            if (!mode) {
                hc.style.cursor = nearRotHandle(x, y) ? 'crosshair' : 'grab';
                return;
            }
            const layer = getSelectedLayer();
            if (!layer) return;

            if (mode === 'drag') {
                const pa   = _computePrintArea(document.getElementById('garment-select').value);
                const rect = hc.getBoundingClientRect();
                const sx = pa.w / rect.width; const sy = pa.h / rect.height;
                const dx = (clientX - lastX) * sx; const dy = (clientY - lastY) * sy;
                lastX = clientX; lastY = clientY;
                layer.posX = Math.max(-1, Math.min(1, layer.posX + dx * 2 / pa.w));
                layer.posY = Math.max(-1, Math.min(1, layer.posY + dy * 2 / pa.h));
                document.getElementById('design-pos-x').value = layer.posX;
                document.getElementById('design-pos-y').value = layer.posY;
                document.getElementById('pos-x-val').textContent = parseFloat(layer.posX).toFixed(2);
                document.getElementById('pos-y-val').textContent = parseFloat(layer.posY).toFixed(2);
            } else if (mode === 'rotate') {
                const { cx, cy } = getLayerHandlePos(layer);
                const angle = Math.atan2(y - cy, x - cx);
                const delta = angle - rotStartAngle;
                layer.rotation = rotStartRot + delta * 180 / Math.PI;
                document.getElementById('design-rotation').value = Math.round(layer.rotation);
                document.getElementById('rotation-val').textContent = Math.round(layer.rotation) + '°';
            }
            if (!_rafPending) {
                _rafPending = true;
                requestAnimationFrame(() => { _rafPending = false; renderDesign(); });
            }
        };

        const onEnd = () => { mode = null; hc.style.cursor = 'grab'; };

        hc.addEventListener('mousedown',  e => { e.preventDefault(); onStart(e.clientX, e.clientY); });
        document.addEventListener('mousemove', e => onMove(e.clientX, e.clientY));
        document.addEventListener('mouseup',   () => onEnd());
        hc.addEventListener('touchstart', e => { e.preventDefault(); onStart(e.touches[0].clientX, e.touches[0].clientY); }, {passive:false});
        document.addEventListener('touchmove', e => { if (mode) { e.preventDefault(); onMove(e.touches[0].clientX, e.touches[0].clientY); } }, {passive:false});
        document.addEventListener('touchend', () => onEnd());

        // Scroll-wheel zoom
        hc.addEventListener('wheel', e => {
            e.preventDefault();
            const layer = getSelectedLayer();
            if (!layer) return;
            const delta = e.deltaY < 0 ? 0.06 : -0.06;
            layer.scale = Math.max(0.2, Math.min(2, (layer.scale || 1) + delta));
            const scaleSlider = document.getElementById('design-scale');
            const scaleVal    = document.getElementById('scale-val');
            if (scaleSlider) scaleSlider.value = layer.scale;
            if (scaleVal)    scaleVal.textContent = layer.scale.toFixed(2);
            requestAnimationFrame(() => renderDesign());
        }, {passive: false});

        // Pinch-to-zoom (two-finger touch)
        let _pinchDist0 = null, _pinchScale0 = null;
        hc.addEventListener('touchstart', e => {
            if (e.touches.length === 2) {
                const dx = e.touches[0].clientX - e.touches[1].clientX;
                const dy = e.touches[0].clientY - e.touches[1].clientY;
                _pinchDist0  = Math.sqrt(dx*dx + dy*dy);
                _pinchScale0 = getSelectedLayer()?.scale || 1;
            }
        }, {passive: true});
        document.addEventListener('touchmove', e => {
            if (e.touches.length === 2 && _pinchDist0) {
                const dx   = e.touches[0].clientX - e.touches[1].clientX;
                const dy   = e.touches[0].clientY - e.touches[1].clientY;
                const dist = Math.sqrt(dx*dx + dy*dy);
                const layer = getSelectedLayer();
                if (layer) {
                    layer.scale = Math.max(0.2, Math.min(2, _pinchScale0 * (dist / _pinchDist0)));
                    const sl = document.getElementById('design-scale');
                    const sv = document.getElementById('scale-val');
                    if (sl) sl.value = layer.scale;
                    if (sv) sv.textContent = layer.scale.toFixed(2);
                    requestAnimationFrame(() => renderDesign());
                }
            }
        }, {passive: true});
        document.addEventListener('touchend', () => { _pinchDist0 = null; _pinchScale0 = null; });
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
        if (!shopId)               { showPrintifyFeedback('Please select a Printify shop.'); return; }
        if (!title)                { showPrintifyFeedback('Please enter a product name.'); return; }
        if (!_layers.front.length) { showPrintifyFeedback('No design loaded in preview.'); return; }
        const frontLayers  = _layers.front;
        const backLayers   = _layers.back;
        const selFront     = frontLayers[0] || null;
        const isBakedFront = frontLayers.length > 1 || !!(selFront?.rotation);
        const posX = selFront?.posX  ?? 0;
        const posY = selFront?.posY  ?? 0;
        const sc   = selFront?.scale ?? 1;
        btn.disabled = true; btn.textContent = 'Preparing…'; resetPrintifyFeedback();
        try {
            const imageSrc     = await getFlattenedSrc(frontLayers);
            const backImageSrc = backLayers.length ? await getFlattenedSrc(backLayers) : null;
            const selBack      = backLayers[0] || null;
            const isBakedBack  = backLayers.length > 1 || !!(selBack?.rotation);
            const bPosX = selBack?.posX  ?? 0;
            const bPosY = selBack?.posY  ?? 0;
            const bSc   = selBack?.scale ?? 1;
            btn.textContent = 'Creating product…';
            const csrf = document.querySelector('meta[name="csrf-token"]').content;
            const payload = {
                shop_id:parseInt(shopId), garment_type:type, image_source:imageSrc, title,
                color:hexToColorName(document.getElementById('printify-color-hex').value),
                pos_x:        isBakedFront ? 0.5 : 0.5+posX*0.5,
                pos_y:        isBakedFront ? 0.5 : 0.5+posY*0.5,
                design_scale: isBakedFront ? 1   : sc,
                publish_after_create: document.getElementById('printify-publish')?.checked ?? false,
            };
            if (backImageSrc) {
                payload.back_image_source = backImageSrc;
                payload.back_pos_x        = isBakedBack ? 0.5 : 0.5+bPosX*0.5;
                payload.back_pos_y        = isBakedBack ? 0.5 : 0.5+bPosY*0.5;
                payload.back_design_scale = isBakedBack ? 1   : bSc;
            }
            const res  = await fetch('/printify/products', {
                method:'POST',
                headers:{ 'Content-Type':'application/json','X-CSRF-TOKEN':csrf,'Accept':'application/json' },
                body: JSON.stringify(payload),
            });
            const data = await res.json().catch(() => { throw new Error(`Server error (HTTP ${res.status}). Check server logs.`); });
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
        if (!shopId || !title || !_layers.front.length) {
            showPrintifyFeedback('Please fill in all fields and load a design.'); return;
        }
        const garments = [
            {type:'tshirt',    label:'T-Shirt'},
            {type:'hoodie',    label:'Hoodie'},
            {type:'zip_hoodie',label:'Zip Hoodie'},
            {type:'tanktop',   label:'Tank Top'},
            {type:'longsleeve',label:'Long Sleeve'},
            {type:'sweatshirt',label:'Sweatshirt'},
            {type:'vneck',     label:'V-Neck Tee'},
            {type:'womens_tee',label:"Women's Tee"},
            {type:'leggings',  label:'Leggings'},
            {type:'joggers',   label:'Joggers'},
            {type:'shorts',    label:'Shorts'},
            {type:'dresses',   label:'Vestidos'},
            {type:'skirts',    label:'Faldas'},
            {type:'bikinis',   label:'Bikinis / Swimwear'},
            {type:'socks',     label:'Calcetines'},
            {type:'underwear', label:'Ropa interior'},
            {type:'pajamas',   label:'Pijamas'},
            {type:'caps',      label:'Gorras'},
            {type:'beanies',   label:'Beanies'},
            {type:'tote_bags', label:'Tote Bags'},
            {type:'scarves',   label:'Bufandas'},
        ];
        btn.disabled = true; send.disabled = true; resetPrintifyFeedback();
        const frontLayers  = _layers.front;
        const backLayers   = _layers.back;
        const imageSrc     = await getFlattenedSrc(frontLayers);
        const backImageSrc = backLayers.length ? await getFlattenedSrc(backLayers) : null;
        const selFront     = frontLayers[0] || null;
        const isBakedFront = frontLayers.length > 1 || !!(selFront?.rotation);
        const posX = !isBakedFront ? (selFront?.posX  ?? 0) : 0;
        const posY = !isBakedFront ? (selFront?.posY  ?? 0) : 0;
        const sc   = !isBakedFront ? (selFront?.scale ?? 1) : 1;
        const selBack      = backLayers[0] || null;
        const isBakedBack  = backLayers.length > 1 || !!(selBack?.rotation);
        const bPosX = !isBakedBack ? (selBack?.posX  ?? 0) : 0;
        const bPosY = !isBakedBack ? (selBack?.posY  ?? 0) : 0;
        const bSc   = !isBakedBack ? (selBack?.scale ?? 1) : 1;
        const csrf = document.querySelector('meta[name="csrf-token"]').content;
        const resultLines = [];
        const renderProgress = (cur, curLabel) => {
            const pct = Math.round((cur/garments.length)*100);
            showPrintifyFeedback(`<div class="space-y-2">
                <div class="flex justify-between text-xs text-white/30 mb-0.5">
                    <span>${cur < garments.length ? 'Uploading '+curLabel+'…' : 'Done'}</span>
                    <span>${cur}/${garments.length}</span>
                </div>
                <div class="w-full rounded h-1.5" style="background:rgba(255,255,255,0.08)">
                    <div class="bg-purple-600 h-1.5 rounded transition-all duration-300" style="width:${pct}%"></div>
                </div>
                <div class="space-y-0.5 pt-1">${resultLines.join('')}</div></div>`);
        };
        for (let i = 0; i < garments.length; i++) {
            const {type,label} = garments[i];
            renderProgress(i, label);
            try {
                const payload = {
                    shop_id:parseInt(shopId), garment_type:type, image_source:imageSrc,
                    title:title+' — '+label,
                    color:hexToColorName(document.getElementById('printify-color-hex').value),
                    pos_x:0.5+posX*0.5, pos_y:0.5+posY*0.5, design_scale:sc,
                    publish_after_create: document.getElementById('printify-publish')?.checked ?? false,
                };
                if (backImageSrc) {
                    payload.back_image_source = backImageSrc;
                    payload.back_pos_x        = 0.5+bPosX*0.5;
                    payload.back_pos_y        = 0.5+bPosY*0.5;
                    payload.back_design_scale = bSc;
                }
                const res  = await fetch('/printify/products', {
                    method:'POST',
                    headers:{'Content-Type':'application/json','X-CSRF-TOKEN':csrf,'Accept':'application/json'},
                    body: JSON.stringify(payload),
                });
                const data = await res.json().catch(() => { throw new Error(`Server error (HTTP ${res.status})`); });
                if (!res.ok || !data.success) throw new Error(data.error || `HTTP ${res.status}`);
                resultLines.push(`<div class="text-green-700 text-xs">✓ ${label} — <a href="${data.printify_url}" target="_blank" rel="noopener noreferrer" class="underline font-medium">Open →</a></div>`);
            } catch (err) {
                resultLines.push(`<div class="text-red-600 text-xs">✗ ${label}: ${escapeHtml(err.message)}</div>`);
            }
        }
        renderProgress(garments.length, '');
        btn.disabled = false; send.disabled = false; btn.textContent = 'Upload to All';
    }

    // ═══════════════════════════════════════════════════════════════
    //  BULK UPLOAD MODAL
    // ═══════════════════════════════════════════════════════════════
    let _bulkUploadImageSrc = null;
    let _bulkShopsLoaded    = false;

    async function openBulkUploadModal(imageSrc) {
        _bulkUploadImageSrc = imageSrc;
        // Reset UI to form state
        document.getElementById('bulk-form-section').classList.remove('hidden');
        document.getElementById('bulk-progress-section').classList.add('hidden');
        document.getElementById('bulk-start-btn').disabled = false;
        document.getElementById('bulk-start-btn').innerHTML = '<i class="fas fa-cloud-upload-alt mr-1"></i> Upload All';
        document.getElementById('bulk-cancel-btn').textContent = 'Cancel';
        document.getElementById('bulk-modal-close-btn').disabled = false;
        document.getElementById('bulk-progress-results').innerHTML = '';
        document.getElementById('bulk-progress-bar').style.width = '0%';
        const bulkPublish = document.getElementById('bulk-publish');
        if (bulkPublish) bulkPublish.checked = false;

        // Pre-fill title from current design
        const existing = document.getElementById('printify-title')?.value;
        document.getElementById('bulk-title').value = existing || 'FabricAI — My Design';

        // Mirror garment color
        const hexSrc = document.getElementById('garment-color')?.value || '#ffffff';
        document.getElementById('bulk-color-hex').value = hexSrc;
        document.getElementById('bulk-color-name').textContent = hexToColorName(hexSrc);

        // Wire up all-colors toggle
        const allColorsChk  = document.getElementById('bulk-all-colors');
        const allColorsNote = document.getElementById('bulk-all-colors-note');
        const colorRow      = document.getElementById('bulk-color-hex')?.closest('.flex.flex-col.gap-1');
        if (allColorsChk) {
            allColorsChk.checked = false;
            if (allColorsNote) allColorsNote.classList.add('hidden');
            if (colorRow) { colorRow.style.opacity = '1'; colorRow.style.pointerEvents = ''; }
            allColorsChk.onchange = () => {
                const on = allColorsChk.checked;
                if (allColorsNote) allColorsNote.classList.toggle('hidden', !on);
                if (colorRow) colorRow.style.opacity = on ? '0.35' : '1';
                if (colorRow) colorRow.style.pointerEvents = on ? 'none' : '';
            };
        }

        // Show modal
        const modal = document.getElementById('bulk-upload-modal');
        modal.classList.remove('hidden');
        modal.classList.add('flex');

        // Load shops
        await _loadBulkShops();
    }

    function closeBulkUploadModal() {
        const modal = document.getElementById('bulk-upload-modal');
        modal.classList.add('hidden');
        modal.classList.remove('flex');
        _bulkUploadImageSrc = null;
    }

    async function _loadBulkShops() {
        if (_bulkShopsLoaded) return;
        const sel = document.getElementById('bulk-shop');
        sel.innerHTML = '<option value="">Loading…</option>';
        try {
            const statusRes = await fetch('/printify/status', { headers: { 'Accept': 'application/json' } });
            const status    = await statusRes.json();
            if (!status.connected) {
                sel.innerHTML = '<option value="">Not connected</option>'; return;
            }
            const res   = await fetch('/printify/shops', { headers: { 'Accept': 'application/json' } });
            const shops = await res.json();
            if (!res.ok || !Array.isArray(shops) || !shops.length) {
                sel.innerHTML = '<option value="">No shops found</option>'; return;
            }
            sel.innerHTML = shops.map(s => `<option value="${s.id}">${escapeHtml(s.name)}</option>`).join('');
            _bulkShopsLoaded = true;
        } catch (err) {
            sel.innerHTML = `<option value="">Error: ${escapeHtml(err.message)}</option>`;
        }
    }

    async function startBulkUpload() {
        const shopId    = document.getElementById('bulk-shop').value;
        const title     = document.getElementById('bulk-title').value.trim();
        const allColors = document.getElementById('bulk-all-colors')?.checked ?? false;
        const color     = allColors ? '' : hexToColorName(document.getElementById('bulk-color-hex').value);
        if (!shopId)  { alert('Please select a Printify store.'); return; }
        if (!title)   { alert('Please enter a product name.'); return; }
        if (!_bulkUploadImageSrc) { alert('No image selected.'); return; }

        const garments = [
            {type:'tshirt',    label:'T-Shirt'},
            {type:'hoodie',    label:'Hoodie'},
            {type:'zip_hoodie',label:'Zip Hoodie'},
            {type:'tanktop',   label:'Tank Top'},
            {type:'longsleeve',label:'Long Sleeve'},
            {type:'sweatshirt',label:'Sweatshirt'},
            {type:'vneck',     label:'V-Neck Tee'},
            {type:'womens_tee',label:"Women's Tee"},
            {type:'leggings',  label:'Leggings'},
            {type:'joggers',   label:'Joggers'},
            {type:'shorts',    label:'Shorts'},
            {type:'dresses',   label:'Vestidos'},
            {type:'skirts',    label:'Faldas'},
            {type:'bikinis',   label:'Bikinis / Swimwear'},
            {type:'socks',     label:'Calcetines'},
            {type:'underwear', label:'Ropa interior'},
            {type:'pajamas',   label:'Pijamas'},
            {type:'caps',      label:'Gorras'},
            {type:'beanies',   label:'Beanies'},
            {type:'tote_bags', label:'Tote Bags'},
            {type:'scarves',   label:'Bufandas'},
        ];

        // Switch to progress view
        document.getElementById('bulk-form-section').classList.add('hidden');
        document.getElementById('bulk-progress-section').classList.remove('hidden');
        document.getElementById('bulk-start-btn').disabled = true;
        document.getElementById('bulk-cancel-btn').disabled = true;
        document.getElementById('bulk-modal-close-btn').disabled = true;

        const csrf = document.querySelector('meta[name="csrf-token"]').content;
        let successCount = 0;
        let errorCount   = 0;

        const updateProgress = (cur, curLabel) => {
            const pct    = Math.round((cur / garments.length) * 100);
            const isDone = cur >= garments.length;
            document.getElementById('bulk-progress-bar').style.width   = pct + '%';
            document.getElementById('bulk-progress-count').textContent = `${cur}/${garments.length}`;
            document.getElementById('bulk-progress-label').textContent =
                isDone ? 'Done!' : `Uploading ${curLabel}…`;
        };

        for (let i = 0; i < garments.length; i++) {
            const {type, label} = garments[i];
            updateProgress(i, label);
            try {
                const res  = await fetch('/printify/products', {
                    method: 'POST',
                    headers: {
                        'Content-Type':  'application/json',
                        'X-CSRF-TOKEN':  csrf,
                        'Accept':        'application/json',
                    },
                    body: JSON.stringify({
                        shop_id:      parseInt(shopId),
                        garment_type: type,
                        image_source: _bulkUploadImageSrc,
                        title:        title + ' — ' + label,
                        color:        color,
                        pos_x:        0.5,
                        pos_y:        0.5,
                        design_scale: 1,
                        publish_after_create: document.getElementById('bulk-publish')?.checked ?? false,
                    }),
                });
                const data = await res.json().catch(() => { throw new Error(`Server error (HTTP ${res.status})`); });
                if (!res.ok || !data.success) throw new Error(data.error || `HTTP ${res.status}`);
                successCount++;
            } catch (err) {
                errorCount++;
                adminConsole.warn('Bulk upload error for', type, err.message);
            }
        }
        updateProgress(garments.length, '');

        // Show summary
        const resultsEl = document.getElementById('bulk-progress-results');
        if (successCount > 0) {
            resultsEl.innerHTML = `
                <div class="flex flex-col items-center gap-2 pt-2 text-center">
                    <div class="flex items-center gap-1.5 text-green-700 text-xs font-medium">
                        <i class="fas fa-check-circle"></i>
                        <span>${successCount} product${successCount > 1 ? 's' : ''} created${errorCount > 0 ? ` (${errorCount} failed)` : ''}</span>
                    </div>
                    <a href="https://printify.com/app/store/products" target="_blank" rel="noopener noreferrer"
                       class="px-4 py-2 bg-[#7c3ca0] text-white text-xs font-medium rounded-xl hover:bg-[#5a2275] transition-colors flex items-center gap-1.5">
                        <i class="fas fa-external-link-alt text-[10px]"></i> View in Printify
                    </a>
                </div>`;
        } else {
            resultsEl.innerHTML = `<p class="text-xs text-red-600 text-center pt-2">All uploads failed. Please try again.</p>`;
        }

        // Re-enable close
        document.getElementById('bulk-cancel-btn').disabled = false;
        document.getElementById('bulk-modal-close-btn').disabled = false;
        document.getElementById('bulk-cancel-btn').textContent = 'Close';
    }

    function switchSide(side) {
        if (_activeSide === side) return;
        _activeSide = side;
        _selectedLayerId = null;
        // Update tab button styles
        const frontBtn = document.getElementById('side-front-btn');
        const backBtn  = document.getElementById('side-back-btn');
        [frontBtn, backBtn].forEach((btn, i) => {
            if (!btn) return;
            const isActive = (i === 0 && side === 'front') || (i === 1 && side === 'back');
            btn.className = `flex-1 py-1.5 text-xs font-medium rounded-lg border transition-colors ${
                isActive ? 'bg-[#7c3ca0] text-white border-[#7c3ca0]' : 'text-white/40 hover:text-white' + ' rounded-lg px-2 py-1 text-xs'
            }`;
        });
        updateControlsFromSelected();
        renderPreview();
        renderLayersList();
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
        // Preload garment SVGs in the background so first render is instant
        const _initialColor = document.getElementById('garment-color')?.value || '#ffffff';
        Object.keys(GARMENTS).forEach(k => {
            _getColoredGarmentImg(k, _initialColor);          // warm front cache
            _getColoredGarmentImg(k, _initialColor, 'back');  // warm back cache
        });
        const gc=document.getElementById('garment-color');
        if (gc) gc.addEventListener('input', e => {
            const p=document.getElementById('printify-color-hex');
            const n=document.getElementById('printify-color-name');
            if (p) p.value=e.target.value;
            if (n) n.textContent=hexToColorName(e.target.value);
        });
    });

    // ═══════════════════════════════════════════════════════════════
    //  SAVED DESIGNS
    // ═══════════════════════════════════════════════════════════════
    let _selectedSavedDesign = null;

    async function loadSavedDesigns() {
        const list = document.getElementById('saved-designs-list');
        if (!list) return;
        list.innerHTML = '<p class="text-[10px] text-white/30 text-center py-6 leading-relaxed">Loading…</p>';
        try {
            const res  = await fetch('/designs/saved', { headers: { 'Accept': 'application/json' } });
            const data = await res.json();
            renderSavedDesignsList(Array.isArray(data) ? data : []);
        } catch (e) {
            list.innerHTML = '<p class="text-[10px] text-red-500 text-center py-4">Error loading</p>';
        }
    }

    function _setAddBtnState(enabled) {
        ['add-to-canvas-btn', 'add-to-canvas-btn-mobile'].forEach(id => {
            const btn = document.getElementById(id);
            if (btn) btn.disabled = !enabled;
        });
    }

    function renderSavedDesignsList(designs) {
        const list   = document.getElementById('saved-designs-list');
        _selectedSavedDesign = null;
        _setAddBtnState(false);
        if (!designs.length) {
            list.innerHTML = '<p class="text-[10px] text-white/30 text-center py-6 leading-relaxed px-2">No saved designs yet.<br>Tap <i class=\'fas fa-bookmark\'></i> on a design to save it.</p>';
            return;
        }
        list.innerHTML = '';
        designs.forEach(d => {
            const item = document.createElement('div');
            item.className = 'saved-design-item group relative rounded-lg overflow-hidden cursor-pointer ' +
                'border-2 border-transparent hover:border-[#7c3ca0] transition-all flex-shrink-0 w-16 sm:w-auto';
            item.dataset.id = d.id;
            const img = document.createElement('img');
            img.src = d.image_data; img.alt = d.title || 'Design';
            img.className = 'w-full h-16 sm:h-20 object-contain block' + ' bg-white/[0.05]';
            const del = document.createElement('button');
            del.type = 'button'; del.title = 'Remove';
            del.className = 'absolute top-0.5 right-0.5 w-5 h-5 bg-red-500/90 text-white rounded-full ' +
                'hidden group-hover:flex items-center justify-center text-xs leading-none';
            del.innerHTML = '×';
            del.onclick = async (e) => { e.stopPropagation(); await deleteSavedDesign(d.id); };
            item.appendChild(img); item.appendChild(del);
            item.addEventListener('click', () => {
                document.querySelectorAll('.saved-design-item').forEach(el => {
                    el.classList.remove('border-[#7c3ca0]');
                    el.classList.add('border-transparent');
                });
                item.classList.add('border-[#7c3ca0]');
                item.classList.remove('border-transparent');
                _selectedSavedDesign = { id: d.id, src: d.image_data };
                _setAddBtnState(true);
            });
            list.appendChild(item);
        });
    }

    function imgKey(src) { return src.slice(0, 120); }

    async function saveDesign(imageSrc, btn) {
        const key  = imgKey(imageSrc);
        const csrf = document.querySelector('meta[name="csrf-token"]').content;

        // ── Already saved → unsave ──
        if (savedImgKeys.has(key)) {
            const id = savedImgIds.get(key);
            if (!id) return;
            try {
                const res = await fetch(`/designs/saved/${id}`, {
                    method: 'DELETE',
                    headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
                });
                if (!res.ok) throw new Error();
                savedImgKeys.delete(key);
                savedImgIds.delete(key);
                // Reset all matching buttons
                document.querySelectorAll('.save-design-btn').forEach(b => {
                    if (imgKey(b.getAttribute('data-image-src') || '') === key) {
                        b.style.background = '';
                        b.style.borderColor = '';
                        const icon = b.querySelector('i');
                        if (icon) { icon.style.color = ''; }
                        b.title = 'Save design';
                    }
                });
                showToast('Design removed');
            } catch(e) {
                showToast('Could not remove design', 'error');
            }
            return;
        }

        // ── Not saved → save ──
        try {
            const res = await fetch('/designs/saved', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
                body: JSON.stringify({ image_data: imageSrc }),
            });
            if (!res.ok) throw new Error('Failed to save');
            const data = await res.json();
            savedImgKeys.add(key);
            savedImgIds.set(key, data.id);
            // Mark all matching buttons as saved (purple)
            document.querySelectorAll('.save-design-btn').forEach(b => {
                if (imgKey(b.getAttribute('data-image-src') || '') === key) {
                    b.style.background = '#7c3ca0';
                    b.style.borderColor = '#7c3ca0';
                    const icon = b.querySelector('i');
                    if (icon) { icon.style.color = '#ffffff'; }
                    b.title = 'Remove from saved';
                }
            });
            showToast('Design saved! ✓');
        } catch (e) {
            showToast('Could not save design', 'error');
        }
    }

    async function deleteSavedDesign(id) {
        const csrf = document.querySelector('meta[name="csrf-token"]').content;
        try {
            await fetch(`/designs/saved/${id}`, {
                method: 'DELETE',
                headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
            });
            if (_selectedSavedDesign?.id === id) {
                _selectedSavedDesign = null;
                _setAddBtnState(false);
            }
            await loadSavedDesigns();
        } catch (e) { /* silent */ }
    }

    function addSelectedToCanvas() {
        if (!_selectedSavedDesign) return;
        addLayerToCanvas(_selectedSavedDesign.src);
        document.querySelectorAll('.saved-design-item').forEach(el => {
            el.classList.remove('border-[#7c3ca0]'); el.classList.add('border-transparent');
        });
        _selectedSavedDesign = null;
        _setAddBtnState(false);
    }

    function showToast(msg, type = 'success') {
        const t = document.createElement('div');
        t.className = 'fixed bottom-6 right-6 z-[100] px-4 py-2.5 rounded-xl text-sm font-medium shadow-lg ' +
            (type === 'error' ? 'bg-red-600 text-white' : type === 'info' ? 'bg-[#7c3ca0] text-white' : 'bg-ink text-white');
        t.textContent = msg;
        document.body.appendChild(t);
        setTimeout(() => {
            t.style.transition = 'opacity 0.3s';
            t.style.opacity = '0';
            setTimeout(() => t.remove(), 300);
        }, 2200);
    }
</script>

@include('layouts.printify-popup')

{{-- Credit Packs Modal --}}
<div id="credit-packs-modal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-sm px-4">
    <div class="w-full max-w-sm rounded-2xl p-6" style="background:#1a1a1a;border:1px solid rgba(255,255,255,0.1)">
        <div class="flex items-center justify-between mb-5">
            <h2 class="text-base font-semibold text-white">Get more Spools</h2>
            <button onclick="closeCreditPacksModal()" class="text-white/30 hover:text-white/70 transition-colors text-lg leading-none">&times;</button>
        </div>
        <div class="space-y-3">
            @php
            $packs = [
                ['key' => 'small',  'credits' => 10,  'price' => '$1',  'save' => null,      'label' => 'Starter Pack'],
                ['key' => 'medium', 'credits' => 60,  'price' => '$5',  'save' => '20%',     'label' => 'Popular Pack'],
                ['key' => 'large',  'credits' => 140, 'price' => '$10', 'save' => '40%',     'label' => 'Best Value'],
            ];
            @endphp
            @foreach($packs as $pack)
            <form method="POST" action="{{ route('credits.checkout') }}">
                @csrf
                <input type="hidden" name="pack" value="{{ $pack['key'] }}">
                <button type="submit" class="w-full flex items-center justify-between px-4 py-3.5 rounded-xl transition-colors text-left"
                        style="background:rgba(255,255,255,0.05);border:1px solid rgba(255,255,255,0.08)"
                        onmouseover="this.style.background='rgba(124,60,160,0.15)';this.style.borderColor='rgba(124,60,160,0.4)'"
                        onmouseout="this.style.background='rgba(255,255,255,0.05)';this.style.borderColor='rgba(255,255,255,0.08)'">
                    <div>
                        <span class="block text-sm font-medium text-white">{{ $pack['credits'] }} Spools</span>
                        <span class="block text-xs text-white/40">{{ $pack['label'] }}</span>
                    </div>
                    <div class="flex items-center gap-2">
                        @if($pack['save'])
                        <span class="text-[10px] px-1.5 py-0.5 rounded-full font-bold text-white" style="background:#7c3ca0">Save {{ $pack['save'] }}</span>
                        @endif
                        <span class="text-sm font-semibold" style="color:#c084fc">{{ $pack['price'] }}</span>
                    </div>
                </button>
            </form>
            @endforeach
        </div>
        <p class="text-[10px] text-white/20 text-center mt-4">Spools never expire. Secure checkout via Stripe.</p>
    </div>
</div>

@if(session('credits_purchased'))
<div id="credits-success-toast" class="fixed bottom-6 right-6 z-50 flex items-center gap-3 px-4 py-3 rounded-xl shadow-xl"
     style="background:#1a1a1a;border:1px solid rgba(124,60,160,0.4)">
    <img src="/images/spool.webp" class="w-5 h-5 object-contain" alt="Spools">
    <span class="text-sm text-white font-medium">{{ session('credits_purchased') }} Spools added to your account!</span>
    <button onclick="this.parentElement.remove()" class="text-white/30 hover:text-white ml-2">&times;</button>
</div>
<script>setTimeout(()=>{ const t=document.getElementById('credits-success-toast'); if(t) t.remove(); }, 5000);</script>
@endif

<script>
    function openCreditPacksModal()  { document.getElementById('credit-packs-modal').classList.remove('hidden'); }
    function closeCreditPacksModal() { document.getElementById('credit-packs-modal').classList.add('hidden'); }
    document.getElementById('credit-packs-modal').addEventListener('click', function(e) {
        if (e.target === this) closeCreditPacksModal();
    });
</script>
</body>
</html>
