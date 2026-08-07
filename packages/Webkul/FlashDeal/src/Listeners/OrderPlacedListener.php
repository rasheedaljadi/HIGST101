<?php

namespace Webkul\FlashDeal\Listeners;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Webkul\FlashDeal\Models\FlashDealProduct;
use Webkul\Sales\Contracts\Order;

class OrderPlacedListener
{
    /**
     * Handle the event when an order is placed.
     */
    public function handle(Order $order): void
    {
        try {
            $now = Carbon::now();

            foreach ($order->items as $item) {
                $productIds = [$item->product_id];

                if (! empty($item->product->parent_id)) {
                    $productIds[] = $item->product->parent_id;
                }

                // Find matching active flash deal product for this order item or its parent product
                $flashDealProduct = FlashDealProduct::whereIn('product_id', $productIds)
                    ->whereHas('deal', function ($query) use ($now) {
                        $query->where('status', 1)
                            ->where('starts_at', '<=', $now)
                            ->where('ends_at', '>=', $now);
                    })
                    ->first();

                if ($flashDealProduct) {
                    $qtyOrdered = (int) $item->qty_ordered;

                    $flashDealProduct->increment('sold_qty', $qtyOrdered);

                    Log::info("FlashDeal: Incremented sold_qty for deal product #{$flashDealProduct->id} (ordered item #{$item->product_id}) by {$qtyOrdered}");
                }
            }
        } catch (\Throwable $e) {
            Log::error('FlashDeal OrderPlacedListener failed: '.$e->getMessage(), [
                'order_id' => $order->id ?? null,
                'exception' => $e,
            ]);
        }
    }
}
