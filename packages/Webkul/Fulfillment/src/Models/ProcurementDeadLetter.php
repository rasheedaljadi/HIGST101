<?php

namespace Webkul\Fulfillment\Models;

use Illuminate\Database\Eloquent\Model;

class ProcurementDeadLetter extends Model
{
    protected $table = 'procurement_dead_letters';

    protected $fillable = [
        'procurement_session_id',
        'reason',
        'payload',
        'retries',
        'stack',
        'correlation_id',
    ];

    protected $casts = [
        'payload' => 'array',
    ];
}
