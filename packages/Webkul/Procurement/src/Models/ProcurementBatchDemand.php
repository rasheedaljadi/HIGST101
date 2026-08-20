<?php

namespace Webkul\Procurement\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProcurementBatchDemand extends Model
{
    protected $table = 'procurement_batch_demands';

    protected $fillable = [
        'batch_id',
        'procurement_demand_id',
        'qty_batched',
        'qty_released',
        'state',
    ];

    protected $casts = [
        'qty_batched' => 'integer',
        'qty_released' => 'integer',
    ];

    public function batch(): BelongsTo
    {
        return $this->belongsTo(ProcurementBatchProxy::modelClass(), 'batch_id');
    }

    public function demand(): BelongsTo
    {
        return $this->belongsTo(ProcurementDemandProxy::modelClass(), 'procurement_demand_id');
    }
}
