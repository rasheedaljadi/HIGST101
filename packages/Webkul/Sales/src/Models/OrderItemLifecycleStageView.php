<?php

namespace Webkul\Sales\Models;

use Illuminate\Database\Eloquent\Model;
use Webkul\Sales\Contracts\OrderItemLifecycleStageView as OrderItemLifecycleStageViewContract;

class OrderItemLifecycleStageView extends Model implements OrderItemLifecycleStageViewContract
{
    protected $table = 'order_item_lifecycle_stage_views';

    protected $fillable = [
        'order_item_id',
        'order_id',
        'origin_type',
        'current_stage_code',
        'source_type',
        'source_entity_type',
        'source_entity_id',
        'is_exception',
        'exception_reason',
        'computed_at',
    ];

    protected $casts = [
        'is_exception' => 'boolean',
        'computed_at' => 'datetime',
    ];

    public function orderItem()
    {
        return $this->belongsTo(OrderItemProxy::modelClass(), 'order_item_id');
    }

    public function order()
    {
        return $this->belongsTo(OrderProxy::modelClass(), 'order_id');
    }
}
