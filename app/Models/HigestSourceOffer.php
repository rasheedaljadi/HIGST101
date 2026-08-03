<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Webkul\Product\Models\ProductProxy;

/**
 * Represents a purchasable product offer from an external sourcing source.
 *
 * Simple products are treated as single-variant products where variant_id = product_id.
 * For configurable products, variant_id points to the child variant product_id,
 * and product_id points to the parent configurable product ID (for quick index lookup).
 *
 * Example:
 * AliExpress offer for variant X at acquisition cost $15.00.
 *
 * @property int $id
 * @property int $variant_id
 * @property int $product_id
 * @property string $source_provider
 * @property string|null $source_sku_id
 * @property float $acquisition_cost
 * @property float|null $acquisition_original_cost
 * @property string $source_currency
 * @property Carbon $captured_at
 * @property Carbon|null $synced_at
 */
class HigestSourceOffer extends Model
{
    protected $table = 'higest_source_offers';

    protected $fillable = [
        'variant_id',
        'product_id',
        'source_provider',
        'source_sku_id',
        'acquisition_cost',
        'acquisition_original_cost',
        'source_currency',
        'captured_at',
        'synced_at',
    ];

    protected $casts = [
        'acquisition_cost' => 'decimal:4',
        'acquisition_original_cost' => 'decimal:4',
        'captured_at' => 'datetime',
        'synced_at' => 'datetime',
    ];

    /**
     * Get the variant product model.
     */
    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductProxy::modelClass(), 'variant_id');
    }

    /**
     * Get the parent product model.
     */
    public function parentProduct(): BelongsTo
    {
        return $this->belongsTo(ProductProxy::modelClass(), 'product_id');
    }

    /**
     * Get offer acquisition cost history entries.
     */
    public function histories(): HasMany
    {
        return $this->hasMany(HigestSourceOfferHistory::class, 'source_offer_id');
    }

    /**
     * Scope to find offer by variant_id and source_provider.
     */
    public function scopeForVariant($query, int $variantId, string $sourceProvider = 'aliexpress')
    {
        return $query->where('variant_id', $variantId)
            ->where('source_provider', $sourceProvider);
    }

    /**
     * Scope to find all variant offers for a parent product.
     */
    public function scopeForParentProduct($query, int $productId, string $sourceProvider = 'aliexpress')
    {
        return $query->where('product_id', $productId)
            ->where('source_provider', $sourceProvider);
    }
}
