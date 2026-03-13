<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>FabricAI — AI Design Studio</title>
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
            background: #3f3f46;
            border-radius: 99px;
        }
        ::-webkit-scrollbar-thumb:hover { background: #7c3aed; }
        * { scrollbar-width: thin; scrollbar-color: #3f3f46 transparent; }
    </style>
</head>
<body class="bg-gray-950 text-white h-screen overflow-hidden">

<div class="flex h-screen">
    <!-- ================= SIDEBAR ================= -->
    <aside class="w-72 bg-gray-900 border-r border-gray-800 flex flex-col">
        <div class="p-6 border-b border-gray-800 flex justify-center">
            <a href="/">
                <img src="/images/logo.png" alt="Logo" class="h-20 w-20 mx-auto">
            </a>
        </div>
        <div class="flex-1 overflow-y-auto p-4 text-sm text-gray-500">
            <!-- Future chat history -->
            <div class="flex-1 overflow-y-auto p-4 text-sm text-gray-400">
    <button
        onclick="newChat()"
        class="w-full mb-4 px-4 py-2 rounded-lg bg-purple-600 hover:bg-purple-500 text-white text-sm font-semibold transition">
        + New chat
    </button>

    <div id="chat-list" class="space-y-2"></div>
</div>
        </div>

        <!-- Token counter -->
        <div class="p-4 border-t border-gray-800">
            <div class="rounded-xl bg-gray-800/60 border border-gray-700 px-4 py-3 flex flex-col gap-2">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-medium text-gray-400">Tokens</span>
                    <div class="flex items-center gap-1.5">
                        <span id="token-icon" class="text-base">&#9889;</span>
                        <span id="token-count" class="text-sm font-bold text-white">10</span>
                        <span class="text-xs text-gray-500">/ 10</span>
                    </div>
                </div>
                <div class="w-full bg-gray-700 rounded-full h-1.5">
                    <div id="token-bar" class="h-1.5 rounded-full transition-all duration-500 bg-gradient-to-r from-purple-500 to-indigo-500" style="width:100%"></div>
                </div>
                <button id="refill-btn"
                        onclick="TokenManager.refill()"
                        class="hidden w-full mt-1 py-2 rounded-lg text-xs font-semibold text-white bg-gradient-to-r from-purple-500 to-indigo-500 hover:opacity-90 transition">
                    Want more tokens? &#10024;
                </button>
            </div>
        </div>
    </aside>

    <!-- ================= MAIN ================= -->
    <main class="flex-1 flex flex-col relative overflow-hidden">
        <!-- Background glow -->
        <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2
                    w-[900px] h-[900px] bg-purple-600/10 blur-[150px] rounded-full pointer-events-none">
        </div>

        <!-- HEADER -->
        <header class="relative z-10 px-8 py-4 border-b border-gray-800
                       backdrop-blur-md bg-gray-950/80 flex items-center">
            <!-- Left spacer -->
            <div class="flex-1"></div>
            <!-- Centered chat title -->
            <h1 id="chat-title" class="font-semibold text-white text-sm truncate max-w-xs text-center">New chat</h1>
            <!-- Right: user + logout -->
            <div class="flex-1 flex justify-end items-center gap-3">
                <span class="text-sm text-gray-400">{{ Auth::user()->name }}</span>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit"
                            class="px-3 py-1.5 rounded-lg border border-gray-700 text-xs text-gray-400 hover:text-white hover:border-gray-500 transition">
                        Log out
                    </button>
                </form>
            </div>
        </header>

        <!-- ================= CHAT AREA ================= -->
        <div id="chat-container" class="flex-1 overflow-y-auto p-8 relative z-10">
            <div class="max-w-4xl mx-auto space-y-8">
                <!-- Welcome Message -->
                <div class="flex items-start gap-4">
                    <div class="w-10 h-10 rounded-xl bg-gray-800 flex items-center justify-center flex-shrink-0 overflow-hidden p-1">
                        <img src="/images/logo.png" alt="FabricAI" class="w-full h-full object-contain">
                    </div>
                    <div class="bg-gray-900 border border-gray-800 rounded-2xl px-6 py-4 text-gray-300 max-w-2xl">
                        Welcome to FabricAI.  
                        Describe the clothing design you want to create and I'll generate it for you.
                    </div>
                </div>
                <div id="messages" class="space-y-8"></div>
            </div>
        </div>

        <!-- ================= INPUT AREA ================= -->
        <div class="relative z-10 border-t border-gray-800 p-6 bg-gray-950/80 backdrop-blur-md">
            <div id="error" class="hidden text-red-400 text-sm mb-3"></div>
            <div id="loader" class="hidden items-center gap-3 text-purple-400 text-sm mb-4">
                <div class="spinner w-5 h-5 border-2 border-purple-500 border-t-transparent rounded-full"></div>
                Generating design...
            </div>
            <form id="design-form" class="max-w-4xl mx-auto">
                <div class="mb-3 flex justify-end">
                    <div class="relative">
                        <select id="ai-model" style="background-image:none" class="appearance-none bg-gray-900 border border-gray-800 rounded-xl pl-4 pr-9 py-2 text-sm text-gray-300 focus:outline-none focus:border-purple-500 transition cursor-pointer">
                            <option value="fabric_light">Fabric Light</option>
                            <option value="fabric_pro">Fabric Pro</option>
                        </select>
                        <i class="fas fa-chevron-down absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 text-xs pointer-events-none"></i>
                    </div>
                </div>
                <!-- Banner modo edición -->
                <div id="edit-banner" class="hidden mb-3 flex items-center gap-2 px-4 py-2 rounded-xl bg-orange-500/10 border border-orange-500/40 text-orange-400 text-sm font-medium">
                    <span>✏️ You are now editing the previous photo</span>
                    <button type="button" id="cancel-edit-btn" class="ml-auto text-orange-300 hover:text-white transition text-xs underline">Cancel</button>
                </div>
                <div class="flex gap-3 items-end">
    <!-- Upload image -->
    <label class="cursor-pointer px-4 py-4 rounded-2xl bg-gray-800 hover:bg-gray-700 transition">
        <i class="fas fa-paperclip"></i>
        <input type="file" id="image-upload" accept="image/*" class="hidden">
    </label>
    <div id="image-preview" class="ml-2"></div>

    <textarea
        id="prompt"
        rows="1"
        placeholder="Describe the design or upload an image to edit..."
        class="flex-1 bg-gray-900 border border-gray-800 rounded-2xl px-5 py-4 text-sm resize-none
               focus:outline-none focus:border-purple-500 transition
               placeholder-gray-500 max-h-40 scrollbar-hide"></textarea>

    <button
        type="submit"
        id="submit-btn"
        class="px-6 py-4 rounded-2xl font-semibold text-sm
               bg-gradient-to-r from-purple-500 to-indigo-500
               hover:opacity-90 transition disabled:opacity-50">
        Generate ✨
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

        // ─── Token Manager ────────────────────────────────────────────────
        // TODO (payment integration): replace localStorage calls with:
        //   GET  /api/tokens         -> { remaining: int, total: int }
        //   POST /api/tokens/deduct  -> { remaining: int }
        //   POST /api/tokens/refill  -> { checkout_url: string }  (redirect to Stripe/etc)
        const TokenManager = {
            MAX: 10,
            STORAGE_KEY: 'fabricai_tokens_{{ Auth::id() }}',

            get() {
                const v = localStorage.getItem(this.STORAGE_KEY);
                return v !== null ? parseInt(v, 10) : this.MAX;
            },

            set(n) {
                localStorage.setItem(this.STORAGE_KEY, Math.max(0, n));
                this._render(Math.max(0, n));
            },

            deduct() {
                const cur = this.get();
                if (cur <= 0) return false;
                this.set(cur - 1);
                return true;
            },

            // TODO: replace with redirect to checkout_url from POST /api/tokens/refill
            refill() {
                this.set(this.MAX);
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
                    iconEl.textContent = String.fromCodePoint(0x1FABA); // low battery
                    btnEl.classList.remove('hidden');
                    if (submitBtn) submitBtn.disabled = true;
                } else {
                    barEl.className = n <= 3
                        ? 'h-1.5 rounded-full transition-all duration-500 bg-orange-400'
                        : 'h-1.5 rounded-full transition-all duration-500 bg-gradient-to-r from-purple-500 to-indigo-500';
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
        const aiModelSelect = document.getElementById('ai-model');
        const imageInput = document.getElementById('image-upload');
        const form = document.getElementById('design-form');
        const promptInput = document.getElementById('prompt');
        const submitBtn = document.getElementById('submit-btn');
        const loader = document.getElementById('loader');
        const errorEl = document.getElementById('error');
        const messagesContainer = document.getElementById('messages');
        const chatContainer = document.getElementById('chat-container');
        
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
        
        // Estado de carga
        function setLoading(loading) {
            submitBtn.disabled = loading;
            loader.classList.toggle('hidden', !loading);
            loader.classList.toggle('flex', loading);
            errorEl.classList.add('hidden');
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
                : `<div class="w-8 h-8 rounded-full bg-gradient-to-br from-purple-500 to-indigo-500 flex items-center justify-center text-white text-sm font-bold flex-shrink-0">${userInitial}</div>`;

            // Image as a separate bubble above the text
            if (imageBase64) {
                const imgDiv = document.createElement('div');
                imgDiv.className = 'mb-2 flex flex-row-reverse items-end gap-3';
                imgDiv.innerHTML = `
                    <div class="w-8 h-8 flex-shrink-0"></div>
                    <img src="${imageBase64}" alt="Attached image"
                         class="rounded-2xl max-w-xs max-h-56 object-cover shadow-md">
                `;
                messagesContainer.appendChild(imgDiv);
            }

            const messageDiv = document.createElement('div');
            messageDiv.className = 'mb-5 flex flex-row-reverse items-start gap-3';
            messageDiv.innerHTML = `
                ${avatarHtml}
                <div class="bg-white text-gray-900 px-4 py-3.5 rounded-xl text-sm leading-relaxed max-w-2xl">${escapeHtml(text)}</div>
            `;
            messagesContainer.appendChild(messageDiv);
            scrollToBottom();
        }
        
        // Agregar respuesta del bot con imagen
        function addBotResponse(imageUrl) {
            const messageDiv = document.createElement('div');
            messageDiv.className = 'mb-5 flex items-start gap-4';
            
            const uniqueId = 'bg-' + Date.now();
            
            messageDiv.innerHTML = `
                <div class="w-10 h-10 rounded-xl bg-gray-800 flex items-center justify-center flex-shrink-0 self-start overflow-hidden p-1">
                    <img src="/images/logo.png" alt="FabricAI" class="w-full h-full object-contain">
                </div>
                <div class="flex flex-col gap-3 max-w-2xl">
                    <div id="${uniqueId}" class="rounded-2xl overflow-hidden bg-gray-900 p-2 transition-colors">
                        <img src="${imageUrl}" alt="Generated design" class="rounded-xl shadow-lg w-full block" crossorigin="anonymous">
                    </div>
                    <div class="flex gap-2 items-center flex-wrap px-1">
                        <span class="text-xs text-gray-500 mr-1">Background:</span>
                        <button type="button" onclick="changeBg('${uniqueId}', '#18181b')" class="w-7 h-7 rounded-md border-2 border-gray-700 bg-gray-900 hover:border-purple-500 transition-colors" title="Dark"></button>
                        <button type="button" onclick="changeBg('${uniqueId}', '#ffffff')" class="w-7 h-7 rounded-md border-2 border-gray-700 bg-white hover:border-purple-500 transition-colors" title="White"></button>
                        <button type="button" onclick="changeBg('${uniqueId}', '#000000')" class="w-7 h-7 rounded-md border-2 border-gray-700 bg-black hover:border-purple-500 transition-colors" title="Black"></button>
                        <button type="button" onclick="changeBg('${uniqueId}', '#a78bfa')" class="w-7 h-7 rounded-md border-2 border-gray-700 bg-purple-400 hover:border-purple-500 transition-colors" title="Purple"></button>
                        <button type="button" onclick="changeBg('${uniqueId}', '#6366f1')" class="w-7 h-7 rounded-md border-2 border-gray-700 bg-indigo-500 hover:border-purple-500 transition-colors" title="Indigo"></button>
                        <input type="color" onchange="changeBg('${uniqueId}', this.value)" class="w-7 h-7 rounded-md border-2 border-gray-700 cursor-pointer" title="Custom">
                    </div>
                    <div class="flex gap-3 px-1">
                        <a href="${imageUrl}" download="design.png"
                           class="px-4 py-2 bg-purple-600 hover:bg-purple-500 rounded-lg text-sm font-medium transition">
                            Download
                        </a>
                        <button type="button" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-500 rounded-lg text-sm font-medium transition edit-btn">
                            Edit image
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
            messageDiv.className = 'mb-5 flex items-start gap-4';
            messageDiv.innerHTML = `
                <div class="w-10 h-10 rounded-xl bg-gray-800 flex items-center justify-center flex-shrink-0 overflow-hidden p-1">
                    <img src="/images/logo.png" alt="FabricAI" class="w-full h-full object-contain">
                </div>
                <div class="bg-red-950/60 border border-red-800 rounded-2xl px-6 py-4 max-w-2xl text-red-300 text-sm leading-relaxed">
                    ⚠️ ${escapeHtml(msg)}
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
                ? 'bg-gray-800 text-white'
                : 'hover:bg-gray-800 text-gray-400');
        div.textContent = chat.title ?? 'New chat';
        div.onclick = () => loadChat(chat.id);

        // Input inline para renombrar (oculto por defecto)
        const input = document.createElement('input');
        input.type = 'text';
        input.value = chat.title ?? 'New chat';
        input.className = 'hidden flex-1 px-3 py-1.5 rounded-lg bg-gray-800 text-white text-sm border border-purple-500 focus:outline-none';

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
        renameBtn.className = 'ml-1 text-gray-500 hover:text-purple-400 text-sm px-1 focus:outline-none opacity-0 group-hover:opacity-100 transition-opacity';
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
        delBtn.className = 'ml-1 text-gray-500 hover:text-red-400 text-sm px-1 focus:outline-none opacity-0 group-hover:opacity-100 transition-opacity';
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
        document.getElementById('chat-title').textContent = 'New chat';
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
    document.getElementById('chat-title').textContent = data.chat?.title ?? 'New chat';
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
                showError('You have no tokens left. Get more to keep designing!');
                return;
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
                const aiModel = aiModelSelect.value;
                const csrf = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
                console.log('Enviando datos:', {
                    prompt,
                    chat_id: currentChatId,
                    imageBase64: snapshotImage,
                    mimeType: snapshotMime,
                    model: aiModel
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
        document.addEventListener('DOMContentLoaded', async () => {
    TokenManager.init();
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
        img.className = 'rounded-lg border border-gray-700 max-h-20 max-w-20 mt-2';
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
            promptInput.classList.remove('border-gray-800', 'focus:border-purple-500');
            promptInput.classList.add('border-orange-500', 'focus:border-orange-400', 'bg-orange-950/30');
            promptInput.placeholder = 'Describe how you want to edit the previous image....';
            promptInput.focus();
        }

        function exitEditMode() {
            isEditMode = false;
            const banner = document.getElementById('edit-banner');
            banner.classList.add('hidden');
            banner.classList.remove('flex');
            promptInput.classList.add('border-gray-800', 'focus:border-purple-500');
            promptInput.classList.remove('border-orange-500', 'focus:border-orange-400', 'bg-orange-950/30');
            promptInput.placeholder = 'Describe the design or upload an image to edit...';
        }

        document.getElementById('cancel-edit-btn').addEventListener('click', () => exitEditMode());

        document.addEventListener('click', function(e) {
            if (e.target && e.target.classList.contains('edit-btn')) {
                enterEditMode();
            }
        });
    </script>
</body>
</html>
