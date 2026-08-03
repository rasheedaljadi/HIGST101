<?php

namespace Webkul\Wallet\Listeners;

use Illuminate\Support\Facades\Log;
use Webkul\Customer\Contracts\Customer;
use Webkul\Wallet\Repositories\WalletAccountRepository;

class CreateWalletOnCustomerRegistered
{
    /**
     * Create a new listener instance.
     */
    public function __construct(
        protected WalletAccountRepository $walletAccountRepository
    ) {}

    /**
     * Handle the event: customer.create.after
     *
     * D-001 Decision: Proactive — wallet created immediately on customer registration.
     *
     * @param  Customer  $customer
     */
    public function handle($customer): void
    {
        try {
            $this->walletAccountRepository->firstOrCreate(
                ['customer_id' => $customer->id],
                [
                    'currency_code' => core()->getBaseCurrencyCode(),
                    'total_balance' => 0,
                    'available_balance' => 0,
                    'held_balance' => 0,
                    'status' => 'active',
                ]
            );
        } catch (\Exception $e) {
            Log::error('Wallet: Failed to create wallet for customer #'.$customer->id.': '.$e->getMessage());
        }
    }
}
