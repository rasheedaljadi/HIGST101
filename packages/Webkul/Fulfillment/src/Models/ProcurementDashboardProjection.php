<?php

namespace Webkul\Fulfillment\Models;

use Illuminate\Database\Eloquent\Model;
use Webkul\Fulfillment\Contracts\ProcurementDashboardProjection as ProcurementDashboardProjectionContract;

class ProcurementDashboardProjection extends Model implements ProcurementDashboardProjectionContract
{
    protected $table = 'procurement_dashboard_projections';

    public $timestamps = false;

    protected $fillable = [
        'purchase_order_id',
        'supplier_code',
        'current_step',
        'current_status',
        'progress_percent',
        'tracking_number',
        'retries_count',
        'health_status',
        'started_at',
        'updated_at',
        'estimated_delivery_at',
    ];

    protected $casts = [
        'started_at'            => 'datetime',
        'updated_at'            => 'datetime',
        'estimated_delivery_at' => 'datetime',
    ];
}
