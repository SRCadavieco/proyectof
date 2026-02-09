<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>FabricAI - Generador de Diseños</title>
    @if (file_exists(public_path('build/manifest.json')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @else
        <!-- Fallback CSS -->
        <link rel="stylesheet" href="https://cdn.tailwindcss.com">
    @endif
    <style>
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        .spinner {
            animation: spin 0.8s linear infinite;
        }
    </style>
</head>
<body class="bg-gray-50 text-gray-900 h-screen overflow-hidden">
    <div class="flex h-screen">
        <!-- Sidebar -->
        <aside class="w-60 bg-white border-r border-gray-200 flex flex-col overflow-y-auto">
            <div class="p-5 border-b border-gray-200">
                <div class="text-xs text-gray-400 text-center py-10 px-5 leading-relaxed">
                    Aquí irá el archivo de chats
                </div>
            </div>
        </aside>
        
        <!-- Contenido principal -->
        <main class="flex-1 flex flex-col bg-white overflow-hidden">
            <!-- Header -->
            <header class="flex items-center justify-between px-6 py-4 border-b border-gray-200">
                <div class="flex items-center gap-2">
                    <div class="w-6 h-6 bg-emerald-500 rounded-md"></div>
                    <span class="text-base font-bold text-gray-900">FabricAI</span>
                </div>
                <div class="flex items-center gap-1.5 text-sm text-gray-500">
                    <span class="w-2 h-2 bg-emerald-500 rounded-full"></span>
                    <span>IA Conectada</span>
                </div>
            </header>
            
            <!-- Chat area -->
            <div class="flex-1 overflow-y-auto p-6 flex flex-col" id="chat-container">
                <div class="max-w-4xl mx-auto w-full">
                    <!-- Mensaje de bienvenida -->
                    <div class="mb-5 flex flex-col items-start">
                        <div class="w-8 h-8 rounded-full bg-emerald-500 flex items-center justify-center text-white text-sm font-semibold mb-2">
                            AI
                        </div>
                        <div class="bg-gray-50 px-4 py-3.5 rounded-xl text-sm leading-relaxed text-gray-700 max-w-2xl">
                            <p>Soy FabricAI, tu IA para generar diseños de ropa. Escribe tu prompt y yo te generaré el diseño.</p>
                        </div>
                    </div>
                    
                    <!-- Aquí se agregarán los mensajes dinámicamente -->
                    <div id="messages"></div>
                </div>
            </div>
            
            <!-- Input area -->
            <div class="border-t border-gray-200 p-5 bg-white">
                <div id="error" class="hidden text-red-500 text-sm mt-2"></div>
                <div id="loader" class="hidden items-center gap-2 text-emerald-500 text-sm mb-3">
                    <div class="spinner w-4 h-4 border-2 border-emerald-500 border-t-transparent rounded-full"></div>
                    <span>Generando diseño...</span>
                </div>
                <form id="design-form">
                    <div class="flex gap-3 items-end">
                        <textarea 
                            id="prompt" 
                            class="flex-1 min-h-[44px] max-h-[120px] px-4 py-3 border border-gray-200 rounded-xl text-sm resize-none outline-none transition-colors focus:border-emerald-500 placeholder-gray-400"
                            placeholder="Describe la ropa que quieres diseñar... ej: 'Una chaqueta cyberpunk con detalles neón'"
                            rows="1"
                        ></textarea>
                        <button type="submit" id="submit-btn" class="px-6 py-3 bg-emerald-500 text-white rounded-xl text-sm font-semibold cursor-pointer transition-colors hover:bg-emerald-600 disabled:opacity-60 disabled:cursor-not-allowed whitespace-nowrap">
                            Generar ✨
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
            
            messageDiv.innerHTML = `
                <div class="w-8 h-8 rounded-full bg-emerald-500 flex items-center justify-center text-white text-sm font-semibold mb-2">AI</div>
                <div class="bg-gray-50 px-4 py-3.5 rounded-xl text-sm leading-relaxed text-gray-700 max-w-2xl">
                    <div class="mt-3">
                        <img src="${imageUrl}" alt="Diseño generado" class="max-w-full rounded-xl shadow-md block" crossorigin="anonymous">
                        <div class="flex gap-2 mt-3">
                            <a href="${imageUrl}" download="diseño.png" class="px-4 py-2 bg-emerald-500 text-white rounded-lg text-sm font-medium hover:bg-emerald-600 transition-colors no-underline inline-block">Descargar</a>
                        </div>
                    </div>
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
                const csrf = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
                const res = await fetch('{{ route('designs.generate', [], false) }}', {
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
                    throw new Error('No hay imagen en la respuesta');
                }
                
            } catch (err) {
                showError(err.message || 'Error inesperado');
            } finally {
                setLoading(false);
            }
        });
    </script>
</body>
</html>
