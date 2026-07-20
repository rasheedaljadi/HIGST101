<?php

namespace Webkul\Fulfillment\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Webkul\Fulfillment\Contracts\PurchaseOrderItem as PurchaseOrderItemContract;

class PurchaseOrderItem extends Model implements PurchaseOrderItemContract
{
    protected $fillable = [
        'purchase_order_id',
        'order_item_id',
        'aliexpress_product_id',
        'sku_id',
        'qty',
        'supplier_unit_cost',
    ];

    /**
     * Get the purchase order that owns the item.
     */
    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrderProxy::modelClass(), 'purchase_order_id');
    }

    /**
     * Get the Bagisto order item.
     */
    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(\Webkul\Sales\Models\OrderItemProxy::modelClass(), 'order_item_id');
    }
}
