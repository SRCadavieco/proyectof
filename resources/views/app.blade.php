<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Ugly T-Shirt Generator (Test)</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        body { font-family: Arial, sans-serif; margin: 0; background: #f4f6f8; color: #222; }
        .container { max-width: 760px; margin: 40px auto; padding: 0 16px; }
        .header { margin-bottom: 16px; }
        .header h1 { font-size: 22px; margin: 0; }
        .card { background: #fff; border: 1px solid #e0e3e7; border-radius: 8px; box-shadow: 0 2px 6px rgba(0,0,0,0.05); padding: 16px; }
        label { display: block; font-size: 13px; color: #555; margin-bottom: 6px; }
        input[type="text"] { width: 100%; padding: 10px 12px; border: 1px solid #c6ccd3; border-radius: 6px; outline: none; }
        input[type="text"]:focus { border-color: #7aa6f4; box-shadow: 0 0 0 3px rgba(122,166,244,0.2); }
        .actions { margin-top: 10px; }
        button { padding: 10px 14px; border: 1px solid #1e66c6; background: #2b7be4; color: #fff; border-radius: 6px; cursor: pointer; }
        button:hover { background: #1e66c6; }
        button:disabled { background: #9dbbef; border-color: #9dbbef; cursor: not-allowed; }
        #status { margin-top: 10px; color: #555; display: flex; align-items: center; gap: 8px; min-height: 20px; }
        .spinner { width: 14px; height: 14px; border: 2px solid #c3c8cf; border-top-color: #2b7be4; border-radius: 50%; display: none; animation: spin .8s linear infinite; }
        @keyframes spin { from { transform: rotate(0deg);} to { transform: rotate(360deg);} }
        #error { margin-top: 10px; color: #a00; background: #ffecec; border: 1px solid #d77; border-radius: 6px; white-space: pre-wrap; padding: 10px; display: none; }
        #result { margin-top: 14px; }
        #result img { max-width: 100%; border: 1px solid #ddd; border-radius: 6px; }
        .tools { margin-top: 14px; padding-top: 12px; border-top: 1px dashed #e1e4e8; }
        .row { display: flex; gap: 10px; align-items: center; flex-wrap: wrap; }
        .row label { margin: 0; }
        input[type="range"] { width: 160px; }
        select { padding: 6px 8px; border: 1px solid #c6ccd3; border-radius: 6px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>T-Shirt Generator (Internal Test)</h1>
        </div>
        <div class="card">
            <label for="promptInput">Prompt (t-shirt idea)</label>
            <input id="promptInput" type="text" placeholder="e.g., Cat playing guitar">
            <div class="actions">
                <button id="generateBtn">Generate</button>
            </div>
            <div id="status"><span id="spinner" class="spinner" aria-hidden="true"></span><span id="statusText"></span></div>
            <pre id="error"></pre>
            <div id="result">
                <img id="resultImage" alt="Generated Image" style="display:none;" />
            </div>
            <div class="tools">
                <div class="row">
                    <label for="bgColorSelect">Background color</label>
                    <select id="bgColorSelect">
                        <option value="#2b7be4">Azul</option>
                        <option value="#ff00ff">Fucsia</option>
                        <option value="#ff0000">Rojo</option>
                    </select>
                    <label for="tolInput">Tolerance</label>
                    <input id="tolInput" type="range" min="0" max="100" value="20" />
                    <span id="tolValue">20</span>
                </div>
                <div class="actions" style="margin-top:8px;">
                    <button id="removeBgBtn">Remove Background</button>
                    <button id="applyBgBtn" style="margin-left:8px;">Apply Background Color</button>
                    
                </div>
            </div>
        </div>
    </div>

    <script>
        // Backend base URL from environment. Example: https://your-backend-host
        const backendUrl = "{{ env('BACKEND_URL') }}";

        const promptInput = document.getElementById('promptInput');
        const generateBtn = document.getElementById('generateBtn');
        const statusEl = document.getElementById('status');
        const statusTextEl = document.getElementById('statusText');
        const spinnerEl = document.getElementById('spinner');
        const errorEl = document.getElementById('error');
        const resultImg = document.getElementById('resultImage');
        const bgColorSelect = document.getElementById('bgColorSelect');
        const tolInput = document.getElementById('tolInput');
        const tolValue = document.getElementById('tolValue');
        const removeBgBtn = document.getElementById('removeBgBtn');
        const applyBgBtn = document.getElementById('applyBgBtn');
        

        function showError(text) {
            errorEl.textContent = text;
            errorEl.style.display = 'block';
        }
        function clearError() {
            errorEl.textContent = '';
            errorEl.style.display = 'none';
        }

        function hexToRgb(hex) {
            const h = hex.replace('#','');
            const bigint = parseInt(h.length === 3 ? h.split('').map(c => c + c).join('') : h, 16);
            return { r: (bigint >> 16) & 255, g: (bigint >> 8) & 255, b: bigint & 255 };
        }

        function removeBackgroundFromImage(imgEl, targetColor, tolerance) {
            const canvas = document.createElement('canvas');
            const ctx = canvas.getContext('2d');
            canvas.width = imgEl.naturalWidth;
            canvas.height = imgEl.naturalHeight;
            ctx.drawImage(imgEl, 0, 0);
            const imgData = ctx.getImageData(0, 0, canvas.width, canvas.height);
            const data = imgData.data;
            const thr = Math.min(765, Math.round(tolerance * 3)); // tolerance 0-100 → threshold 0-300 roughly
            const cr = targetColor.r, cg = targetColor.g, cb = targetColor.b;
            for (let i = 0; i < data.length; i += 4) {
                const r = data[i], g = data[i+1], b = data[i+2];
                const diff = Math.abs(r - cr) + Math.abs(g - cg) + Math.abs(b - cb);
                if (diff <= thr) {
                    data[i+3] = 0; // transparent
                }
            }
            ctx.putImageData(imgData, 0, 0);
            return canvas.toDataURL('image/png');
        }

        function applyBackgroundColorToImage(imgEl, targetColor) {
            const canvas = document.createElement('canvas');
            const ctx = canvas.getContext('2d');
            canvas.width = imgEl.naturalWidth;
            canvas.height = imgEl.naturalHeight;
            ctx.fillStyle = `rgb(${targetColor.r}, ${targetColor.g}, ${targetColor.b})`;
            ctx.fillRect(0, 0, canvas.width, canvas.height);
            ctx.drawImage(imgEl, 0, 0);
            return canvas.toDataURL('image/png');
        }

        generateBtn.addEventListener('click', async () => {
            clearError();
            resultImg.style.display = 'none';
            resultImg.src = '';

            const prompt = promptInput.value.trim();
            if (!backendUrl) {
                showError('Missing BACKEND_URL. Set it in your .env file.');
                return;
            }
            if (!prompt) {
                showError('Prompt is required.');
                return;
            }

            generateBtn.disabled = true;
            statusTextEl.textContent = 'Generating...';
            spinnerEl.style.display = 'inline-block';

            try {
                const url = backendUrl.replace(/\/+$/, '') + '/generate';
                const resp = await fetch(url, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ prompt })
                });

                if (!resp.ok) {
                    const raw = await resp.text();
                    showError(raw || ('HTTP ' + resp.status));
                } else {
                    let data;
                    try {
                        data = await resp.json();
                    } catch (e) {
                        const raw = await resp.text();
                        showError(raw || 'Invalid JSON response.');
                        return;
                    }
                    if (!data || !data.imageBase64) {
                        showError('Response missing imageBase64.');
                        return;
                    }
                    resultImg.src = 'data:image/png;base64,' + data.imageBase64;
                    resultImg.style.display = 'block';
                }
            } catch (e) {
                showError(String(e && e.message ? e.message : e));
            } finally {
                statusTextEl.textContent = '';
                spinnerEl.style.display = 'none';
                generateBtn.disabled = false;
            }
        });

        tolInput.addEventListener('input', () => {
            tolValue.textContent = tolInput.value;
        });

        removeBgBtn.addEventListener('click', () => {
            clearError();
            if (!resultImg.src) { showError('No image to process. Generate first.'); return; }
            const color = hexToRgb(bgColorSelect.value);
            const tol = parseInt(tolInput.value || '20', 10);
            try {
                const out = removeBackgroundFromImage(resultImg, color, tol);
                resultImg.src = out;
                resultImg.style.display = 'block';
            } catch (e) {
                showError(String(e && e.message ? e.message : e));
            }
        });

        applyBgBtn.addEventListener('click', () => {
            clearError();
            if (!resultImg.src) { showError('No image to process. Generate first.'); return; }
            const color = hexToRgb(bgColorSelect.value);
            try {
                const out = applyBackgroundColorToImage(resultImg, color);
                resultImg.src = out;
                resultImg.style.display = 'block';
            } catch (e) {
                showError(String(e && e.message ? e.message : e));
            }
        });

        
    </script>
</body>
</html>
