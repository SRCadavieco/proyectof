<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Servicio para la generación de imágenes vía Chutes AI.
 *
 * Chutes AI es una plataforma de inferencia descentralizada (Bittensor SN64)
 * que expone modelos de difusión (FLUX.1, Stable Diffusion, etc.) como
 * endpoints HTTP configurables.
 *
 * Autenticación: Bearer token con prefijo `cpk_`.
 *
 * Configuración (config/services.php → env vars):
 *   CHUTES_BACKEND_URL          URL base del chute desplegado
 *   CHUTES_API_TOKEN            API key (cpk_...)
 *   CHUTES_BACKEND_PATH         Ruta text-to-image  (default: /generate)
 *   CHUTES_BACKEND_PATH_IMG2IMG Ruta image-to-image (default: /img2img)
 */
class ChutesService
{
    /**
     * Genera una imagen a partir de un prompt de texto.
     *
     * El endpoint debe aceptar un body JSON con al menos `prompt`.
     * La respuesta puede ser:
     *   - Binaria (image/jpeg, image/png) → se convierte a base64.
     *   - JSON con campo `image` o `imageBase64` (base64 puro).
     */
    public function generateDesign(string $prompt, ?string $backgroundColor = null, string $model = 'z_image_turbo'): array
    {
        $urls    = config('services.chutes.urls', []);
        $baseUrl = (string) ($urls[$model] ?? $urls['z_image_turbo'] ?? '');
        $token   = (string) config('services.chutes.token');
        $path    = (string) (config('services.chutes.path') ?? '/generate');

        if (empty($baseUrl)) {
            return [
                'success' => false,
                'error'   => "Falta configuración para el modelo '{$model}' (services.chutes.urls)",
                'status'  => 500,
                'code'    => 'config_error',
            ];
        }

        $url = rtrim($baseUrl, '/') . '/' . ltrim($path, '/');

        $finalPrompt = $prompt;
        if (!empty($backgroundColor)) {
            $hex       = strtolower(trim($backgroundColor));
            $colorName = match ($hex) {
                '#2b7be4' => 'azul',
                '#ff00ff' => 'fucsia',
                '#ff0000' => 'rojo',
                '#00ff00' => 'verde',
                default   => $hex,
            };
            $finalPrompt .= "\nFondo sólido y uniforme de color {$colorName} ({$hex}). Sin transparencia.";
        }

        $payload = ['prompt' => $finalPrompt, 'negative_prompt' => 'comic panel border, panel frame, speech bubble, text box, manga panel, vignette border, white border frame, page layout, multiple panels'];
        if (!empty($backgroundColor)) {
            $payload['backgroundColor'] = $backgroundColor;
        }

        return $this->sendRequest($url, $token, $payload);
    }

    /**
     * Genera una imagen usando como referencia una imagen base64 existente.
     *
     * El endpoint debe aceptar `prompt` e `image_b64` (o `imageBase64`).
     * Se prueba primero con `image_b64` (estándar Chutes), que es el campo
     * usado en los ejemplos oficiales de imagen-a-imagen.
     */
    public function generateFromReference(
        string $prompt,
        string $imageBase64,
        string $mimeType = 'image/png',
        string $model = 'z_image_turbo'
    ): array {
        $urls    = config('services.chutes.urls', []);
        $baseUrl = (string) ($urls[$model] ?? $urls['z_image_turbo'] ?? '');
        $token   = (string) config('services.chutes.token');
        $path    = (string) (config('services.chutes.path') ?? '/generate');

        if (empty($baseUrl)) {
            return [
                'success' => false,
                'error'   => "Falta configuración para el modelo '{$model}' (services.chutes.urls)",
                'status'  => 500,
                'code'    => 'config_error',
            ];
        }

        // Limpiar prefijo data URI si lo tiene.
        $base64 = $imageBase64;
        if (str_starts_with($base64, 'data:image')) {
            $base64 = preg_replace('/^data:image\/(png|jpeg|jpg|webp);base64,/i', '', $base64);
        }

        $url     = rtrim($baseUrl, '/') . '/' . ltrim($path, '/');

        // Incluir la imagen en todos los campos que usan distintos modelos Chutes.
        // Si el modelo no soporta img2img simplemente ignorará los campos extra.
        $payload = [
            'prompt'      => $prompt,
            'image_b64'   => $base64,   // Z-Image / FLUX estilo Chutes
            'init_image'  => $base64,   // Hunyuan y otros
            'image'       => $base64,   // campo genérico
        ];

        return $this->sendRequest($url, $token, $payload);
    }

    /**
     * Genera una imagen con contexto de conversación previo.
     * Delega a generateDesign() preprependiendo el historial al prompt.
     */
    public function generateDesignWithContext(
        string $prompt,
        array $context = [],
        ?string $backgroundColor = null,
        string $model = 'z_image_turbo'
    ): array {
        // Diffusion models (FLUX, Z-Image) use a CLIP encoder with ~77 token limit.
        // Conversation history makes prompts too long and causes API errors — ignore it.
        // Truncate to 350 chars to stay safely under the token limit.
        $truncated = mb_substr($prompt, 0, 350);

        return $this->generateDesign($truncated, $backgroundColor, $model);
    }

    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Envía la petición HTTP al endpoint Chutes y normaliza la respuesta.
     *
     * Maneja dos formatos de respuesta:
     *   1. Binario (Content-Type image/*) → convierte a base64.
     *   2. JSON con campo `image`, `imageBase64` o `base64`.
     */
    private function sendRequest(string $url, string $token, array $payload): array
    {
        try {
            Log::debug('ChutesService request', [
                'url'     => $url,
                'payload' => array_merge($payload, isset($payload['image_b64'])
                    ? ['image_b64' => substr($payload['image_b64'], 0, 30) . '...']
                    : []),
            ]);

            $request = Http::timeout(180)->retry(1, 2000);

            if (!empty($token)) {
                $request = $request->withToken($token);
            }

            $response = $request->post($url, $payload);

            Log::debug('ChutesService response', [
                'status'       => $response->status(),
                'content_type' => $response->header('Content-Type'),
            ]);

            if ($response->failed()) {
                $body = $response->body();
                $json = null;
                try { $json = $response->json(); } catch (\Throwable) {}
                $error = is_array($json) ? ($json['detail'] ?? $json['error'] ?? $body) : $body;

                return [
                    'success' => false,
                    'error'   => $error,
                    'status'  => $response->status(),
                ];
            }

            // Respuesta binaria (image/jpeg, image/png, etc.)
            $contentType = strtolower($response->header('Content-Type') ?? '');
            if (str_contains($contentType, 'image/')) {
                $base64 = base64_encode($response->body());
                return [
                    'success'     => true,
                    'imageBase64' => $base64,
                ];
            }

            // Respuesta JSON
            $json = $response->json();

            // Normalizar los distintos nombres de campo que usan los chutes.
            $base64 =
                $json['imageBase64']  ??
                $json['image_base64'] ??
                $json['base64']       ??
                $json['image']        ??
                null;

            if ($base64) {
                return array_merge($json, ['imageBase64' => $base64, 'success' => true]);
            }

            // Si el JSON no tiene imagen, devolvemos tal cual (puede ser un error descriptivo).
            return array_merge($json, ['success' => isset($json['error']) ? false : true]);

        } catch (\Throwable $e) {
            Log::error('ChutesService exception', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'error'   => $e->getMessage(),
                'status'  => 500,
            ];
        }
    }
}
