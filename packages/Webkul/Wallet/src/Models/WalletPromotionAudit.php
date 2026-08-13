<?php

namespace Webkul\Wallet\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Webkul\User\Models\AdminProxy;
use Webkul\Wallet\Contracts\WalletPromotionAudit as WalletPromotionAuditContract;

class WalletPromotionAudit extends Model implements WalletPromotionAuditContract
{
    public $timestamps = false;

    protected $table = 'wallet_promotion_audits';

    protected $fillable = [
        'promotion_id',
        'admin_user_id',
        'action',
        'old_values',
        'new_values',
        'ip_address',
        'created_at',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
        'created_at' => 'datetime',
    ];

    public const ACTION_CREATED = 'created';

    public const ACTION_UPDATED = 'updated';

    public const ACTION_ACTIVATED = 'activated';

    public const ACTION_DEACTIVATED = 'deactivated';

    public const ACTION_ARCHIVED = 'archived';

    public const ACTION_MANUAL_ADJUSTMENT = 'manual_adjustment';

    public function promotion(): BelongsTo
    {
        return $this->belongsTo(WalletPromotionProxy::modelClass(), 'promotion_id');
    }

    public function adminUser(): BelongsTo
    {
        return $this->belongsTo(AdminProxy::modelClass(), 'admin_user_id');
    }
}
