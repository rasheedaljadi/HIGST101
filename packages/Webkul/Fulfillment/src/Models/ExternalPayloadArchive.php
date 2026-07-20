<?php

namespace Webkul\Fulfillment\Models;

use Illuminate\Database\Eloquent\Model;
use Webkul\Fulfillment\Contracts\ExternalPayloadArchive as ExternalPayloadArchiveContract;

class ExternalPayloadArchive extends Model implements ExternalPayloadArchiveContract
{
    protected $table = 'external_payload_archives';

    protected $fillable = [
        'request_payload',
        'response_payload',
        'normalized_dto',
        'request_hash',
        'response_hash',
        'provider_version',
        'contract_version',
    ];

    protected $casts = [
        'request_payload'  => 'array',
        'response_payload' => 'array',
        'normalized_dto'   => 'array',
    ];
}
