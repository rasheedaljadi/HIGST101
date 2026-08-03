<?php

namespace Webkul\Wallet\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Webkul\Wallet\Contracts\WalletWithdrawalRequest as WalletWithdrawalRequestContract;

class WalletWithdrawalRequest extends Model implements WalletWithdrawalRequestContract
{
    protected $table = 'wallet_withdrawal_requests';

    protected $fillable = [
        'wallet_id',
        'amount',
        'currency_code',
        'status',
        'bank_details',
        'admin_user_id',
        'bank_transaction_reference',
        'transferred_at',
        'admin_notes',
        'rejected_at',
        'rejection_reason',
    ];

    protected $casts = [
        'amount' => 'float',
        'bank_details' => 'encrypted:array', // Encrypted + JSON (Sprint 0.5: ملاحظة 3)
        'transferred_at' => 'datetime',
        'rejected_at' => 'datetime',
    ];

    const STATUS_PENDING = 'pending';

    const STATUS_COMPLETED = 'completed';

    const STATUS_REJECTED = 'rejected';

    public function wallet(): BelongsTo
    {
        return $this->belongsTo(WalletAccountProxy::modelClass(), 'wallet_id');
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    /**
     * Get masked IBAN for display (e.g. SA**...7519).
     */
    public function getMaskedIbanAttribute(): string
    {
        $iban = $this->bank_details['iban'] ?? '';
        if (strlen($iban) <= 6) {
            return $iban;
        }

        return substr($iban, 0, 2).str_repeat('*', strlen($iban) - 6).substr($iban, -4);
    }
}
