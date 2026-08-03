<?php

namespace Webkul\Wallet\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Webkul\Sales\Models\RefundProxy;
use Webkul\Wallet\Contracts\WalletPendingCredit as WalletPendingCreditContract;

class WalletPendingCredit extends Model implements WalletPendingCreditContract
{
    protected $table = 'wallet_pending_credits';

    protected $fillable = [
        'wallet_id',
        'refund_id',
        'amount',
        'status',
        'attempts',
        'error_message',
        'last_attempt_at',
    ];

    protected $casts = [
        'amount' => 'float',
        'attempts' => 'integer',
        'last_attempt_at' => 'datetime',
    ];

    const STATUS_PENDING = 'pending';

    const STATUS_PROCESSING = 'processing';

    const STATUS_COMPLETED = 'completed';

    const STATUS_FAILED = 'failed';

    public function wallet(): BelongsTo
    {
        return $this->belongsTo(WalletAccountProxy::modelClass(), 'wallet_id');
    }

    public function refund(): BelongsTo
    {
        return $this->belongsTo(RefundProxy::modelClass(), 'refund_id');
    }
}
