<?php

namespace Webkul\Fulfillment\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Webkul\Fulfillment\Contracts\AllocationLog as AllocationLogContract;

class AllocationLog extends Model implements AllocationLogContract
{
    protected $fillable = [
        'order_allocation_id',
        'action',
        'old_qty',
        'new_qty',
        'reason',
    ];

    /**
     * Get the order allocation associated with this log entry.
     */
    public function orderAllocation(): BelongsTo
    {
        return $this->belongsTo(OrderAllocationProxy::modelClass(), 'order_allocation_id');
    }
}
