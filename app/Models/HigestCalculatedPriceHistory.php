<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Webkul\Product\Models\ProductProxy;

/**
 * First-class domain entity tracking calculated price history and pipeline breakdown.
 *
 * @property int $id
 * @property int $variant_id
 * @property int $product_id
 * @property float|null $old_acquisition_cost
 * @property float $new_acquisition_cost
 * @property float|null $old_selling_price
 * @property float $new_selling_price
 * @property int|null $pricing_rule_id
 * @property int|null $rule_version
 * @property array|null $rule_snapshot
 * @property array|null $calculation_breakdown
 * @property string $trigger 'import', 'sync', 'rule_change', 'manual', 'migration'
 * @property Carbon $created_at
 */
class HigestCalculatedPriceHistory extends Model
{
    public $timestamps = false;

    protected $table = 'higest_calculated_price_histories';

    protected $fillable = [
        'variant_id',
        'product_id',
        'old_acquisition_cost',
        'new_acquisition_cost',
        'old_selling_price',
        'new_selling_price',
        'pricing_rule_id',
        'rule_version',
        'rule_snapshot',
        'calculation_breakdown',
        'trigger',
        'created_at',
    ];

    protected $casts = [
        'old_acquisition_cost' => 'decimal:4',
        'new_acquisition_cost' => 'decimal:4',
        'old_selling_price' => 'decimal:4',
        'new_selling_price' => 'decimal:4',
        'rule_version' => 'integer',
        'rule_snapshot' => 'array',
        'calculation_breakdown' => 'array',
        'created_at' => 'datetime',
    ];

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductProxy::modelClass(), 'variant_id');
    }

    public function parentProduct(): BelongsTo
    {
        return $this->belongsTo(ProductProxy::modelClass(), 'product_id');
    }

    public function pricingRule(): BelongsTo
    {
        return $this->belongsTo(HigestPricingRule::class, 'pricing_rule_id');
    }
}
