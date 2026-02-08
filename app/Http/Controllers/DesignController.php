<?php

namespace App\Http\Controllers;

use App\Services\GeminiService;
use Illuminate\Http\Request;

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
    public function generate(Request $request, GeminiService $gemini)
    {
        // Validación del input: el prompt es obligatorio y debe ser texto.
        $validated = $request->validate([
            'prompt' => ['required', 'string'],
            'backgroundColor' => ['nullable', 'string'], // hex color like #ff0000
        ]);

        // Llamada al servicio: aquí se pasa el prompt a la IA/Backend.
        $result = $gemini->generateDesign($validated['prompt'], $validated['backgroundColor'] ?? null);

        // Por defecto 200. Si el servicio indica error, usamos su 'status'.
        $status = 200;
        if (is_array($result) && array_key_exists('success', $result) && $result['success'] === false) {
            $status = isset($result['status']) ? (int) $result['status'] : 500;
        }

        // Respuesta JSON hacia el frontend (vista Blade).
        return response()->json($result, $status);
    }
}
