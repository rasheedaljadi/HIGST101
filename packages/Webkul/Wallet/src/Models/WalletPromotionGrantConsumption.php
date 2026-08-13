<?php

namespace Webkul\Wallet\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Webkul\Customer\Models\CustomerProxy;
use Webkul\Sales\Models\OrderItemProxy;
use Webkul\Sales\Models\OrderProxy;
use Webkul\Wallet\Contracts\WalletPromotionGrantConsumption as WalletPromotionGrantConsumptionContract;

class WalletPromotionGrantConsumption extends Model implements WalletPromotionGrantConsumptionContract
{
    public $timestamps = false;

    protected $table = 'wallet_promotion_grant_consumptions';

    protected $fillable = [
        'grant_id',
        'customer_id',
        'wallet_id',
        'order_id',
        'order_item_id',
        'wallet_transaction_id',
        'currency_code',
        'exchange_rate',
        'consumed_amount',
        'base_consumed_amount',
        'reversed_amount',
        'status',
        'reversed_at',
        'reversal_transaction_id',
        'created_at',
    ];

    protected $casts = [
        'consumed_amount' => 'string',
        'base_consumed_amount' => 'string',
        'reversed_amount' => 'string',
        'exchange_rate' => 'string',
        'created_at' => 'datetime',
        'reversed_at' => 'datetime',
    ];

    public const STATUS_CONSUMED = 'consumed';

    public const STATUS_PARTIALLY_REVERSED = 'partially_reversed';

    public const STATUS_FULLY_REVERSED = 'fully_reversed';

    public function grant(): BelongsTo
    {
        return $this->belongsTo(WalletPromotionGrantProxy::modelClass(), 'grant_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(CustomerProxy::modelClass(), 'customer_id');
    }

    public function wallet(): BelongsTo
    {
        return $this->belongsTo(WalletAccountProxy::modelClass(), 'wallet_id');
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(OrderProxy::modelClass(), 'order_id');
    }

    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(OrderItemProxy::modelClass(), 'order_item_id');
    }

    public function walletTransaction(): BelongsTo
    {
        return $this->belongsTo(WalletTransactionProxy::modelClass(), 'wallet_transaction_id');
    }

    public function reversalTransaction(): BelongsTo
    {
        return $this->belongsTo(WalletTransactionProxy::modelClass(), 'reversal_transaction_id');
    }
}
