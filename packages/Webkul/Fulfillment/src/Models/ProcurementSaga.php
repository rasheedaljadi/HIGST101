<?php

namespace Webkul\Fulfillment\Models;

use Illuminate\Database\Eloquent\Model;
use Webkul\Fulfillment\Contracts\ProcurementSaga as ProcurementSagaContract;

class ProcurementSaga extends Model implements ProcurementSagaContract
{
    protected $table = 'procurement_sagas';

    protected $fillable = [
        'purchase_order_id',
        'state',
        'correlation_id',
        'causation_id',
        'trace_id',
        'span_id',
    ];
}
