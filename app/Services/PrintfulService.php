<?php

namespace App\Services;

use App\Models\PrintfulConnection;
use Illuminate\Support\Facades\Http;

class PrintfulService
{
    private const BASE_URL   = 'https://api.printful.com/';
    private const AUTH_URL   = 'https://www.printful.com/oauth/authorize';
    private const TOKEN_URL  = 'https://www.printful.com/oauth/token';

    /**
     * Garment type → Printful catalog product ID.
     * Verify / adjust via GET https://api.printful.com/products
     */
    public const PRODUCT_MAP = [
        'tshirt'     => 71,   // Bella+Canvas 3001 Unisex Short Sleeve Jersey T-Shirt
        'hoodie'     => 380,  // Gildan 18500 Heavy Blend Hooded Sweatshirt
        'tanktop'    => 145,  // Bella+Canvas 3480 Unisex Jersey Tank
        'longsleeve' => 57,   // Bella+Canvas 3501 Unisex Jersey Long Sleeve Tee
        'sweatshirt' => 382,  // Independent Trading Co. SS3000 Midweight Sweatshirt
    ];

    // ─── OAuth helpers ───────────────────────────────────────────────────────

    public static function authorizeUrl(string $state): string
    {
        return self::AUTH_URL . '?' . http_build_query([
            'client_id'     => config('services.printful.client_id'),
            'redirect_uri'  => route('printful.callback'),
            'response_type' => 'code',
            'state'         => $state,
            'scope'         => 'store_data orders files sync_products',
        ]);
    }

    public function exchangeCode(string $code): array
    {
        $response = Http::asForm()->post(self::TOKEN_URL, [
            'grant_type'    => 'authorization_code',
            'client_id'     => config('services.printful.client_id'),
            'client_secret' => config('services.printful.client_secret'),
            'code'          => $code,
            'redirect_uri'  => route('printful.callback'),
        ]);

        $response->throw();
        return $response->json();
    }

    public function refreshTokens(PrintfulConnection $connection): void
    {
        $response = Http::asForm()->post(self::TOKEN_URL, [
            'grant_type'    => 'refresh_token',
            'client_id'     => config('services.printful.client_id'),
            'client_secret' => config('services.printful.client_secret'),
            'refresh_token' => $connection->refresh_token,
        ]);

        $response->throw();
        $data = $response->json();

        $connection->update([
            'access_token'            => $data['access_token'],
            'refresh_token'           => $data['refresh_token'] ?? $connection->refresh_token,
            'access_token_expires_at' => now()->addSeconds($data['expires_in'] ?? 3600),
        ]);
    }

    // ─── Internal HTTP client ─────────────────────────────────────────────────

    private function http(PrintfulConnection $connection)
    {
        if ($connection->isAccessTokenExpired()) {
            $this->refreshTokens($connection);
            $connection->refresh();
        }

        return Http::withToken($connection->access_token)
            ->withHeaders(['User-Agent' => 'FabricAI/1.0'])
            ->acceptJson()
            ->baseUrl(self::BASE_URL);
    }

    // ─── Stores ──────────────────────────────────────────────────────────────

    public function getStores(PrintfulConnection $connection): array
    {
        $response = $this->http($connection)->get('stores');
        $response->throw();
        $data = $response->json();
        // Printful wraps responses in { code, result }
        return $data['result'] ?? $data;
    }

    // ─── Files ───────────────────────────────────────────────────────────────

    public function uploadFile(PrintfulConnection $connection, string $imageSource, string $filename = 'fabricai-design.png'): array
    {
        $isUrl = str_starts_with($imageSource, 'http://') || str_starts_with($imageSource, 'https://');

        if ($isUrl) {
            $body = ['type' => 'default', 'url' => $imageSource, 'filename' => $filename];
        } else {
            // base64 — strip data URI prefix if present
            $contents = str_contains($imageSource, ',')
                ? explode(',', $imageSource, 2)[1]
                : $imageSource;

            $body = ['type' => 'default', 'content' => $contents, 'filename' => $filename];
        }

        $response = $this->http($connection)->post('files', $body);
        $response->throw();
        $data = $response->json();
        return $data['result'] ?? $data;
    }

    // ─── Catalog ─────────────────────────────────────────────────────────────

    public function getProductVariants(PrintfulConnection $connection, int $productId): array
    {
        $response = $this->http($connection)->get("products/{$productId}");
        $response->throw();
        $data = $response->json();
        $result = $data['result'] ?? $data;
        return $result['variants'] ?? [];
    }

    // ─── Sync Products ────────────────────────────────────────────────────────

    public function createSyncProduct(PrintfulConnection $connection, int $storeId, array $data): array
    {
        $response = $this->http($connection)
            ->withHeaders(['X-PF-Store-Id' => (string) $storeId])
            ->post('store/products', $data);
        $response->throw();
        $body = $response->json();
        return $body['result'] ?? $body;
    }

    // ─── High-level helper ────────────────────────────────────────────────────

    public function sendDesign(
        PrintfulConnection $connection,
        int $storeId,
        string $garmentType,
        string $imageSource,
        string $title
    ): array {
        $productId = self::PRODUCT_MAP[$garmentType] ?? self::PRODUCT_MAP['tshirt'];

        // 1. Upload design file
        $file = $this->uploadFile($connection, $imageSource);
        $fileUrl = $file['preview_url'] ?? $file['url'] ?? null;

        // 2. Fetch catalog variants and pick common sizes
        $allVariants = $this->getProductVariants($connection, $productId);

        $preferred = array_values(array_filter($allVariants, static function (array $v): bool {
            return (bool) preg_match('/\b(XS|S|M|L|XL|2XL)\b/', $v['name'] ?? $v['size'] ?? '');
        }));

        $selected = $preferred ?: array_slice($allVariants, 0, 6);

        if (empty($selected)) {
            throw new \RuntimeException("No variants available for Printful product {$productId}.");
        }

        // 3. Build sync variants
        $syncVariants = array_map(static function (array $v) use ($fileUrl): array {
            $variant = [
                'variant_id'   => $v['id'],
                'retail_price' => '20.00',
                'files'        => [
                    ['type' => 'front', 'url' => $fileUrl],
                ],
            ];
            return $variant;
        }, $selected);

        // 4. Create sync product
        $payload = [
            'sync_product' => [
                'name'      => $title,
                'thumbnail' => $fileUrl,
            ],
            'sync_variants' => $syncVariants,
        ];

        $product = $this->createSyncProduct($connection, $storeId, $payload);

        return $product;
    }
}
