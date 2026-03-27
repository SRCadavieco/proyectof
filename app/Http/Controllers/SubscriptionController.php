<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class SubscriptionController extends Controller
{
    /**
     * Map plan+billing to Stripe price IDs and local plan names.
     */
    private function resolvePlan(string $plan, string $billing): array
    {
        $prices = config('services.stripe.prices');

        $map = [
            'pro' => [
                'monthly' => $prices['pro_monthly'],
                'yearly'  => $prices['pro_yearly'],
            ],
            'studio' => [
                'monthly' => $prices['studio_monthly'],
                'yearly'  => $prices['studio_yearly'],
            ],
        ];

        $priceId = $map[$plan][$billing] ?? null;

        if (! $priceId) {
            abort(400, 'Invalid plan or billing period.');
        }

        return ['price_id' => $priceId, 'plan' => $plan];
    }

    /**
     * Tokens granted per plan.
     */
    public static function tokensForPlan(string $plan): int
    {
        return match ($plan) {
            'pro'    => 100,
            'studio' => 9999,
            default  => 5,
        };
    }

    /**
     * Redirect the user to Stripe Checkout.
     */
    public function checkout(Request $request)
    {
        $request->validate([
            'plan'    => ['required', 'in:pro,studio'],
            'billing' => ['required', 'in:monthly,yearly'],
        ]);

        $user = $request->user();
        $resolved = $this->resolvePlan($request->plan, $request->billing);

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

        if (in_array($priceId, [$prices['pro_monthly'], $prices['pro_yearly']])) {
            return 'pro';
        }

        if (in_array($priceId, [$prices['studio_monthly'], $prices['studio_yearly']])) {
            return 'studio';
        }

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
                        'plan'   => $plan,
                        'tokens' => self::tokensForPlan($plan),
                    ]);
                }
            } else {
                // Fallback: read session metadata from Stripe
                $sessionId = $request->get('session_id');
                if ($sessionId) {
                    $stripe = new \Stripe\StripeClient(env('STRIPE_SECRET'));
                    $session = $stripe->checkout->sessions->retrieve($sessionId);
                    $plan = $session->metadata->plan ?? null;

                    if ($plan) {
                        $user->update([
                            'plan'   => $plan,
                            'tokens' => self::tokensForPlan($plan),
                        ]);
                    }
                }
            }
        }

        return view('subscription.success');
    }

    /**
     * Handle cancelled checkout.
     */
    public function cancel()
    {
        return view('subscription.cancel');
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
