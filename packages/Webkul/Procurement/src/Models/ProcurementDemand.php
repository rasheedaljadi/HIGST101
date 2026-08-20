<?php

namespace Webkul\Procurement\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Webkul\Procurement\Contracts\ProcurementDemand as ProcurementDemandContract;
use Webkul\Product\Models\ProductProxy;
use Webkul\Sales\Models\OrderItemProxy;
use Webkul\Sales\Models\OrderProxy;

class ProcurementDemand extends Model implements ProcurementDemandContract
{
    public const STATE_ELIGIBLE = 'eligible';

    public const STATE_LOCALLY_COVERED = 'locally_covered';

    public const STATE_OPEN_FOR_BATCHING = 'open_for_batching';

    public const STATE_BATCHED = 'batched';

    public const STATE_ORDERED = 'ordered';

    public const STATE_PARTIALLY_RECEIVED = 'partially_received';

    public const STATE_FULFILLED = 'fulfilled';

    public const STATE_CANCELLED = 'cancelled';

    public const STATE_INTERNAL_STOCK_EXCEPTION = 'internal_stock_exception';

    public const STATE_SUPPLIER_EXCEPTION = 'supplier_exception';

    protected $table = 'procurement_demands';

    protected $fillable = [
        'order_id',
        'order_item_id',
        'product_id',
        'variant_product_id',
        'provider',
        'provider_account_id',
        'supplier_store_id',
        'supplier_store_name',
        'supplier_product_id',
        'supplier_sku_id',
        'destination_source_code',
        'order_currency_code',
        'supplier_currency_code',
        'qty_requested',
        'qty_covered_by_local',
        'qty_required_external',
        'qty_batched',
        'qty_ordered_external',
        'qty_received_good',
        'qty_cancelled',
        'state',
        'source_snapshot',
        'eligibility_snapshot',
        'active_fingerprint',
        'lock_version',
        'cancelled_at',
        'cancelled_by',
        'cancellation_reason',
    ];

    protected $casts = [
        'source_snapshot' => 'array',
        'eligibility_snapshot' => 'array',
        'qty_requested' => 'integer',
        'qty_covered_by_local' => 'integer',
        'qty_required_external' => 'integer',
        'qty_batched' => 'integer',
        'qty_ordered_external' => 'integer',
        'qty_received_good' => 'integer',
        'qty_cancelled' => 'integer',
        'lock_version' => 'integer',
        'cancelled_at' => 'datetime',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(OrderProxy::modelClass(), 'order_id');
    }

    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(OrderItemProxy::modelClass(), 'order_item_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(ProductProxy::modelClass(), 'product_id');
    }

    public function variantProduct(): BelongsTo
    {
        return $this->belongsTo(ProductProxy::modelClass(), 'variant_product_id');
    }

    public function batchDemands(): HasMany
    {
        return $this->hasMany(ProcurementBatchDemand::class, 'procurement_demand_id');
    }

    public function batches(): BelongsToMany
    {
        return $this->belongsToMany(
            ProcurementBatchProxy::modelClass(),
            'procurement_batch_demands',
            'procurement_demand_id',
            'batch_id'
        )->withPivot(['qty_batched', 'qty_released', 'state'])->withTimestamps();
    }

    public function allocations(): HasMany
    {
        return $this->hasMany(ProcurementDemandAllocationProxy::modelClass(), 'procurement_demand_id');
    }

    public function getRemainingUnbatchedQtyAttribute(): int
    {
        return max(0, $this->qty_required_external - $this->qty_batched - $this->qty_cancelled);
    }

    public function isOpenForBatching(): bool
    {
        return $this->state === self::STATE_OPEN_FOR_BATCHING && $this->remaining_unbatched_qty > 0;
    }
}
