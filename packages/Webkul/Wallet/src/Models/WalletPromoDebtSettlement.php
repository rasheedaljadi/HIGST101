<?php

namespace Webkul\Wallet\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Webkul\Customer\Models\CustomerProxy;
use Webkul\Wallet\Contracts\WalletPromoDebtSettlement as WalletPromoDebtSettlementContract;

class WalletPromoDebtSettlement extends Model implements WalletPromoDebtSettlementContract
{
    public $timestamps = false;

    protected $table = 'wallet_promo_debt_settlements';

    protected $fillable = [
        'debt_id',
        'wallet_id',
        'customer_id',
        'grant_id',
        'settlement_amount',
        'base_settlement_amount',
        'currency_code',
        'wallet_transaction_id',
        'event_key',
        'created_at',
    ];

    protected $casts = [
        'settlement_amount' => 'string',
        'base_settlement_amount' => 'string',
        'created_at' => 'datetime',
    ];

    public function debt(): BelongsTo
    {
        return $this->belongsTo(WalletPromoDebtProxy::modelClass(), 'debt_id');
    }

    public function wallet(): BelongsTo
    {
        return $this->belongsTo(WalletAccountProxy::modelClass(), 'wallet_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(CustomerProxy::modelClass(), 'customer_id');
    }

    public function grant(): BelongsTo
    {
        return $this->belongsTo(WalletPromotionGrantProxy::modelClass(), 'grant_id');
    }

    public function walletTransaction(): BelongsTo
    {
        return $this->belongsTo(WalletTransactionProxy::modelClass(), 'wallet_transaction_id');
    }
}
