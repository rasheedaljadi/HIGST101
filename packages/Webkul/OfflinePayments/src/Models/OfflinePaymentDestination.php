<?php

namespace Webkul\OfflinePayments\Models;

use Illuminate\Database\Eloquent\Model;
use Webkul\Core\Models\CurrencyProxy;
use Webkul\OfflinePayments\Contracts\OfflinePaymentDestination as OfflinePaymentDestinationContract;

class OfflinePaymentDestination extends Model implements OfflinePaymentDestinationContract
{
    protected $table = 'offline_payment_destinations';

    protected $fillable = [
        'offline_payment_account_id',
        'currency_id',
        'account_identifier',
        'swift_code',
        'transfer_instructions',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function account()
    {
        return $this->belongsTo(OfflinePaymentAccountProxy::modelClass(), 'offline_payment_account_id');
    }

    public function currency()
    {
        return $this->belongsTo(CurrencyProxy::modelClass(), 'currency_id');
    }
}
