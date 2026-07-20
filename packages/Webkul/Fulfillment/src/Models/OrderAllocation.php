<?php

namespace Webkul\Fulfillment\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Webkul\Fulfillment\Contracts\OrderAllocation as OrderAllocationContract;
use Webkul\Fulfillment\Exceptions\InvalidStateTransitionException;
use Webkul\Fulfillment\Traits\OptimisticLocking;

class OrderAllocation extends Model implements OrderAllocationContract
{
    use OptimisticLocking;

    protected $fillable = [
        'order_id',
        'order_item_id',
        'product_id',
        'variant_product_id',
        'allocation_type',
        'source_code',
        'supplier_signature',
        'reserved_qty',
        'fulfilled_qty',
        'canceled_qty',
        'state',
        'supplier_snapshot',
        'version',
    ];

    protected $casts = [
        'supplier_snapshot' => 'array',
    ];

    /**
     * Get the associated base product.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(\Webkul\Product\Models\ProductProxy::modelClass(), 'product_id');
    }

    /**
     * Get the associated variant product.
     */
    public function variantProduct(): BelongsTo
    {
        return $this->belongsTo(\Webkul\Product\Models\ProductProxy::modelClass(), 'variant_product_id');
    }

    /**
     * Get the customer order associated with the allocation.
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(\Webkul\Sales\Models\OrderProxy::modelClass(), 'order_id');
    }

    /**
     * Get the order item associated with the allocation.
     */
    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(\Webkul\Sales\Models\OrderItemProxy::modelClass(), 'order_item_id');
    }

    /**
     * Get the logs associated with the allocation.
     */
    public function logs(): HasMany
    {
        return $this->hasMany(AllocationLogProxy::modelClass(), 'order_allocation_id');
    }

    /**
     * Set a reserved quantity.
     *
     * @param  int  $qty
     * @return void
     *
     * @throws \Webkul\Fulfillment\Exceptions\InvalidStateTransitionException
     */
    public function reserve(int $qty): void
    {
        if ($this->state !== 'reserved') {
            throw new InvalidStateTransitionException("Cannot reserve allocation in state: {$this->state}");
        }

        $this->reserved_qty = $qty;
        $this->save();
    }

    /**
     * Fulfill the allocation.
     *
     * @param  int  $qty
     * @return void
     *
     * @throws \Webkul\Fulfillment\Exceptions\InvalidStateTransitionException
     */
    public function fulfill(int $qty): void
    {
        if ($this->state !== 'reserved') {
            throw new InvalidStateTransitionException("Cannot fulfill allocation in state: {$this->state}");
        }

        $this->state = 'fulfilled';
        $this->fulfilled_qty = $qty;
        $this->reserved_qty = 0; // remaining reserved qty is 0 after fulfill
        $this->save();
    }

    /**
     * Cancel the allocation.
     *
     * @param  int  $qty
     * @param  string  $reason
     * @return void
     *
     * @throws \Webkul\Fulfillment\Exceptions\InvalidStateTransitionException
     */
    public function cancel(int $qty, string $reason): void
    {
        if ($this->state !== 'reserved') {
            throw new InvalidStateTransitionException("Cannot cancel allocation in state: {$this->state}");
        }

        $this->state = 'canceled';
        $this->canceled_qty = $qty;
        $this->reserved_qty = 0; // reserved quantity becomes 0 after cancel
        $this->save();

        // Audit log entry creation
        $this->logs()->create([
            'action'  => 'cancel',
            'old_qty' => $qty,
            'new_qty' => 0,
            'reason'  => $reason,
        ]);
    }

    /**
     * Release the reserved allocation.
     *
     * @return void
     */
    public function release(): void
    {
        $this->cancel($this->reserved_qty, "Released reservation");
    }

    /**
     * Expire the reservation.
     *
     * @return void
     */
    public function expire(): void
    {
        $this->cancel($this->reserved_qty, "Reservation expired");
    }

    /**
     * Split the reserved allocation into two.
     *
     * @param  int  $splitQty
     * @return self
     *
     * @throws \Webkul\Fulfillment\Exceptions\InvalidStateTransitionException
     * @throws \InvalidArgumentException
     */
    public function split(int $splitQty): self
    {
        if ($this->state !== 'reserved') {
            throw new InvalidStateTransitionException("Cannot split allocation in state: {$this->state}");
        }

        if ($splitQty >= $this->reserved_qty || $splitQty <= 0) {
            throw new \InvalidArgumentException("Invalid split quantity: {$splitQty}. Current: {$this->reserved_qty}");
        }

        $this->reserved_qty -= $splitQty;
        $this->save();

        $this->logs()->create([
            'action'  => 'split',
            'old_qty' => $this->reserved_qty + $splitQty,
            'new_qty' => $this->reserved_qty,
            'reason'  => "Split allocation of {$splitQty}",
        ]);

        return self::create([
            'order_id'           => $this->order_id,
            'order_item_id'      => $this->order_item_id,
            'allocation_type'    => $this->allocation_type,
            'source_code'        => $this->source_code,
            'supplier_signature' => $this->supplier_signature,
            'reserved_qty'       => $splitQty,
            'state'              => 'reserved',
        ]);
    }

    /**
     * Merge another reserved allocation into this one.
     *
     * @param  self  $other
     * @return void
     *
     * @throws \Webkul\Fulfillment\Exceptions\InvalidStateTransitionException
     */
    public function merge(self $other): void
    {
        if ($this->state !== 'reserved' || $other->state !== 'reserved') {
            throw new InvalidStateTransitionException("Only reserved allocations can be merged.");
        }

        if ($this->allocation_type !== $other->allocation_type || $this->source_code !== $other->source_code) {
            throw new InvalidStateTransitionException("Cannot merge allocations from different sources or types.");
        }

        $oldQty = $this->reserved_qty;
        $this->reserved_qty += $other->reserved_qty;
        $this->save();

        $this->logs()->create([
            'action'  => 'merge',
            'old_qty' => $oldQty,
            'new_qty' => $this->reserved_qty,
            'reason'  => "Merged with allocation ID: {$other->id}",
        ]);

        // Cancel the other allocation
        $other->state = 'canceled';
        $other->reserved_qty = 0;
        $other->canceled_qty = 0;
        $other->save();
    }
}
