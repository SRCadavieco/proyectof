<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Laravel\Cashier\Http\Controllers\WebhookController as CashierWebhookController;

class StripeWebhookController extends CashierWebhookController
{
    /**
     * Handle customer subscription updated.
     */
    public function handleCustomerSubscriptionUpdated(array $payload): void
    {
        parent::handleCustomerSubscriptionUpdated($payload);

        $stripeSubscription = $payload['data']['object'];
        $stripeId = $stripeSubscription['customer'] ?? null;

        if (! $stripeId) {
            return;
        }

        $user = User::where('stripe_id', $stripeId)->first();
        if (! $user) {
            return;
        }

        $plan = $this->planFromPriceId($stripeSubscription['items']['data'][0]['price']['id'] ?? '');

        if ($stripeSubscription['status'] === 'active' && $plan) {
            $user->update([
                'plan'            => $plan,
                'tokens'          => SubscriptionController::tokensForPlan($plan),
                'tokens_reset_at' => now()->startOfMonth(),
            ]);
        }
    }

    /**
     * Handle customer subscription deleted (cancellation).
     */
    public function handleCustomerSubscriptionDeleted(array $payload): void
    {
        parent::handleCustomerSubscriptionDeleted($payload);

        $stripeId = $payload['data']['object']['customer'] ?? null;

        if (! $stripeId) {
            return;
        }

        $user = User::where('stripe_id', $stripeId)->first();
        if (! $user) {
            return;
        }

        $user->update([
            'plan'            => 'free',
            'tokens'          => SubscriptionController::tokensForPlan('free'),
            'tokens_reset_at' => now()->startOfMonth(),
        ]);
    }

    /**
     * Resolve the local plan name from a Stripe price ID.
     */
    private function planFromPriceId(string $priceId): ?string
    {
        return SubscriptionController::planFromPriceId($priceId);
    }
}
