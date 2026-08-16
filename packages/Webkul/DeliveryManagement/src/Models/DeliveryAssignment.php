<?php

namespace Webkul\DeliveryManagement\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Webkul\DeliveryManagement\Contracts\DeliveryAssignment as DeliveryAssignmentContract;
use Webkul\Sales\Models\OrderProxy;
use Webkul\Sales\Models\ShipmentProxy;
use Webkul\User\Models\AdminProxy;

class DeliveryAssignment extends Model implements DeliveryAssignmentContract
{
    /**
     * State constants.
     */
    public const STATUS_READY_FOR_ASSIGNMENT = 'ready_for_assignment';

    public const STATUS_ASSIGNED = 'assigned';

    public const STATUS_PICKED_UP = 'picked_up';

    public const STATUS_OUT_FOR_DELIVERY = 'out_for_delivery';

    public const STATUS_ARRIVED_AT_POINT = 'arrived_at_point';

    public const STATUS_DELIVERED = 'delivered';

    public const STATUS_DELIVERY_FAILED = 'delivery_failed';

    public const STATUS_RETRY_SCHEDULED = 'retry_scheduled';

    public const STATUS_RETURNED_TO_HAYEST = 'returned_to_hayest';

    /**
     * Delivery type constants.
     */
    public const TYPE_HOME_DELIVERY = 'home_delivery';

    public const TYPE_DELIVERY_POINT = 'delivery_point';

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'delivery_assignments';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'order_id',
        'shipment_id',
        'delivery_type',
        'delivery_boy_id',
        'delivery_point_id',
        'status',
        'assigned_by',
        'assigned_at',
        'picked_up_at',
        'out_for_delivery_at',
        'delivered_at',
        'failed_at',
        'returned_at',
        'attempt_count',
        'max_attempts',
        'failure_reason',
        'customer_address_snapshot',
        'delivery_point_snapshot',
        'notes',
        'idempotency_key',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'assigned_at' => 'datetime',
        'picked_up_at' => 'datetime',
        'out_for_delivery_at' => 'datetime',
        'delivered_at' => 'datetime',
        'failed_at' => 'datetime',
        'returned_at' => 'datetime',
        'customer_address_snapshot' => 'array',
        'delivery_point_snapshot' => 'array',
        'attempt_count' => 'integer',
        'max_attempts' => 'integer',
    ];

    /**
     * Scope query to assignments for a specific delivery agent.
     */
    public function scopeForAgent(Builder $query, int $agentId): Builder
    {
        return $query->where('delivery_boy_id', $agentId);
    }

    /**
     * Scope query to assignments for a specific delivery point.
     */
    public function scopeForDeliveryPoint(Builder $query, int $deliveryPointId): Builder
    {
        return $query->where('delivery_point_id', $deliveryPointId);
    }

    /**
     * Scope query for operations supervisors (unrestricted).
     */
    public function scopeForSupervisor(Builder $query): Builder
    {
        return $query;
    }

    /**
     * Get the associated order.
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(OrderProxy::modelClass(), 'order_id');
    }

    /**
     * Get the associated shipment.
     */
    public function shipment(): BelongsTo
    {
        return $this->belongsTo(ShipmentProxy::modelClass(), 'shipment_id');
    }

    /**
     * Get the delivery boy / agent.
     */
    public function deliveryBoy(): BelongsTo
    {
        return $this->belongsTo(AdminProxy::modelClass(), 'delivery_boy_id');
    }

    /**
     * Get the assigned supervisor.
     */
    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(AdminProxy::modelClass(), 'assigned_by');
    }

    /**
     * Get the delivery point.
     */
    public function deliveryPoint(): BelongsTo
    {
        return $this->belongsTo(DeliveryPointProxy::modelClass(), 'delivery_point_id');
    }

    /**
     * Get attempt logs for this assignment.
     */
    public function attemptLogs(): HasMany
    {
        return $this->hasMany(DeliveryAttemptLogProxy::modelClass(), 'delivery_assignment_id');
    }

    /**
     * Get cash collections for this assignment.
     */
    public function cashCollections(): HasMany
    {
        return $this->hasMany(DeliveryCashCollectionProxy::modelClass(), 'delivery_assignment_id');
    }
}
