<?php

use Webkul\Fulfillment\Providers\AliExpress\AliExpressFulfillmentProvider;

return [

    /*
    |--------------------------------------------------------------------------
    | Default Fulfillment Provider
    |--------------------------------------------------------------------------
    |
    | The `provider code` used when a purchase order does not specify one
    | explicitly. It must match a key registered in the "providers" map below.
    |
    */

    'default_provider' => env('FULFILLMENT_DEFAULT_PROVIDER', 'aliexpress'),

    /*
    |--------------------------------------------------------------------------
    | Registered Fulfillment Providers
    |--------------------------------------------------------------------------
    |
    | Maps each text `provider code` to the class that implements
    | Webkul\Fulfillment\Contracts\FulfillmentProviderInterface. The
    | FulfillmentProviderRegistry resolves the code to the class listed here.
    | Adding a new provider is done here alone — no change to the Sales module
    | or the Fulfillment service is required.
    |
    */

    'providers' => [
        'aliexpress' => AliExpressFulfillmentProvider::class,
        // 'cj'      => \Webkul\Fulfillment\Providers\CJ\CJFulfillmentProvider::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Webhook Verifiers, Event Normalizers, Retry Policies, and Capabilities
    |--------------------------------------------------------------------------
    */

    'verifiers' => [
        'aliexpress' => \Webkul\Fulfillment\Providers\AliExpress\AliExpressWebhookVerifier::class,
        'cj'         => \Webkul\Fulfillment\Providers\CJ\CJCapabilities::class, // Simple mock verifier
    ],

    'normalizers' => [
        'aliexpress' => \Webkul\Fulfillment\Providers\AliExpress\AliExpressEventNormalizer::class,
        'cj'         => \Webkul\Fulfillment\Providers\CJ\CJEventNormalizer::class,
    ],

    'retry_policies' => [
        'aliexpress' => \Webkul\Fulfillment\Providers\AliExpress\AliExpressRetryPolicy::class,
        'cj'         => \Webkul\Fulfillment\Providers\CJ\CJRetryPolicy::class,
    ],

    'capabilities' => [
        'aliexpress' => \Webkul\Fulfillment\Providers\AliExpress\AliExpressCapabilities::class,
        'cj'         => \Webkul\Fulfillment\Providers\CJ\CJCapabilities::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Trigger Event
    |--------------------------------------------------------------------------
    |
    | The Bagisto event that starts fulfillment planning. "sales.invoice.save.
    | after" is the single point that guarantees payment confirmation for both
    | online payments and Cash-on-Delivery (see ADR-003).
    |
    */

    'trigger' => [
        'event' => 'sales.invoice.save.after',
    ],

    /*
    |--------------------------------------------------------------------------
    | Retry Policy
    |--------------------------------------------------------------------------
    |
    | "max_attempts" bounds automatic retries for transient failures (aligned
    | with SyncProductJob::$tries). "backoff" is the base delay in seconds used
    | for exponential backoff (aligned with SyncProductJob::$backoff).
    |
    */

    'retry' => [
        'max_attempts' => (int) env('FULFILLMENT_RETRY_MAX_ATTEMPTS', 3),
        'backoff' => (int) env('FULFILLMENT_RETRY_BACKOFF', 60),
    ],

    /*
    |--------------------------------------------------------------------------
    | Execution Lock TTL
    |--------------------------------------------------------------------------
    |
    | Maximum lease duration (seconds) for the "fulfillment-po-{id}" execution
    | lock that prevents concurrent processing of the same purchase order
    | (aligned with the Cache::lock pattern in AliExpressProductSyncer).
    |
    */

    'lock_ttl' => (int) env('FULFILLMENT_LOCK_TTL', 600),

    /*
    |--------------------------------------------------------------------------
    | Supplier Status Polling
    |--------------------------------------------------------------------------
    |
    | "enabled" toggles the scheduled PollSupplierOrdersJob. "interval" is the
    | number of seconds between polling cycles — a starting value that should be
    | reviewed against the provider rate limits.
    |
    */

    'poll' => [
        'enabled' => (bool) env('FULFILLMENT_POLL_ENABLED', true),
        'interval' => (int) env('FULFILLMENT_POLL_INTERVAL', 900),
    ],

    /*
    |--------------------------------------------------------------------------
    | Failure Log Channel
    |--------------------------------------------------------------------------
    |
    | The log channel used when the Bridge records a fulfillment failure. It
    | reuses the existing "aliexpress" channel defined in config/logging.php so
    | supplier-related diagnostics stay in one place. All content written here
    | is passed through the SecretRedactor first (see Requirement 11).
    |
    */

    'log_channel' => env('FULFILLMENT_LOG_CHANNEL', 'aliexpress'),

    /*
    |--------------------------------------------------------------------------
    | Feature Flags
    |--------------------------------------------------------------------------
    |
    | "admin_ui_enabled" controls the visibility of fulfillment admin menus and views.
    | "retry_enabled" enables or disables automatic queue-based retries.
    | "manual_cancel_enabled" enables or disables administrative cancel actions.
    |
    */
    'admin_ui_enabled' => (bool) env('FULFILLMENT_ADMIN_UI_ENABLED', true),
    'retry_enabled'    => (bool) env('FULFILLMENT_RETRY_ENABLED', true),
    'manual_cancel_enabled' => (bool) env('FULFILLMENT_MANUAL_CANCEL_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Approval Workflow Settings
    |--------------------------------------------------------------------------
    |
    | Controls whether high-risk administrative operations (cancellation, edit, 
    | state override) require approval from a Manager or Super Admin before execution.
    |
    */
    'approval_workflow' => [
        'enabled' => (bool) env('FULFILLMENT_APPROVAL_WORKFLOW_ENABLED', false),
    ],

    'operations_email' => env('FULFILLMENT_OPERATIONS_EMAIL', 'ops@hayest.com'),

    'commission_rates' => [
        'stripe'         => 0.029,
        'paypal'         => 0.039,
        'cashondelivery' => 0.00,
        'default'        => 0.03,
    ],
];

