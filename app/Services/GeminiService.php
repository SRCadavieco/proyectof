<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeminiService
{
    public function generateDesign(string $prompt)
    {
        $baseUrl = (string) config('services.gemini.url');
        $token = (string) config('services.gemini.token');
        $path = (string) (config('services.gemini.path') ?? '/generate-design');
        $authHeader = strtolower((string) (config('services.gemini.auth_header') ?? 'bearer'));
        $authQueryKey = (string) (config('services.gemini.auth_query_key') ?? '');
        $method = strtoupper((string) (config('services.gemini.method') ?? 'POST'));

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

        $url = rtrim($baseUrl, '/').'/'.ltrim($path, '/');
        if (!empty($authQueryKey) && !empty($token)) {
            $glue = str_contains($url, '?') ? '&' : '?';
            $url .= $glue.$authQueryKey.'='.urlencode($token);
        }

        try {
            $request = Http::acceptJson()
                ->timeout(20)
                ->retry(2, 500);

            // Auth options
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

            // Log en local (token oculto)
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

            $payload = [ 'prompt' => $prompt ];
            $response = $method === 'GET'
                ? $request->get($url, $payload)
                : $request->post($url, $payload);

            if ($response->failed()) {
                $status = $response->status();
                $json = null;
                try { $json = $response->json(); } catch (\Throwable $e) { $json = null; }
                $errorMsg = is_array($json) ? ($json['error'] ?? null) : null;
                if (!$errorMsg) { $errorMsg = (string) $response->body(); }
                $details = is_array($json) ? ($json['details'] ?? null) : null;
                $blockReason = is_array($json) ? ($json['blockReason'] ?? null) : null;

                // Map common backend case to a user-friendly 422 instead of generic 500
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

            return $response->json();
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'error' => 'Error conectando con el backend: '.$e->getMessage(),
                'status' => 500,
                'code' => 'connection_error',
            ];
        }
    }
}
