<?php

namespace App\Http\Controllers;

use App\Models\PrintifyConnection;
use App\Services\PrintifyService;
use Illuminate\Http\Request;

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

    // POST /printify/products
    public function createProduct(Request $request)
    {
        $conn = auth()->user()->printifyConnection;
        if (!$conn) {
            return response()->json(['error' => 'Printify not connected'], 401);
        }

        $data = $request->validate([
            'shop_id'      => 'required|integer',
            'garment_type' => 'required|string|in:tshirt,hoodie,tanktop,longsleeve,sweatshirt',
            'image_source' => 'required|string',
            'title'        => 'required|string|max:140',
            'pos_x'        => 'nullable|numeric|min:0|max:1',
            'pos_y'        => 'nullable|numeric|min:0|max:1',
            'design_scale' => 'nullable|numeric|min:0.1|max:3',
        ]);

        try {
            $product = $this->printify->sendDesign(
                $conn->api_token,
                (int) $data['shop_id'],
                $data['title'],
                $data['garment_type'],
                $data['image_source'],
                (float) ($data['pos_x']        ?? 0.5),
                (float) ($data['pos_y']        ?? 0.5),
                (float) ($data['design_scale'] ?? 1.0)
            );

            $shopId    = $data['shop_id'];
            $productId = $product['id'];
            $url       = "https://printify.com/app/store/{$shopId}/products/{$productId}/edit";

            return response()->json(['success' => true, 'printify_url' => $url, 'product' => $product]);
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
