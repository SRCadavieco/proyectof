<?php

namespace App\Http\Controllers;

use App\Services\GeminiService;
use App\Services\ChutesService;
use App\Services\TogetherService;
use App\Services\NanoGptService;
use App\Services\BackgroundRemovalService;
use App\Jobs\GenerateDesignJob;
use App\Models\ApiUsageLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use App\Models\Chat;
use App\Models\SavedDesign;

use Illuminate\Support\Str;

/**
 * Controlador de la funcionalidad de "Diseños".
 *
 * Orquesta el flujo entre la vista (formulario), la validación del prompt
 * y la llamada al servicio que conecta con la IA/Backend.
 */
class DesignController extends Controller
{
    /**
     * Poll the status of an async NanoGPT generation.
     * GET /designs/generation/{id}
     */
    public function generationStatus(string $id, Request $request): \Illuminate\Http\JsonResponse
    {
        // Only alphanumeric + hyphens (UUID format) — reject anything else
        if (!preg_match('/^[a-f0-9\-]{36}$/', $id)) {
            return response()->json(['status' => 'error', 'error' => 'Invalid generation ID'], 400);
        }

        $result = Cache::get('generation:' . $id);

        if (!$result) {
            return response()->json(['status' => 'pending']);
        }

        return response()->json($result);
    }

    /**
     * Muestra el formulario simple para solicitar el diseño.
     *
     * GET /designs
     */
    public function form()
    {
        // Renderiza la vista Blade con el formulario y el script de envío.
        return view('designs.generate');
    }

