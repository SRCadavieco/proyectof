
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>FabricAI — AI Design Studio</title>
    @vite(['resources/css/app.css','resources/js/app.js'])
    <style>
            .scrollbar-hide::-webkit-scrollbar { display: none; }
            .scrollbar-hide { -ms-overflow-style: none; scrollbar-width: none; }
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        .spinner {
            animation: spin 0.8s linear infinite;
        }
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
                       backdrop-blur-md bg-gray-950/80 flex justify-between items-center">
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-lg bg-gradient-to-r from-purple-500 to-indigo-500"></div>
                <span class="font-semibold">AI Design Generator</span>
            </div>
            <div class="flex items-center gap-2 text-sm text-gray-400">
                <span class="w-2 h-2 bg-green-500 rounded-full animate-pulse"></span>
                AI Online
            </div>
        </header>

        <!-- ================= CHAT AREA ================= -->
        <div id="chat-container" class="flex-1 overflow-y-auto p-8 relative z-10">
            <div class="max-w-4xl mx-auto space-y-8">
                <!-- Welcome Message -->
                <div class="flex gap-4">
                    <div class="w-10 h-10 rounded-xl bg-gradient-to-r from-purple-500 to-indigo-500 flex items-center justify-center font-bold">
                        AI
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
                <div class="flex gap-4 items-end">
                    <textarea
                        id="prompt"
                        rows="1"
                        placeholder="Example: Futuristic cyberpunk jacket with neon purple details..."
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
        // Referencias a elementos
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
        function addUserMessage(text) {
            const messageDiv = document.createElement('div');
            messageDiv.className = 'mb-5 flex flex-col items-end';
            messageDiv.innerHTML = `
                <div class="w-8 h-8 rounded-full bg-blue-500 flex items-center justify-center text-white text-sm font-semibold mb-2 ml-auto">U</div>
                <div class="bg-blue-50 px-4 py-3.5 rounded-xl text-sm leading-relaxed text-gray-700 max-w-2xl">${escapeHtml(text)}</div>
            `;
            messagesContainer.appendChild(messageDiv);
            scrollToBottom();
        }
        
        // Agregar respuesta del bot con imagen
        function addBotResponse(imageUrl) {
            const messageDiv = document.createElement('div');
            messageDiv.className = 'mb-5 flex flex-col items-start';
            
            const uniqueId = 'bg-' + Date.now();
            
            messageDiv.innerHTML = `
                <div class="w-10 h-10 rounded-xl bg-gradient-to-r from-purple-500 to-indigo-500 flex items-center justify-center font-bold">AI</div>
                <div class="bg-gray-900 border border-gray-800 rounded-2xl px-6 py-4 max-w-2xl text-gray-300">
                    <div class="mt-3">
                        <div id="${uniqueId}" class="p-4 rounded-xl transition-colors bg-gray-900">
                            <img src="${imageUrl}" alt="Generated design" class="rounded-xl shadow-lg max-w-full" crossorigin="anonymous">
                        </div>
                        <div class="mt-3 p-3 bg-gray-900 rounded-lg border border-gray-800">
                            <div class="text-xs font-medium text-gray-400 mb-2">Background color (preview):</div>
                            <div class="flex gap-2 items-center flex-wrap">
                                <button type="button" onclick="changeBg('${uniqueId}', '#18181b')" class="w-8 h-8 rounded-md border-2 border-gray-700 bg-gray-900 hover:border-purple-500 transition-colors" title="Dark"></button>
                                <button type="button" onclick="changeBg('${uniqueId}', '#ffffff')" class="w-8 h-8 rounded-md border-2 border-gray-700 bg-white hover:border-purple-500 transition-colors" title="White"></button>
                                <button type="button" onclick="changeBg('${uniqueId}', '#000000')" class="w-8 h-8 rounded-md border-2 border-gray-700 bg-black hover:border-purple-500 transition-colors" title="Black"></button>
                                <button type="button" onclick="changeBg('${uniqueId}', '#a78bfa')" class="w-8 h-8 rounded-md border-2 border-gray-700 bg-purple-400 hover:border-purple-500 transition-colors" title="Purple"></button>
                                <button type="button" onclick="changeBg('${uniqueId}', '#6366f1')" class="w-8 h-8 rounded-md border-2 border-gray-700 bg-indigo-500 hover:border-purple-500 transition-colors" title="Indigo"></button>
                                <input type="color" onchange="changeBg('${uniqueId}', this.value)" class="w-8 h-8 rounded-md border-2 border-gray-700 cursor-pointer" title="Custom">
                            </div>
                        </div>
                        <div class="flex gap-3 mt-4">
                            <a href="${imageUrl}" download="design.png"
                               class="px-4 py-2 bg-purple-600 hover:bg-purple-500 rounded-lg text-sm font-medium transition">
                                Download
                            </a>
                            <button type="button" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-500 rounded-lg text-sm font-medium transition edit-btn">
                                Editar imagen
                            </button>
                        </div>
                        
                        </div>
                        </div>
                        `;
                        // Delegación de eventos para el botón Editar imagen (solo una vez)
                        document.addEventListener('click', function(e) {
                            if (e.target && e.target.classList.contains('edit-btn')) {
                                const promptInput = document.getElementById('prompt');
                                if (promptInput) {
                                    promptInput.value = '/edit ';
                                    promptInput.focus();
                                }
                            }
                        });
            
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
        

        // Enviar formulario
        form.addEventListener('submit', async (e) => {
            e.preventDefault();

            const prompt = promptInput.value.trim();
            if (!prompt) {
                showError('Por favor escribe un prompt');
                return;
            }

            // Agregar mensaje del usuario
            addUserMessage(prompt);
            promptInput.value = '';
            promptInput.style.height = 'auto';

            // Iniciar carga
            setLoading(true);

            try {
                // Enviar prompt a Laravel
                const csrf = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
                const res = await fetch('/designs/generate', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrf,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ prompt })
                });
                const data = await res.json().catch(() => ({ 
                    success: false, 
                    error: 'Respuesta inválida del servidor' 
                }));

                if (!res.ok) {
                    throw new Error(data?.message || data?.error || `Error ${res.status}`);
                }

                // Extraer URL de imagen
                const imageUrl = data.imageUrl || data.image_url || data.url;
                const base64 = data.imageBase64 || data.image_base64 || data.base64;

                if (imageUrl) {
                    addBotResponse(imageUrl);
                } else if (base64) {
                    const fullBase64 = base64.startsWith('data:') ? base64 : 'data:image/png;base64,' + base64;
                    addBotResponse(fullBase64);
                } else {
                    throw new Error('No image in response');
                }
            } catch (err) {
                showError(err.message || 'Unexpected error');
            } finally {
                setLoading(false);
            }
        });
    </script>
</body>
</html>
