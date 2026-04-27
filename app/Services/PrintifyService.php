<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class PrintifyService
{
    private const BASE_URL = 'https://api.printify.com/v1';

    // Blueprint ID map (Printify catalog IDs)
    public const BLUEPRINT_MAP = [
        'tshirt'     => 6,    // Unisex Heavy Cotton Tee (Gildan 5000)
        'hoodie'     => 77,   // Unisex Heavy Blend™ Hooded Sweatshirt (Gildan 18500)
        'tanktop'    => 39,   // Unisex Jersey Tank
        'longsleeve' => 41,   // Unisex Jersey Long Sleeve Tee
        'sweatshirt' => 49,   // Unisex Heavy Blend™ Crewneck Sweatshirt
    ];

    // ─── HTTP client ─────────────────────────────────────────────────────────

    private function http(string $token)
    {
        return Http::withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'Accept'        => 'application/json',
        ]);
    }

    // ─── Public methods ───────────────────────────────────────────────────────

    public function getShops(string $token): array
    {
        $response = $this->http($token)->get(self::BASE_URL . '/shops.json');
        $response->throw();
        return $response->json();
    }

    public function uploadImage(string $token, string $imageSource, string $fileName = 'design.png'): array
    {
        // Detect base64 data URL vs HTTP URL
        if (str_starts_with($imageSource, 'data:')) {
            // Extract MIME type and raw base64
            preg_match('/^data:([^;]+);base64,/', $imageSource, $matches);
            $mime   = $matches[1] ?? 'image/png';
            $base64 = substr($imageSource, strpos($imageSource, ',') + 1);

            // Printify only supports PNG and JPEG — convert WebP/GIF to PNG
            if (in_array($mime, ['image/webp', 'image/gif', 'image/bmp'])) {
                $base64 = $this->convertToPng($base64);
                $mime   = 'image/png';
            }

            $fileName = $mime === 'image/jpeg' ? 'design.jpg' : 'design.png';
            $payload  = ['file_name' => $fileName, 'contents' => $base64];
        } else {
            $payload = ['file_name' => $fileName, 'url' => $imageSource];
        }

        $response = $this->http($token)->post(self::BASE_URL . '/uploads/images.json', $payload);
        $response->throw();
        return $response->json();
    }

    /**
     * Convert raw base64 (any GD-readable format) to PNG base64 using GD.
     */
    private function convertToPng(string $base64): string
    {
        $binary = base64_decode($base64, true);
        if ($binary === false) {
            return $base64; // fallback: return as-is
        }

        $img = @imagecreatefromstring($binary);
        if ($img === false) {
            return $base64; // fallback: return as-is
        }

        // Preserve transparency
        imagealphablending($img, false);
        imagesavealpha($img, true);

        ob_start();
        imagepng($img, null, 6); // compression 6 = good balance
        $pngBinary = ob_get_clean();
        imagedestroy($img);

        return base64_encode($pngBinary);
    }

    public function getBlueprint(string $token, int $blueprintId): array
    {
        $response = $this->http($token)->get(self::BASE_URL . "/catalog/blueprints/{$blueprintId}.json");
        $response->throw();
        return $response->json();
    }

    public function getPrintProviders(string $token, int $blueprintId): array
    {
        $response = $this->http($token)->get(self::BASE_URL . "/catalog/blueprints/{$blueprintId}/print_providers.json");
        $response->throw();
        return $response->json();
    }

    public function getVariants(string $token, int $blueprintId, int $printProviderId): array
    {
        $response = $this->http($token)->get(
            self::BASE_URL . "/catalog/blueprints/{$blueprintId}/print_providers/{$printProviderId}/variants.json"
        );
        $response->throw();
        return $response->json();
    }

    public function sendDesign(string $token, int $shopId, string $title, string $garmentType, string $imageUrl, float $posX = 0.5, float $posY = 0.5, float $scale = 1.0, string $color = ''): array
    {
        $blueprintId = self::BLUEPRINT_MAP[$garmentType] ?? self::BLUEPRINT_MAP['tshirt'];

        // 1. Upload the design image
        $upload  = $this->uploadImage($token, $imageUrl);
        $imageId = $upload['id'];

        // 2. Pick first print provider for this blueprint
        $providers = $this->getPrintProviders($token, $blueprintId);
        if (empty($providers)) {
            throw new \RuntimeException('No print providers available for this product type.');
        }
        $providerId = $providers[0]['id'];

        // 3. Get available variants, filter by color, limit to 10 sizes
        $variantsData = $this->getVariants($token, $blueprintId, $providerId);
        $available    = array_values(array_filter(
            $variantsData['variants'] ?? [],
            fn($v) => $v['is_available'] ?? true
        ));
        $variants = array_slice($this->filterVariantsByColor($available, $color), 0, 10);

        if (empty($variants)) {
            throw new \RuntimeException('No variants available for this product.');
        }

        $variantIds = array_column($variants, 'id');

        // 4. Build product payload
        $payload = [
            'title'             => $title,
            'blueprint_id'      => $blueprintId,
            'print_provider_id' => $providerId,
            'variants'          => array_map(fn($v) => [
                'id'         => $v['id'],
                'price'      => 2500,
                'is_enabled' => true,
            ], $variants),
            'print_areas' => [
                [
                    'variant_ids'  => $variantIds,
                    'placeholders' => [
                        [
                            'position' => 'front',
                            'images'   => [
                                [
                                    'id'    => $imageId,
                                    'x'     => $posX,
                                    'y'     => $posY,
                                    'scale' => $scale,
                                    'angle' => 0,
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $response = $this->http($token)->post(self::BASE_URL . "/shops/{$shopId}/products.json", $payload);
        $response->throw();
        return $response->json();
    }

    /**
     * Upload the design once, then create one product per clothing garment type.
     * Returns array keyed by garment_type => ['success' => bool, 'url' => ..., 'error' => ...]
     */
    private function filterVariantsByColor(array $variants, string $color): array
    {
        if (empty(trim($color))) return $variants;

        $getColor = fn($v) => $v['options']['color'] ?? $v['title'] ?? '';

        // 1. Exact substring match (e.g. "Dark Heather" inside "Dark Heather Grey")
        $filtered = array_values(array_filter($variants, fn($v) =>
            stripos($getColor($v), $color) !== false
        ));
        if (!empty($filtered)) return $filtered;

        // 2. Any significant word from the color name (e.g. "heather" or "dark")
        $words = array_filter(explode(' ', strtolower($color)), fn($w) => strlen($w) > 3);
        if (!empty($words)) {
            $filtered = array_values(array_filter($variants, function ($v) use ($words, $getColor) {
                $hay = strtolower($getColor($v));
                foreach ($words as $word) {
                    if (str_contains($hay, $word)) return true;
                }
                return false;
            }));
            if (!empty($filtered)) return $filtered;
        }

        // 3. No match at all — return only the first available color group (not everything)
        $firstColor = null;
        $firstGroup = [];
        foreach ($variants as $v) {
            $c = $getColor($v);
            if ($firstColor === null) $firstColor = $c;
            if ($c === $firstColor) $firstGroup[] = $v;
        }
        return !empty($firstGroup) ? $firstGroup : array_slice($variants, 0, 5);
    }

    public function sendDesignToAll(string $token, int $shopId, string $title, string $imageSource, float $posX = 0.5, float $posY = 0.5, float $scale = 1.0, string $color = ''): array
    {
        // Upload the image only once
        $upload  = $this->uploadImage($token, $imageSource);
        $imageId = $upload['id'];

        $results = [];
        foreach (array_keys(self::BLUEPRINT_MAP) as $garmentType) {
            try {
                $blueprintId = self::BLUEPRINT_MAP[$garmentType];

                $providers = $this->getPrintProviders($token, $blueprintId);
                if (empty($providers)) {
                    $results[$garmentType] = ['success' => false, 'error' => 'No print providers'];
                    continue;
                }
                $providerId = $providers[0]['id'];

                $variantsData = $this->getVariants($token, $blueprintId, $providerId);
                $available    = array_values(array_filter(
                    $variantsData['variants'] ?? [],
                    fn($v) => $v['is_available'] ?? true
                ));
                $variants = array_slice($this->filterVariantsByColor($available, $color), 0, 10);

                if (empty($variants)) {
                    $results[$garmentType] = ['success' => false, 'error' => 'No variants available'];
                    continue;
                }

                $variantIds = array_column($variants, 'id');

                $payload = [
                    'title'             => $title . ' — ' . ucfirst($garmentType),
                    'blueprint_id'      => $blueprintId,
                    'print_provider_id' => $providerId,
                    'variants'          => array_map(fn($v) => [
                        'id'         => $v['id'],
                        'price'      => 2500,
                        'is_enabled' => true,
                    ], $variants),
                    'print_areas' => [
                        [
                            'variant_ids'  => $variantIds,
                            'placeholders' => [
                                [
                                    'position' => 'front',
                                    'images'   => [
                                        [
                                            'id'    => $imageId,
                                            'x'     => $posX,
                                            'y'     => $posY,
                                            'scale' => $scale,
                                            'angle' => 0,
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ];

                $response  = $this->http($token)->post(self::BASE_URL . "/shops/{$shopId}/products.json", $payload);
                $response->throw();
                $product   = $response->json();
                $productId = $product['id'];

                $results[$garmentType] = [
                    'success' => true,
                    'url'     => "https://printify.com/app/store/{$shopId}/products/{$productId}/edit",
                ];
            } catch (\Throwable $e) {
                $results[$garmentType] = ['success' => false, 'error' => $e->getMessage()];
            }
        }

        return $results;
    }
}
