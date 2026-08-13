<?php

namespace Webkul\Wallet\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Webkul\Sales\Models\InvoiceProxy;
use Webkul\Sales\Models\OrderItemProxy;
use Webkul\Sales\Models\OrderProxy;
use Webkul\Wallet\Contracts\WalletPromotionOrderItemAllocation as WalletPromotionOrderItemAllocationContract;

class WalletPromotionOrderItemAllocation extends Model implements WalletPromotionOrderItemAllocationContract
{
    protected $table = 'wallet_promotion_order_item_allocations';

    protected $fillable = [
        'usage_id',
        'grant_id',
        'order_id',
        'invoice_id',
        'order_item_id',
        'item_sku',
        'item_eligible_price',
        'allocated_reward',
        'base_allocated_reward',
        'reversed_reward',
        'status',
    ];

    protected $casts = [
        'item_eligible_price' => 'string',
        'allocated_reward' => 'string',
        'base_allocated_reward' => 'string',
        'reversed_reward' => 'string',
    ];

    public const STATUS_ALLOCATED = 'allocated';

    public const STATUS_PARTIALLY_REVERSED = 'partially_reversed';

    public const STATUS_FULLY_REVERSED = 'fully_reversed';

    public function usage(): BelongsTo
    {
        return $this->belongsTo(WalletPromotionUsageProxy::modelClass(), 'usage_id');
    }

    public function grant(): BelongsTo
    {
        return $this->belongsTo(WalletPromotionGrantProxy::modelClass(), 'grant_id');
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(OrderProxy::modelClass(), 'order_id');
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(InvoiceProxy::modelClass(), 'invoice_id');
    }

    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(OrderItemProxy::modelClass(), 'order_item_id');
    }
}
