<?php

namespace Webkul\Procurement\Listeners;

use App\Services\AliExpress\AliExpressLiveStockValidator;
use Illuminate\Support\Facades\Log;
use Throwable;
use Webkul\Checkout\Facades\Cart;
use Webkul\Product\Exceptions\InsufficientProductInventoryException;

class AliExpressLiveStockListener
{
    public function __construct(
        protected AliExpressLiveStockValidator $validator,
    ) {}

    /**
     * Handle pre-cart addition live stock check.
     *
     *
     * @throws InsufficientProductInventoryException
     */
    public function handleCartAddBefore(int $productId): void
    {
        $requestData = request()->all();

        $this->validator->validateLiveStock($productId, $requestData);
    }

    /**
     * Handle pre-checkout order placement live stock check for all cart items.
     *
     *
     * @throws InsufficientProductInventoryException
     */
    public function handleOrderSaveBefore(array $orderData = []): void
    {
        try {
            $cart = Cart::getCart();
            if (! $cart || $cart->items->isEmpty()) {
                return;
            }

            foreach ($cart->items as $item) {
                $childProductId = $item->child?->product_id ?? $item->product_id;
                $cartData = [
                    'selected_configurable_option' => $childProductId,
                    'quantity' => (int) $item->quantity,
                ];

                $this->validator->validateLiveStock($item->product_id, $cartData);
            }
        } catch (InsufficientProductInventoryException $e) {
            throw $e;
        } catch (Throwable $e) {
            Log::channel('aliexpress')->warning('Live stock check on order save exception: '.$e->getMessage());
        }
    }
}
