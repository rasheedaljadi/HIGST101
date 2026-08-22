<?php

namespace App\Services\AliExpress;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Generic signed client for AliExpress System & Business (IOP) REST APIs.
 *
 * Used to call dropshipping endpoints (e.g. aliexpress.ds.product.get,
 * aliexpress.ds.order.create) once an access token has been obtained.
 *
 * Signing rules match AliExpressOAuthService::sign() — params sorted by key,
 * concatenated after the API path, then HMAC-SHA256 with the app secret.
 */
class AliExpressApiClient
{
    public function __construct(
        protected ?string $appKey = null,
        protected ?string $appSecret = null,
    ) {
        $this->appKey ??= config('aliexpress.app_key');
        $this->appSecret ??= config('aliexpress.app_secret');
    }

    /**
     * Call a business API method.
     *
     * @param  string  $method  Dotted API name, e.g. "aliexpress.ds.product.get".
     * @param  array<string, mixed>  $params  Business parameters.
     * @return array{ok: bool, status: int, code: string|null, message: string|null, body: array<string, mixed>}
     */
    public function call(string $method, string $accessToken, array $params = []): array
    {
        if (empty($this->appKey) || empty($this->appSecret)) {
            throw new RuntimeException('AliExpress credentials are not configured.');
        }

        // The system params required by the IOP gateway.
        $request = array_merge($params, [
            'app_key' => $this->appKey,
            'access_token' => $accessToken,
            'method' => $method,
            'format' => 'json',
            'sign_method' => config('aliexpress.sign_method', 'sha256'),
            'timestamp' => (string) (int) (microtime(true) * 1000),
        ]);

        // Stringify scalar values for a stable signature.
        foreach ($request as $key => $value) {
            if (is_array($value)) {
                $request[$key] = json_encode($value, JSON_UNESCAPED_UNICODE);
            } elseif (is_bool($value)) {
                $request[$key] = $value ? 'true' : 'false';
            } else {
                $request[$key] = (string) $value;
            }
        }

        $endpoint = config('aliexpress.business_url');
        $maxRetries = 3;
        $attempt = 0;

        while ($attempt < $maxRetries) {
            $attempt++;

            // Refresh timestamp and signature for each attempt
            $request['timestamp'] = (string) (int) (microtime(true) * 1000);
            $request['sign'] = $this->sign('', $request);

            Log::channel('aliexpress')->info('AliExpress API call', [
                'method' => $method,
                'endpoint' => $endpoint,
                'attempt' => $attempt,
            ]);

            try {
                $response = Http::asForm()
                    ->connectTimeout((int) config('aliexpress.connect_timeout', 30))
                    ->timeout((int) config('aliexpress.timeout', 60))
                    ->post($endpoint, $request);
            } catch (ConnectionException $e) {
                if ($attempt < $maxRetries) {
                    usleep(1500000); // 1.5s

                    continue;
                }

                Log::channel('aliexpress')->error('AliExpress API transport error', [
                    'method' => $method,
                    'message' => $e->getMessage(),
                ]);

                throw new RuntimeException('Unable to reach AliExpress API: '.$e->getMessage(), 0, $e);
            }

            $body = $response->json() ?? [];

            // Error envelope: top-level "code" (!= 0) or "error_response".
            $code = $body['code']
                ?? ($body['error_response']['code'] ?? null);

            $message = $body['message']
                ?? ($body['error_response']['msg'] ?? null);

            // If rate limited by AliExpress (ApiCallLimit or frequency exceeds limit), backoff and retry
            if ($code === 'ApiCallLimit' || str_contains(strtolower((string) $message), 'frequency exceeds the limit') || str_contains(strtolower((string) $message), 'calllimit')) {
                if ($attempt < $maxRetries) {
                    Log::channel('aliexpress')->warning('AliExpress API rate limited, backing off 1.5s...', [
                        'method' => $method,
                        'attempt' => $attempt,
                    ]);
                    usleep(1500000);

                    continue;
                }
            }

            $ok = $response->successful() && ($code === null || (string) $code === '0');

            Log::channel('aliexpress')->info('AliExpress API result', [
                'method' => $method,
                'status' => $response->status(),
                'ok' => $ok,
                'code' => $code,
                'message' => $message,
            ]);

            return [
                'ok' => $ok,
                'status' => $response->status(),
                'code' => $code !== null ? (string) $code : null,
                'message' => $message,
                'body' => $body,
            ];
        }

        return [
            'ok' => false,
            'status' => 429,
            'code' => 'ApiCallLimit',
            'message' => 'AliExpress API access frequency exceeds limit after retries.',
            'body' => [],
        ];
    }

    /**
     * IOP signature: sort params, concat after path, HMAC-SHA256, upper hex.
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
