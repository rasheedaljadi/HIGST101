<?php

namespace Webkul\Inventory\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Webkul\Product\Models\ProductProxy;

class ExternalAvailabilitySnapshot extends Model
{
    protected $table = 'external_availability_snapshots';

    protected $fillable = [
        'provider',
        'external_product_id',
        'external_sku',
        'internal_product_id',
        'available_quantity',
        'price_usd',
        'raw_payload',
        'synced_at',
        'sync_status',
    ];

    protected $casts = [
        'raw_payload' => 'array',
        'synced_at' => 'datetime',
        'available_quantity' => 'integer',
        'price_usd' => 'decimal:4',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(ProductProxy::modelClass(), 'internal_product_id');
    }
}
