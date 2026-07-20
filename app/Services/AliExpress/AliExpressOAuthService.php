<?php

namespace App\Services\AliExpress;

use App\Models\AliExpressToken;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Handles the AliExpress Open Platform OAuth 2.0 authorization-code flow.
 *
 * Flow:
 *   1. buildAuthorizationUrl() -> redirect the merchant to AliExpress.
 *   2. AliExpress redirects back to the (HTTPS) callback with ?code & ?state.
 *   3. createToken($code) -> exchange the code for an access/refresh token.
 *
 * Note: AliExpress rejects http/localhost callback URLs. The redirect URI is
 * always resolved to an HTTPS URL (see resolveRedirectUri()).
 */
class AliExpressOAuthService
{
    public function __construct(
        protected ?string $appKey = null,
        protected ?string $appSecret = null,
    ) {
        $this->appKey ??= config('aliexpress.app_key');
        $this->appSecret ??= config('aliexpress.app_secret');
    }

    /**
     * Whether the integration has the minimum credentials configured.
     */
    public function isConfigured(): bool
    {
        return ! empty($this->appKey) && ! empty($this->appSecret);
    }

    /**
     * Resolve the HTTPS callback URL that must match the AliExpress console.
     *
     * Precedence:
     *   1. ALIEXPRESS_REDIRECT_URI (config) — required for local tunnels.
     *   2. The named route, forced to the https scheme.
     */
    public function resolveRedirectUri(): string
    {
        $configured = config('aliexpress.redirect_uri');

        $uri = $configured ?: route('aliexpress.oauth.callback');

        // AliExpress only accepts HTTPS callbacks. Force the scheme so a local
        // APP_URL=http://... never leaks an http callback to the platform.
        $uri = preg_replace('#^http://#i', 'https://', $uri);

        if (! Str::startsWith($uri, 'https://')) {
            $uri = 'https://'.ltrim($uri, '/');
        }

        return $uri;
    }

    /**
     * Build the authorization URL the merchant is redirected to.
     *
     * @return array{url: string, state: string}
     */
    public function buildAuthorizationUrl(?string $state = null): array
    {
        if (! $this->isConfigured()) {
            throw new RuntimeException('AliExpress credentials are not configured. Set ALIEXPRESS_APP_KEY and ALIEXPRESS_APP_SECRET.');
        }

        $state ??= Str::random(40);

        $query = http_build_query([
            'response_type' => 'code',
            'force_auth' => 'true',
            'redirect_uri' => $this->resolveRedirectUri(),
            'client_id' => $this->appKey,
            'state' => $state,
        ]);

        $url = rtrim(config('aliexpress.authorize_url'), '/').'?'.$query;

        Log::channel('aliexpress')->info('Built AliExpress authorization URL', [
            'redirect_uri' => $this->resolveRedirectUri(),
            'state' => $state,
        ]);

        return ['url' => $url, 'state' => $state];
    }

    /**
     * Exchange an authorization code for an access token.
     *
     * @return array<string, mixed> Decoded token response.
     *
     * @throws RuntimeException On API or transport errors.
     */
    public function createToken(string $code): array
    {
        if (! $this->isConfigured()) {
            throw new RuntimeException('AliExpress credentials are not configured.');
        }

        $path = config('aliexpress.token_create_path');

        $params = [
            'app_key' => $this->appKey,
            'code' => $code,
            'sign_method' => config('aliexpress.sign_method', 'sha256'),
            'timestamp' => (string) (int) (microtime(true) * 1000),
        ];

        return $this->dispatch($path, $params);
    }

    /**
     * Persist a token payload returned by AliExpress (encrypted at rest).
     *
     * AliExpress returns slightly different field names across endpoints, so
     * we normalise the common ones.
     *
     * @param  array<string, mixed>  $token
     */
    public function storeToken(array $token): AliExpressToken
    {
        $accessToken = $token['access_token'] ?? ($token['accessToken'] ?? null);
        $refreshToken = $token['refresh_token'] ?? ($token['refreshToken'] ?? null);

        // Expiry is returned in seconds (expires_in) on most AliExpress responses.
        $expiresIn = isset($token['expires_in']) ? (int) $token['expires_in'] : null;

        if (isset($token['refresh_token_valid_time'])) {
            $refreshExpiresAt = Carbon::createFromTimestamp((int) ($token['refresh_token_valid_time'] / 1000));
            $refreshExpiresIn = (int) ($token['refresh_token_valid_time'] / 1000) - time();
        } else {
            $refreshExpiresIn = isset($token['refresh_expires_in']) ? (int) $token['refresh_expires_in'] : null;
            $refreshExpiresAt = $refreshExpiresIn ? Carbon::now()->addSeconds($refreshExpiresIn) : null;
        }

        $record = AliExpressToken::create([
            'account' => $token['account'] ?? null,
            'account_id' => $token['user_id'] ?? ($token['account_id'] ?? null),
            'seller_id' => $token['seller_id'] ?? null,
            'access_token' => $accessToken,
            'refresh_token' => $refreshToken,
            'expires_in' => $expiresIn,
            'access_token_expires_at' => $expiresIn ? Carbon::now()->addSeconds($expiresIn) : null,
            'refresh_expires_in' => $refreshExpiresIn,
            'refresh_token_expires_at' => $refreshExpiresAt,
            'payload' => $token,
        ]);

        Log::channel('aliexpress')->info('AliExpress token persisted', [
            'id' => $record->id,
            'account' => $record->account,
            'expires_at' => optional($record->access_token_expires_at)->toDateTimeString(),
        ]);

        return $record;
    }

