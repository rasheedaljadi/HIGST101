<?php

namespace Webkul\DeliveryManagement\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Webkul\DeliveryManagement\Contracts\DeliveryCashCollection as DeliveryCashCollectionContract;
use Webkul\Sales\Models\OrderProxy;
use Webkul\User\Models\AdminProxy;

class DeliveryCashCollection extends Model implements DeliveryCashCollectionContract
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'delivery_cash_collections';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'delivery_assignment_id',
        'order_id',
        'delivery_boy_id',
        'amount',
        'order_currency_code',
        'order_amount',
        'collected_currency_code',
        'collected_amount',
        'currency',
        'exchange_rate',
        'base_currency',
        'amount_in_base_currency',
        'rate_snapshot_at',
        'collected_at',
        'idempotency_key',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'amount' => 'decimal:4',
        'order_amount' => 'decimal:4',
        'collected_amount' => 'decimal:4',
        'exchange_rate' => 'decimal:6',
        'amount_in_base_currency' => 'decimal:4',
        'collected_at' => 'datetime',
        'rate_snapshot_at' => 'datetime',
    ];

    /**
     * Get the associated assignment.
     */
    public function assignment(): BelongsTo
    {
        return $this->belongsTo(DeliveryAssignmentProxy::modelClass(), 'delivery_assignment_id');
    }

    /**
     * Get the associated order.
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(OrderProxy::modelClass(), 'order_id');
    }

    /**
     * Get the delivery agent.
     */
    public function deliveryBoy(): BelongsTo
    {
        return $this->belongsTo(AdminProxy::modelClass(), 'delivery_boy_id');
    }
}
