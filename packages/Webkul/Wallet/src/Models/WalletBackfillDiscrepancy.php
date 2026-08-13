<?php

namespace Webkul\Wallet\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Webkul\Customer\Models\CustomerProxy;
use Webkul\User\Models\AdminProxy;
use Webkul\Wallet\Contracts\WalletBackfillDiscrepancy as WalletBackfillDiscrepancyContract;

class WalletBackfillDiscrepancy extends Model implements WalletBackfillDiscrepancyContract
{
    public $timestamps = false;

    protected $table = 'wallet_backfill_discrepancies';

    protected $fillable = [
        'wallet_id',
        'customer_id',
        'total_balance',
        'historical_promo_credits',
        'total_debits',
        'calculated_cash',
        'calculated_promo',
        'discrepancy_reason',
        'status',
        'resolved_by_admin_id',
        'admin_notes',
        'created_at',
        'resolved_at',
    ];

    protected $casts = [
        'total_balance' => 'string',
        'historical_promo_credits' => 'string',
        'total_debits' => 'string',
        'calculated_cash' => 'string',
        'calculated_promo' => 'string',
        'created_at' => 'datetime',
        'resolved_at' => 'datetime',
    ];

    public const STATUS_PENDING_REVIEW = 'pending_review';

    public const STATUS_RESOLVED = 'resolved';

    public const STATUS_IGNORED = 'ignored';

    public function wallet(): BelongsTo
    {
        return $this->belongsTo(WalletAccountProxy::modelClass(), 'wallet_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(CustomerProxy::modelClass(), 'customer_id');
    }

    public function resolvedByAdmin(): BelongsTo
    {
        return $this->belongsTo(AdminProxy::modelClass(), 'resolved_by_admin_id');
    }
}