    /**
     * Recibe el prompt desde el formulario y lo envía al servicio Gemini.
     *
     * POST /designs/generate
     * - Valida que el campo 'prompt' existe y es cadena.
     * - Llama a GeminiService para generar el diseño en el backend.
     * - Mapea el status HTTP según el resultado devuelto.
     */
   public function generate(
    Request $request,
    GeminiService $gemini,
    ChutesService $chutes,
    TogetherService $together,
    NanoGptService $nanogpt,
    BackgroundRemovalService $backgrounds
) {
   set_time_limit(300); // Chutes models can take up to ~3 min on cold start
   ini_set('memory_limit', '256M'); // large base64 images need more than the 128M default
   try {
       $validated = $request->validate([
           'prompt' => ['required', 'string'],
           'chat_id' => ['required', 'exists:chats,id'],
           'backgroundColor' => ['nullable', 'string'],
           'imageBase64' => ['nullable', 'string'],
           'mimeType' => ['nullable', 'string'],
           'model' => ['nullable', 'string', 'in:fabric_light,fabric_pro,z_image_turbo,flux_dev,juggernaut_z'],
           'provider' => ['nullable', 'string', 'in:gemini,chutes,together,nanogpt'],
           'is_edit' => ['nullable', 'boolean'],
       ]);
   } catch (\Illuminate\Validation\ValidationException $e) {
       return response()->json([
           'success' => false,
           'error' => 'Error de validación',
           'details' => $e->errors(),
       ], 422);
   }

   try {

    $userPrompt = trim($validated['prompt']);
    $prompt = $userPrompt;
    $provider = $validated['provider'] ?? 'gemini';
    $hasReferenceImage = !empty($validated['imageBase64']) && empty($validated['is_edit']);

    // Diffusion models are sensitive to long prompts, keep user intent concise.
    $userPromptForDiffusion = mb_substr($userPrompt, 0, 270);

    if ($hasReferenceImage) {
        $prompt = $userPrompt;
    } elseif ($provider === 'chutes' || $provider === 'nanogpt') {
        $prompt = $this->buildHybridPrompt($userPromptForDiffusion, $provider, $model ?? null);
    } elseif ($provider === 'together') {
        $prompt = $this->buildHybridPrompt($userPromptForDiffusion, $provider, $model ?? null);
    } else {
        $prompt = $userPrompt;
    }
    $backgroundColor = $validated['backgroundColor'] ?? null;
    $chatId = $validated['chat_id'];

    // Obtener chat y verificar que pertenece al usuario autenticado
    $chat = Chat::findOrFail($chatId);
    if ($chat->user_id !== \Illuminate\Support\Facades\Auth::id()) {
        return response()->json(['success' => false, 'error' => 'Forbidden'], 403);
    }

    // Verificar y descontar token del usuario (con reset mensual lazy)
    $user = $request->user();
    $user->refreshCreditsIfNeeded();
    $tokenCost = ($provider === 'nanogpt' && empty($validated['is_edit'])) ? 2 : 1;
    if ($user->tokens < $tokenCost) {
        return response()->json([
            'success' => false,
            'error' => $tokenCost > 1
                ? "Necesitas {$tokenCost} Spools para usar Fabric Max. Consigue más para seguir diseñando."
                : 'No tienes tokens disponibles. Consigue más para seguir diseñando.',
        ], 403);
    }
    $user->decrement('tokens', $tokenCost);
    $user->increment('tokens_used', $tokenCost);

    // Guardar solo el prompt del usuario (sin el prefijo del sistema)
    $chat->messages()->create([
        'role' => 'user',
        'content' => $userPrompt,
        'image' => $validated['imageBase64'] ?? null,
    ]);

    // Obtener contexto del chat (últimos 6 mensajes del usuario)
    $context = $chat->messages()
        ->where('role', 'user')
        ->orderBy('created_at', 'desc')
        ->take(6)
        ->pluck('content')
        ->reverse()
        ->values()
        ->toArray();
    $imageBase64 = $validated['imageBase64'] ?? null;
    $mimeType = $validated['mimeType'] ?? 'image/png';
    $model = $validated['model'] ?? ($provider === 'chutes'
        ? 'z_image_turbo'
        : ($provider === 'together'
            ? 'flux_dev'
            : ($provider === 'nanogpt' ? 'juggernaut_z' : 'fabric_light')));

    // Resize user-uploaded image before sending to AI backends (they reject large payloads)
    if ($imageBase64) {
        $imageBase64 = $this->resizeImageForAI($imageBase64);
        $mimeType = 'image/jpeg'; // always JPEG after resize
    }

    $isEdit = !empty($validated['is_edit']);

// Seleccionar el servicio de IA según el proveedor elegido
$ai = match($provider) {
    'chutes'   => $chutes,
    'together' => $together,
    'nanogpt'  => $nanogpt,
    default    => $gemini,
};

// Chutes and Together are text-to-image only — they completely ignore any uploaded image.
// Whenever the user provides a reference image OR is in edit mode, always use Gemini
// (multimodal LLM) which actually reads and follows instructions about the image.
$needsImg2Img = !empty($imageBase64) || $isEdit;
if ($needsImg2Img) {
    // Together supports img2img. Chutes/NanoGPT text-to-image models do not.
    if (in_array($provider, ['chutes', 'nanogpt'], true)) {
        $ai    = $gemini;
        $model = 'fabric_light';
    }
}

// Guard: nanogpt is only available for pro/business plans
$effectivePlan = strtolower((string) ($user->plan ?? 'free'));
if ($effectivePlan === 'studio') {
    $effectivePlan = 'business';
}
if ($provider === 'nanogpt' && !in_array($effectivePlan, ['pro', 'business'], true)) {
    $provider = 'chutes';
    $model = 'z_image_turbo';
    $ai = $chutes;
}

if ($isEdit) {
    // Buscar la última imagen del chat en BD; fallback a sesión
    $lastImageMsg = $chat->messages()
        ->where('role', 'assistant')
        ->whereNotNull('image')
        ->orderBy('created_at', 'desc')
        ->first();

    $lastImage = $lastImageMsg?->image ?? session('last_image');

    if (!$lastImage) {
        return response()->json([
            'success' => false,
            'error' => 'No se encontró ninguna imagen anterior en este chat para editar.'
        ], 422);
    }

    // Limpiar prefijo data URI si lo tiene, guardando el mimeType real
    $cleanBase64 = $lastImage;
    $detectedMime = 'image/jpeg'; // default
    if (str_starts_with($cleanBase64, 'data:image')) {
        if (preg_match('/^data:(image\/[a-z+]+);base64,/i', $cleanBase64, $m)) {
            $detectedMime = strtolower($m[1]);
        }
        $cleanBase64 = preg_replace('/^data:image\/[^;]+;base64,/i', '', $cleanBase64);
    }

    // Resize stored image before sending (resizeImageForAI always outputs JPEG when successful)
    $resized = $this->resizeImageForAI($cleanBase64);
    // If resize succeeded the output is JPEG; if it returned the same string it failed → keep real mime
    $editMimeType = ($resized !== $cleanBase64) ? 'image/jpeg' : $detectedMime;
    $cleanBase64  = $resized;

    $result = $ai->generateFromReference(
        $prompt,
        $cleanBase64,
        $editMimeType,
        $model
    );

    // If Together failed (e.g. local URL unreachable), retry with Gemini
    if ($provider === 'together' && (!is_array($result) || !empty($result['error']))) {
        $result = $gemini->generateFromReference($prompt, $cleanBase64, $editMimeType, 'fabric_light');
    }

} elseif ($imageBase64) {

    if (str_starts_with($imageBase64, 'data:image')) {
        $imageBase64 = preg_replace(
            '/^data:image\/(png|jpeg|jpg|webp);base64,/i',
            '',
            $imageBase64
        );
    }

    $result = $ai->generateFromReference(
        $prompt,
        $imageBase64,
        $mimeType,
        $model
    );

    // If Together failed (e.g. local URL unreachable), retry with Gemini
    if ($provider === 'together' && (!is_array($result) || !empty($result['error']))) {
        $result = $gemini->generateFromReference($prompt, $imageBase64, $mimeType, 'fabric_light');
    }

} else {

    // NanoGPT (juggernaut-z) is slow (60-120s) — dispatch an async job and return
    // a generation_id immediately so the frontend can poll for the result.
    if ($provider === 'nanogpt') {
        $generationId = (string) Str::uuid();

        GenerateDesignJob::dispatch(
            $generationId,
            $chat->id,
            $user->id,
            $prompt,
            $userPrompt,
            $backgroundColor,
            $model,
            $provider,
        );

        return response()->json([
            'success'       => true,
            'status'        => 'generating',
            'generation_id' => $generationId,
            'provider'      => $provider,
            'model'         => $model,
        ]);
    }

    $result = $ai->generateDesignWithContext(
        $prompt,
        $context,
        $backgroundColor,
        $model
    );

    // Safety fallback: if NanoGPT fails for business text-to-image, retry with z-image.
    $hasGeneratedImage = is_array($result) && (
        !empty($result['imageBase64'] ?? $result['image_base64'] ?? $result['base64'] ?? null)
        || !empty($result['imageUrl'] ?? $result['image_url'] ?? $result['url'] ?? null)
    );
    if ($provider === 'nanogpt' && !$hasGeneratedImage) {
        $fallback = $chutes->generateDesignWithContext($prompt, $context, $backgroundColor, 'z_image_turbo');
        if (is_array($fallback)) {
            $result = $fallback;
            $provider = 'chutes';
            $model = 'z_image_turbo';
        }
    }
}

    // Log AI generation call
    $aiSuccess = is_array($result) && (
        !empty($result['imageBase64'] ?? $result['image_base64'] ?? $result['base64'] ?? null)
        || !empty($result['imageUrl'] ?? $result['image_url'] ?? $result['url'] ?? null)
    );
    try {
        ApiUsageLog::record($provider, $model, $isEdit ? 'img2img' : 'generate', $user->id, $aiSuccess);
    } catch (\Throwable $logEx) {
        \Illuminate\Support\Facades\Log::warning('ApiUsageLog::record failed', ['error' => $logEx->getMessage()]);
    }

    // Procesar imagen
    $imageValue = null;

    if (is_array($result)) {
        $base64 =
            $result['imageBase64']
            ?? $result['image_base64']
            ?? $result['base64']
            ?? null;

        $imageUrl =
            $result['imageUrl']
            ?? $result['image_url']
            ?? $result['url']
            ?? null;

        if ($base64) {
            $noBg = $backgrounds->removeBackground($base64);
            $bgRemovalMethod = $backgrounds->getLastMethod();
            $result['bg_removal_method'] = $bgRemovalMethod;
            try { ApiUsageLog::record('replicate', 'remove_bg', 'remove_bg', $user->id, $noBg !== null); } catch (\Throwable $logEx) { \Illuminate\Support\Facades\Log::warning('ApiUsageLog::record failed', ['error' => $logEx->getMessage()]); }
            if ($noBg) {
                $processed = $noBg; // Keep as PNG — converting to WebP and back degrades quality
            } else {
                \Illuminate\Support\Facades\Log::warning('DesignController: removeBackground failed, serving raw image', [
                    'provider' => $provider,
                    'model'    => $model,
                    'method'   => $bgRemovalMethod,
                ]);
                $processed = null;
                $result['bg_removal_failed'] = true;
            }

            if (is_string($processed) && $processed !== '') {
                $result['imageBase64'] = $processed;
                unset($result['image_url'], $result['url']);
                $base64 = $processed;
            }

            // Ensure stored value always has a data URI prefix so the browser
            // can use it directly as <img src> on reload without treating it as a URL.
            if (!str_starts_with($base64, 'data:')) {
                $base64 = 'data:image/png;base64,' . $base64;
            }

            session(['last_image' => $base64]);
            $imageValue = $base64;
        } elseif ($imageUrl) {
            $imageValue = $imageUrl;
        }
    }

    // Solo guardar respuesta IA si se generó una imagen
    if ($imageValue) {
        $chat->messages()->create([
            'role'  => 'assistant',
            'image' => $imageValue,
            'model' => $model,
        ]);

        // Título automático del chat
        if (!$chat->title) {
            $chat->update([
                'title' => Str::limit($userPrompt, 40),
            ]);
        }
    }

    // Si no hay imagen, loguear el error real y devolverlo
    if (!$imageValue) {
        $aiError = is_array($result) ? ($result['error'] ?? 'sin error') : 'resultado no es array';
        $aiStatus = is_array($result) ? ($result['status'] ?? '?') : '?';
        \Illuminate\Support\Facades\Log::error('DesignController: no image generated', [
            'provider' => $provider,
            'model'    => $model,
            'status'   => $aiStatus,
            'error'    => $aiError,
        ]);
        return response()->json([
            'success' => false,
            'error'   => 'No se pudo generar la imagen. Revisa bien el prompt e inténtalo de nuevo.',
            'debug'   => "[{$provider}/{$model} HTTP {$aiStatus}] {$aiError}",
        ], 500);
    }

    // Status HTTP
    $status = 200;
    if (
        is_array($result) &&
        array_key_exists('success', $result) &&
        $result['success'] === false
    ) {
        $status = isset($result['status'])
            ? (int) $result['status']
            : 500;
    }

    // Expose the effective provider/model used (after any runtime fallback)
    // so the frontend can confirm exactly which engine generated the image.
    if (is_array($result)) {
        $result['provider'] = $provider;
        $result['model'] = $model;
    }

    return response()->json($result, $status);

   } catch (\Throwable $e) {
       \Illuminate\Support\Facades\Log::error('DesignController::generate unhandled exception', [
           'message' => $e->getMessage(),
           'file'    => $e->getFile(),
           'line'    => $e->getLine(),
           'trace'   => substr($e->getTraceAsString(), 0, 500),
       ]);
       $msg = app()->environment('production')
           ? 'Ocurrió un error inesperado. Por favor inténtalo de nuevo.'
           : $e->getMessage();
       return response()->json([
           'success' => false,
           'error'   => $msg,
       ], 500);
   }
}

// ─── Saved Designs ──────────────────────────────────────────────────────────

