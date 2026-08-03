<?php

namespace Webkul\Wallet\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Webkul\Wallet\Contracts\WalletTransaction as WalletTransactionContract;

class WalletTransaction extends Model implements WalletTransactionContract
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'wallet_transactions';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'wallet_id',
        'type',
        'direction',
        'amount',
        'running_balance',
        'description',
        'reference_type',
        'reference_id',
        'reference_transaction_id',
        'created_by_type',
        'created_by_id',
        'meta',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'amount' => 'float',
        'running_balance' => 'float',
        'meta' => 'array',
    ];

    /**
     * Transaction types — Sprint 0.5 Lifecycle Matrix.
     */
    const TYPE_CREDIT_TOPUP = 'CREDIT_TOPUP';

    const TYPE_CREDIT_REFUND = 'CREDIT_REFUND';

    const TYPE_CREDIT_CANCEL = 'CREDIT_CANCEL';

    const TYPE_RELEASE_PAYMENT = 'RELEASE_PAYMENT';

    const TYPE_DEBIT_PAYMENT = 'DEBIT_PAYMENT';

    const TYPE_HOLD_WITHDRAWAL = 'HOLD_WITHDRAWAL';

    const TYPE_DEBIT_WITHDRAWAL = 'DEBIT_WITHDRAWAL';

    const TYPE_RELEASE_HOLD = 'RELEASE_HOLD';

    const TYPE_ADJUSTMENT = 'ADJUSTMENT';

    const TYPE_SUSPENSION_FREEZE = 'SUSPENSION_FREEZE';

    const TYPE_SUSPENSION_RELEASE = 'SUSPENSION_RELEASE';

    const TYPE_CREDIT_PROMOTION = 'CREDIT_PROMOTION';

    const TYPE_HOLD_PARTIAL_PAYMENT = 'HOLD_PARTIAL_PAYMENT';

    /**
     * Immutability: prevent any update or delete after creation.
     */
    public static function boot()
    {
        parent::boot();

        static::updating(function () {
            throw new \RuntimeException('WalletTransaction records are immutable and cannot be updated.');
        });

        static::deleting(function () {
            throw new \RuntimeException('WalletTransaction records cannot be deleted. Use ADJUSTMENT type for corrections.');
        });
    }

    /**
     * Get the wallet that owns the transaction.
     */
    public function wallet(): BelongsTo
    {
        return $this->belongsTo(WalletAccountProxy::modelClass(), 'wallet_id');
    }

    /**
     * Get the source entity (Order, Refund, WalletTopUp, WalletWithdrawalRequest).
     */
    public function reference(): MorphTo
    {
        return $this->morphTo('reference');
    }

    /**
     * Get the original transaction this corrects (for ADJUSTMENT types).
     */
    public function referencedTransaction(): BelongsTo
    {
        return $this->belongsTo(self::class, 'reference_transaction_id');
    }

    /**
     * Check if this is a credit transaction.
     */
    public function isCredit(): bool
    {
        return $this->direction === 'credit';
    }

    /**
     * Check if this is a debit transaction.
     */
    public function isDebit(): bool
    {
        return $this->direction === 'debit';
    }
}
