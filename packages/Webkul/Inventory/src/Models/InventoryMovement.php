<?php

namespace Webkul\Inventory\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Webkul\Inventory\Contracts\InventoryMovement as InventoryMovementContract;
use Webkul\Product\Models\ProductProxy;
use Webkul\Sales\Models\OrderProxy;
use Webkul\Sales\Models\ShipmentProxy;
use Webkul\User\Models\AdminProxy;

class InventoryMovement extends Model implements InventoryMovementContract
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'inventory_movements';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'movement_type',
        'product_id',
        'sku',
        'quantity',
        'source_inventory_source_id',
        'target_inventory_source_id',
        'order_id',
        'order_item_id',
        'purchase_order_id',
        'purchase_order_item_id',
        'shipment_id',
        'delivery_assignment_id',
        'actor_id',
        'actor_type',
        'reference_event',
        'job_class',
        'idempotency_key',
        'notes',
    ];

    /**
     * Get the associated product.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(ProductProxy::modelClass(), 'product_id');
    }

    /**
     * Get the source inventory source.
     */
    public function sourceInventorySource(): BelongsTo
    {
        return $this->belongsTo(InventorySourceProxy::modelClass(), 'source_inventory_source_id');
    }

    /**
     * Get the target inventory source.
     */
    public function targetInventorySource(): BelongsTo
    {
        return $this->belongsTo(InventorySourceProxy::modelClass(), 'target_inventory_source_id');
    }

    /**
     * Get the associated sales order.
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(OrderProxy::modelClass(), 'order_id');
    }

    /**
     * Get the associated shipment.
     */
    public function shipment(): BelongsTo
    {
        return $this->belongsTo(ShipmentProxy::modelClass(), 'shipment_id');
    }

    /**
     * Get the acting admin user.
     */
    public function actor(): BelongsTo
    {
        return $this->belongsTo(AdminProxy::modelClass(), 'actor_id');
    }
}