    /**
     * GET /designs/saved  — list the current user's saved designs.
     */
    public function savedDesigns()
    {
        $designs = SavedDesign::where('user_id', auth()->id())
            ->latest()
            ->select(['id', 'image_data', 'title', 'created_at'])
            ->get();

        return response()->json($designs);
    }

    /**
     * POST /designs/saved  — save a design image for the current user.
     */
    public function saveDesign(\Illuminate\Http\Request $request)
    {
        $data = $request->validate([
            'image_data' => ['required', 'string'],
            'title'      => ['nullable', 'string', 'max:120'],
        ]);

        // Limit to 50 saved designs per user
        $count = SavedDesign::where('user_id', auth()->id())->count();
        if ($count >= 50) {
            return response()->json(['error' => 'Maximum 50 saved designs reached.'], 422);
        }

        $design = SavedDesign::create([
            'user_id'    => auth()->id(),
            'image_data' => $data['image_data'],
            'title'      => $data['title'] ?? null,
        ]);

        return response()->json(['success' => true, 'id' => $design->id], 201);
    }

    /**
     * PATCH /designs/saved/{savedDesign}  — rename a saved design.
     */
    public function renameSavedDesign(Request $request, SavedDesign $savedDesign)
    {
        if ($savedDesign->user_id !== auth()->id()) {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        $request->validate(['title' => 'required|string|max:120']);
        $savedDesign->update(['title' => $request->title]);

        return response()->json(['success' => true, 'title' => $savedDesign->title]);
    }

    /**
     * DELETE /designs/saved/{savedDesign}  — remove a saved design.
     */
    public function deleteSavedDesign(SavedDesign $savedDesign)
    {
        if ($savedDesign->user_id !== auth()->id()) {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        $savedDesign->delete();

        return response()->json(['success' => true]);
    }

    // ─────────────────────────────────────────────────────────────────────────────

    /**
 * Build a provider-aware hybrid prompt that keeps the product's visual essence
 * while adapting quality guidance to each image model family.
 */
private function buildHybridPrompt(string $userPrompt, string $provider, ?string $model = null): string
{
    $cleanPrompt = trim($userPrompt);

    // Keep Z-Image/Together behavior aligned with the legacy prompt strategy.
    if (in_array($provider, ['chutes', 'together'], true)) {
            $legacyStyleGuide = 'vector-like illustration, flat colors, bold outlines, no gradients, no heavy shadows, high contrast.';
        return trim($cleanPrompt . ' ' . $legacyStyleGuide);
    }

        // NanoGPT (juggernaut-z): minimal guidance so the model can render full scenes freely.
        $baseGuide = 'sharp details, high quality, no text, no letters, no logos, no watermark.';
        $stylePriorityGuide = 'Honor the requested style exactly (realistic, vector, anime, 3d, watercolor, etc.) without overriding it.';

        return trim($cleanPrompt . ' ' . $baseGuide . ' ' . $stylePriorityGuide);
}

    /**
 * Resize a base64 image to max 1024px on the longest side, JPEG quality 85.
 * This keeps payloads under ~300 KB which all AI backends accept.
 * Returns base64 string without data URI prefix.
 */
private function resizeImageForAI(string $base64): string
{
    // Strip data URI prefix if present
    $raw = $base64;
    if (str_starts_with($raw, 'data:image')) {
        $raw = preg_replace('/^data:image\/[^;]+;base64,/i', '', $raw);
    }

    $binary = base64_decode($raw);
    if (!$binary) return $raw;

    // GD en producción compila con --with-webp, por lo que imagecreatefromstring
    // soporta JPEG, PNG y WebP nativamente.
    $src = @imagecreatefromstring($binary);
    if (!$src) return $raw;

    $origW = imagesx($src);
    $origH = imagesy($src);
    $maxDim = 1024;

    if ($origW <= $maxDim && $origH <= $maxDim) {
        // Already small enough — just re-encode as JPEG to reduce file size
        ob_start();
        imagejpeg($src, null, 85);
        $out = ob_get_clean();
        imagedestroy($src);
        return base64_encode($out);
    }

    // Scale down proportionally
    if ($origW >= $origH) {
        $newW = $maxDim;
        $newH = (int) round($origH * $maxDim / $origW);
    } else {
        $newH = $maxDim;
        $newW = (int) round($origW * $maxDim / $origH);
    }

    $dst = imagecreatetruecolor($newW, $newH);
    // Preserve transparency for PNG sources
    imagealphablending($dst, false);
    imagesavealpha($dst, true);
    imagecopyresampled($dst, $src, 0, 0, 0, 0, $newW, $newH, $origW, $origH);
    imagedestroy($src);

    ob_start();
    imagejpeg($dst, null, 85);
    $out = ob_get_clean();
    imagedestroy($dst);

    return base64_encode($out);
}
}
