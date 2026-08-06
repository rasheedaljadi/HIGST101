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

    /**
     * Get human-readable localized label for transaction type.
     */
    public function getTypeLabelAttribute(): string
    {
        if (empty($this->type)) {
            if (! empty($this->description)) {
                if (stripos($this->description, 'cashback') !== false) {
                    return 'رصيد ترويجي (مكافأة كاشباك)';
                }

                return $this->description;
            }

            return 'حركة مالية';
        }

        $key = match ($this->type) {
            self::TYPE_CREDIT_TOPUP => 'credit_topup',
            self::TYPE_HOLD_WITHDRAWAL => 'hold_withdrawal',
            self::TYPE_RELEASE_HOLD => 'release_hold',
            self::TYPE_DEBIT_WITHDRAWAL => 'debit_withdrawal',
            self::TYPE_CREDIT_REFUND => 'credit_refund',
            self::TYPE_CREDIT_CANCEL => 'credit_cancel',
            self::TYPE_RELEASE_PAYMENT => 'release_payment',
            self::TYPE_DEBIT_PAYMENT => 'debit_payment',
            self::TYPE_ADJUSTMENT => 'adjustment',
            self::TYPE_SUSPENSION_FREEZE => 'suspension_freeze',
            self::TYPE_SUSPENSION_RELEASE => 'suspension_release',
            self::TYPE_CREDIT_PROMOTION => 'credit_promotion',
            self::TYPE_HOLD_PARTIAL_PAYMENT => 'hold_partial_payment',
            default => null,
        };

        if ($key) {
            $transPath1 = 'wallet::app.admin.wallet.transactions.types.'.$key;
            $transPath2 = 'wallet::app.transactions.types.'.$key;

            $translated = trans($transPath1);
            if ($translated && $translated !== $transPath1) {
                return $translated;
            }

            $translated = trans($transPath2);
            if ($translated && $translated !== $transPath2) {
                return $translated;
            }

            $fallbackMap = [
                'credit_topup' => 'إيداع رصيد',
                'hold_withdrawal' => 'حجز طلب سحب',
                'release_hold' => 'إلغاء حجز (إعادة رصيد)',
                'debit_withdrawal' => 'إتمام سحب بنكي',
                'credit_refund' => 'استرداد رصيد',
                'credit_cancel' => 'إلغاء وإعادة رصيد',
                'release_payment' => 'إلغاء حجز دفع',
                'debit_payment' => 'مشتريات عبر المحفظة',
                'adjustment' => 'تعديل رصيد إداري',
                'suspension_freeze' => 'تجميد رصيد',
                'suspension_release' => 'إلغاء تجميد رصيد',
                'credit_promotion' => 'رصيد ترويجي (مكافأة كاشباك)',
                'hold_partial_payment' => 'حجز دفع جزئي',
            ];

            return $fallbackMap[$key] ?? $this->type;
        }

        return $this->type ?: 'حركة مالية';
    }
}
