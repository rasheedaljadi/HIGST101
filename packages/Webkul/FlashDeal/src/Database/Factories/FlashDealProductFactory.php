<?php

namespace Webkul\FlashDeal\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Webkul\FlashDeal\Models\FlashDeal;
use Webkul\FlashDeal\Models\FlashDealProduct;
use Webkul\Product\Models\Product;

class FlashDealProductFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = FlashDealProduct::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'flash_deal_id' => FlashDeal::factory(),
            'product_id' => Product::factory(),
            'flash_price' => 19.99,
            'allocation_qty' => 50,
            'sold_qty' => 0,
        ];
    }
}
