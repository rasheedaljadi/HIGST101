<?php

namespace Webkul\Wallet\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Webkul\Wallet\Listeners\ApplyWalletCashbackListener;
use Webkul\Wallet\Listeners\CreateWalletOnCustomerRegistered;
use Webkul\Wallet\Listeners\CreditWalletOnOrderCanceled;
use Webkul\Wallet\Listeners\CreditWalletOnRefundCreated;
use Webkul\Wallet\Listeners\DebitWalletOnOrderCreated;
use Webkul\Wallet\Listeners\PromotionCustomerRegistrationListener;
use Webkul\Wallet\Listeners\PromotionInvoicePaidListener;
use Webkul\Wallet\Listeners\PromotionRefundListener;
use Webkul\Wallet\Listeners\PromotionTopUpApprovedListener;

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
         * Invoice saved — apply legacy cashback for wallet purchases.
         */
        'sales.invoice.save.after' => [
            [ApplyWalletCashbackListener::class, 'handle'],
            [PromotionInvoicePaidListener::class, 'handle'],
        ],

        /**
         * Promotional event bindings
         */
        'customer.registration.after' => [
            [CreateWalletOnCustomerRegistered::class, 'handle'],
            [PromotionCustomerRegistrationListener::class, 'handle'],
        ],

        'customer.create.after' => [
            [CreateWalletOnCustomerRegistered::class, 'handle'],
            [PromotionCustomerRegistrationListener::class, 'handle'],
        ],

        'wallet.topup.after' => [
            [PromotionTopUpApprovedListener::class, 'handle'],
        ],

        'sales.refund.save.after' => [
            [CreditWalletOnRefundCreated::class, 'handle'],
            [PromotionRefundListener::class, 'handle'],
        ],
    ];
}
