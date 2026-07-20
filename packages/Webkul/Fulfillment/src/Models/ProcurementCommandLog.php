<?php

namespace Webkul\Fulfillment\Models;

use Illuminate\Database\Eloquent\Model;

class ProcurementCommandLog extends Model
{
    protected $table = 'procurement_commands';

    public $timestamps = false;

    protected $fillable = [
        'command_type',
        'idempotency_key',
        'procurement_session_id',
        'status',
        'processed_at',
        'created_at',
    ];

    protected $casts = [
        'processed_at' => 'datetime',
        'created_at'   => 'datetime',
    ];
}
