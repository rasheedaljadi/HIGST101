<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Read model projection mapping an external supplier's SKU ID to a local variant.
 *
 * @property int $id
 * @property int $product_id
 * @property int $variant_product_id
 * @property string $provider
 * @property string $external_sku_id
 * @property string $external_product_id
 * @property string|null $external_variant_version
 * @property int $projection_version
 * @property \Illuminate\Support\Carbon|null $provider_updated_at
 */
class ExternalVariantProjection extends Model
{
    protected $table = 'external_variant_projections';

    protected $fillable = [
        'product_id',
        'variant_product_id',
        'provider',
        'external_sku_id',
        'external_product_id',
        'external_variant_version',
        'projection_version',
        'provider_updated_at',
    ];

    protected $casts = [
        'product_id' => 'integer',
        'variant_product_id' => 'integer',
        'projection_version' => 'integer',
        'provider_updated_at' => 'datetime',
    ];

    /**
     * Get the configurable parent product.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(\Webkul\Product\Models\Product::class, 'product_id');
    }

    /**
     * Get the variant product.
     */
    public function variantProduct(): BelongsTo
    {
        return $this->belongsTo(\Webkul\Product\Models\Product::class, 'variant_product_id');
    }
}
