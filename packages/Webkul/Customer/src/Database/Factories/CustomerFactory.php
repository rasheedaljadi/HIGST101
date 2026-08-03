<?php

namespace Webkul\Customer\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Hash;
use Webkul\Customer\Models\Customer;

class CustomerFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Customer::class;

    /**
     * States.
     *
     * @var array
     */
    protected $states = [
        'male',
        'female',
    ];

    /**
     * Define the model's default state.
     *
     * @throws \Exception
     */
    public function definition(): array
    {
        return [
            'first_name' => 'TestCustomer',
            'last_name' => 'User',
            'gender' => Arr::random(['male', 'female', 'other']),
            'email' => 'customer_'.rand(1000, 999999).'@example.com',
            'status' => 1,
            'password' => Hash::make('password123'),
            'customer_group_id' => 2,
            'channel_id' => 1,
            'is_verified' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    /**
     * Male.
     */
    public function male(): CustomerFactory
    {
        return $this->state(function (array $attributes) {
            return [
                'gender' => 'Male',
            ];
        });
    }

    /**
     * Female.
     */
    public function female(): CustomerFactory
    {
        return $this->state(function (array $attributes) {
            return [
                'gender' => 'Female',
            ];
        });
    }
}
