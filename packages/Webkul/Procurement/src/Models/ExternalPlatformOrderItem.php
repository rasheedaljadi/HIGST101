<?php

namespace Webkul\Procurement\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExternalPlatformOrderItem extends Model
{
    protected $table = 'external_platform_order_items';

    protected $fillable = [
        'external_platform_order_id',
        'supplier_purchase_order_item_id',
        'external_sku_id',
        'quantity',
        'actual_item_amount',
        'actual_shipping_amount',
        'actual_tax_amount',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'actual_item_amount' => 'decimal:4',
        'actual_shipping_amount' => 'decimal:4',
        'actual_tax_amount' => 'decimal:4',
    ];

    public function platformOrder(): BelongsTo
    {
        return $this->belongsTo(ExternalPlatformOrder::class, 'external_platform_order_id');
    }

    public function purchaseOrderItem(): BelongsTo
    {
        return $this->belongsTo(SupplierPurchaseOrderItemProxy::modelClass(), 'supplier_purchase_order_item_id');
    }
}
