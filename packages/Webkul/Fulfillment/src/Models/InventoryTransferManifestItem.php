<?php

namespace Webkul\Fulfillment\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Webkul\Fulfillment\Enums\ReceiptItemCondition;
use Webkul\Product\Models\Product;
use Webkul\Sales\Models\Order;
use Webkul\Sales\Models\OrderItem;

class InventoryTransferManifestItem extends Model
{
    use HasFactory;

    protected $table = 'inventory_transfer_manifest_items';

    protected $guarded = ['id'];

    protected $casts = [
        'item_condition' => ReceiptItemCondition::class,
        'qty_shipped' => 'integer',
        'qty_received_good' => 'integer',
        'qty_received_damaged' => 'integer',
        'qty_received_missing' => 'integer',
    ];

    public function manifest(): BelongsTo
    {
        return $this->belongsTo(InventoryTransferManifest::class, 'inventory_transfer_manifest_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class, 'order_item_id');
    }

    public function orderAllocation(): BelongsTo
    {
        return $this->belongsTo(OrderAllocation::class, 'order_allocation_id');
    }
}
