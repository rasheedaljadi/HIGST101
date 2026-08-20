<?php

namespace Webkul\Procurement\Models;

use DomainException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ProcurementCostSnapshot extends Model
{
    public const TYPE_EXPECTED_AT_BATCHING = 'expected_at_batching';

    public const TYPE_EXPECTED_BEFORE_SUBMIT = 'expected_before_submit';

    public const TYPE_ACTUAL_AFTER_MANUAL_PAYMENT = 'actual_after_manual_payment';

    public const TYPE_ACTUAL_REFUND = 'actual_refund';

    public $timestamps = false;

    protected $table = 'procurement_cost_snapshots';

    protected $fillable = [
        'snapshotable_type',
        'snapshotable_id',
        'snapshot_type',
        'items_subtotal',
        'shipping_amount',
        'discount_amount',
        'tax_fee_amount',
        'total_amount',
        'currency_code',
        'exchange_rate',
        'allocation_basis',
        'breakdown',
        'external_reference',
        'actor_id',
        'actor_type',
        'correlation_id',
        'snapshot_hash',
        'created_at',
    ];

    protected $casts = [
        'items_subtotal' => 'decimal:4',
        'shipping_amount' => 'decimal:4',
        'discount_amount' => 'decimal:4',
        'tax_fee_amount' => 'decimal:4',
        'total_amount' => 'decimal:4',
        'exchange_rate' => 'decimal:6',
        'breakdown' => 'array',
        'created_at' => 'datetime',
    ];

    /**
     * Boot model and enforce immutability.
     */
    protected static function boot(): void
    {
        parent::boot();

        static::updating(function () {
            throw new DomainException('Procurement cost snapshots are immutable and cannot be updated.');
        });

        static::deleting(function () {
            throw new DomainException('Procurement cost snapshots are immutable and cannot be deleted.');
        });
    }

    public function snapshotable(): MorphTo
    {
        return $this->morphTo();
    }
}
