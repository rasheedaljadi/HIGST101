<?php

namespace App\Models;

use App\Enums\SourceDiscountPolicy;
use Illuminate\Database\Eloquent\Model;

/**
 * Merchant pricing rule with scope hierarchy (global → category → product)
 * and incremental versioning.
 *
 * @property int $id
 * @property string $name
 * @property string $scope 'global', 'category', or 'product'
 * @property int|null $scope_id NULL for global; category_id or product_id
 * @property string $type 'percentage' or 'fixed'
 * @property float $value margin percentage or fixed markup amount
 * @property SourceDiscountPolicy $source_discount_policy
 * @property int $priority
 * @property int $version incremental version tracking
 * @property bool $status
 */
class HigestPricingRule extends Model
{
    protected $table = 'higest_pricing_rules';

    protected $fillable = [
        'name',
        'scope',
        'scope_id',
        'type',
        'value',
        'source_discount_policy',
        'priority',
        'version',
        'status',
    ];

    protected $attributes = [
        'version' => 1,
        'source_discount_policy' => 'PASS_TO_CUSTOMER',
        'priority' => 0,
        'status' => true,
    ];

    protected $casts = [
        'value' => 'decimal:4',
        'source_discount_policy' => SourceDiscountPolicy::class,
        'priority' => 'integer',
        'version' => 'integer',
        'status' => 'boolean',
    ];

    protected static function boot()
    {
        parent::boot();

        static::updating(function (HigestPricingRule $rule) {
            // Automatically increment version when rule properties change
            if ($rule->isDirty(['name', 'scope', 'scope_id', 'type', 'value', 'source_discount_policy', 'priority', 'status'])) {
                $rule->version = $rule->version + 1;
            }
        });
    }

    /**
     * Scope to active rules only.
     */
    public function scopeActive($query)
    {
        return $query->where('status', true);
    }

    /**
     * Scope to rules matching a specific scope and scope_id.
     */
    public function scopeForScope($query, string $scope, ?int $scopeId = null)
    {
        $query->where('scope', $scope);

        if ($scopeId !== null) {
            $query->where('scope_id', $scopeId);
        } else {
            $query->whereNull('scope_id');
        }

        return $query;
    }

    /**
     * Scope to product-level overrides.
     */
    public function scopeForProduct($query, int $productId)
    {
        return $query->where('scope', 'product')
            ->where('scope_id', $productId);
    }

    /**
     * Scope to category-level rules.
     */
    public function scopeForCategory($query, int $categoryId)
    {
        return $query->where('scope', 'category')
            ->where('scope_id', $categoryId);
    }

    /**
     * Scope to global rules.
     */
    public function scopeGlobal($query)
    {
        return $query->where('scope', 'global');
    }

    /**
     * Create a frozen snapshot of this rule for audit logs.
     *
     * @return array<string, mixed>
     */
    public function toSnapshot(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'scope' => $this->scope,
            'scope_id' => $this->scope_id,
            'type' => $this->type,
            'value' => $this->value,
            'source_discount_policy' => $this->source_discount_policy?->value ?? SourceDiscountPolicy::PASS_TO_CUSTOMER->value,
            'priority' => $this->priority,
            'version' => $this->version,
        ];
    }
}
