<?php

namespace App\Http\Controllers;

use App\Models\PrintfulConnection;
use App\Services\PrintfulService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PrintfulOAuthController extends Controller
{
    public function __construct(private PrintfulService $printful) {}

    /**
     * GET /printful/connect
     * Redirect the user to Printful's OAuth consent screen.
     */
    public function redirect(Request $request)
    {
        $state = Str::random(40);
        $request->session()->put('printful_oauth_state', $state);

        return redirect(PrintfulService::authorizeUrl($state));
    }

    /**
     * GET /printful/callback
     * Printful redirects here after the user grants (or denies) access.
     */
    public function callback(Request $request)
    {
        // Validate state to prevent CSRF
        $sessionState = $request->session()->pull('printful_oauth_state');
        if (! $sessionState || ! hash_equals($sessionState, (string) $request->query('state', ''))) {
            return redirect()->route('profile.edit')
                ->with('printful_error', 'Invalid state parameter. Please try connecting again.');
        }

        if ($request->has('error')) {
            return redirect()->route('profile.edit')
                ->with('printful_error', 'Printful connection declined.');
        }

        $code = $request->query('code');
        if (! $code) {
            return redirect()->route('profile.edit')
                ->with('printful_error', 'No authorisation code received from Printful.');
        }

        try {
            $tokens = $this->printful->exchangeCode($code);

            $connection = PrintfulConnection::updateOrCreate(
                ['user_id' => $request->user()->id],
                [
                    'access_token'            => $tokens['access_token'],
                    'refresh_token'           => $tokens['refresh_token'] ?? null,
                    'access_token_expires_at' => isset($tokens['expires_in'])
                        ? now()->addSeconds($tokens['expires_in'])
                        : null,
                ]
            );

            // Fetch and store the first store automatically
            $stores = $this->printful->getStores($connection);
            if (! empty($stores)) {
                $connection->update([
                    'store_id'   => $stores[0]['id'],
                    'store_name' => $stores[0]['name'],
                ]);
            }
        } catch (\Throwable $e) {
            return redirect()->route('profile.edit')
                ->with('printful_error', 'Could not complete Printful connection: ' . $e->getMessage());
        }

        return redirect()->route('profile.edit')
            ->with('printful_success', 'Printful account connected successfully!');
    }

    /**
     * DELETE /printful/disconnect
     * Remove the stored tokens for the current user.
     */
    public function disconnect(Request $request)
    {
        $request->user()->printfulConnection()->delete();

        if ($request->expectsJson()) {
            return response()->json(['success' => true]);
        }

        return redirect()->route('profile.edit')
            ->with('printful_success', 'Printful account disconnected.');
    }
}
