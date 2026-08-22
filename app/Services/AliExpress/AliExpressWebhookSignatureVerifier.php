<?php

namespace App\Services\AliExpress;

use App\Models\AliExpressSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AliExpressWebhookSignatureVerifier
{
    /**
     * Verify incoming webhook signature from AliExpress Open Platform.
     *
     * Protocol:
     * Base = {AppKey} + {RawRequestBody}
     * Signature = HEX_ENCODE(HMAC_SHA256(Base, AppSecret))
     * Authorization Header = Signature
     */
    public function verify(Request $request): bool
    {
        $authHeader = (string) ($request->header('Authorization') ?? $request->header('authorization') ?? '');
        if (empty($authHeader)) {
            Log::channel('aliexpress')->warning('AliExpress Webhook rejected: missing Authorization header.');

            return false;
        }

        $rawBody = (string) $request->getContent();
        if ($rawBody === '') {
            Log::channel('aliexpress')->warning('AliExpress Webhook rejected: empty request body.');

            return false;
        }

        $setting = AliExpressSetting::current();
        if (! $setting || empty($setting->app_key) || empty($setting->app_secret)) {
            Log::channel('aliexpress')->error('AliExpress Webhook rejected: app_key or app_secret not configured in database.');

            return false;
        }

        $appKey = (string) $setting->app_key;
        $appSecret = (string) $setting->app_secret;

        $base = $appKey.$rawBody;
        $calculatedSignature = hash_hmac('sha256', $base, $appSecret);

        $isValid = hash_equals(strtolower($calculatedSignature), strtolower($authHeader));

        if (! $isValid) {
            $requestId = substr(hash('sha256', $rawBody), 0, 12);
            Log::channel('aliexpress')->warning('AliExpress Webhook signature verification failed.', [
                'request_hash' => $requestId,
            ]);
        }

        return $isValid;
    }
}
