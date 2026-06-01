
<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DesignController;
use App\Http\Controllers\PrintifyController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\SubscriptionController;
use App\Http\Controllers\StripeWebhookController;
use App\Http\Controllers\CreditPackController;
use App\Http\Controllers\TrendController;
use App\Models\User;


// FAQ page
Route::get('/faq', function () {
    return view('faq');
});

// Terms of Use page
Route::get('/terms', function () {
    return view('terms');
});

// Privacy Policy page
Route::get('/privacy', function () {
    return view('privacy');
});

// Pricing page
Route::get('/pricing', function () {
    return view('pricing');
});
// Home apunta siempre a la vista welcome
Route::get('/', function () {
    $totalDesigns = \App\Models\SavedDesign::count();
    return view('welcome', compact('totalDesigns'));
})->name('home');

// Alias requerido por los controladores de autenticación de Laravel Breeze
Route::get('/dashboard', function () {
    return redirect()->route('designs.form');
})->middleware('auth')->name('dashboard');

// Public sitemap
Route::get('/sitemap.xml', function () {
    $baseUrl = rtrim(config('app.url') ?: url('/'), '/');
    $lastmod = now()->toAtomString();

    $pages = [
        ['path' => '/', 'changefreq' => 'weekly', 'priority' => '1.0'],
        ['path' => '/pricing', 'changefreq' => 'weekly', 'priority' => '0.9'],
        ['path' => '/faq', 'changefreq' => 'monthly', 'priority' => '0.7'],
        ['path' => '/terms', 'changefreq' => 'yearly', 'priority' => '0.4'],
        ['path' => '/privacy', 'changefreq' => 'yearly', 'priority' => '0.4'],
        ['path' => '/login', 'changefreq' => 'monthly', 'priority' => '0.5'],
        ['path' => '/register', 'changefreq' => 'monthly', 'priority' => '0.5'],
    ];

    $xml = '<?xml version="1.0" encoding="UTF-8"?>';
    $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';

    foreach ($pages as $page) {
        $loc = $page['path'] === '/' ? $baseUrl . '/' : $baseUrl . $page['path'];

        $xml .= '<url>';
        $xml .= '<loc>' . e($loc) . '</loc>';
        $xml .= '<lastmod>' . $lastmod . '</lastmod>';
        $xml .= '<changefreq>' . $page['changefreq'] . '</changefreq>';
        $xml .= '<priority>' . $page['priority'] . '</priority>';
        $xml .= '</url>';
    }

    $xml .= '</urlset>';

    return response($xml, 200)->header('Content-Type', 'application/xml');
})->name('sitemap');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'show'])->name('profile.show');
    Route::get('/designs/select-style', [DesignController::class, 'selectStyle'])->name('designs.select-style');
    Route::get('/design', [DesignController::class, 'form']);
    Route::get('/designs', [DesignController::class, 'form'])->name('designs.form');
    Route::post('/designs/generate', [DesignController::class, 'generate'])->name('designs.generate');
    Route::get('/designs/generation/{id}', [DesignController::class, 'generationStatus'])->name('designs.generation.status');

    // Credit packs (one-time purchases)
    Route::post('/credits/checkout', [CreditPackController::class, 'checkout'])->name('credits.checkout');
    Route::get('/credits/success',   [CreditPackController::class, 'success'])->name('credits.success');

    // Saved designs
    Route::get('/designs/saved',                  [DesignController::class, 'savedDesigns'])->name('designs.saved.index');
    Route::post('/designs/saved',                 [DesignController::class, 'saveDesign'])->name('designs.saved.store');
    Route::patch('/designs/saved/{savedDesign}',  [DesignController::class, 'renameSavedDesign'])->name('designs.saved.rename');
    Route::delete('/designs/saved/{savedDesign}', [DesignController::class, 'deleteSavedDesign'])->name('designs.saved.destroy');

    // Token API
    Route::get('/api/tokens', function () {
        $user = auth()->user();
        return response()->json([
            'remaining' => $user->tokens,
            'total' => User::creditsForPlan((string) ($user->plan ?? 'free')),
        ]);
    })->name('api.tokens');

    // CSRF token refresh — called by JS when a 419 is received
    Route::get('/api/csrf-refresh', function () {
        return response()->json(['token' => csrf_token()]);
    })->name('api.csrf-refresh');

    // Trend intelligence API
    Route::prefix('api/trends')->name('api.trends.')->controller(TrendController::class)->group(function () {
        Route::get('/',               'index')->name('index');
        Route::get('/debug',          'debug')->name('debug');
        Route::get('/{id}',           'show')->name('show')->whereNumber('id');
        Route::post('/refresh',       'refresh')->name('refresh');
    });

    // Printify
    Route::post('/printify/connect',         [PrintifyController::class, 'connect'])->name('printify.connect');
    Route::post('/printify/dismiss-popup',   [PrintifyController::class, 'dismissPopup'])->name('printify.dismiss-popup');
    Route::delete('/printify/disconnect',    [PrintifyController::class, 'disconnect'])->name('printify.disconnect');
    Route::get('/printify/garments',       [PrintifyController::class, 'garments'])->name('printify.garments');
    Route::get('/printify/status',         [PrintifyController::class, 'status'])->name('printify.status');
    Route::get('/printify/shops',       [PrintifyController::class, 'shops'])->name('printify.shops');
    Route::post('/printify/products',      [PrintifyController::class, 'createProduct'])->name('printify.products');
    Route::post('/printify/products/bulk', [PrintifyController::class, 'createProductBulk'])->name('printify.products.bulk');

    // Subscription / Billing
    Route::post('/subscribe', [SubscriptionController::class, 'checkout'])->name('subscription.checkout');
    Route::get('/subscription/success', [SubscriptionController::class, 'success'])->name('subscription.success');
    Route::get('/subscription/cancel', [SubscriptionController::class, 'cancel'])->name('subscription.cancel');
    Route::post('/subscription/cancel', [SubscriptionController::class, 'cancelSubscription'])->name('subscription.cancel.confirm');
    Route::get('/billing', [SubscriptionController::class, 'portal'])->name('billing');
});

