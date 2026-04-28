<?php

namespace App\Http\Controllers;

use App\Models\PrintifyConnection;
use App\Services\PrintifyService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class PrintifyController extends Controller
{
    public function __construct(private PrintifyService $printify) {}

    // POST /printify/connect  — save & validate token
    public function connect(Request $request)
    {
        $request->validate(['api_token' => 'required|string']);

        $token = trim($request->input('api_token'));

        try {
            $shops     = $this->printify->getShops($token);
            $firstShop = is_array($shops) ? ($shops[0] ?? null) : null;

            PrintifyConnection::updateOrCreate(
                ['user_id' => auth()->id()],
                [
                    'api_token' => $token,
                    'shop_id'   => $firstShop['id']    ?? null,
                    'shop_name' => $firstShop['title'] ?? null,
                ]
            );

            return back()->with('printify_success', 'Printify account connected successfully!');
        } catch (\Illuminate\Http\Client\RequestException $e) {
            \Log::error('Printify connect HTTP error: ' . $e->getMessage());
            return back()->withErrors(['api_token' => 'Invalid token or cannot reach Printify. Please check your API key.']);
        } catch (\Throwable $e) {
            \Log::error('Printify connect error: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
            return back()->withErrors(['api_token' => 'Error: ' . $e->getMessage()]);
        }
    }

    // DELETE /printify/disconnect
    public function disconnect()
    {
        PrintifyConnection::where('user_id', auth()->id())->delete();
        return back()->with('printify_success', 'Printify account disconnected.');
    }

    // GET /printify/status
    public function status()
    {
        $conn = auth()->user()->printifyConnection;
        if (!$conn) {
            return response()->json(['connected' => false]);
        }
        return response()->json([
            'connected'  => true,
            'shop_id'    => $conn->shop_id,
            'shop_name'  => $conn->shop_name,
        ]);
    }

    // GET /printify/shops
    public function shops()
    {
        $conn = auth()->user()->printifyConnection;
        if (!$conn) {
            return response()->json(['error' => 'Not connected'], 401);
        }

        try {
            $shops = $this->printify->getShops($conn->api_token);
            // Normalize to {id, name}
            $normalized = array_map(fn($s) => ['id' => $s['id'], 'name' => $s['title']], $shops);
            return response()->json($normalized);
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    // GET /printify/garments  — returns local URLs for cached blueprint images
    // Images are downloaded once from Printify CDN and stored in public/images/garments/
    public function garments()
    {
        $conn = auth()->user()->printifyConnection;
        if (!$conn) {
            return response()->json([]);
        }

        $dir    = public_path('images/garments');
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $result = [];
        foreach (PrintifyService::BLUEPRINT_MAP as $type => $blueprintId) {
            $localFile = "{$dir}/{$type}.jpg";

            if (!file_exists($localFile)) {
                try {
                    $blueprint = $this->printify->getBlueprint($conn->api_token, $blueprintId);
                    $imageUrl  = $blueprint['images'][0] ?? null;
                    if ($imageUrl) {
                        $content = Http::timeout(15)->get($imageUrl)->body();
                        file_put_contents($localFile, $content);
                    }
                } catch (\Throwable $e) {
                    \Log::warning("Could not fetch blueprint image for {$type}: " . $e->getMessage());
                    $result[$type] = null;
                    continue;
                }
            }

            $result[$type] = file_exists($localFile)
                ? asset("images/garments/{$type}.jpg") . '?v=' . filemtime($localFile)
                : null;
        }

        return response()->json($result);
    }

    // POST /printify/products
    public function createProduct(Request $request)    {
        $conn = auth()->user()->printifyConnection;
        if (!$conn) {
            return response()->json(['error' => 'Printify not connected'], 401);
        }

        $data = $request->validate([
            'shop_id'           => 'required|integer',
            'garment_type'      => 'required|string|in:tshirt,hoodie,tanktop,longsleeve,sweatshirt',
            'image_source'      => 'required|string',
            'title'             => 'required|string|max:140',
            'color'             => 'nullable|string|max:50',
            'pos_x'             => 'nullable|numeric|min:0|max:1',
            'pos_y'             => 'nullable|numeric|min:0|max:1',
            'design_scale'      => 'nullable|numeric|min:0.1|max:3',
            'back_image_source' => 'nullable|string',
            'back_pos_x'        => 'nullable|numeric|min:0|max:1',
            'back_pos_y'        => 'nullable|numeric|min:0|max:1',
            'back_design_scale' => 'nullable|numeric|min:0.1|max:3',
        ]);

        try {
            $product = $this->printify->sendDesign(
                $conn->api_token,
                (int) $data['shop_id'],
                $data['title'],
                $data['garment_type'],
                $data['image_source'],
                (float) ($data['pos_x']             ?? 0.5),
                (float) ($data['pos_y']             ?? 0.5),
                (float) ($data['design_scale']      ?? 1.0),
                $data['color']                      ?? '',
                $data['back_image_source']          ?? null,
                (float) ($data['back_pos_x']        ?? 0.5),
                (float) ($data['back_pos_y']        ?? 0.5),
                (float) ($data['back_design_scale'] ?? 1.0)
            );

            $shopId    = $data['shop_id'];
            $productId = $product['id'];
            $url       = "https://printify.com/app/store/{$shopId}/products/{$productId}/edit";

            $conn->increment('products_pushed');

            return response()->json(['success' => true, 'printify_url' => $url, 'product' => $product]);
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    // POST /printify/products/bulk
    public function createProductBulk(Request $request)
    {
        $conn = auth()->user()->printifyConnection;
        if (!$conn) {
            return response()->json(['error' => 'Printify not connected'], 401);
        }

        $data = $request->validate([
            'shop_id'      => 'required|integer',
            'image_source' => 'required|string',
            'title'        => 'required|string|max:140',
            'pos_x'        => 'nullable|numeric|min:0|max:1',
            'pos_y'        => 'nullable|numeric|min:0|max:1',
            'design_scale' => 'nullable|numeric|min:0.1|max:3',
        ]);

        try {
            $results = $this->printify->sendDesignToAll(
                $conn->api_token,
                (int) $data['shop_id'],
                $data['title'],
                $data['image_source'],
                (float) ($data['pos_x']        ?? 0.5),
                (float) ($data['pos_y']        ?? 0.5),
                (float) ($data['design_scale'] ?? 1.0)
            );

            $successCount = count(array_filter($results, fn($r) => $r['success']));
            $conn->increment('products_pushed', $successCount);

            return response()->json(['success' => true, 'results' => $results]);
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