    /**
     * Get the most recently stored token, refreshing it if expired.
     */
    public function latestToken(): ?AliExpressToken
    {
        $token = AliExpressToken::query()->latest('id')->first();

        if ($token === null) {
            return null;
        }

        if (! $token->isAccessTokenValid() && ! empty($token->refresh_token)) {
            try {
                $refreshed = $this->refreshToken($token->refresh_token);

                return $this->storeToken($refreshed);
            } catch (\Throwable $e) {
                Log::channel('aliexpress')->error('AliExpress token auto-refresh failed', [
                    'message' => $e->getMessage(),
                ]);

                // Dispatch critical alert notification
                \Webkul\Fulfillment\Services\FulfillmentAlertService::sendAlert(
                    'critical',
                    "AliExpress token auto-refresh failed: " . $e->getMessage()
                );
            }
        }

        return $token;
    }

    /**
     * Get a token by its ID, auto-refreshing it if expired.
     */
    public function getTokenById(int $id): ?AliExpressToken
    {
        $token = AliExpressToken::find($id);

        if ($token === null) {
            return null;
        }

        if (! $token->isAccessTokenValid() && ! empty($token->refresh_token)) {
            try {
                $refreshed = $this->refreshToken($token->refresh_token);

                return $this->storeToken($refreshed);
            } catch (\Throwable $e) {
                Log::channel('aliexpress')->error('AliExpress token auto-refresh failed', [
                    'id'      => $id,
                    'message' => $e->getMessage(),
                ]);

                // Dispatch critical alert notification
                \Webkul\Fulfillment\Services\FulfillmentAlertService::sendAlert(
                    'critical',
                    "AliExpress token auto-refresh failed for Token ID {$id}: " . $e->getMessage()
                );
            }
        }

        return $token;
    }

    /**
     * Refresh an access token using a refresh token.
     *
     * @return array<string, mixed>
     */
    public function refreshToken(string $refreshToken): array
    {
        if (! $this->isConfigured()) {
            throw new RuntimeException('AliExpress credentials are not configured.');
        }

        $path = config('aliexpress.token_refresh_path');

        $params = [
            'app_key' => $this->appKey,
            'refresh_token' => $refreshToken,
            'sign_method' => config('aliexpress.sign_method', 'sha256'),
            'timestamp' => (string) (int) (microtime(true) * 1000),
        ];

        return $this->dispatch($path, $params);
    }

    /**
     * Sign the request and send it to the AliExpress system REST gateway.
     *
     * @param  array<string, string>  $params
     * @return array<string, mixed>
     */
    protected function dispatch(string $path, array $params): array
    {
        $params['sign'] = $this->sign($path, $params);

        $endpoint = rtrim(config('aliexpress.token_url'), '/').$path;

        Log::channel('aliexpress')->info('Dispatching AliExpress token request', [
            'endpoint' => $endpoint,
            'app_key' => $this->appKey,
            'sign_method' => $params['sign_method'],
        ]);

        try {
            $response = Http::asForm()
                ->connectTimeout((int) config('aliexpress.connect_timeout', 30))
                ->timeout((int) config('aliexpress.timeout', 60))
                ->post($endpoint, $params);
        } catch (ConnectionException $e) {
            Log::channel('aliexpress')->error('AliExpress token request transport error', [
                'message' => $e->getMessage(),
            ]);

            throw new RuntimeException('Unable to reach AliExpress token endpoint: '.$e->getMessage(), 0, $e);
        }

        $body = $response->json() ?? [];

        // AliExpress returns an error envelope (code != 0 / error_response) on failure.
        $errorCode = $body['code'] ?? ($body['error_response']['code'] ?? null);

        if ($response->failed() || ($errorCode !== null && (string) $errorCode !== '0')) {
            Log::channel('aliexpress')->error('AliExpress token request failed', [
                'status' => $response->status(),
                'body' => $body,
            ]);

            $message = $body['message']
                ?? ($body['error_response']['msg'] ?? 'Unknown AliExpress API error');

            throw new RuntimeException('AliExpress token request failed: '.$message);
        }

        Log::channel('aliexpress')->info('AliExpress token request succeeded', [
            'keys' => array_keys($body),
        ]);

        return $body;
    }

    /**
     * Generate the IOP gateway signature.
     *
     * Algorithm (sha256 / md5):
     *   1. Sort all request params (excluding "sign") by key (ASCII order).
     *   2. Concatenate as: apiPath + key1value1 + key2value2 + ...
     *   3. HMAC-SHA256 with the app secret (or MD5 with secret on both ends).
     *   4. Upper-case hex.
     *
     * @param  array<string, string>  $params
     */
    protected function sign(string $path, array $params): string
    {
        unset($params['sign']);

        ksort($params);

        $base = $path;

        foreach ($params as $key => $value) {
            $base .= $key.$value;
        }

        $method = strtolower((string) config('aliexpress.sign_method', 'sha256'));

        if ($method === 'md5') {
            return strtoupper(md5($this->appSecret.$base.$this->appSecret));
        }

        return strtoupper(hash_hmac('sha256', $base, $this->appSecret));
    }
}
