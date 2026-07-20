<?php

namespace Webkul\Fulfillment\Models;

use Illuminate\Database\Eloquent\Model;
use Webkul\Fulfillment\Contracts\OutgoingRequest as OutgoingRequestContract;

class OutgoingRequest extends Model implements OutgoingRequestContract
{
    protected $table = 'outgoing_requests';

    public $timestamps = false;

    protected $fillable = [
        'request_hash',
        'endpoint',
        'idempotency_key',
        'response_payload',
        'response_hash',
        'sent_at',
    ];

    protected $casts = [
        'response_payload' => 'array',
        'sent_at'          => 'datetime',
    ];
}
