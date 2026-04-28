<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class SubscriptionController extends Controller
{
    /**
     * Resolve the Stripe price ID for a given plan.
     */
    private function resolvePlan(string $plan): array
    {
        $prices = config('services.stripe.prices');

        $map = [
            'starter'  => $prices['starter_monthly'],
            'pro'      => $prices['pro_monthly'],
            'business' => $prices['business_monthly'],
        ];

        $priceId = $map[$plan] ?? null;

        if (! $priceId) {
            abort(400, 'Invalid plan.');
        }

        return ['price_id' => $priceId, 'plan' => $plan];
    }

    /**
     * Tokens (credits) granted per plan per month.
     * Single source of truth — delegates to User model.
     */
    public static function tokensForPlan(string $plan): int
    {
        return User::creditsForPlan($plan);
    }

    /**
     * Redirect the user to Stripe Checkout.
     */
    public function checkout(Request $request)
    {
        $request->validate([
            'plan' => ['required', 'in:starter,pro,business'],
        ]);

        $user = $request->user();
        $resolved = $this->resolvePlan($request->plan);

        return $user->newSubscription('default', $resolved['price_id'])
            ->checkout([
                'success_url' => route('subscription.success') . '?session_id={CHECKOUT_SESSION_ID}',
                'cancel_url'  => route('subscription.cancel'),
                'metadata'    => [
                    'plan' => $resolved['plan'],
                ],
            ]);
    }

    /**
     * Resolve plan name from a Stripe price ID.
     */
    public static function planFromPriceId(string $priceId): ?string
    {
        $prices = config('services.stripe.prices');

        if ($priceId === $prices['starter_monthly'])  return 'starter';
        if ($priceId === $prices['pro_monthly'])       return 'pro';
        if ($priceId === $prices['business_monthly'])  return 'business';

        return null;
    }

    /**
     * Handle successful checkout return.
     */
    public function success(Request $request)
    {
        $user = $request->user();

        if ($user) {
            // Primary: look up the active Cashier subscription (most reliable)
            $subscription = $user->subscription('default');

            if ($subscription && $subscription->active()) {
                $priceId = $subscription->items()->first()?->stripe_price;
                $plan = $priceId ? self::planFromPriceId($priceId) : null;

                if ($plan) {
                    $user->update([
                        'plan'            => $plan,
                        'tokens'          => self::tokensForPlan($plan),
                        'tokens_reset_at' => now()->startOfMonth(),
                    ]);
                }
            } else {
                // Fallback: read session metadata from Stripe
                $sessionId = $request->get('session_id');
                if ($sessionId) {
                    $stripe = new \Stripe\StripeClient(config('cashier.secret'));
                    $session = $stripe->checkout->sessions->retrieve($sessionId);
                    $plan = $session->metadata->plan ?? null;

                    if ($plan) {
                        $user->update([
                            'plan'            => $plan,
                            'tokens'          => self::tokensForPlan($plan),
                            'tokens_reset_at' => now()->startOfMonth(),
                        ]);
                    }
                }
            }
        }

        return view('subscription.success');
    }

    /**
     * Handle cancelled checkout (Stripe redirect).
     */
    public function cancel()
    {
        return view('subscription.cancel');
    }

    /**
     * Cancel the user's active subscription immediately.
     */
    public function cancelSubscription(Request $request)
    {
        $user = $request->user();

        if ($user->subscribed()) {
            $user->subscription()->cancel();
            $user->update([
                'plan'            => 'free',
                'tokens'          => self::tokensForPlan('free'),
                'tokens_reset_at' => now()->startOfMonth(),
            ]);
        }

        return redirect()->route('profile.show')
            ->with('subscription_cancelled', 'Your subscription has been cancelled. You are now on the Free plan.');
    }

    /**
     * Show the billing portal.
     */
    public function portal(Request $request)
    {
        $configuration = config('services.stripe.portal_configuration');

        return $request->user()->redirectToBillingPortal(
            route('designs.form'),
            array_filter(['configuration' => $configuration])
        );
    }
}
