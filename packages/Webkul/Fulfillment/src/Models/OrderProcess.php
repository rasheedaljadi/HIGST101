<?php

namespace Webkul\Fulfillment\Models;

use Illuminate\Database\Eloquent\Model;
use Webkul\Fulfillment\Contracts\OrderProcess as OrderProcessContract;

class OrderProcess extends Model implements OrderProcessContract
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'order_processes';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'order_id',
        'payment_mode',
        'lifecycle_state',
        'accepted_at',
        'accepted_by',
        'blocked_reason',
        'correlation_id',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'accepted_at' => 'datetime',
    ];

    /**
     * Transition the state to accepted.
     */
    public function accept(string $by): void
    {
        $validBefore = ['waiting_payment', 'pending_acceptance', 'ops_review'];

        if (! in_array($this->lifecycle_state, $validBefore)) {
            throw new \DomainException("Cannot accept order in lifecycle state: {$this->lifecycle_state}");
        }

        $this->lifecycle_state = 'accepted';
        $this->accepted_at = now();
        $this->accepted_by = $by;
        $this->save();
    }

    /**
     * Transition to fulfillment started.
     */
    public function startFulfillment(): void
    {
        if ($this->lifecycle_state !== 'accepted') {
            throw new \DomainException("Cannot start fulfillment from state: {$this->lifecycle_state}");
        }

        $this->lifecycle_state = 'fulfillment_started';
        $this->save();
    }

    /**
     * Flag order for operations review.
     */
    public function flagOps(string $reason): void
    {
        $this->lifecycle_state = 'ops_review';
        $this->blocked_reason = $reason;
        $this->save();
    }

    /**
     * Mark ready to ship.
     */
    public function markReadyToShip(): void
    {
        $this->lifecycle_state = 'ready_to_ship';
        $this->save();
    }

    /**
     * Mark settled.
     */
    public function markSettled(): void
    {
        $this->lifecycle_state = 'settled';
        $this->save();
    }
}
