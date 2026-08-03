<?php

namespace Webkul\Wallet\Repositories;

use Webkul\Core\Eloquent\Repository;
use Webkul\Wallet\Models\WalletAccount;

class WalletAccountRepository extends Repository
{
    /**
     * Specify model class name.
     */
    public function model(): string
    {
        return 'Webkul\Wallet\Contracts\WalletAccount';
    }

    /**
     * Get or create wallet for a customer.
     */
    public function getOrCreateForCustomer(int $customerId, string $currencyCode): WalletAccount
    {
        return $this->firstOrCreate(
            ['customer_id' => $customerId],
            [
                'currency_code' => $currencyCode,
                'total_balance' => 0,
                'available_balance' => 0,
                'held_balance' => 0,
                'status' => 'active',
            ]
        );
    }
}
