<?php

namespace Webkul\Fulfillment\Models;

use Illuminate\Database\Eloquent\Model;

class ExternalOrderProjection extends Model
{
    protected $table = 'external_order_projections';

    protected $fillable = [
        'external_order_id',
        'purchase_order_id',
        'status',
        'tracking_number',
        'carrier',
    ];
}
