<?php

namespace Webkul\Fulfillment\Models;

use Illuminate\Database\Eloquent\Model;
use Webkul\Fulfillment\Contracts\ExternalOrder as ExternalOrderContract;

class ExternalOrder extends Model implements ExternalOrderContract
{
    protected $table = 'external_orders';

    protected $fillable = [
        'provider',
        'provider_account_id',
        'external_order_id',
        'purchase_order_id',
        'procurement_session_id',
        'status',
        'raw_reference',
    ];

    public function purchaseOrder()
    {
        return $this->belongsTo(PurchaseOrderProxy::modelClass(), 'purchase_order_id');
    }

    public function session()
    {
        return $this->belongsTo(ProcurementSessionProxy::modelClass(), 'procurement_session_id');
    }
}
