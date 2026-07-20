<?php

namespace Webkul\Fulfillment\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Webkul\Fulfillment\Contracts\FulfillmentProviderEvent as FulfillmentProviderEventContract;

class FulfillmentProviderEvent extends Model implements FulfillmentProviderEventContract
{
    protected $fillable = [
        'purchase_order_id',
        'provider',
        'external_state',
        'source_type',
        'payload',
        'received_at',
        'processed_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'payload'      => 'array',
            'received_at'  => 'datetime',
            'processed_at' => 'datetime',
        ];
    }

    /**
     * Get the purchase order associated with the event.
     */
    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrderProxy::modelClass(), 'purchase_order_id');
    }
}
