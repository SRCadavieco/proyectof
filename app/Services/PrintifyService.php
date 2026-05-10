<?php

namespace App\Services;

use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;

class PrintifyService
{
    private const BASE_URL = 'https://api.printify.com/v1';

    // Blueprint ID map (Printify catalog IDs)
    public const BLUEPRINT_MAP = [
        'tshirt'      => 6,    // Unisex Heavy Cotton Tee (Gildan 5000)
        'hoodie'      => 77,   // Unisex Heavy Blend™ Hooded Sweatshirt (Gildan 18500)
        'zip_hoodie'  => 66,   // Unisex Heavy Blend™ Full Zip Hooded Sweatshirt (Gildan 18600)
        'tanktop'     => 39,   // Unisex Jersey Tank
        'longsleeve'  => 41,   // Unisex Jersey Long Sleeve Tee
        'sweatshirt'  => 49,   // Unisex Heavy Blend™ Crewneck Sweatshirt
        'vneck'       => 61,   // Unisex Jersey Short Sleeve V-Neck Tee (Bella+Canvas 3005)
        'womens_tee'  => 9,    // Women's Jersey Short Sleeve Tee (Bella+Canvas 6004)
        'leggings'    => 509,  // Women's Casual Spandex Leggings (AOP)
        'joggers'     => 591,  // Athletic Joggers (AOP)
        'shorts'      => 1110, // Women's Shorts (AOP)
        'dresses'     => 276,  // Women's Cut & Sew Racerback Dress (AOP)
        'skirts'      => 286,  // Women's Skater Skirt (AOP)
        'bikinis'     => 349,  // Women's Bikini Swimsuit (AOP)
        'socks'       => 365,  // Crew Socks
        'underwear'   => 406,  // Men's Boxer Briefs (AOP)
        'pajamas'     => 1037, // Women's Satin Pajamas (AOP)
        'caps'        => 1108, // Low Profile Baseball Cap
        'beanies'     => 1689, // Cuff Beanie
        'tote_bags'   => 553,  // Cotton Tote Bag
        'scarves'     => 264,  // Poly Scarf
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

    private function isBackPosition(string $position): bool
    {
        return str_starts_with($position, 'back') || str_ends_with($position, '_back');
    }

    private function isFrontPosition(string $position): bool
    {
        if ($this->isBackPosition($position)) {
            return false;
        }

        // Avoid applying front artwork to sleeves, labels, neck tags, pockets, etc.
        return str_starts_with($position, 'front')
            || str_ends_with($position, '_front')
            || $position === 'default';
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

    private function buildPlaceholdersFromVariants(
        array $variants,
        ?string $frontImageId,
        float $frontPosX,
        float $frontPosY,
        float $frontScale,
        ?string $backImageId = null,
        float $backPosX = 0.5,
        float $backPosY = 0.5,
        float $backScale = 1.0
    ): array {
        $positions = [];
        foreach ($variants as $variant) {
            foreach (($variant['placeholders'] ?? []) as $placeholder) {
                $position = $placeholder['position'] ?? null;
                if (is_string($position) && $position !== '') {
                    $positions[$position] = true;
                }
            }
        }

        $positions = array_keys($positions);
        if (empty($positions)) {
            $fallback = [];

            if ($frontImageId !== null) {
                $fallback[] = [
                    'position' => 'front',
                    'images' => [[
                        'id' => $frontImageId,
                        'x' => $frontPosX,
                        'y' => $frontPosY,
                        'scale' => $frontScale,
                        'angle' => 0,
                    ]],
                ];
            }

            if ($backImageId !== null) {
                $fallback[] = [
                    'position' => 'back',
                    'images' => [[
                        'id' => $backImageId,
                        'x' => $backPosX,
                        'y' => $backPosY,
                        'scale' => $backScale,
                        'angle' => 0,
                    ]],
                ];
            }

            return $fallback;
        }

        $placeholders = [];
        $frontAdded = false;
        $backAdded = false;
        $usedPositions = [];

        foreach ($positions as $position) {
            $isBackPosition = $this->isBackPosition($position);
            $isFrontPosition = $this->isFrontPosition($position);

            if ($isBackPosition && $backImageId === null) {
                continue;
            }
            if ($isFrontPosition && $frontImageId === null) {
                continue;
            }
            if (!$isBackPosition && !$isFrontPosition) {
                continue;
            }

            $useBack = $isBackPosition;

            $placeholders[] = [
                'position' => $position,
                'images' => [[
                    'id' => $useBack ? $backImageId : $frontImageId,
                    'x' => $useBack ? $backPosX : $frontPosX,
                    'y' => $useBack ? $backPosY : $frontPosY,
                    'scale' => $useBack ? $backScale : $frontScale,
                    'angle' => 0,
                ]],
            ];

            $usedPositions[$position] = true;
            if ($useBack) {
                $backAdded = true;
            } else {
                $frontAdded = true;
            }
        }

        // Some AOP garments expose non-standard placeholder names. If no front slot matched,
        // fallback to the first non-back position so front-only uploads still work.
        if ($frontImageId !== null && !$frontAdded) {
            foreach ($positions as $position) {
                if ($this->isBackPosition($position) || isset($usedPositions[$position])) {
                    continue;
                }

                $placeholders[] = [
                    'position' => $position,
                    'images' => [[
                        'id' => $frontImageId,
                        'x' => $frontPosX,
                        'y' => $frontPosY,
                        'scale' => $frontScale,
                        'angle' => 0,
                    ]],
                ];
                $frontAdded = true;
                break;
            }
        }

        return $placeholders;
    }

    public function sendDesign(string $token, int $shopId, string $title, string $garmentType, ?string $imageUrl, float $posX = 0.5, float $posY = 0.5, float $scale = 1.0, string $color = '', ?string $backImageUrl = null, float $backPosX = 0.5, float $backPosY = 0.5, float $backScale = 1.0): array
    {
        $blueprintId = self::BLUEPRINT_MAP[$garmentType] ?? self::BLUEPRINT_MAP['tshirt'];

        if (!$imageUrl && !$backImageUrl) {
            throw new \InvalidArgumentException('At least one side image is required.');
        }

        // 1. Upload the design image
        $imageId = null;
        if ($imageUrl) {
            $upload  = $this->uploadImage($token, $imageUrl);
            $imageId = $upload['id'];
        }

        // 1b. Upload back image if provided
        $backImageId = null;
        if ($backImageUrl) {
            $backUpload  = $this->uploadImage($token, $backImageUrl);
            $backImageId = $backUpload['id'];
        }

        // 2. Get all print providers for this blueprint and try them in order
        try {
            $providers = $this->getPrintProviders($token, $blueprintId);
        } catch (RequestException $e) {
            if ($e->response && $e->response->status() === 404) {
                throw new \RuntimeException('This garment is not available in Printify catalog right now.');
            }
            throw $e;
        }
        if (empty($providers)) {
            throw new \RuntimeException('No print providers available for this product type.');
        }

        $lastError = 'No variants available for this product.';

        foreach ($providers as $provider) {
            $providerId = $provider['id'];

            try {
                // 3. Get available variants, filter by color, limit to 10 sizes
                $variantsData = $this->getVariants($token, $blueprintId, $providerId);
                $available    = array_values(array_filter(
                    $variantsData['variants'] ?? [],
                    fn($v) => $v['is_available'] ?? true
                ));
                $variants = array_slice($this->filterVariantsByColor($available, $color), 0, 10);

                if (empty($variants)) {
                    $lastError = 'No variants available for this product.';
                    continue;
                }

                $variantIds = array_column($variants, 'id');
                $placeholders = $this->buildPlaceholdersFromVariants(
                    $variants,
                    $imageId,
                    $posX,
                    $posY,
                    $scale,
                    $backImageId,
                    $backPosX,
                    $backPosY,
                    $backScale
                );

                if (empty($placeholders)) {
                    $lastError = 'No printable placeholders available for the selected side(s).';
                    continue;
                }

                // 5. Build product payload
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
                            'placeholders' => $placeholders,
                        ],
                    ],
                ];

                $response = $this->http($token)->post(self::BASE_URL . "/shops/{$shopId}/products.json", $payload);
                $response->throw();
                return $response->json();
            } catch (\Throwable $e) {
                $lastError = $e->getMessage();
            }
        }

