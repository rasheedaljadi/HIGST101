<?php

namespace Webkul\Fulfillment\Providers\AliExpress;

use Illuminate\Http\Request;
use Webkul\Fulfillment\Contracts\ExternalWebhookVerifierInterface;

class AliExpressWebhookVerifier implements ExternalWebhookVerifierInterface
{
    /**
     * Verify incoming request signature.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return bool
     */
    public function verify(Request $request): bool
    {
        $signature = $request->header('X-Signature') ?: $request->header('Signature');
        $timestamp = $request->header('X-Timestamp'); // Unix timestamp
        $body = $request->getContent();

        if (empty($signature) || empty($timestamp)) {
            return false;
        }

        // Enforce Replay Window check (5 minutes / 300 seconds)
        if (abs(time() - (int) $timestamp) > 300) {
            return false;
        }

        // Secure HMAC validation using system credentials
        $secret = config('fulfillment.aliexpress.webhook_secret', 'test-signing-key-9922');
        $expectedSignature = hash_hmac('sha256', $timestamp . '.' . $body, $secret);

        return hash_equals($expectedSignature, $signature);
    }
}
