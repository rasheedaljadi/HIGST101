<?php

namespace Webkul\DeliveryManagement\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Webkul\DeliveryManagement\Contracts\DeliveryAttemptLog as DeliveryAttemptLogContract;
use Webkul\User\Models\AdminProxy;

class DeliveryAttemptLog extends Model implements DeliveryAttemptLogContract
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'delivery_attempt_logs';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'delivery_assignment_id',
        'attempt_number',
        'status',
        'failure_reason',
        'attempted_at',
        'attempted_by',
        'latitude',
        'longitude',
        'notes',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'attempted_at' => 'datetime',
        'latitude' => 'decimal:7',
        'longitude' => 'decimal:7',
        'attempt_number' => 'integer',
    ];

    /**
     * Get the associated assignment.
     */
    public function assignment(): BelongsTo
    {
        return $this->belongsTo(DeliveryAssignmentProxy::modelClass(), 'delivery_assignment_id');
    }

    /**
     * Get the executing delivery agent.
     */
    public function attemptedBy(): BelongsTo
    {
        return $this->belongsTo(AdminProxy::modelClass(), 'attempted_by');
    }
}
