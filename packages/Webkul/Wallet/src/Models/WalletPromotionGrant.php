<?php

namespace Webkul\Wallet\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Webkul\Customer\Models\CustomerProxy;
use Webkul\Wallet\Contracts\WalletPromotionGrant as WalletPromotionGrantContract;
use Webkul\Wallet\Models\Traits\ProhibitsPhysicalDeletion;

class WalletPromotionGrant extends Model implements WalletPromotionGrantContract
{
    use ProhibitsPhysicalDeletion;

    protected $table = 'wallet_promotion_grants';

    protected $fillable = [
        'promotion_id',
        'customer_id',
        'wallet_id',
        'usage_id',
        'wallet_transaction_id',
        'original_amount',
        'remaining_amount',
        'consumed_amount',
        'currency_code',
        'base_amount',
        'status',
        'reference_type',
        'reference_id',
        'granted_at',
        'expires_at',
    ];

    protected $casts = [
        'original_amount' => 'string',
        'remaining_amount' => 'string',
        'consumed_amount' => 'string',
        'base_amount' => 'string',
        'granted_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    public const STATUS_PENDING = 'pending';

    public const STATUS_ACTIVE = 'active';

    public const STATUS_PARTIALLY_CONSUMED = 'partially_consumed';

    public const STATUS_FULLY_CONSUMED = 'fully_consumed';

    public const STATUS_EXPIRED = 'expired';

    public const STATUS_REVERSED = 'reversed';

    public function promotion(): BelongsTo
    {
        return $this->belongsTo(WalletPromotionProxy::modelClass(), 'promotion_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(CustomerProxy::modelClass(), 'customer_id');
    }

    public function wallet(): BelongsTo
    {
        return $this->belongsTo(WalletAccountProxy::modelClass(), 'wallet_id');
    }

    public function usage(): BelongsTo
    {
        return $this->belongsTo(WalletPromotionUsageProxy::modelClass(), 'usage_id');
    }

    public function consumptions(): HasMany
    {
        return $this->hasMany(WalletPromotionGrantConsumptionProxy::modelClass(), 'grant_id');
    }
}
