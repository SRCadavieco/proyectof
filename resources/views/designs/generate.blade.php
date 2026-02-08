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
        select { background: #0b1220; color: #e5e7eb; border: 1px solid #374151; border-radius: 6px; padding: 6px 8px; }
        .checkerboard { background: conic-gradient(#ddd 0 25%, #fff 0 50%, #ddd 0 75%, #fff 0) 0/20px 20px; border-radius: 8px; }
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
            <div id="detected-bg" style="display:none; margin-top:8px">
                <strong>Fondo detectado:</strong>
                <span id="detected-swatch" style="display:inline-block;width:16px;height:16px;border:1px solid #374151;border-radius:4px;vertical-align:middle;margin:0 6px;"></span>
                <span id="detected-hex"></span>
                <!-- Procesado automático: sin botones -->
            </div>
            <div id="processed-wrapper" style="display:none; margin-top:12px">
                <h3>Procesado local</h3>
                <div class="checkerboard" style="padding:6px">
                    <img id="processed-image" class="preview" alt="Imagen procesada" />
                </div>
                <div style="margin-top:8px">
                    <a id="download-link" href="#" download="procesado.png" style="color:#93c5fd">Descargar PNG</a>
                </div>
                <div id="process-error" class="error" style="display:none"></div>
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
        const detectedBgEl   = document.getElementById('detected-bg');
        const detectedSwatch = document.getElementById('detected-swatch');
        const detectedHexEl  = document.getElementById('detected-hex');
        // Procesado automático: sin controles manuales
        const processedWrapper = document.getElementById('processed-wrapper');
        const processedImage   = document.getElementById('processed-image');
        const downloadLink     = document.getElementById('download-link');
        const processError     = document.getElementById('process-error');

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

            detectedBgEl.style.display = 'none';

            if (url) {
                imageEl.crossOrigin = 'anonymous';
                imageEl.src = url;
                imageWrapper.style.display = 'block';
            } else if (base64) {
                imageEl.src = base64.startsWith('data:') ? base64 : 'data:image/png;base64,' + base64;
                imageWrapper.style.display = 'block';
            } else {
                imageWrapper.style.display = 'none';
            }

            // Intentar detectar color del borde y procesar automáticamente para quitar el fondo
            if (imageWrapper.style.display === 'block') {
                setTimeout(() => {
                    detectEdgeColorFromSrc(imageEl.src)
                        .then((hex) => {
                            detectedSwatch.style.background = hex;
                            detectedHexEl.textContent = hex;
                            detectedBgEl.style.display = 'block';
                            // Procesado automático: quitar fondo con el color detectado
                            processBackground('transparent', hex)
                                .then(() => {
                                    imageWrapper.style.display = 'none';
                                })
                                .catch(() => {
                                    // si falla (p.ej. CORS), mantenemos la original
                                });
                        })
                        .catch(() => {
                            detectedBgEl.style.display = 'none';
                        });
                }, 0);
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

        // Utilidades para detección y procesamiento local del fondo
        function rgbToHex(r, g, b) {
            const toHex = (n) => n.toString(16).padStart(2, '0');
            return '#' + toHex(r) + toHex(g) + toHex(b);
        }

        function hexToRgb(hex) {
            const m = hex.replace('#','').match(/.{1,2}/g);
            return { r: parseInt(m[0],16), g: parseInt(m[1],16), b: parseInt(m[2],16) };
        }

        // Detecta un color tomando 1 pixel aleatorio cerca de un borde
        async function detectEdgeColorFromSrc(src) {
            return new Promise((resolve, reject) => {
                const img = new Image();
                img.crossOrigin = 'anonymous';
                img.onload = () => {
                    const canvas = document.createElement('canvas');
                    canvas.width = img.naturalWidth || img.width;
                    canvas.height = img.naturalHeight || img.height;
                    const ctx = canvas.getContext('2d');
                    ctx.drawImage(img, 0, 0);

                    const margin = Math.max(5, Math.floor(Math.min(canvas.width, canvas.height) * 0.02));
                    const side = Math.floor(Math.random() * 4);
                    let x, y;
                    if (side === 0) { // top
                        x = Math.floor(Math.random() * canvas.width);
                        y = Math.floor(Math.random() * margin);
                    } else if (side === 1) { // right
                        x = canvas.width - 1 - Math.floor(Math.random() * margin);
                        y = Math.floor(Math.random() * canvas.height);
                    } else if (side === 2) { // bottom
                        x = Math.floor(Math.random() * canvas.width);
                        y = canvas.height - 1 - Math.floor(Math.random() * margin);
                    } else { // left
                        x = Math.floor(Math.random() * margin);
                        y = Math.floor(Math.random() * canvas.height);
                    }

                    try {
                        const pixel = ctx.getImageData(x, y, 1, 1).data;
                        resolve(rgbToHex(pixel[0], pixel[1], pixel[2]));
                    } catch (err) {
                        reject(err);
                    }
                };
                img.onerror = reject;
                img.src = src;
            });
        }

        // Sin regeneración adicional: procesado automático local

        // Procesa la imagen actual localmente: quita fondo (transparent) o lo reemplaza (replace)
        async function processBackground(mode, detectedHex, targetHex = null) {
            processError.style.display = 'none';
            processedWrapper.style.display = 'none';
            try {
                const src = imageEl.src;
                const img = await loadImage(src);
                const canvas = document.createElement('canvas');
                canvas.width = img.naturalWidth || img.width;
                canvas.height = img.naturalHeight || img.height;
                const ctx = canvas.getContext('2d');
                ctx.drawImage(img, 0, 0);

                // Leer pixel data
                const imageData = ctx.getImageData(0, 0, canvas.width, canvas.height);
                const data = imageData.data;

                const { r: br, g: bg, b: bb } = hexToRgb(detectedHex);
                const tol = 40; // tolerancia fija
                const target = targetHex ? hexToRgb(targetHex) : null;

                for (let i = 0; i < data.length; i += 4) {
                    const r = data[i], g = data[i+1], b = data[i+2];
                    const dist = Math.sqrt((r-br)**2 + (g-bg)**2 + (b-bb)**2);
                    if (dist <= tol) {
                        if (mode === 'transparent') {
                            data[i+3] = 0;
                        } else if (mode === 'replace' && target) {
                            data[i] = target.r;
                            data[i+1] = target.g;
                            data[i+2] = target.b;
                            data[i+3] = 255;
                        }
                    }
                }

                ctx.putImageData(imageData, 0, 0);
                const out = canvas.toDataURL('image/png');
                processedImage.src = out;
                downloadLink.href = out;
                processedWrapper.style.display = 'block';
            } catch (err) {
                processError.textContent = 'No se pudo procesar localmente (posible CORS o formato): ' + (err?.message || err);
                processError.style.display = 'block';
            }
        }

        function loadImage(src) {
            return new Promise((resolve, reject) => {
                const img = new Image();
                img.crossOrigin = 'anonymous';
                img.onload = () => resolve(img);
                img.onerror = reject;
                img.src = src;
            });
        }
    </script>
    <script>
        // Override: mostrar imagen solo cuando el fondo esté quitado
        function showResult(data) {
            resultEl.style.display = 'block';
            jsonEl.textContent = JSON.stringify(data, null, 2);

            const url = data.imageUrl || data.image_url || data.url;
            const base64 = data.imageBase64 || data.image_base64 || data.base64;

            detectedBgEl.style.display = 'none';
            processError.style.display = 'none';
            processedWrapper.style.display = 'none';
            imageWrapper.style.display = 'none';

            if (url) {
                imageEl.crossOrigin = 'anonymous';
                imageEl.src = url;
            } else if (base64) {
                imageEl.src = base64.startsWith('data:') ? base64 : 'data:image/png;base64,' + base64;
            } else {
                processError.textContent = 'No hubo imagen en la respuesta.';
                processError.style.display = 'block';
                return;
            }

            setTimeout(() => {
                detectEdgeColorFromSrc(imageEl.src)
                    .then((hex) => {
                        detectedSwatch.style.background = hex;
                        detectedHexEl.textContent = hex;
                        detectedBgEl.style.display = 'block';
                        processBackground('transparent', hex)
                            .then(() => {
                                processedWrapper.style.display = 'block';
                            })
                            .catch(() => {
                                processError.textContent = 'No se pudo procesar localmente (posible CORS o formato).';
                                processError.style.display = 'block';
                            });
                    })
                    .catch(() => {
                        detectedBgEl.style.display = 'none';
                    });
            }, 0);
        }
    </script>
    <script>
        // Production: show server-processed image directly (no local processing)
        function showResult(data) {
            resultEl.style.display = 'block';
            jsonEl.textContent = JSON.stringify(data, null, 2);

            const url = data.imageUrl || data.image_url || data.url;
            const base64 = data.imageBase64 || data.image_base64 || data.base64;

            // Hide local processing UI if present
            const detectedBgEl = document.getElementById('detected-bg');
            const processedWrapper = document.getElementById('processed-wrapper');
            if (detectedBgEl) detectedBgEl.style.display = 'none';
            if (processedWrapper) processedWrapper.style.display = 'none';

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
    </script>
</body>
</html>
