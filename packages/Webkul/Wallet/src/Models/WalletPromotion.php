<?php

namespace Webkul\Wallet\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Webkul\User\Models\AdminProxy;
use Webkul\Wallet\Contracts\WalletPromotion as WalletPromotionContract;
use Webkul\Wallet\Models\Traits\ProhibitsPhysicalDeletion;

class WalletPromotion extends Model implements WalletPromotionContract
{
    use ProhibitsPhysicalDeletion;

    protected $table = 'wallet_promotions';

    protected $fillable = [
        'name',
        'description',
        'type',
        'status',
        'action_type',
        'reward_value',
        'max_reward_amount',
        'min_spend_amount',
        'grant_validity_days',
        'total_budget',
        'total_allocated',
        'usage_limit',
        'usage_per_customer',
        'times_used',
        'starts_from',
        'ends_till',
        'conditions',
        'priority',
        'end_other_promotions',
        'created_by_admin_id',
    ];

    protected $casts = [
        'reward_value' => 'string',
        'max_reward_amount' => 'string',
        'min_spend_amount' => 'string',
        'total_budget' => 'string',
        'total_allocated' => 'string',
        'conditions' => 'array',
        'priority' => 'integer',
        'end_other_promotions' => 'boolean',
        'starts_from' => 'datetime',
        'ends_till' => 'datetime',
    ];

    public const TYPE_WELCOME_BONUS = 'welcome_bonus';

    public const TYPE_TOPUP_BONUS = 'topup_bonus';

    public const TYPE_ORDER_SUBTOTAL_CASHBACK = 'order_subtotal_cashback';

    public const TYPE_ORDER_CONDITIONAL_CASHBACK = 'order_conditional_cashback';

    public const STATUS_DRAFT = 'draft';

    public const STATUS_ACTIVE = 'active';

    public const STATUS_INACTIVE = 'inactive';

    public const STATUS_ARCHIVED = 'archived';

    public const ACTION_FIXED = 'fixed';

    public const ACTION_PERCENTAGE = 'percentage';

    public function usages(): HasMany
    {
        return $this->hasMany(WalletPromotionUsageProxy::modelClass(), 'promotion_id');
    }

    public function grants(): HasMany
    {
        return $this->hasMany(WalletPromotionGrantProxy::modelClass(), 'promotion_id');
    }

    public function createdByAdmin(): BelongsTo
    {
        return $this->belongsTo(AdminProxy::modelClass(), 'created_by_admin_id');
    }
}
