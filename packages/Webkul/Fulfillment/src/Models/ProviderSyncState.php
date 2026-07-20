<?php

namespace Webkul\Fulfillment\Models;

use Illuminate\Database\Eloquent\Model;

class ProviderSyncState extends Model
{
    protected $table = 'provider_sync_states';

    protected $primaryKey = 'provider';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'provider',
        'last_attempt_cursor',
        'last_successful_cursor',
        'last_attempt_at',
        'last_successful_at',
        'last_full_sync_at',
        'schema_version',
    ];

    protected $casts = [
        'last_attempt_cursor'    => 'array',
        'last_successful_cursor' => 'array',
        'last_attempt_at'        => 'datetime',
        'last_successful_at'     => 'datetime',
        'last_full_sync_at'      => 'datetime',
    ];
}
