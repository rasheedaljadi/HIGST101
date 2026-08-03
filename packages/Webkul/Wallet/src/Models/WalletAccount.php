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
        'currency_code',
        'status',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'total_balance' => 'float',
        'available_balance' => 'float',
        'held_balance' => 'float',
    ];

    /**
     * Wallet statuses.
     */
    const STATUS_ACTIVE = 'active';

    const STATUS_SUSPENDED = 'suspended';

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
     * Check if wallet can cover an amount.
     */
    public function canCover(float $amount): bool
    {
        return $this->available_balance >= $amount;
    }
}
