<?php

namespace Webkul\MobileApi\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Webkul\Core\Models\ChannelProxy;
use Webkul\MobileApi\Contracts\MobileApiKey as MobileApiKeyContract;

class MobileApiKey extends Model implements MobileApiKeyContract
{
    protected $table = 'mobile_api_keys';

    protected $fillable = [
        'name',
        'key',
        'channel_id',
        'status',
        'last_used_at',
    ];

    protected $casts = [
        'status' => 'boolean',
        'last_used_at' => 'datetime',
    ];

    public function channel(): BelongsTo
    {
        return $this->belongsTo(ChannelProxy::modelClass(), 'channel_id');
    }
}
