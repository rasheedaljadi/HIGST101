<?php

namespace Webkul\Procurement\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Webkul\Procurement\Contracts\ProcurementDemandAllocation as ProcurementDemandAllocationContract;

class ProcurementDemandAllocation extends Model implements ProcurementDemandAllocationContract
{
    public const STATE_ALLOCATED = 'allocated';

    public const STATE_ORDERED = 'ordered';

    public const STATE_RECEIVED = 'received';

    public const STATE_CANCELLED = 'cancelled';

    protected $table = 'procurement_demand_allocations';

    protected $fillable = [
        'procurement_demand_id',
        'supplier_purchase_order_item_id',
        'qty_allocated',
        'qty_ordered',
        'qty_received_good',
        'qty_cancelled',
        'state',
    ];

    protected $casts = [
        'qty_allocated' => 'integer',
        'qty_ordered' => 'integer',
        'qty_received_good' => 'integer',
        'qty_cancelled' => 'integer',
    ];

    public function demand(): BelongsTo
    {
        return $this->belongsTo(ProcurementDemandProxy::modelClass(), 'procurement_demand_id');
    }

    public function purchaseOrderItem(): BelongsTo
    {
        return $this->belongsTo(SupplierPurchaseOrderItemProxy::modelClass(), 'supplier_purchase_order_item_id');
    }
}
