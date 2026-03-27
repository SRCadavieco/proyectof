<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Chat;
use App\Models\Message;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminController extends Controller
{
    public function dashboard()
    {
        $totalUsers = User::count();
        $onlineUsers = DB::table('sessions')
            ->whereNotNull('user_id')
            ->where('last_activity', '>=', now()->subMinutes(5)->timestamp)
            ->distinct('user_id')
            ->count('user_id');

        $totalChats = Chat::count();
        $totalMessages = Message::count();

        // Users by plan
        $usersByPlan = User::select('plan', DB::raw('count(*) as total'))
            ->groupBy('plan')
            ->pluck('total', 'plan');

        // Model usage (fabric_light vs fabric_pro) from messages
        $modelUsage = Message::where('role', 'user')
            ->select(DB::raw("COUNT(*) as total"))
            ->first();

        // Hourly activity (last 7 days) - based on sessions last_activity
        $driver = DB::getDriverName();
        $hourRaw = $driver === 'sqlite'
            ? "CAST(strftime('%H', datetime(last_activity, 'unixepoch')) AS INTEGER) as hour"
            : "HOUR(FROM_UNIXTIME(last_activity)) as hour";

        $hourlyActivity = DB::table('sessions')
            ->whereNotNull('user_id')
            ->where('last_activity', '>=', now()->subDays(7)->timestamp)
            ->select(DB::raw($hourRaw), DB::raw("COUNT(*) as total"))
            ->groupBy('hour')
            ->orderBy('hour')
            ->pluck('total', 'hour')
            ->toArray();

        // Fill missing hours with 0
        $hourlyData = [];
        for ($i = 0; $i < 24; $i++) {
            $hourlyData[$i] = $hourlyActivity[$i] ?? 0;
        }

        // Recent registrations (last 30 days)
        $dateRaw = $driver === 'sqlite'
            ? "strftime('%Y-%m-%d', created_at) as date"
            : "DATE(created_at) as date";

        $dailyRegistrations = User::where('created_at', '>=', now()->subDays(30))
            ->select(DB::raw($dateRaw), DB::raw("COUNT(*) as total"))
            ->groupBy('date')
            ->orderBy('date')
            ->pluck('total', 'date')
            ->toArray();

        return view('admin.dashboard', compact(
            'totalUsers',
            'onlineUsers',
            'totalChats',
            'totalMessages',
            'usersByPlan',
            'hourlyData',
            'dailyRegistrations'
        ));
    }

    public function users(Request $request)
    {
        $query = User::query();

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($plan = $request->input('plan')) {
            $query->where('plan', $plan);
        }

        $users = $query->withCount('chats')
            ->orderBy('created_at', 'desc')
            ->paginate(20)
            ->withQueryString();

        return view('admin.users', compact('users'));
    }

    public function updateUser(Request $request, User $user)
    {
        $validated = $request->validate([
            'tokens' => 'nullable|integer|min:0',
            'plan' => 'nullable|in:free,pro,studio',
        ]);

        if (isset($validated['tokens'])) {
            $user->tokens = $validated['tokens'];
        }

        if (isset($validated['plan'])) {
            $user->plan = $validated['plan'];
        }

        $user->save();

        return back()->with('success', "Usuario {$user->name} actualizado.");
    }

    public function addTokens(Request $request, User $user)
    {
        $validated = $request->validate([
            'amount' => 'required|integer|min:1|max:10000',
        ]);

        $user->increment('tokens', $validated['amount']);

        return back()->with('success', "Se añadieron {$validated['amount']} tokens a {$user->name}.");
    }

    public function deleteUser(User $user)
    {
        if ($user->is_admin) {
            return back()->with('error', 'No puedes eliminar a un administrador.');
        }

        $name = $user->name;
        $user->chats()->each(function ($chat) {
            $chat->messages()->delete();
            $chat->delete();
        });
        $user->delete();

        return back()->with('success', "Usuario {$name} eliminado.");
    }

    public function stats()
    {
        // Model version usage from messages content (checking for fabric_pro/fabric_light patterns)
        // We'll derive this from chats - since the model is sent in the generate request
        // For now, we count from messages table
        $fabricProChats = Message::where('role', 'assistant')->count();
        $fabricLightChats = Message::where('role', 'user')->count();

        return response()->json([
            'fabric_pro' => $fabricProChats,
            'fabric_light' => $fabricLightChats,
        ]);
    }
}
