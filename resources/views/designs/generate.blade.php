<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Generar Diseño</title>
    <style>
        body { font-family: system-ui, -apple-system, Segoe UI, Roboto, Arial, sans-serif; margin: 0; padding: 24px; background: #0f172a; color: #e2e8f0; }
        .card { max-width: 800px; margin: 0 auto; background: #111827; border: 1px solid #1f2937; border-radius: 12px; padding: 20px; }
        h1 { font-size: 20px; margin: 0 0 12px; }
        label { display: block; font-weight: 600; margin-bottom: 8px; }
        textarea { width: 100%; min-height: 120px; border-radius: 8px; border: 1px solid #374151; background: #0b1220; color: #e5e7eb; padding: 10px; resize: vertical; }
        .row { display: flex; gap: 16px; align-items: center; justify-content: space-between; }
        .actions { display: flex; align-items: center; gap: 12px; margin-top: 12px; }
        button { background: #2563eb; color: white; border: 0; padding: 10px 16px; border-radius: 8px; cursor: pointer; font-weight: 600; }
        button:disabled { opacity: .6; cursor: not-allowed; }
        .loader { display: none; align-items: center; gap: 8px; color: #93c5fd; }
        .spinner { width: 16px; height: 16px; border: 2px solid #3b82f6; border-top-color: transparent; border-radius: 50%; animation: spin 1s linear infinite; }
        @keyframes spin { to { transform: rotate(360deg); } }
        .result { margin-top: 16px; }
        .error { color: #fca5a5; margin-top: 8px; }
        img.preview { max-width: 100%; border-radius: 8px; border: 1px solid #374151; background: #0b1220; }
        pre { background: #0b1220; border: 1px solid #1f2937; border-radius: 8px; padding: 12px; color: #9ca3af; overflow: auto; }
    </style>
</head>
<body>
    <!--
        Vista de prueba para generar diseños con IA.
        Contiene un formulario con textarea para el prompt, un botón para enviar
        y una sección de resultados que muestra imagen (si existe) y el JSON crudo.
    -->
    <div class="card">
        <h1>Generar Diseño (test)</h1>
        <form id="design-form">
            <label for="prompt">Prompt</label>
            <textarea id="prompt" name="prompt" placeholder="Describe el diseño que quieres generar..."></textarea>
            <div class="actions">
                <button id="submit-btn" type="submit">Generar</button>
                <div id="loader" class="loader">
                    <div class="spinner"></div>
                    <span>Generando...</span>
                </div>
            </div>
        </form>

        <div id="error" class="error" role="alert" style="display:none"></div>
        <div id="result" class="result" style="display:none">
            <h2>Resultado</h2>
            <div id="image-wrapper" style="display:none; margin-bottom:12px">
                <img id="image" class="preview" alt="Diseño generado" />
            </div>
            <details>
                <summary>Ver JSON devuelto</summary>
                <pre id="json"></pre>
            </details>
        </div>
    </div>

    <script>
        // --- Referencias a elementos de interfaz ---
        const form = document.getElementById('design-form');
        const errorEl = document.getElementById('error');
        const resultEl = document.getElementById('result');
        const imageWrapper = document.getElementById('image-wrapper');
        const imageEl = document.getElementById('image');
        const jsonEl = document.getElementById('json');
        const loader = document.getElementById('loader');
        const submitBtn = document.getElementById('submit-btn');

        // Muestra/oculta loader y deshabilita el botón para evitar dobles envíos.
        function setLoading(loading) {
            loader.style.display = loading ? 'flex' : 'none';
            submitBtn.disabled = loading;
        }

        // Muestra un mensaje de error en la UI.
        function setError(msg) {
            if (msg) {
                errorEl.textContent = msg;
                errorEl.style.display = 'block';
            } else {
                errorEl.textContent = '';
                errorEl.style.display = 'none';
            }
        }

        // Renderiza el resultado del backend: imagen si viene URL/Base64 y JSON crudo.
        function showResult(data) {
            resultEl.style.display = 'block';
            jsonEl.textContent = JSON.stringify(data, null, 2);

            // Try common keys for image response
            const url = data.imageUrl || data.image_url || data.url;
            const base64 = data.imageBase64 || data.image_base64 || data.base64;

            if (url) {
                imageEl.src = url;
                imageWrapper.style.display = 'block';
            } else if (base64) {
                imageEl.src = base64.startsWith('data:') ? base64 : 'data:image/png;base64,' + base64;
                imageWrapper.style.display = 'block';
            } else {
                imageWrapper.style.display = 'none';
            }
        }

        // Evento de envío: valida prompt, envía POST JSON a la ruta Laravel y procesa la respuesta.
        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            setError('');
            setLoading(true);
            imageWrapper.style.display = 'none';
            resultEl.style.display = 'none';

            // CSRF para proteger la petición POST.
            const csrf = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
            const prompt = document.getElementById('prompt').value.trim();
            if (!prompt) {
                setLoading(false);
                setError('El prompt es obligatorio.');
                return;
            }

            try {
                // Envío del prompt al backend propio: DesignController->generate()
                // Usa ruta relativa para evitar problemas si APP_URL no coincide con el host actual
                const res = await fetch('{{ route('designs.generate', [], false) }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrf,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ prompt })
                });

                const data = await res.json().catch(() => ({ success: false, error: 'Respuesta no válida del servidor' }));

                if (!res.ok) {
                    throw new Error(data?.message || data?.error || `Error ${res.status}`);
                }

                // Si todo bien, mostramos imagen (si hay) y el JSON crudo para depurar.
                showResult(data);
            } catch (err) {
                setError(err.message || 'Error inesperado');
            } finally {
                setLoading(false);
            }
        });
    </script>
</body>
</html>
