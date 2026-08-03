<?php

namespace Webkul\Wallet\Listeners;

use Illuminate\Support\Facades\Log;
use Webkul\Wallet\Repositories\WalletAccountRepository;

class CustomerRegistrationListener
{
    /**
     * Create a new listener instance.
     */
    public function __construct(
        protected WalletAccountRepository $walletAccountRepository
    ) {}

    /**
     * Handle customer registration event (customer.registration.after & customer.create.after).
     *
     * @param  mixed  $customer
     */
    public function handle($customer): void
    {
        if (! $customer || ! isset($customer->id)) {
            return;
        }

        try {
            $this->walletAccountRepository->firstOrCreate(
                ['customer_id' => $customer->id],
                [
                    'currency_code' => function_exists('core') ? (core()->getBaseCurrencyCode() ?? 'USD') : 'USD',
                    'total_balance' => 0,
                    'available_balance' => 0,
                    'held_balance' => 0,
                    'status' => 'active',
                ]
            );
        } catch (\Exception $e) {
            Log::error('Wallet CustomerRegistrationListener Error for Customer #'.$customer->id.': '.$e->getMessage());
        }
    }
}
