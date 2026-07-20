<?php

namespace Webkul\Fulfillment\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Webkul\Fulfillment\Contracts\FulfillmentAttempt as FulfillmentAttemptContract;

class FulfillmentAttempt extends Model implements FulfillmentAttemptContract
{
    protected $fillable = [
        'purchase_order_id',
        'attempt_no',
        'result',
        'error_type',
        'provider_code',
        'message',
    ];

    /**
     * Get the purchase order associated with the attempt.
     */
    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrderProxy::modelClass(), 'purchase_order_id');
    }
}
