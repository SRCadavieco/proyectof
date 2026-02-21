<?php

namespace App\Http\Controllers;

use App\Services\GeminiService;
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
    BackgroundRemovalService $backgrounds
) {
   try {
       $validated = $request->validate([
           'prompt' => ['required', 'string'],
           'chat_id' => ['required', 'exists:chats,id'],
           'backgroundColor' => ['nullable', 'string'],
           'imageBase64' => ['nullable', 'string'],
           'mimeType' => ['nullable', 'string'],
       ]);
   } catch (\Illuminate\Validation\ValidationException $e) {
       return response()->json([
           'success' => false,
           'error' => 'Error de validación',
           'details' => $e->errors(),
       ], 422);
   }

    $prompt = trim($validated['prompt']);
    $backgroundColor = $validated['backgroundColor'] ?? null;
    $chatId = $validated['chat_id'];

    // Obtener chat (sin auth, entorno pruebas)
    $chat = Chat::findOrFail($chatId);

    // Guardar mensaje del usuario (con imagen si se proporciona)
    $chat->messages()->create([
        'role' => 'user',
        'content' => $prompt,
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
    // Detectar edición
    $isEdit = str_starts_with($prompt, '/edit');

if ($isEdit) {
    $lastImage = session('last_image');

    if (!$lastImage) {
        return response()->json([
            'success' => false,
            'error' => 'No previous design found to edit.'
        ], 422);
    }

    $cleanPrompt = trim(preg_replace('/^\/edit/i', '', $prompt));

    $result = $gemini->generateFromReference(
        $cleanPrompt,
        $lastImage,
        'image/png'
    );

} elseif ($imageBase64) {

    if (str_starts_with($imageBase64, 'data:image')) {
        $imageBase64 = preg_replace(
            '/^data:image\/(png|jpeg|jpg|webp);base64,/i',
            '',
            $imageBase64
        );
    }

    $result = $gemini->generateFromReference(
        $prompt,
        $imageBase64,
        $mimeType
    );

} else {

    $result = $gemini->generateDesignWithContext(
        $prompt,
        $context,
        $backgroundColor
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
            $processed = $backgrounds->removeBackgroundByEdgeSample($base64, 40);

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

    // Guardar respuesta IA
    $chat->messages()->create([
        'role' => 'assistant',
        'image' => $imageValue,
    ]);

    // Título automático del chat
    if (!$chat->title) {
        $chat->update([
            'title' => Str::limit($prompt, 40),
        ]);
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
