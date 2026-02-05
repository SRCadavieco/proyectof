<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DesignController;

Route::get('/', function () {
    return view('app');
});

if (app()->environment('local')) {
    // En local, expone sin auth para facilitar la prueba manual
    Route::get('/designs', [DesignController::class, 'form'])->name('designs.form');
    Route::post('/designs/generate', [DesignController::class, 'generate'])->name('designs.generate');
} else {
    Route::middleware('auth')->group(function () {
        Route::get('/designs', [DesignController::class, 'form'])->name('designs.form');
        Route::post('/designs/generate', [DesignController::class, 'generate'])->name('designs.generate');
    });
}

// Debug local-only endpoint to inspect Gemini config
if (app()->environment('local')) {
    Route::get('/debug/gemini-config', function () {
        return response()->json([
            'config' => config('services.gemini'),
            'env_url' => env('GEMINI_BACKEND_URL'),
            'env_token_present' => env('GEMINI_BACKEND_TOKEN') ? true : false,
            'computed' => [
                'full_url' => rtrim(config('services.gemini.url'), '/').'/'.ltrim(config('services.gemini.path', '/generate-design'), '/'),
            ],
        ]);
    });

    Route::get('/debug/gemini-probe', function () {
        $baseUrl = (string) config('services.gemini.url');
        $token = (string) config('services.gemini.token');
        $path = (string) (config('services.gemini.path') ?? '/generate-design');

        $paths = array_values(array_unique([
            $path,
            '/gen',
            '/generate-design',
            '/generate',
            '/api/gen',
            '/api/generate',
            '/',
            '/health',
        ]));

        $combos = [
            ['method' => 'GET', 'auth' => 'x-goog-api-key'],
            ['method' => 'POST', 'auth' => 'x-goog-api-key'],
            ['method' => 'GET', 'auth' => 'x-api-key'],
            ['method' => 'POST', 'auth' => 'x-api-key'],
            ['method' => 'GET', 'auth' => 'bearer'],
            ['method' => 'POST', 'auth' => 'bearer'],
            ['method' => 'GET', 'auth' => 'query-key'],
            ['method' => 'POST', 'auth' => 'query-key'],
        ];

        $results = [];
        foreach ($paths as $p) {
            $url = rtrim($baseUrl, '/').'/'.ltrim($p, '/');
            foreach ($combos as $c) {
                $testUrl = $url;
                $request = Illuminate\Support\Facades\Http::acceptJson()->timeout(12);

                if ($c['auth'] === 'x-goog-api-key' && $token) {
                    $request = $request->withHeaders(['x-goog-api-key' => $token]);
                } elseif ($c['auth'] === 'x-api-key' && $token) {
                    $request = $request->withHeaders(['X-API-Key' => $token]);
                } elseif ($c['auth'] === 'bearer' && $token) {
                    $request = $request->withToken($token);
                } elseif ($c['auth'] === 'query-key' && $token) {
                    $testUrl .= (str_contains($testUrl, '?') ? '&' : '?').'key='.urlencode($token);
                }

                try {
                    $payload = ['prompt' => 'probe'];
                    $resp = $c['method'] === 'GET' ? $request->get($testUrl, $payload) : $request->post($testUrl, $payload);
                    $body = (string) $resp->body();
                    $results[] = [
                        'path' => $p,
                        'method' => $c['method'],
                        'auth' => $c['auth'],
                        'url' => $testUrl,
                        'status' => $resp->status(),
                        'ok' => $resp->ok(),
                        'body_preview' => mb_substr($body, 0, 160),
                    ];
                } catch (\Throwable $e) {
                    $results[] = [
                        'path' => $p,
                        'method' => $c['method'],
                        'auth' => $c['auth'],
                        'url' => $testUrl,
                        'error' => $e->getMessage(),
                    ];
                }
            }
        }

        return response()->json([
            'base_url' => $baseUrl,
            'path' => $path,
            'probe' => $results,
        ]);
    });
}
