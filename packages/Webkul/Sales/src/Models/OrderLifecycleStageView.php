<?php

namespace Webkul\Sales\Models;

use Illuminate\Database\Eloquent\Model;
use Webkul\Sales\Contracts\OrderLifecycleStageView as OrderLifecycleStageViewContract;

class OrderLifecycleStageView extends Model implements OrderLifecycleStageViewContract
{
    protected $table = 'order_lifecycle_stage_views';

    protected $fillable = [
        'order_id',
        'current_stage_code',
        'bottleneck_stage_code',
        'is_mixed_order',
        'has_imported_items',
        'has_internal_items',
        'is_exception',
        'exception_reason',
        'computed_at',
        'source_version',
    ];

    protected $casts = [
        'is_mixed_order' => 'boolean',
        'has_imported_items' => 'boolean',
        'has_internal_items' => 'boolean',
        'is_exception' => 'boolean',
        'computed_at' => 'datetime',
    ];

    public function order()
    {
        return $this->belongsTo(OrderProxy::modelClass(), 'order_id');
    }

    public function itemViews()
    {
        return $this->hasMany(OrderItemLifecycleStageViewProxy::modelClass(), 'order_id', 'order_id');
    }
}
