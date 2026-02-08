<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Servicio que encapsula la comunicación con el backend/IA Gemini.
 *
 * Se encarga de construir la URL, aplicar autenticación según configuración,
 * enviar el prompt y tratar las respuestas/errores en un formato consistente.
 */
class GeminiService
{
    /**
     * Genera un diseño a partir de un prompt de texto.
     *
     * Flujo:
     * 1) Lee configuración: URL base, token, ruta, cabecera de auth, método.
     * 2) Construye la URL final y añade token por query si corresponde.
     * 3) Prepara el cliente HTTP con headers de autenticación.
     * 4) Envía el payload `{ prompt }` por GET o POST.
     * 5) Devuelve el JSON del backend o un objeto de error consistente.
     */
    public function generateDesign(string $prompt)
    {
        // Configuración desde config/services.php o variables de entorno.
        $baseUrl = (string) config('services.gemini.url');           // URL del backend
        $token = (string) config('services.gemini.token');           // Token compartido
        $path = (string) (config('services.gemini.path') ?? '/generate-design');
        $authHeader = strtolower((string) (config('services.gemini.auth_header') ?? 'bearer')); // Tipo de auth
        $authQueryKey = (string) (config('services.gemini.auth_query_key') ?? '');              // Auth por query
        $method = strtoupper((string) (config('services.gemini.method') ?? 'POST'));            // Verbo HTTP

        // Validaciones tempranas de configuración faltante.
        if (empty($baseUrl)) {
            return [
                'success' => false,
                'error' => 'Falta configuración: services.gemini.url',
                'status' => 500,
                'code' => 'config_error',
            ];
        }

        if (empty($token) && $authHeader !== 'none' && empty($authQueryKey)) {
            return [
                'success' => false,
                'error' => 'Falta configuración: services.gemini.token',
                'status' => 500,
                'code' => 'config_error',
            ];
        }

        // Construye la URL final y añade auth por query si está configurado.
        $url = rtrim($baseUrl, '/').'/'.ltrim($path, '/');
        if (!empty($authQueryKey) && !empty($token)) {
            $glue = str_contains($url, '?') ? '&' : '?';
            $url .= $glue.$authQueryKey.'='.urlencode($token);
        }

        try {
            // Cliente HTTP JSON, con timeout y reintentos simples.
            $request = Http::acceptJson()
                ->timeout(20)
                ->retry(2, 500);

            // Cabeceras de autenticación según estrategia elegida.
            if ($authHeader === 'bearer' || $authHeader === 'both') {
                if (!empty($token)) {
                    $request = $request->withToken($token);
                }
            }
            if ($authHeader === 'x-api-key' || $authHeader === 'both') {
                if (!empty($token)) {
                    $request = $request->withHeaders(['X-API-Key' => $token]);
                }
            }
            if ($authHeader === 'x-goog-api-key') {
                if (!empty($token)) {
                    $request = $request->withHeaders(['x-goog-api-key' => $token]);
                }
            }

            // Logging en local para diagnóstico (token enmascarado).
            if (app()->environment('local')) {
                $masked = empty($token) ? null : (str_repeat('*', max(strlen($token) - 6, 0)).substr($token, -6));
                Log::debug('Gemini request', [
                    'url' => $url,
                    'method' => $method,
                    'auth' => $authHeader,
                    'query_auth' => $authQueryKey ? true : false,
                    'token_masked' => $masked,
                ]);
            }

            // Payload con el prompt que describe el diseño deseado.
            $payload = [ 'prompt' => $prompt ];

            // Envía la solicitud al backend.
            $response = $method === 'GET'
                ? $request->get($url, $payload)
                : $request->post($url, $payload);

            // Si el backend falla, normalizamos el error.
            if ($response->failed()) {
                $status = $response->status();
                $json = null;
                try { $json = $response->json(); } catch (\Throwable $e) { $json = null; }
                $errorMsg = is_array($json) ? ($json['error'] ?? null) : null;
                if (!$errorMsg) { $errorMsg = (string) $response->body(); }
                $details = is_array($json) ? ($json['details'] ?? null) : null;
                $blockReason = is_array($json) ? ($json['blockReason'] ?? null) : null;

                // Caso común: el backend indica que no hay imagen en la respuesta.
                // Lo mapeamos a 422 para que el frontend lo trate como error de entrada/contenido.
                if (stripos($errorMsg, 'No image in response') !== false) {
                    return [
                        'success' => false,
                        'error' => 'El modelo no devolvió una imagen. Puede que el prompt esté bloqueado por políticas (personas reales, marcas, etc.) o que la salida haya sido texto. Prueba describir estilos y atributos visuales sin rasgos identificables.',
                        'details' => $details,
                        'blockReason' => $blockReason,
                        'status' => 422,
                        'code' => 'no_image',
                    ];
                }

                return [
                    'success' => false,
                    'error' => $errorMsg,
                    'details' => $details,
                    'blockReason' => $blockReason,
                    'status' => $status,
                ];
            }

            // Éxito: devolvemos el JSON del backend tal cual.
            return $response->json();
        } catch (\Throwable $e) {
            // Error de conexión o excepción inesperada.
            return [
                'success' => false,
                'error' => 'Error conectando con el backend: '.$e->getMessage(),
                'status' => 500,
                'code' => 'connection_error',
            ];
        }
    }
}
