<?php

namespace App\Http\Controllers;

use App\Services\GeminiService;
use Illuminate\Http\Request;

class DesignController extends Controller
{
    public function form()
    {
        return view('designs.generate');
    }

    public function generate(Request $request, GeminiService $gemini)
    {
        $validated = $request->validate([
            'prompt' => ['required', 'string'],
        ]);

        $result = $gemini->generateDesign($validated['prompt']);

        $status = 200;
        if (is_array($result) && array_key_exists('success', $result) && $result['success'] === false) {
            $status = isset($result['status']) ? (int) $result['status'] : 500;
        }

        return response()->json($result, $status);
    }
}
