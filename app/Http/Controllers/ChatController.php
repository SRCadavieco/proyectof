<?php

namespace App\Http\Controllers;

use App\Models\Chat;
use Illuminate\Support\Facades\Auth;

class ChatController extends Controller

{
    public function index()
    {
        // Para pruebas sin login, devolver todos los chats
        // Cuando se implemente login, descomentar la lógica de usuario
        // return Auth::user()
        //     ->chats()
        //     ->latest()
        //     ->get(['id', 'title', 'created_at']);
        return Chat::latest()->get(['id', 'title', 'created_at']);
    }

    public function store()
    {
        // Para pruebas sin login, crear chat sin usuario
        // Cuando se implemente login, descomentar la lógica de usuario
        // $user = Auth::user();
        // if ($user->chats()->count() >= 5) {
        //     return response()->json([
        //         'error' => 'Has alcanzado el límite de 3 chats.'
        //     ], 403);
        // }
        // $chat = $user->chats()->create([
        //     'title' => null
        // ]);
        $chat = Chat::create(['title' => null]);
        return response()->json($chat);
    }

    public function show(Chat $chat)
    {
        // Para pruebas sin login, permitir ver cualquier chat
        // Cuando se implemente login, descomentar la lógica de usuario
        // abort_if($chat->user_id !== Auth::id(), 403);
        return response()->json([
            'chat' => $chat,
            'messages' => $chat->messages()->orderBy('created_at')->get()
        ]);
    }
        public function destroy(Chat $chat)
    {
        // Para pruebas sin login, permitir borrar cualquier chat
        // Cuando se implemente login, descomentar la lógica de usuario
        // if ($chat->user_id !== Auth::id()) {
        //     abort(403);
        // }
        $chat->delete();
        return response()->json(['success' => true]);
    }
}