<?php

namespace App\Http\Controllers;

use App\Models\PrintfulConnection;
use App\Services\PrintfulService;
use Illuminate\Http\Request;

class PrintfulController extends Controller
{
    public function __construct(private PrintfulService $printful) {}

    /**
     * GET /printful/status
     */
    public function status(Request $request)
    {
        $connection = $request->user()->printfulConnection()->first();

        if (! $connection) {
            return response()->json(['connected' => false]);
        }

        return response()->json([
            'connected'  => true,
            'store_id'   => $connection->store_id,
            'store_name' => $connection->store_name,
        ]);
    }

    /**
     * GET /printful/stores
     */
    public function stores(Request $request)
    {
        $connection = $this->requireConnection($request);
        if ($connection instanceof \Illuminate\Http\JsonResponse) return $connection;

        try {
            $stores = $this->printful->getStores($connection);

            // Persist first store if not yet saved
            if (! $connection->store_id && count($stores)) {
                $connection->update([
                    'store_id'   => $stores[0]['id'],
                    'store_name' => $stores[0]['name'],
                ]);
            }

            return response()->json($stores);
        } catch (\Illuminate\Http\Client\RequestException $e) {
            return $this->printfulError($e);
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * POST /printful/products
     */
    public function createProduct(Request $request)
    {
        $connection = $this->requireConnection($request);
        if ($connection instanceof \Illuminate\Http\JsonResponse) return $connection;

        $validated = $request->validate([
            'store_id'     => ['required', 'integer', 'min:1'],
            'garment_type' => ['required', 'string', 'in:tshirt,hoodie,tanktop,longsleeve,sweatshirt'],
            'image_source' => ['required', 'string'],
            'title'        => ['required', 'string', 'max:255'],
        ]);

        try {
            $product = $this->printful->sendDesign(
                $connection,
                (int) $validated['store_id'],
                $validated['garment_type'],
                $validated['image_source'],
                $validated['title'],
            );

            $productId = $product['id'] ?? ($product['sync_product']['id'] ?? null);

            return response()->json([
                'success'      => true,
                'product_id'   => $productId,
                'title'        => $product['sync_product']['name'] ?? $validated['title'],
                'printful_url' => $productId
                    ? 'https://www.printful.com/dashboard/sync/products/' . $productId
                    : 'https://www.printful.com/dashboard',
            ]);
        } catch (\Illuminate\Http\Client\RequestException $e) {
            return $this->printfulError($e);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    // ─── Helpers ─────────────────────────────────────────────────────────────

    private function requireConnection(Request $request): PrintfulConnection|\Illuminate\Http\JsonResponse
    {
        $connection = $request->user()->printfulConnection()->first();

        if (! $connection) {
            return response()->json([
                'error'   => 'Printful account not connected.',
                'connect' => true,
            ], 403);
        }

        return $connection;
    }

    private function printfulError(\Illuminate\Http\Client\RequestException $e): \Illuminate\Http\JsonResponse
    {
        $body = $e->response->json();
        $msg  = $body['error']['reason'] ?? $body['message'] ?? $e->getMessage();
        return response()->json(['error' => $msg], $e->response->status());
    }
}
