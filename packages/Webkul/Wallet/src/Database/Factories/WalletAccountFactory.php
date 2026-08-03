<?php

namespace Webkul\Wallet\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Webkul\Customer\Models\Customer;
use Webkul\Wallet\Models\WalletAccount;

class WalletAccountFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = WalletAccount::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'customer_id' => Customer::factory(),
            'total_balance' => 0,
            'available_balance' => 0,
            'held_balance' => 0,
            'currency_code' => 'USD',
            'status' => 'active',
        ];
    }
}
