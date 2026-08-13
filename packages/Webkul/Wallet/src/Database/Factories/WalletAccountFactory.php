<?php

namespace Webkul\Wallet\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\DB;
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
            'customer_id' => function () {
                return DB::table('customers')->insertGetId([
                    'first_name' => 'User',
                    'last_name' => 'Test',
                    'email' => 'user_'.uniqid().'@example.com',
                ]);
            },
            'total_balance' => 0,
            'available_balance' => 0,
            'held_balance' => 0,
            'cash_balance' => 0,
            'promo_balance' => 0,
            'unclassified_balance' => 0,
            'promo_debt' => 0,
            'backfill_status' => 'verified',
            'currency_code' => 'SAR',
            'status' => 'active',
        ];
    }
}
