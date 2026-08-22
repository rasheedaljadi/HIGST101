<?php

namespace Webkul\Procurement\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Webkul\Procurement\Contracts\SupplierPurchaseOrder as SupplierPurchaseOrderContract;

class SupplierPurchaseOrder extends Model implements SupplierPurchaseOrderContract
{
    public const STATE_DRAFT = 'draft';

    public const STATE_READY_TO_SUBMIT = 'ready_to_submit';

    public const STATE_SUBMITTED = 'submitted';

    public const STATE_AWAITING_MANUAL_PAYMENT = 'awaiting_manual_payment';

    public const STATE_PAYMENT_DECLARED = 'payment_declared';

    public const STATE_COST_VARIANCE_REVIEW = 'cost_variance_review';

    public const STATE_SUPPLIER_PROCESSING = 'supplier_processing';

    public const STATE_SUPPLIER_SHIPPED = 'supplier_shipped';

    public const STATE_CLOSED = 'closed';

    public const STATE_PARTIALLY_CANCELLED = 'partially_cancelled';

    public const STATE_CANCELLED = 'cancelled';

    public const STATE_PAYMENT_FAILED = 'payment_failed';

    public const STATE_SUPPLIER_EXCEPTION = 'supplier_exception';

    public const STATE_REFUNDED = 'refunded';

    protected $table = 'supplier_purchase_orders';

    protected $fillable = [
        'batch_id',
        'purchase_order_number',
        'provider',
        'provider_account_id',
        'supplier_store_id',
        'supplier_store_name',
        'currency_code',
        'destination_signature',
        'state',
        'expected_items_total',
        'expected_shipping_total',
        'expected_discount_total',
        'expected_total',
        'actual_items_total',
        'actual_shipping_total',
        'actual_discount_total',
        'actual_total',
        'cost_variance_amount',
        'payment_state',
        'external_sync_state',
        'active_fingerprint',
        'lock_version',
    ];

    protected $casts = [
        'expected_items_total' => 'decimal:4',
        'expected_shipping_total' => 'decimal:4',
        'expected_discount_total' => 'decimal:4',
        'expected_total' => 'decimal:4',
        'actual_items_total' => 'decimal:4',
        'actual_shipping_total' => 'decimal:4',
        'actual_discount_total' => 'decimal:4',
        'actual_total' => 'decimal:4',
        'cost_variance_amount' => 'decimal:4',
        'lock_version' => 'integer',
    ];

    public function batch(): BelongsTo
    {
        return $this->belongsTo(ProcurementBatchProxy::modelClass(), 'batch_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(SupplierPurchaseOrderItemProxy::modelClass(), 'supplier_purchase_order_id');
    }

    public function platformOrders(): HasMany
    {
        return $this->hasMany(ExternalPlatformOrder::class, 'supplier_purchase_order_id');
    }

    public function costSnapshots(): MorphMany
    {
        return $this->morphMany(ProcurementCostSnapshot::class, 'snapshotable');
    }

    public function manualPaymentConfirmations(): HasMany
    {
        return $this->hasMany(ProcurementManualPaymentConfirmation::class, 'supplier_purchase_order_id');
    }

    public function allocations(): HasManyThrough
    {
        return $this->hasManyThrough(
            ProcurementDemandAllocationProxy::modelClass(),
            SupplierPurchaseOrderItemProxy::modelClass(),
            'supplier_purchase_order_id',
            'supplier_purchase_order_item_id'
        );
    }
}
