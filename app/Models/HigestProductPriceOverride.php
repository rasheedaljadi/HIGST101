<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Webkul\Product\Models\ProductProxy;

/**
 * Dedicated Catalog Price Override entity for variant-specific manual price overrides.
 *
 * Allows merchants to switch variants between 'AUTO' (Engine calculated) and 'MANUAL' (Merchant override)
 * without corrupting external acquisition cost facts (HigestSourceOffer).
 *
 * @property int $id
 * @property int $variant_id
 * @property int $product_id
 * @property string $pricing_mode 'AUTO' or 'MANUAL'
 * @property float|null $manual_price
 * @property float|null $manual_special_price
 * @property string|null $override_reason
 * @property int|null $updated_by
 */
class HigestProductPriceOverride extends Model
{
    protected $table = 'higest_product_price_overrides';

    protected $fillable = [
        'variant_id',
        'product_id',
        'pricing_mode',
        'manual_price',
        'manual_special_price',
        'override_reason',
        'updated_by',
    ];

    protected $attributes = [
        'pricing_mode' => 'AUTO',
    ];

    protected $casts = [
        'variant_id' => 'integer',
        'product_id' => 'integer',
        'manual_price' => 'decimal:4',
        'manual_special_price' => 'decimal:4',
        'updated_by' => 'integer',
    ];

    /**
     * Get variant product.
     */
    public function variant()
    {
        return $this->belongsTo(ProductProxy::modelClass(), 'variant_id');
    }

    /**
     * Get parent product.
     */
    public function product()
    {
        return $this->belongsTo(ProductProxy::modelClass(), 'product_id');
    }

    /**
     * Helper to check if manual override is active.
     */
    public function isManual(): bool
    {
        return $this->pricing_mode === 'MANUAL';
    }
}
