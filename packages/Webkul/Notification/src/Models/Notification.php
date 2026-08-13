<?php

namespace Webkul\Notification\Models;

use Illuminate\Database\Eloquent\Model;
use Webkul\Customer\Models\CustomerProxy;
use Webkul\Notification\Contracts\Notification as NotificationContract;
use Webkul\Sales\Models\OrderProxy;

class Notification extends Model implements NotificationContract
{
    protected $fillable = [
        'customer_id',
        'type',
        'title',
        'message',
        'action_url',
        'event_key',
        'read',
        'order_id',
    ];

    /**
     * Get Order Details.
     */
    public function order()
    {
        return $this->belongsTo(OrderProxy::modelClass());
    }

    /**
     * Get Customer Details.
     */
    public function customer()
    {
        return $this->belongsTo(CustomerProxy::modelClass());
    }
}
