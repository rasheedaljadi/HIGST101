<?php

namespace Webkul\DeliveryManagement\Models;

use Illuminate\Database\Eloquent\Model;
use Webkul\DeliveryManagement\Contracts\DeliveryGovernorateRule as DeliveryGovernorateRuleContract;

class DeliveryGovernorateRule extends Model implements DeliveryGovernorateRuleContract
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'delivery_governorate_rules';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'state_code',
        'delivery_type',
        'is_enabled',
        'allowed_payment_methods',
        'delivery_fee',
        'min_order_amount',
        'effective_from',
        'effective_until',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'is_enabled' => 'boolean',
        'allowed_payment_methods' => 'array',
        'delivery_fee' => 'decimal:2',
        'min_order_amount' => 'decimal:2',
        'effective_from' => 'date',
        'effective_until' => 'date',
    ];
}
