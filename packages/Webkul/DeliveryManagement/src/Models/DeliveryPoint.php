<?php

namespace Webkul\DeliveryManagement\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Webkul\DeliveryManagement\Contracts\DeliveryPoint as DeliveryPointContract;

class DeliveryPoint extends Model implements DeliveryPointContract
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'delivery_points';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'code',
        'name',
        'name_ar',
        'state_code',
        'city',
        'address',
        'latitude',
        'longitude',
        'contact_name',
        'contact_phone',
        'working_hours',
        'max_capacity',
        'is_active',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'working_hours' => 'array',
        'is_active' => 'boolean',
        'latitude' => 'decimal:7',
        'longitude' => 'decimal:7',
    ];

    /**
     * Get the assignments associated with this delivery point.
     */
    public function assignments(): HasMany
    {
        return $this->hasMany(DeliveryAssignmentProxy::modelClass(), 'delivery_point_id');
    }
}
