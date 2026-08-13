<?php

namespace Webkul\Wallet\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Webkul\Customer\Models\CustomerProxy;
use Webkul\Wallet\Contracts\WalletAccount as WalletAccountContract;
use Webkul\Wallet\Database\Factories\WalletAccountFactory;

class WalletAccount extends Model implements WalletAccountContract
{
    use HasFactory;

    protected static function newFactory()
    {
        return WalletAccountFactory::new();
    }

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'wallet_accounts';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'customer_id',
        'total_balance',
        'available_balance',
        'held_balance',
        'promo_balance',
        'cash_balance',
        'unclassified_balance',
        'promo_debt',
        'backfill_status',
        'currency_code',
        'status',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'total_balance' => 'string',
        'available_balance' => 'string',
        'held_balance' => 'string',
        'promo_balance' => 'string',
        'cash_balance' => 'string',
        'unclassified_balance' => 'string',
        'promo_debt' => 'string',
    ];

    /**
     * Wallet statuses.
     */
    const STATUS_ACTIVE = 'active';

    const STATUS_SUSPENDED = 'suspended';

    const BACKFILL_STATUS_VERIFIED = 'verified';

    const BACKFILL_STATUS_PENDING_REVIEW = 'pending_review';

    const BACKFILL_STATUS_RESOLVED = 'resolved';

    /**
     * Get the customer that owns the wallet.
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(CustomerProxy::modelClass(), 'customer_id');
    }

    /**
     * Get all transactions for this wallet.
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(WalletTransactionProxy::modelClass(), 'wallet_id')
            ->latest();
    }

    /**
     * Get all top-up requests for this wallet.
     */
    public function topups(): HasMany
    {
        return $this->hasMany(WalletTopUpProxy::modelClass(), 'wallet_id')
            ->latest();
    }

    /**
     * Get all withdrawal requests for this wallet.
     */
    public function withdrawals(): HasMany
    {
        return $this->hasMany(WalletWithdrawalRequestProxy::modelClass(), 'wallet_id')
            ->latest();
    }

    /**
     * Alias for withdrawals relationship.
     */
    public function withdrawalRequests(): HasMany
    {
        return $this->withdrawals();
    }

    /**
     * Get all promotion grants for this wallet.
     */
    public function promotionGrants(): HasMany
    {
        return $this->hasMany(WalletPromotionGrantProxy::modelClass(), 'wallet_id');
    }

    /**
     * Get all promo debts for this wallet.
     */
    public function promoDebts(): HasMany
    {
        return $this->hasMany(WalletPromoDebtProxy::modelClass(), 'wallet_id');
    }

    /**
     * Scope: active wallets only.
     */
    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    /**
     * Scope: get wallet by customer id.
     */
    public function scopeForCustomer($query, int $customerId)
    {
        return $query->where('customer_id', $customerId);
    }

    /**
     * Check if wallet is active.
     */
    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    /**
     * Check if wallet is under audit review.
     */
    public function isUnderAudit(): bool
    {
        return $this->backfill_status === self::BACKFILL_STATUS_PENDING_REVIEW;
    }

    /**
     * Check if wallet can cover an amount.
     */
    public function canCover(float|string $amount): bool
    {
        return bccomp((string) $this->available_balance, (string) $amount, 4) >= 0;
    }

    /**
     * Get withdrawable balance strictly derived from cash balance minus held balance.
     * Promotional balance is non-withdrawable.
     */
    public function getWithdrawableBalanceAttribute(): float
    {
        $cash = (float) $this->cash_balance;
        $held = (float) $this->held_balance;

        return max(0.0, $cash - $held);
    }
}
