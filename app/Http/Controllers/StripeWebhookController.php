<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
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
            // On plan change, always grant the full new plan's token amount immediately.
            // Also refresh the reset timestamp so the next monthly reset is correct.
            $user->update([
                'plan'            => $plan,
                'tokens'          => SubscriptionController::tokensForPlan($plan),
                'tokens_reset_at' => now(),
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
     * Handle one-time credit pack payment as webhook backup.
     */
    public function handleCheckoutSessionCompleted(array $payload): void
    {
        $session = $payload['data']['object'];

        if (($session['mode'] ?? '') !== 'payment') {
            return;
        }

        $sessionId = $session['id'] ?? null;
        $userId    = $session['metadata']['user_id'] ?? null;
        $credits   = (int) ($session['metadata']['credits'] ?? 0);

        if (! $sessionId || ! $userId || $credits <= 0) {
            return;
        }

        if ($session['payment_status'] !== 'paid') {
            return;
        }

        // Idempotency: skip if already credited via success URL
        if (! Cache::add('cp_sess_' . $sessionId, true, now()->addDays(7))) {
            return;
        }

        $user = User::find($userId);
        $user?->increment('tokens', $credits);
    }

    /**
     * Resolve the local plan name from a Stripe price ID.
     */
    private function planFromPriceId(string $priceId): ?string
    {
        return SubscriptionController::planFromPriceId($priceId);
    }
}
