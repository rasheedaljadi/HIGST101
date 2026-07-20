<?php

namespace Webkul\Fulfillment\Models;

use Illuminate\Database\Eloquent\Model;
use Webkul\Fulfillment\Contracts\ProcessedEvent as ProcessedEventContract;

class ProcessedEvent extends Model implements ProcessedEventContract
{
    protected $table = 'processed_events';

    protected $fillable = [
        'id',
        'provider',
        'event_id',
        'event_name',
        'processed_at',
    ];
}
