<?php

namespace App\Http\Controllers\AliExpress;

use App\Http\Controllers\Controller;
use App\Services\AliExpress\AliExpressOAuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Drives the AliExpress Open Platform OAuth authorization-code flow.
 *
 *   GET /aliexpress/connect   -> redirect merchant to AliExpress (stores state)
 *   GET /aliexpress/callback  -> receives ?code & ?state, exchanges for token
 */
class AliExpressOAuthController extends Controller
{
    public function __construct(
        protected AliExpressOAuthService $oauth,
    ) {}

    /**
     * Redirect the merchant to the AliExpress authorization screen.
     */
    public function connect(Request $request): RedirectResponse|JsonResponse
    {
        if (! $this->oauth->isConfigured()) {
            return response()->json([
                'message' => 'AliExpress integration is not configured. Set ALIEXPRESS_APP_KEY and ALIEXPRESS_APP_SECRET.',
            ], 503);
        }

        $authorization = $this->oauth->buildAuthorizationUrl();

        // Persist state for CSRF protection on the callback.
        $request->session()->put('aliexpress_oauth_state', $authorization['state']);

        return redirect()->away($authorization['url']);
    }

    /**
     * Handle the callback from AliExpress (the registered HTTPS Callback URL).
     */
    public function callback(Request $request): JsonResponse|RedirectResponse
    {
        Log::channel('aliexpress')->info('AliExpress callback received', [
            'has_code' => $request->filled('code'),
            'has_state' => $request->filled('state'),
            'query' => $request->except(['code']),
        ]);

        // Surface OAuth-level errors returned in the redirect.
        if ($request->filled('error') || $request->filled('error_description')) {
            Log::channel('aliexpress')->warning('AliExpress callback returned an error', [
                'error' => $request->query('error'),
                'error_description' => $request->query('error_description'),
            ]);

            return response()->json([
                'message' => 'AliExpress authorization failed.',
                'error' => $request->query('error'),
                'details' => $request->query('error_description'),
            ], 400);
        }

        $code = $request->query('code');

        if (empty($code)) {
            return response()->json([
                'message' => 'Missing authorization code in AliExpress callback.',
            ], 400);
        }

        // Validate state (CSRF) when present in the session.
        $expectedState = $request->session()->pull('aliexpress_oauth_state');

        if ($expectedState !== null && $request->query('state') !== $expectedState) {
            Log::channel('aliexpress')->warning('AliExpress callback state mismatch', [
                'expected' => $expectedState,
                'received' => $request->query('state'),
            ]);

            return response()->json([
                'message' => 'Invalid OAuth state. Possible CSRF attempt.',
            ], 419);
        }

        try {
            $token = $this->oauth->createToken($code);

            // Persist the token (encrypted) so it can be reused for API calls.
            $record = $this->oauth->storeToken($token);
        } catch (Throwable $e) {
            Log::channel('aliexpress')->error('AliExpress token exchange failed', [
                'message' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Failed to exchange authorization code for an access token.',
                'error' => $e->getMessage(),
            ], 502);
        }

        // NOTE: Tokens are stored encrypted in the aliexpress_tokens table.
        // Avoid logging the raw access/refresh tokens.
        Log::channel('aliexpress')->info('AliExpress authorization completed successfully', [
            'token_id' => $record->id,
            'token_fields' => array_keys($token),
        ]);

        return response()->json([
            'message' => 'AliExpress authorization successful.',
            'token_id' => $record->id,
            'account' => $record->account,
            'expires_in' => $token['expires_in'] ?? ($token['expire_time'] ?? null),
            'has_refresh' => ! empty($record->refresh_token),
        ]);
    }
}
