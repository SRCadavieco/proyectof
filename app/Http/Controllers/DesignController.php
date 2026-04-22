<?php

namespace App\Http\Controllers;

use App\Services\GeminiService;
use App\Services\ChutesService;
use App\Services\BackgroundRemovalService;
use App\Jobs\GenerateDesignJob;
use Illuminate\Http\Request;
use App\Models\Chat;

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
    BackgroundRemovalService $backgrounds
) {
   set_time_limit(300); // Chutes models can take up to ~3 min on cold start
   try {
       $validated = $request->validate([
           'prompt' => ['required', 'string'],
           'chat_id' => ['required', 'exists:chats,id'],
           'backgroundColor' => ['nullable', 'string'],
           'imageBase64' => ['nullable', 'string'],
           'mimeType' => ['nullable', 'string'],
           'model' => ['nullable', 'string', 'in:fabric_light,fabric_pro,z_image_turbo,flux_schnell'],
           'provider' => ['nullable', 'string', 'in:gemini,chutes'],
           'is_edit' => ['nullable', 'boolean'],
       ]);
   } catch (\Illuminate\Validation\ValidationException $e) {
       return response()->json([
           'success' => false,
           'error' => 'Error de validación',
           'details' => $e->errors(),
       ], 422);
   }

    $userPrompt = trim($validated['prompt']);
    $provider = $validated['provider'] ?? 'gemini';

    if ($provider === 'chutes') {
        // Para modelos de difusión: prompt visual descriptivo, no instrucciones en lenguaje natural
        $prompt = "print-ready graphic design for clothing, centered on white background, "
                . "clean vector illustration, flat colors, bold outlines, no gradients, no shadows, "
                . "no text unless specified, high contrast, isolated subject, "
                . $userPrompt;
    } else {
        // Gemini (LLM): entiende instrucciones en lenguaje natural
        $systemPrompt = "You are a professional fashion and apparel designer.\nCreate a print-ready, high-quality design suitable for clothing.\n\nDesign requirements:\n\nCentered composition\nPlain color background (no shadows)\nNo use of gradients\nBackground must be a color you haven't used for the design\nClean vector style with crisp, well-defined lines\nScalable without loss of quality\nDo not create any text unless the user specifies so. Create only the words the user has mentioned\n\n";
        $prompt = $systemPrompt . $userPrompt;
    }
    $backgroundColor = $validated['backgroundColor'] ?? null;
    $chatId = $validated['chat_id'];

    // Obtener chat y verificar que pertenece al usuario autenticado
    $chat = Chat::findOrFail($chatId);
    if ($chat->user_id !== \Illuminate\Support\Facades\Auth::id()) {
        return response()->json(['success' => false, 'error' => 'Forbidden'], 403);
    }

    // Verificar y descontar token del usuario
    $user = $request->user();
    if ($user->tokens <= 0) {
        return response()->json([
            'success' => false,
            'error' => 'No tienes tokens disponibles. Consigue más para seguir diseñando.',
        ], 403);
    }
    $user->decrement('tokens');

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
    $model = $validated['model'] ?? ($provider === 'chutes' ? 'z_image_turbo' : 'fabric_light');
    // $provider ya está definido arriba

    $isEdit = !empty($validated['is_edit']);

// Seleccionar el servicio de IA según el proveedor elegido
$ai = $provider === 'chutes' ? $chutes : $gemini;

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

    // Limpiar prefijo data URI si lo tiene
    $cleanBase64 = $lastImage;
    if (str_starts_with($cleanBase64, 'data:image')) {
        $cleanBase64 = preg_replace('/^data:image\/(png|jpeg|jpg|webp);base64,/i', '', $cleanBase64);
    }

    $result = $ai->generateFromReference(
        $prompt,
        $cleanBase64,
        'image/png',
        $model
    );

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

} else {

    $result = $ai->generateDesignWithContext(
        $prompt,
        $context,
        $backgroundColor,
        $model
    );
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
            if ($noBg) {
                $processed = $backgrounds->convertToWebp($noBg) ?? $noBg;
            } else {
                $processed = null;
            }

            if (is_string($processed) && $processed !== '') {
                $result['imageBase64'] = $processed;
                unset($result['image_url'], $result['url']);
                $base64 = $processed;
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
            'role' => 'assistant',
            'image' => $imageValue,
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
        ], 422);
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

    return response()->json($result, $status);
}
}
