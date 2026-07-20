<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Source reference linking a Bagisto product to the AliExpress product it was
 * imported from, along with the import status for duplicate detection and audit.
 *
 * @property int $id
 * @property string $aliexpress_product_id
 * @property int|null $product_id
 * @property string|null $type
 * @property string $status
 * @property string|null $sku
 * @property int $variants_count
 * @property int $images_count
 * @property string|null $error
 * @property array|null $payload_snapshot
 */
class AliExpressProductImport extends Model
{
    protected $table = 'aliexpress_product_imports';

    protected $fillable = [
        'aliexpress_product_id',
        'product_id',
        'type',
        'status',
        'sku',
        'variants_count',
        'images_count',
        'error',
        'payload_snapshot',
        'base_shipping_cost',
        'shipping_currency',
        'shipping_min_days',
        'shipping_max_days',
        'shipping_company',
        'shipping_tracking',
        'shipping_synced_at',
        'snapshot_hash',
        'supplier_snapshot_version',
        'external_product_version',
        'provider_updated_at',
    ];

    protected function casts(): array
    {
        return [
            'payload_snapshot' => 'array',
            'variants_count' => 'integer',
            'images_count' => 'integer',
            'product_id' => 'integer',
            'base_shipping_cost' => 'decimal:4',
            'shipping_min_days' => 'integer',
            'shipping_max_days' => 'integer',
            'shipping_tracking' => 'boolean',
            'shipping_synced_at' => 'datetime',
            'provider_updated_at' => 'datetime',
        ];
    }

    /**
     * Scope to the import row(s) for a given AliExpress product id.
     */
    public function scopeForAliExpressId(Builder $query, string $aliexpressProductId): Builder
    {
        return $query->where('aliexpress_product_id', $aliexpressProductId);
    }

    /**
     * Scope to successfully imported products.
     */
    public function scopeSuccessful(Builder $query): Builder
    {
        return $query->where('status', 'success');
    }

    /**
     * Get the associated local product.
     */
    public function product(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(\Webkul\Product\Models\Product::class, 'product_id');
    }
}
