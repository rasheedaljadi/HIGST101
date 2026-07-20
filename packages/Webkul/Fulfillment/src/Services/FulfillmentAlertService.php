<?php

namespace Webkul\Fulfillment\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class FulfillmentAlertService
{
    /**
     * Dispatch a fulfillment alert.
     *
     * @param  string  $severity  (info, warning, error, critical)
     * @param  string  $message
     * @param  mixed|null  $po
     * @return void
     */
    public static function sendAlert(string $severity, string $message, $po = null): void
    {
        // 1. Log the alert
        Log::channel(config('fulfillment.log_channel', 'aliexpress'))->error("Fulfillment Alert [{$severity}]: {$message}", [
            'po_id' => $po?->id,
        ]);

        // 2. Add to active alerts cache for persistent banners on dashboard
        $alerts = Cache::get('fulfillment_active_alerts', []);
        $alerts[] = [
            'id'        => uniqid('alert_', true),
            'severity'  => $severity,
            'message'   => $message,
            'po_id'     => $po?->id,
            'timestamp' => now()->toDateTimeString(),
        ];
        
        // Keep only last 10 alerts in cache
        if (count($alerts) > 10) {
            array_shift($alerts);
        }
        Cache::put('fulfillment_active_alerts', $alerts, now()->addDays(7));

        // 3. Send email to operations team (Error or Critical) within 60s
        if (in_array($severity, ['error', 'critical'], true)) {
            try {
                $recipient = config('fulfillment.operations_email', 'ops@hayest.com');
                
                // Using standard Laravel mail setup
                Mail::raw("Fulfillment Alert [{$severity}]:\n\n{$message}\n\nPO ID: " . ($po?->id ?? 'N/A'), function ($mail) use ($recipient, $severity) {
                    $mail->to($recipient)
                         ->subject("Fulfillment Alert [{$severity}]");
                });
            } catch (\Throwable $e) {
                Log::channel('aliexpress')->error("Failed to send alert email: " . $e->getMessage());
            }
        }
    }
    
    /**
     * Clear an active alert by ID.
     *
     * @param  string  $alertId
     * @return void
     */
    public static function clearAlert(string $alertId): void
    {
        $alerts = Cache::get('fulfillment_active_alerts', []);
        $alerts = array_filter($alerts, fn($a) => $a['id'] !== $alertId);
        Cache::put('fulfillment_active_alerts', array_values($alerts), now()->addDays(7));
    }
}
