<?php

namespace Webkul\FlashDeal\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;
use Webkul\FlashDeal\Models\FlashDeal;

class FlashDealFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = FlashDeal::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'title' => $this->faker->words(3, true),
            'status' => 1,
            'starts_at' => Carbon::now()->subHour(),
            'ends_at' => Carbon::now()->addHours(24),
        ];
    }

    /**
     * Active state.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 1,
            'starts_at' => Carbon::now()->subHour(),
            'ends_at' => Carbon::now()->addHours(24),
        ]);
    }

    /**
     * Expired state.
     */
    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 1,
            'starts_at' => Carbon::now()->subDays(2),
            'ends_at' => Carbon::now()->subDay(),
        ]);
    }

    /**
     * Future state.
     */
    public function future(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 1,
            'starts_at' => Carbon::now()->addDay(),
            'ends_at' => Carbon::now()->addDays(2),
        ]);
    }

    /**
     * Inactive state.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 0,
        ]);
    }
}
