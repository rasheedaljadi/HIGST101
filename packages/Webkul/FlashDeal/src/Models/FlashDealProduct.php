<?php

namespace Webkul\FlashDeal\Models;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Webkul\FlashDeal\Contracts\FlashDealProduct as FlashDealProductContract;
use Webkul\FlashDeal\Database\Factories\FlashDealProductFactory;
use Webkul\Product\Models\ProductProxy;

class FlashDealProduct extends Model implements FlashDealProductContract
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'flash_deal_products';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'flash_deal_id',
        'product_id',
        'flash_price',
        'allocation_qty',
        'sold_qty',
        'offer_end_time',
        'badge',
        'sort_order',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'flash_price' => 'float',
        'allocation_qty' => 'integer',
        'sold_qty' => 'integer',
        'offer_end_time' => 'datetime',
    ];

    /**
     * Get effective end time (individual offer_end_time or deal ends_at fallback).
     */
    public function getEffectiveEndTimeAttribute()
    {
        return $this->offer_end_time ?? $this->deal?->ends_at;
    }

    /**
     * Get the flash deal that owns the product allocation.
     */
    public function deal(): BelongsTo
    {
        return $this->belongsTo(FlashDealProxy::modelClass(), 'flash_deal_id');
    }

    /**
     * Get the product associated with the flash deal.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(ProductProxy::modelClass(), 'product_id');
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): Factory
    {
        return FlashDealProductFactory::new();
    }
}
