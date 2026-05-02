<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class CreditPackController extends Controller
{
    private const PACKS = [
        'small'  => ['amount' => 100,  'credits' => 10,  'label' => '10 Credits'],
        'medium' => ['amount' => 500,  'credits' => 60,  'label' => '60 Credits'],
        'large'  => ['amount' => 1000, 'credits' => 140, 'label' => '140 Credits'],
    ];

    public function checkout(Request $request)
    {
        $request->validate(['pack' => 'required|in:small,medium,large']);

        $pack = self::PACKS[$request->input('pack')];
        $user = Auth::user();

        $stripe = new \Stripe\StripeClient(config('cashier.secret'));

        $session = $stripe->checkout->sessions->create([
            'payment_method_types' => ['card'],
            'mode'                 => 'payment',
            'customer_email'       => $user->email,
            'line_items'           => [[
                'quantity'   => 1,
                'price_data' => [
                    'currency'     => 'usd',
                    'unit_amount'  => $pack['amount'],
                    'product_data' => [
                        'name'        => 'Credits Pack — ' . $pack['label'],
                        'description' => $pack['credits'] . ' one-time credits for Merch AI',
                    ],
                ],
            ]],
            'metadata' => [
                'user_id' => (string) $user->id,
                'pack'    => $request->input('pack'),
                'credits' => (string) $pack['credits'],
            ],
            'success_url' => route('credits.success') . '?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url'  => route('designs.form'),
        ]);

        return redirect($session->url);
    }

    public function success(Request $request)
    {
        $sessionId = $request->query('session_id');

        if (! $sessionId) {
            return redirect()->route('designs.form');
        }

        $stripe  = new \Stripe\StripeClient(config('cashier.secret'));
        $session = $stripe->checkout->sessions->retrieve($sessionId, ['expand' => ['payment_intent']]);

        if ($session->payment_status !== 'paid') {
            return redirect()->route('designs.form')->with('error', 'Payment not completed.');
        }

        $userId  = $session->metadata->user_id ?? null;
        $credits = (int) ($session->metadata->credits ?? 0);

        if (! $userId || (string) Auth::id() !== $userId || $credits <= 0) {
            return redirect()->route('designs.form');
        }

        // Idempotency: only credit once per Stripe session
        if (! Cache::add('cp_sess_' . $sessionId, true, now()->addDays(7))) {
            return redirect()->route('designs.form')->with('credits_purchased', true);
        }

        Auth::user()->increment('tokens', $credits);

        return redirect()->route('designs.form')->with('credits_purchased', $credits);
    }
}
