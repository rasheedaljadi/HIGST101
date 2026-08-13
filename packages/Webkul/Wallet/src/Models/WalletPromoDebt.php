<?php

namespace Webkul\Wallet\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Webkul\Customer\Models\CustomerProxy;
use Webkul\Sales\Models\OrderProxy;
use Webkul\Sales\Models\RefundProxy;
use Webkul\Wallet\Contracts\WalletPromoDebt as WalletPromoDebtContract;

class WalletPromoDebt extends Model implements WalletPromoDebtContract
{
    protected $table = 'wallet_promo_debts';

    protected $fillable = [
        'wallet_id',
        'customer_id',
        'order_id',
        'source_refund_id',
        'event_key',
        'currency_code',
        'original_debt_amount',
        'remaining_debt_amount',
        'settled_amount',
        'status',
        'reason',
        'settled_at',
    ];

    protected $casts = [
        'original_debt_amount' => 'string',
        'remaining_debt_amount' => 'string',
        'settled_amount' => 'string',
        'settled_at' => 'datetime',
    ];

    public const STATUS_ACTIVE = 'active';

    public const STATUS_PARTIALLY_SETTLED = 'partially_settled';

    public const STATUS_SETTLED = 'settled';

    public function wallet(): BelongsTo
    {
        return $this->belongsTo(WalletAccountProxy::modelClass(), 'wallet_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(CustomerProxy::modelClass(), 'customer_id');
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(OrderProxy::modelClass(), 'order_id');
    }

    public function sourceRefund(): BelongsTo
    {
        return $this->belongsTo(RefundProxy::modelClass(), 'source_refund_id');
    }

    public function settlements(): HasMany
    {
        return $this->hasMany(WalletPromoDebtSettlementProxy::modelClass(), 'debt_id');
    }

    /**
     * The "booted" method of the model.
     * Enforces strict accounting preservation: physical deletion is strictly forbidden.
     */
    protected static function booted(): void
    {
        static::deleting(function (self $model) {
            throw new \LogicException('Physical deletion of WalletPromoDebt records is strictly forbidden to preserve accounting history.');
        });
    }
}
