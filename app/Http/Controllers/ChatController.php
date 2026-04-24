<?php

namespace App\Http\Controllers;

use App\Models\Chat;
use Illuminate\Support\Facades\Auth;

class ChatController extends Controller
{
    public function index()
    {
        return Auth::user()
            ->chats()
            ->latest()
            ->with(['messages' => function ($q) {
                $q->where('role', 'assistant')
                  ->whereNotNull('image')
                  ->orderBy('created_at')
                  ->limit(1);
            }])
            ->get(['id', 'title', 'created_at'])
            ->map(fn ($chat) => [
                'id'         => $chat->id,
                'title'      => $chat->title,
                'created_at' => $chat->created_at,
                'thumbnail'  => $chat->messages->first()?->image,
            ]);
    }

    public function store()
    {
        $user = Auth::user();
        if ($user->chats()->count() >= 5) {
            return response()->json([
                'error' => 'Has alcanzado el límite de 5 chats.'
            ], 403);
        }
        $chat = $user->chats()->create(['title' => null]);
        return response()->json($chat);
    }

    public function show(Chat $chat)
    {
        abort_if($chat->user_id !== Auth::id(), 403);
        return response()->json([
            'chat' => $chat,
            'messages' => $chat->messages()->orderBy('created_at')->get()
        ]);
    }

    public function rename(Chat $chat, \Illuminate\Http\Request $request)
    {
        abort_if($chat->user_id !== Auth::id(), 403);
        $request->validate(['title' => 'required|string|max:100']);
        $chat->update(['title' => trim($request->title)]);
        return response()->json(['success' => true, 'title' => $chat->title]);
    }

    public function destroy(Chat $chat)
    {
        abort_if($chat->user_id !== Auth::id(), 403);
        $chat->delete();
        return response()->json(['success' => true]);
    }
}