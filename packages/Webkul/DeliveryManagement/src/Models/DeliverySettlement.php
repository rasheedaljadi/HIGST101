<?php

namespace Webkul\DeliveryManagement\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Webkul\DeliveryManagement\Contracts\DeliverySettlement as DeliverySettlementContract;
use Webkul\User\Models\AdminProxy;

class DeliverySettlement extends Model implements DeliverySettlementContract
{
    /**
     * State constants.
     */
    public const STATUS_PENDING = 'pending';

    public const STATUS_SETTLED = 'settled';

    public const STATUS_DISCREPANCY = 'discrepancy';

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'delivery_settlements';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'delivery_boy_id',
        'settlement_date',
        'expected_amount',
        'actual_amount',
        'difference',
        'currency',
        'status',
        'settled_by',
        'settled_at',
        'notes',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'settlement_date' => 'date',
        'expected_amount' => 'decimal:4',
        'actual_amount' => 'decimal:4',
        'difference' => 'decimal:4',
        'settled_at' => 'datetime',
    ];

    /**
     * Get the delivery agent.
     */
    public function deliveryBoy(): BelongsTo
    {
        return $this->belongsTo(AdminProxy::modelClass(), 'delivery_boy_id');
    }

    /**
     * Get the accountant/supervisor who settled.
     */
    public function settledBy(): BelongsTo
    {
        return $this->belongsTo(AdminProxy::modelClass(), 'settled_by');
    }
}
