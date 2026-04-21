<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class PrintifyService
{
    private const BASE_URL = 'https://api.printify.com/v1';

    // Blueprint ID map (Printify catalog IDs)
    public const BLUEPRINT_MAP = [
        'tshirt'     => 6,    // Gildan 5000 Heavy Cotton T-Shirt
        'hoodie'     => 77,   // Gildan 18500 Heavy Blend Hooded Sweatshirt
        'tanktop'    => 246,  // Bella+Canvas 3480 Unisex Jersey Tank
        'longsleeve' => 14,   // Gildan 5400 Heavy Cotton Long-Sleeve
        'sweatshirt' => 7,    // Gildan 18000 Heavy Blend Crewneck Sweatshirt
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
            // Extract raw base64 string from data:image/png;base64,XXXX
            $base64 = substr($imageSource, strpos($imageSource, ',') + 1);
            $payload = ['file_name' => $fileName, 'contents' => $base64];
        } else {
            $payload = ['file_name' => $fileName, 'url' => $imageSource];
        }

        $response = $this->http($token)->post(self::BASE_URL . '/uploads/images.json', $payload);
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

    public function sendDesign(string $token, int $shopId, string $title, string $garmentType, string $imageUrl): array
    {
        $blueprintId = self::BLUEPRINT_MAP[$garmentType] ?? self::BLUEPRINT_MAP['tshirt'];

        // 1. Upload the design image
        $upload  = $this->uploadImage($token, $imageUrl);        $imageId = $upload['id'];

        // 2. Pick first print provider for this blueprint
        $providers = $this->getPrintProviders($token, $blueprintId);
        if (empty($providers)) {
            throw new \RuntimeException('No print providers available for this product type.');
        }
        $providerId = $providers[0]['id'];

        // 3. Get variants — limit to first 10 to stay within Printify's max
        $variantsData = $this->getVariants($token, $blueprintId, $providerId);
        $variants     = array_slice(
            array_values(array_filter(
                $variantsData['variants'] ?? [],
                fn($v) => $v['is_available'] ?? true
            )),
            0, 10
        );

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
                                    'x'     => 0.5,
                                    'y'     => 0.5,
                                    'scale' => 1,
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
}
