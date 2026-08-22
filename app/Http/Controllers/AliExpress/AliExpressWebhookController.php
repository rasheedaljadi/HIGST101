<?php

namespace App\Http\Controllers\AliExpress;

use App\Http\Controllers\Controller;
use App\Models\AliExpressSetting;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class AliExpressWebhookController extends Controller
{
    /**
     * Handle incoming Message Push service (Webhook / GOP callback) from AliExpress Open Platform.
     */
    public function handle(Request $request): Response
    {
        $rawBody = (string) $request->getContent();
        $authHeader = (string) ($request->header('Authorization') ?? $request->header('authorization') ?? '');

        // If simple GET ping, return immediate 200 OK
        if ($request->isMethod('get')) {
            return response('{"code":0,"message":"AliExpress Message Push Service endpoint is alive."}', 200)
                ->header('Content-Type', 'application/json');
        }

        $setting = AliExpressSetting::current();
        $appKey = (string) ($setting->app_key ?? '');
        $appSecret = (string) ($setting->app_secret ?? '');

        // Verify signature if provided and credentials exist
        if (! empty($authHeader) && ! empty($appKey) && ! empty($appSecret)) {
            $base = $appKey.$rawBody;
            $calculatedSignature = hash_hmac('sha256', $base, $appSecret);

            if (! hash_equals(strtolower($calculatedSignature), strtolower($authHeader))) {
                Log::channel('aliexpress')->warning('AliExpress Webhook signature mismatch', [
                    'expected' => $calculatedSignature,
                    'received' => $authHeader,
                ]);

                // Still log but if strict signature check is required:
                // return response('{"code":401,"msg":"authorization mismatch"}', 400)->header('Content-Type', 'application/json');
            }
        }

        $data = json_decode($rawBody, true) ?? [];
        $messageType = $data['message_type'] ?? null;

        Log::channel('aliexpress')->info('AliExpress Webhook received', [
            'message_type' => $messageType,
            'seller_id' => $data['seller_id'] ?? null,
            'body_summary' => substr($rawBody, 0, 200),
        ]);

        // AliExpress requires immediate HTTP 200 OK within 500ms
        $responseContent = ! empty($rawBody) ? $rawBody : '{"code":0,"message":"success"}';

        return response($responseContent, 200)
            ->header('Content-Type', 'application/json');
    }
}
