<?php

namespace App\Http\Controllers;

use App\Services\GeminiService;
use App\Services\BackgroundRemovalService;
use App\Jobs\GenerateDesignJob;
use Illuminate\Http\Request;
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
    public function generate(Request $request, GeminiService $gemini, BackgroundRemovalService $backgrounds)
    {
        $validated = $request->validate([
            'prompt' => ['required', 'string'],
            'backgroundColor' => ['nullable', 'string'],
        ]);

        $prompt = trim($validated['prompt']);
        $backgroundColor = $validated['backgroundColor'] ?? null;

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
        } else {
            $result = $gemini->generateDesign($prompt, $backgroundColor);
        }

        // Si hay imagen base64, la guardamos como última imagen
        if (is_array($result)) {
            $base64 = $result['imageBase64'] ?? $result['image_base64'] ?? $result['base64'] ?? null;
            if ($base64) {
                // Procesar fondo siempre que haya imagen
                $processed = $backgrounds->removeBackgroundByEdgeSample($base64, 40);
                if (is_string($processed) && $processed !== '') {
                    $result['imageBase64'] = $processed;
                    unset($result['image_url'], $result['url']);
                    $base64 = $processed;
                }
                session(['last_image' => $base64]);
            }
        }

        // Por defecto 200. Si el servicio indica error, usamos su 'status'.
        $status = 200;
        if (is_array($result) && array_key_exists('success', $result) && $result['success'] === false) {
            $status = isset($result['status']) ? (int) $result['status'] : 500;
        }

        return response()->json($result, $status);
    }
}