        throw new \RuntimeException('Could not create product with available print providers. Last error: ' . $lastError);
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

    /**
     * Poll the product endpoint until Printify has generated at least one mockup image,
     * or until the timeout is reached. This prevents publishing a product with no thumbnail.
     * Printify generates mockups asynchronously after product creation (typically 3–15 s).
     */
    private function waitForMockups(string $token, int $shopId, string $productId, int $timeoutSeconds = 45): void
    {
        $deadline = time() + $timeoutSeconds;
        $delay    = 4; // seconds between polls

        while (time() < $deadline) {
            sleep($delay);
            try {
                $response = $this->http($token)->get(
                    self::BASE_URL . "/shops/{$shopId}/products/{$productId}.json"
                );
                if ($response->successful()) {
                    $images = $response->json('images') ?? [];
                    if (!empty($images)) {
                        return; // mockups ready
                    }
                }
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning('Printify waitForMockups poll failed', ['error' => $e->getMessage()]);
            }
            $delay = min($delay + 2, 8); // back-off: 4s → 6s → 8s
        }

        // Timeout reached — publish anyway so the product is at least live
        \Illuminate\Support\Facades\Log::warning('Printify waitForMockups timed out, publishing without confirmed mockups', [
            'shop_id'    => $shopId,
            'product_id' => $productId,
        ]);
    }

