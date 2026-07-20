<?php

namespace Webkul\Fulfillment\Models;

use Illuminate\Database\Eloquent\Model;
use Webkul\Fulfillment\Contracts\ProcurementAggregate as ProcurementAggregateContract;

class ProcurementAggregate extends Model implements ProcurementAggregateContract
{
    protected $table = 'procurement_aggregates';

    protected $fillable = [
        'purchase_order_id',
    ];

    public function purchaseOrder()
    {
        return $this->belongsTo(PurchaseOrderProxy::modelClass(), 'purchase_order_id');
    }

    public function sessions()
    {
        return $this->hasMany(ProcurementSessionProxy::modelClass(), 'procurement_aggregate_id');
    }
}
