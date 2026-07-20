<?php

namespace Webkul\Fulfillment\Models;

use Illuminate\Database\Eloquent\Model;
use Webkul\Fulfillment\Contracts\ProcurementTimeline as ProcurementTimelineContract;

class ProcurementTimeline extends Model implements ProcurementTimelineContract
{
    protected $table = 'procurement_timelines';

    public $timestamps = false;

    protected $fillable = [
        'procurement_session_id',
        'purchase_order_id',
        'stage',
        'payload',
        'correlation_id',
        'causation_id',
        'trace_id',
        'span_id',
        'created_at',
    ];

    protected $casts = [
        'payload'    => 'array',
        'created_at' => 'datetime',
    ];
}
