<?php

namespace Webkul\Procurement\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Webkul\Procurement\Contracts\ProcurementBatch as ProcurementBatchContract;
use Webkul\User\Models\AdminProxy;

class ProcurementBatch extends Model implements ProcurementBatchContract
{
    public const STATE_DRAFT = 'draft';

    public const STATE_COLLECTING = 'collecting';

    public const STATE_READY_FOR_REVIEW = 'ready_for_review';

    public const STATE_APPROVED = 'approved';

    public const STATE_SPLITTING_BY_STORE = 'splitting_by_store';

    public const STATE_SUBMITTED_TO_PROVIDER = 'submitted_to_provider';

    public const STATE_PARTIALLY_SUBMITTED = 'partially_submitted';

    public const STATE_AWAITING_MANUAL_PAYMENT = 'awaiting_manual_payment';

    public const STATE_PAYMENT_DECLARED = 'payment_declared';

    public const STATE_COST_VARIANCE_REVIEW = 'cost_variance_review';

    public const STATE_PAYMENT_CONFIRMED_EXTERNALLY = 'payment_confirmed_externally';

    public const STATE_SUPPLIER_PROCESSING = 'supplier_processing';

    public const STATE_COMPLETED = 'completed';

    public const STATE_PARTIALLY_CANCELLED = 'partially_cancelled';

    public const STATE_CANCELLED = 'cancelled';

    public const STATE_EXCEPTION = 'exception';

    protected $table = 'procurement_batches';

    protected $fillable = [
        'batch_number',
        'provider',
        'provider_account_id',
        'currency_code',
        'destination_signature',
        'state',
        'created_by',
        'reviewed_by',
        'approved_by',
        'approved_at',
        'rejection_reason',
        'source_snapshot_at',
        'expected_total_cost',
        'actual_total_cost',
        'cost_variance_amount',
        'lock_version',
    ];

    protected $casts = [
        'expected_total_cost' => 'decimal:4',
        'actual_total_cost' => 'decimal:4',
        'cost_variance_amount' => 'decimal:4',
        'approved_at' => 'datetime',
        'source_snapshot_at' => 'datetime',
        'lock_version' => 'integer',
    ];

    public function batchDemands(): HasMany
    {
        return $this->hasMany(ProcurementBatchDemand::class, 'batch_id');
    }

    public function demands(): BelongsToMany
    {
        return $this->belongsToMany(
            ProcurementDemandProxy::modelClass(),
            'procurement_batch_demands',
            'batch_id',
            'procurement_demand_id'
        )->withPivot(['qty_batched', 'qty_released', 'state'])->withTimestamps();
    }

    public function supplierOrders(): HasMany
    {
        return $this->hasMany(SupplierPurchaseOrderProxy::modelClass(), 'batch_id');
    }

    public function costSnapshots(): MorphMany
    {
        return $this->morphMany(ProcurementCostSnapshot::class, 'snapshotable');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(AdminProxy::modelClass(), 'created_by');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(AdminProxy::modelClass(), 'reviewed_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(AdminProxy::modelClass(), 'approved_by');
    }
}
