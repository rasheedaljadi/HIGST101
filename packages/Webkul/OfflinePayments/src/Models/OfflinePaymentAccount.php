<?php

namespace Webkul\OfflinePayments\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Webkul\OfflinePayments\Contracts\OfflinePaymentAccount as OfflinePaymentAccountContract;

class OfflinePaymentAccount extends Model implements OfflinePaymentAccountContract
{
    use SoftDeletes;

    protected $table = 'offline_payment_accounts';

    protected $fillable = [
        'code',
        'display_name',
        'provider_name',
        'recipient_name',
        'logo_path',
        'channel_ids',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'channel_ids' => 'array',
        'is_active' => 'boolean',
    ];

    public function destinations()
    {
        return $this->hasMany(OfflinePaymentDestinationProxy::modelClass(), 'offline_payment_account_id');
    }
}
