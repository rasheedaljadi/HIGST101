<?php

namespace Webkul\Wallet\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Webkul\Wallet\Listeners\ApplyWalletCashbackListener;
use Webkul\Wallet\Listeners\CreateWalletOnCustomerRegistered;
use Webkul\Wallet\Listeners\CreditWalletOnOrderCanceled;
use Webkul\Wallet\Listeners\CreditWalletOnRefundCreated;
use Webkul\Wallet\Listeners\DebitWalletOnOrderCreated;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event handler mappings for the application.
     *
     * @var array
     */
    protected $listen = [
        /**
         * Customer registration — create wallet automatically (D-001: Proactive).
         */
        'customer.create.after' => [
            [CreateWalletOnCustomerRegistered::class, 'handle'],
        ],

        'customer.registration.after' => [
            [CreateWalletOnCustomerRegistered::class, 'handle'],
        ],

        /**
         * Order placed with wallet — debit wallet balance.
         */
        'checkout.order.save.after' => [
            [DebitWalletOnOrderCreated::class, 'handle'],
        ],

        /**
         * Order canceled — credit wallet if it was paid via wallet.
         */
        'sales.order.cancel.after' => [
            [CreditWalletOnOrderCanceled::class, 'handle'],
        ],

        /**
         * Refund created — credit wallet automatically.
         */
        'sales.refund.save.after' => [
            [CreditWalletOnRefundCreated::class, 'handle'],
        ],

        /**
         * Invoice saved — apply promotional cashback for wallet purchases.
         */
        'sales.invoice.save.after' => [
            [ApplyWalletCashbackListener::class, 'handle'],
        ],
    ];
}
