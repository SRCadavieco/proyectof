<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Chat;
use App\Models\Message;
use App\Models\ApiUsageLog;
use App\Models\BillingEvent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;

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

        // Users by plan (legacy values are normalised)
        $planCase = "CASE
            WHEN plan = 'studio' THEN 'business'
            WHEN plan IN ('free', 'starter', 'pro', 'business') THEN plan
            ELSE 'free'
        END";

        $usersByPlan = User::where(function ($query) {
                $query->whereNull('plan')
                    ->orWhereRaw('LOWER(plan) <> ?', ['admin']);
            })
            ->selectRaw("{$planCase} as normalized_plan, COUNT(*) as total")
            ->groupBy('normalized_plan')
            ->pluck('total', 'normalized_plan');

        // Model usage (fabric_light vs fabric_pro) from messages
        $modelUsage = Message::where('role', 'user')
            ->select(DB::raw("COUNT(*) as total"))
            ->first();

        // Usuarios únicos conectados por franja horaria (últimos 7 días)
        $driver = DB::getDriverName();
        $hourRaw = $driver === 'sqlite'
            ? "CAST(strftime('%H', datetime(last_activity, 'unixepoch')) AS INTEGER) as hour"
            : "HOUR(FROM_UNIXTIME(last_activity)) as hour";

        $hourlyActivity = DB::table('sessions')
            ->whereNotNull('user_id')
            ->where('last_activity', '>=', now()->subDays(7)->timestamp)
            ->select(DB::raw($hourRaw), DB::raw("COUNT(DISTINCT user_id) as total"))
            ->groupBy('hour')
            ->orderBy('hour')
            ->pluck('total', 'hour')
            ->toArray();

        // Fill missing hours with 0
        $hourlyData = [];
        for ($i = 0; $i < 24; $i++) {
            $hourlyData[$i] = $hourlyActivity[$i] ?? 0;
        }

        // Registros diarios (últimos 30 días, rellenando días sin registros)
        $dateRaw = $driver === 'sqlite'
            ? "strftime('%Y-%m-%d', created_at) as date"
            : "DATE(created_at) as date";

        $rawRegistrations = User::where('created_at', '>=', now()->subDays(29)->startOfDay())
            ->select(DB::raw($dateRaw), DB::raw("COUNT(*) as total"))
            ->groupBy('date')
            ->orderBy('date')
            ->pluck('total', 'date')
            ->toArray();

        // Rellenar todos los días del rango
        $dailyRegistrations = [];
        for ($i = 29; $i >= 0; $i--) {
            $day = now()->subDays($i)->format('Y-m-d');
            $dailyRegistrations[$day] = $rawRegistrations[$day] ?? 0;
        }

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
            if (! in_array($plan, ['free', 'starter', 'pro', 'business', 'admin'], true)) {
                $plan = null;
            }
        }

        if ($plan) {
            if ($plan === 'business') {
                $query->whereIn('plan', ['business', 'studio']);
            } elseif ($plan === 'admin') {
                $query->where('plan', 'admin')->where('is_admin', true);
            } else {
                $query->where('plan', $plan);
            }
        }

        $users = $query->withCount('chats')
            ->with('printifyConnection:id,user_id')
            ->orderBy('created_at', 'desc')
            ->paginate(20)
            ->withQueryString();

        $userIds = $users->getCollection()->pluck('id')->all();
        $eventsByUser = collect();
        $promptsByUser = collect();

        if (!empty($userIds)) {
            $eventsByUser = BillingEvent::whereIn('user_id', $userIds)
                ->orderByDesc('created_at')
                ->get()
                ->groupBy('user_id');

            $promptsByUser = Message::query()
                ->select('messages.content', 'messages.created_at', 'chats.user_id')
                ->join('chats', 'chats.id', '=', 'messages.chat_id')
                ->whereIn('chats.user_id', $userIds)
                ->where('messages.role', 'user')
                ->whereNotNull('messages.content')
                ->where('messages.content', '!=', '')
                ->orderByDesc('messages.created_at')
                ->get()
                ->groupBy('user_id')
                ->map(fn (Collection $messages) => $messages->take(5)->values());
        }

        $users->setCollection(
            $users->getCollection()->map(function (User $user) use ($eventsByUser, $promptsByUser) {
                $events = $eventsByUser->get($user->id, collect());
                $prompts = $promptsByUser->get($user->id, collect());

                $user->setRelation(
                    'recentTransactions',
                    $events->whereIn('event_type', ['plan_purchase', 'token_purchase'])->take(3)->values()
                );

                $user->setRelation('recentActivity', $events->take(4)->values());
                $user->setRelation('recentPrompts', $prompts);

                return $user;
            })
        );

        return view('admin.users', compact('users'));
    }

    public function updateUser(Request $request, User $user)
    {
        $validated = $request->validate([
            'tokens' => 'nullable|integer|min:0',
            'plan' => 'nullable|in:free,starter,pro,business,admin',
        ]);

        if (isset($validated['tokens'])) {
            $user->tokens = $validated['tokens'];
        }

        if (isset($validated['plan'])) {
            if ($validated['plan'] === 'admin' && ! $user->is_admin) {
                return back()->with('error', 'El Plan Admin solo puede asignarse a usuarios administradores.');
            }
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
        $user->chats()->get()->each(function ($chat) {
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

    public function apiCosts()
    {
        // Total cost and calls by service
        $byService = ApiUsageLog::select(
                'service',
                DB::raw('COUNT(*) as calls'),
                DB::raw('SUM(CASE WHEN success = 1 THEN 1 ELSE 0 END) as successes'),
                DB::raw('SUM(cost_usd) as total_cost')
            )
            ->groupBy('service')
            ->orderByDesc('total_cost')
            ->get();

        // By service + model
        $byModel = ApiUsageLog::select(
                'service',
                'model',
                DB::raw('COUNT(*) as calls'),
                DB::raw('SUM(cost_usd) as total_cost')
            )
            ->groupBy('service', 'model')
            ->orderByDesc('total_cost')
            ->get();

        // Daily cost last 30 days
        $driver = DB::getDriverName();
        $dateRaw = $driver === 'sqlite'
            ? "strftime('%Y-%m-%d', created_at) as date"
            : "DATE(created_at) as date";

        $rawDaily = ApiUsageLog::where('created_at', '>=', now()->subDays(29)->startOfDay())
            ->select(DB::raw($dateRaw), DB::raw('SUM(cost_usd) as total'))
            ->groupBy('date')
            ->orderBy('date')
            ->pluck('total', 'date')
            ->toArray();

        $dailyCost = [];
        for ($i = 29; $i >= 0; $i--) {
            $day = now()->subDays($i)->format('Y-m-d');
            $dailyCost[$day] = round((float)($rawDaily[$day] ?? 0), 5);
        }

        // Top users by cost
        $topUsers = ApiUsageLog::select(
                'user_id',
                DB::raw('COUNT(*) as calls'),
                DB::raw('SUM(cost_usd) as total_cost')
            )
            ->whereNotNull('user_id')
            ->groupBy('user_id')
            ->orderByDesc('total_cost')
            ->with('user:id,name,email')
            ->limit(10)
            ->get();

        $totalCost = ApiUsageLog::sum('cost_usd');
        $totalCalls = ApiUsageLog::count();
        $thisMonthCost = ApiUsageLog::where('created_at', '>=', now()->startOfMonth())->sum('cost_usd');
        $todayCost = ApiUsageLog::where('created_at', '>=', now()->startOfDay())->sum('cost_usd');

        return view('admin.api-costs', compact(
            'byService', 'byModel', 'dailyCost', 'topUsers',
            'totalCost', 'totalCalls', 'thisMonthCost', 'todayCost'
        ));
    }
}