    public function publishProduct(string $token, int $shopId, string $productId): void
    {
        $this->waitForMockups($token, $shopId, $productId);

        $response = $this->http($token)->post(
            self::BASE_URL . "/shops/{$shopId}/products/{$productId}/publish.json",
            [
                'title'             => true,
                'description'       => true,
                'images'            => true,
                'variants'          => true,
                'tags'              => true,
                'keyFeatures'       => true,
                'shipping_template' => true,
            ]
        );
        $response->throw();
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

                try {
                    $providers = $this->getPrintProviders($token, $blueprintId);
                } catch (RequestException $e) {
                    if ($e->response && $e->response->status() === 404) {
                        $results[$garmentType] = ['success' => false, 'error' => 'Garment not available in Printify catalog'];
                        continue;
                    }
                    throw $e;
                }
                if (empty($providers)) {
                    $results[$garmentType] = ['success' => false, 'error' => 'No print providers'];
                    continue;
                }
                $lastError = 'No variants available';
                $created = false;

                foreach ($providers as $provider) {
                    $providerId = $provider['id'];

                    try {
                        $variantsData = $this->getVariants($token, $blueprintId, $providerId);
                        $available    = array_values(array_filter(
                            $variantsData['variants'] ?? [],
                            fn($v) => $v['is_available'] ?? true
                        ));
                        $variants = array_slice($this->filterVariantsByColor($available, $color), 0, 10);

                        if (empty($variants)) {
                            $lastError = 'No variants available';
                            continue;
                        }

                        $variantIds = array_column($variants, 'id');
                        $placeholders = $this->buildPlaceholdersFromVariants(
                            $variants,
                            $imageId,
                            $posX,
                            $posY,
                            $scale
                        );

                        $payload = [
                            'title'             => $title . ' - ' . ucfirst($garmentType),
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
                                    'placeholders' => $placeholders,
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
                        $created = true;
                        break;
                    } catch (\Throwable $e) {
                        \Log::warning('Bulk Printify attempt failed', [
                            'garment_type' => $garmentType,
                            'blueprint_id' => $blueprintId,
                            'provider_id' => $providerId,
                            'error' => $e->getMessage(),
                        ]);
                        $lastError = $e->getMessage();
                    }
                }

                if (!$created) {
                    $results[$garmentType] = ['success' => false, 'error' => $lastError];
                }
            } catch (\Throwable $e) {
                $results[$garmentType] = ['success' => false, 'error' => $e->getMessage()];
            }
        }

        return $results;
    }
}
