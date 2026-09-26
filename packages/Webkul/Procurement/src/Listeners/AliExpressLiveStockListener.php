<?php

namespace Webkul\Procurement\Listeners;

use App\Services\AliExpress\AliExpressLiveStockValidator;
use Illuminate\Support\Facades\Log;
use Throwable;
use Webkul\Checkout\Contracts\CartItem;
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
     * @throws InsufficientProductInventoryException
     */
    public function handleCartAddBefore(int $productId): void
    {
        $requestData = request()->all();

        $existingQty = 0;
        if ($cart = Cart::getCart()) {
            $cartItem = Cart::getItemByProduct(['additional' => $requestData]);
            if ($cartItem) {
                $existingQty = (int) $cartItem->quantity;
            }
        }

        $requestData['quantity'] = $existingQty + max(1, (int) ($requestData['quantity'] ?? 1));

        $this->validator->validateLiveStock($productId, $requestData);
    }

    /**
     * Handle pre-cart item update live stock check.
     *
     * @param  CartItem  $cartItem
     *
     * @throws InsufficientProductInventoryException
     */
    public function handleCartUpdateBefore($cartItem): void
    {
        $childProductId = $cartItem->child?->product_id ?? ($cartItem->additional['selected_configurable_option'] ?? $cartItem->product_id);
        $cartData = [
            'selected_configurable_option' => $childProductId,
            'quantity' => (int) $cartItem->quantity,
        ];

        $this->validator->validateLiveStock($cartItem->product_id, $cartData);
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
