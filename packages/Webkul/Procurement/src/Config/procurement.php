<?php

return [
    /**
     * Procurement V2 Feature Flag.
     * When true, incoming orders are processed via ProcurementDemandService V2.
     * When false, system remains in legacy V1 mode.
     */
    'v2_enabled' => env('PROCUREMENT_V2_ENABLED', true),

    /**
     * Live external order creation flag.
     * When true, allows sending actual live order creation API calls to external providers.
     */
    'v2_live_order_creation_enabled' => env('PROCUREMENT_V2_LIVE_ORDER_CREATION_ENABLED', true),

    /**
     * Default supplier and order currency.
     * In V2, strictly USD. Non-USD orders are routed to manual review.
     */
    'currency' => 'USD',

    /**
     * Sourcing destination source code.
     */
    'destination_source_code' => 'hayest_dropship_ye',

    /**
     * Saudi transit warehouse source code.
     */
    'transit_sa_source_code' => 'hayest_dropship_sa',

    /**
     * Internal local warehouse source code (strictly for internal products).
     */
    'internal_source_code' => 'hayest_internal_ye',

    /**
     * Quarantine source codes.
     */
    'quarantine_sa_source_code' => 'hayest_quarantine_sa',
    'quarantine_ye_source_code' => 'hayest_quarantine_ye',

    /**
     * Polling configuration for external supplier platforms (e.g. AliExpress).
     */
    'polling' => [
        'enabled' => env('PROCUREMENT_POLLING_ENABLED', true),
        'interval_minutes' => 2,
        'batch_size' => 50,
        'max_retries' => 5,
    ],

    /**
     * Automatic batching limits.
     */
    'batching' => [
        'max_items_per_batch' => 500,
        'max_demands_per_batch' => 100,
        'default_currency' => 'USD',
    ],

    /**
     * AliExpress provider capability limits.
     */
    'provider_limits' => [
        'aliexpress' => [
            'max_items_per_order' => 50,
            'allowed_currencies' => ['USD'],
        ],
    ],

    /**
     * Default initial payment timeout in seconds (86400 = 24 hours).
     * Used when live AliExpress order is first placed before live polling updates the exact over_time_left.
     */
    'default_payment_timeout_seconds' => env('PROCUREMENT_DEFAULT_PAYMENT_TIMEOUT_SECONDS', 86400),

    /**
     * Maximum allowed cost variance percentage between expected and live AliExpress price.
     * If exceeded, the SPO is moved to cost_variance_review state and submission is halted.
     */
    'max_cost_variance_percent' => env('PROCUREMENT_MAX_COST_VARIANCE_PERCENT', 15.0),
];
