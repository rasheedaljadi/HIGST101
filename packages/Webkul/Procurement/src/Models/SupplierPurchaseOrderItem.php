<?php

namespace Webkul\Procurement\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Webkul\Procurement\Contracts\SupplierPurchaseOrderItem as SupplierPurchaseOrderItemContract;
use Webkul\Product\Models\ProductProxy;

class SupplierPurchaseOrderItem extends Model implements SupplierPurchaseOrderItemContract
{
    protected $table = 'supplier_purchase_order_items';

    protected $fillable = [
        'supplier_purchase_order_id',
        'supplier_product_id',
        'supplier_sku_id',
        'product_id',
        'variant_product_id',
        'qty_ordered',
        'qty_confirmed',
        'qty_received_good',
        'qty_damaged',
        'qty_missing',
        'expected_unit_cost',
        'actual_unit_cost',
        'snapshots',
    ];

    protected $casts = [
        'qty_ordered' => 'integer',
        'qty_confirmed' => 'integer',
        'qty_received_good' => 'integer',
        'qty_damaged' => 'integer',
        'qty_missing' => 'integer',
        'expected_unit_cost' => 'decimal:4',
        'actual_unit_cost' => 'decimal:4',
        'snapshots' => 'array',
    ];

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(SupplierPurchaseOrderProxy::modelClass(), 'supplier_purchase_order_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(ProductProxy::modelClass(), 'product_id');
    }

    public function variantProduct(): BelongsTo
    {
        return $this->belongsTo(ProductProxy::modelClass(), 'variant_product_id');
    }

    public function allocations(): HasMany
    {
        return $this->hasMany(ProcurementDemandAllocationProxy::modelClass(), 'supplier_purchase_order_item_id');
    }

    public function platformOrderItems(): HasMany
    {
        return $this->hasMany(ExternalPlatformOrderItem::class, 'supplier_purchase_order_item_id');
    }
}
