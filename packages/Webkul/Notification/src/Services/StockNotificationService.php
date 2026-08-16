<?php

namespace Webkul\Notification\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Webkul\Product\Contracts\Product;
use Webkul\Product\Models\ProductProxy;
use Webkul\Product\Repositories\ProductRepository;

class StockNotificationService
{
    /**
     * Create a new service instance.
     */
    public function __construct(
        protected ProductRepository $productRepository
    ) {}

    /**
     * Check and process threshold notifications for a given product (and its variants if configurable).
     *
     * @param  Product|int  $productOrId
     */
    public function checkProductStock(mixed $productOrId): void
    {
        $product = is_numeric($productOrId)
            ? $this->productRepository->find($productOrId)
            : $productOrId;

        if (! $product) {
            return;
        }

        // If configurable product, inspect all its variant child products
        if ($product->type === 'configurable') {
            $variants = $product->variants;
            foreach ($variants as $variant) {
                $this->evaluateSingleProductStock($variant, $product);
            }

            return;
        }

        // Single simple or virtual product
        $this->evaluateSingleProductStock($product);
    }

    /**
     * Evaluate single product / variant quantity against thresholds.
     */
    protected function evaluateSingleProductStock(Product $product, ?Product $parentProduct = null): void
    {
        if ($product->manage_stock !== null && ! (bool) $product->manage_stock) {
            return;
        }

        $rawLow = core()->getConfigData('catalog.inventory.stock_options.low_stock_threshold');
        $lowThreshold = ($rawLow !== null && $rawLow !== '') ? (int) $rawLow : (int) config('catalog.inventory.stock_options.low_stock_threshold', 5);

        $rawOut = core()->getConfigData('catalog.inventory.stock_options.out_of_stock_threshold');
        $outThreshold = ($rawOut !== null && $rawOut !== '') ? (int) $rawOut : (int) config('catalog.inventory.stock_options.out_of_stock_threshold', 0);

        $qty = $this->getProductQuantity($product);
        $productName = $product->name ?? ($parentProduct ? $parentProduct->name : $product->sku);
        $sku = $product->sku;
        $targetEditId = $parentProduct ? $parentProduct->id : $product->id;

        if ($qty <= $outThreshold) {
            // Out of stock threshold reached
            $this->createAdminStockNotification(
                type: 'out_of_stock',
                productId: $product->id,
                targetEditId: $targetEditId,
                title: 'نفاد مخزون المنتج',
                message: "نفد مخزون المنتج {$productName} (رمز SKU: {$sku}) حيث أصبحت الكمية {$qty} قطعة (حد النفاد: {$outThreshold}).",
                eventKey: "product:{$product->id}:out_of_stock",
                qty: $qty
            );

            // Mark any previous unread low_stock notification as resolved/read
            DB::table('notifications')
                ->whereNull('customer_id')
                ->where('type', 'low_stock')
                ->where('entity_id', $product->id)
                ->where('read', 0)
                ->update(['read' => 1]);

        } elseif ($qty <= $lowThreshold) {
            // Low stock threshold reached
            $this->createAdminStockNotification(
                type: 'low_stock',
                productId: $product->id,
                targetEditId: $targetEditId,
                title: 'انخفاض مخزون المنتج',
                message: "انخفض مخزون المنتج {$productName} (رمز SKU: {$sku}) إلى {$qty} قطعة (حد الانخفاض: {$lowThreshold}).",
                eventKey: "product:{$product->id}:low_stock",
                qty: $qty
            );
        } else {
            // Stock is sufficient / replenished: automatically mark any old unread notifications for this product as read
            DB::table('notifications')
                ->whereNull('customer_id')
                ->whereIn('type', ['low_stock', 'out_of_stock'])
                ->where('entity_id', $product->id)
                ->where('read', 0)
                ->update(['read' => 1]);
        }
    }

    /**
     * Get remaining stock quantity for a product.
     */
    protected function getProductQuantity(Product $product): int
    {
        if ($product->inventories()->exists()) {
            $total = (int) $product->inventories()->sum('qty');
            $ordered = (int) $product->ordered_inventories()->sum('qty');

            return max(0, $total - $ordered);
        }

        return (int) $product->totalQuantity();
    }

    /**
     * Create or update Admin Stock Notification.
     */
    protected function createAdminStockNotification(
        string $type,
        int $productId,
        int $targetEditId,
        string $title,
        string $message,
        string $eventKey,
        int $qty
    ): void {
        try {
            // Check if an unread notification with same type and product already exists
            $existing = DB::table('notifications')
                ->whereNull('customer_id')
                ->where('type', $type)
                ->where('entity_id', $productId)
                ->where('read', 0)
                ->first();

            if ($existing) {
                // Update message and timestamp with latest qty
                DB::table('notifications')
                    ->where('id', $existing->id)
                    ->update([
                        'title' => $title,
                        'message' => $message,
                        'updated_at' => now(),
                    ]);

                return;
            }

            DB::table('notifications')->insert([
                'type' => $type,
                'customer_id' => null,
                'title' => $title,
                'message' => $message,
                'action_url' => "/admin/catalog/products/edit/{$targetEditId}",
                'event_key' => $eventKey,
                'entity_type' => ProductProxy::modelClass(),
                'entity_id' => $productId,
                'order_id' => null,
                'read' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } catch (\Throwable $e) {
            Log::error("Failed to create stock notification for product {$productId}: ".$e->getMessage());
        }
    }

    /**
     * Check products from an Order instance.
     */
    public function checkOrderProducts(mixed $order): void
    {
        if (! $order || ! isset($order->all_items)) {
            return;
        }

        $checkedProductIds = [];

        foreach ($order->all_items as $item) {
            $productId = $item->product_id;
            if ($productId && ! in_array($productId, $checkedProductIds)) {
                $checkedProductIds[] = $productId;
                $this->checkProductStock($productId);
            }
        }
    }

    /**
     * Scan all catalog products and verify stock thresholds.
     */
    public function checkAllProducts(): array
    {
        $products = $this->productRepository->all();
        $checked = 0;

        foreach ($products as $product) {
            $checked++;
            $this->checkProductStock($product);
        }

        return [
            'checked_products' => $checked,
        ];
    }
}
