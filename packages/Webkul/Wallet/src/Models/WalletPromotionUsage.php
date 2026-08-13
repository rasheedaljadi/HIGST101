<?php

namespace Webkul\Wallet\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Webkul\Customer\Models\CustomerProxy;
use Webkul\Wallet\Contracts\WalletPromotionUsage as WalletPromotionUsageContract;

class WalletPromotionUsage extends Model implements WalletPromotionUsageContract
{
    protected $table = 'wallet_promotion_usages';

    protected $fillable = [
        'promotion_id',
        'customer_id',
        'event_key',
        'reward_amount',
        'base_reward_amount',
        'net_credited_amount',
        'currency_code',
        'exchange_rate',
        'status',
        'promotion_snapshot',
        'decision_meta',
    ];

    protected $casts = [
        'reward_amount' => 'string',
        'base_reward_amount' => 'string',
        'net_credited_amount' => 'string',
        'exchange_rate' => 'string',
        'promotion_snapshot' => 'array',
        'decision_meta' => 'array',
    ];

    public const STATUS_PENDING = 'pending';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_REVERSED = 'reversed';

    public const STATUS_REJECTED = 'rejected';

    public function promotion(): BelongsTo
    {
        return $this->belongsTo(WalletPromotionProxy::modelClass(), 'promotion_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(CustomerProxy::modelClass(), 'customer_id');
    }

    public function grant(): HasOne
    {
        return $this->hasOne(WalletPromotionGrantProxy::modelClass(), 'usage_id');
    }

    /**
     * The "booted" method of the model.
     * Enforces strict accounting preservation: physical deletion is strictly forbidden.
     */
    protected static function booted(): void
    {
        static::deleting(function (self $model) {
            throw new \LogicException('Physical deletion of WalletPromotionUsage records is strictly forbidden to preserve accounting history.');
        });
    }
}