// Stripe webhooks (no CSRF)
Route::post('/stripe/webhook', [StripeWebhookController::class, 'handleWebhook'])->name('cashier.webhook');

// Temp image route — serves files written to sys_get_temp_dir() by TogetherService img2img.
// Files are deleted immediately after Together fetches them (or after the request).
Route::get('/tmp-img/{filename}', function (string $filename) {
    // Sanitise: only allow UUID-named image files, no path traversal
    if (!preg_match('/^[0-9a-f\-]{36}\.(jpg|png)$/i', $filename)) {
        abort(404);
    }
    $path = rtrim(sys_get_temp_dir(), '/\\') . DIRECTORY_SEPARATOR . $filename;
    if (!file_exists($path)) {
        abort(404);
    }
    $mime = str_ends_with($filename, '.png') ? 'image/png' : 'image/jpeg';
    return response()->file($path, ['Content-Type' => $mime]);
})->name('tmp-img');

// Admin panel
Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [AdminController::class, 'dashboard'])->name('dashboard');
    Route::get('/users', [AdminController::class, 'users'])->name('users');
    Route::put('/users/{user}', [AdminController::class, 'updateUser'])->name('users.update');
    Route::post('/users/{user}/tokens', [AdminController::class, 'addTokens'])->name('users.tokens');
    Route::delete('/users/{user}', [AdminController::class, 'deleteUser'])->name('users.delete');
    Route::get('/stats', [AdminController::class, 'stats'])->name('stats');
    Route::get('/api-costs', [AdminController::class, 'apiCosts'])->name('api-costs');
});

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

// Printify upload endpoint removed

// Testing page with background tools (Blade: resources/views/app.blade.php)
Route::get('/app', function () {
    return view('app');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile/edit', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::post('/profile/email', [ProfileController::class, 'updateEmail'])->name('profile.update-email');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::middleware('auth')->group(function () {
    Route::get('/chats', [ChatController::class, 'index']);
    Route::post('/chats', [ChatController::class, 'store']);
    Route::get('/chats/{chat}', [ChatController::class, 'show']);
    Route::patch('/chats/{chat}', [ChatController::class, 'rename']);
    Route::delete('/chats/{chat}', [ChatController::class, 'destroy']);
    Route::delete('/chats', [ChatController::class, 'destroyAll']);
});
require __DIR__.'/auth.php';

