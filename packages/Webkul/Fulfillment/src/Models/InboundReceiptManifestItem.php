<?php

namespace Webkul\Fulfillment\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Webkul\Fulfillment\Enums\ReceiptItemCondition;
use Webkul\Product\Models\Product;
use Webkul\Sales\Models\Order;
use Webkul\Sales\Models\OrderItem;

class InboundReceiptManifestItem extends Model
{
    use HasFactory;

    protected $table = 'inbound_receipt_manifest_items';

    protected $guarded = ['id'];

    protected $casts = [
        'condition' => ReceiptItemCondition::class,
        'qty_good' => 'integer',
        'qty_damaged' => 'integer',
        'qty_missing' => 'integer',
    ];

    public function receipt(): BelongsTo
    {
        return $this->belongsTo(InboundReceiptManifest::class, 'inbound_receipt_manifest_id');
    }

    public function transferItem(): BelongsTo
    {
        return $this->belongsTo(InventoryTransferManifestItem::class, 'inventory_transfer_manifest_item_id');
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
