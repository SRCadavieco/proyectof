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

    private static function chatLimitForPlan(string $plan): int
    {
        return match ($plan) {
            'pro'      => 10,
            'business' => 30,
            'admin'    => 30,
            default    => 5,  // free, starter
        };
    }

    public function store()
    {
        $user  = Auth::user();
        $limit = self::chatLimitForPlan($user->plan ?? 'free');

        if ($user->chats()->count() >= $limit) {
            return response()->json([
                'error'       => "Has alcanzado el límite de {$limit} chats para tu plan.",
                'upgrade_url' => '/pricing',
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

    public function destroyAll()
    {
        Auth::user()->chats()->delete();
        return response()->json(['success' => true]);
    }
}