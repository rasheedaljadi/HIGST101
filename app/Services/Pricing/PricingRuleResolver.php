<?php

namespace App\Services\Pricing;

use App\Enums\SourceDiscountPolicy;
use App\Models\HigestPricingRule;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Resolves which pricing rule applies for a given product.
 *
 * Resolution hierarchy (most specific wins):
 *   1. Product Override  (scope='product', scope_id=product_id)
 *   2. Category Rule     (scope='category', scope_id=category_id)
 *   3. Global Rule       (scope='global', scope_id=NULL)
 *
 * Within the same scope level, higher `priority` wins.
 * If priority ties, the most recently updated rule wins.
 */
class PricingRuleResolver
{
    /**
     * Resolve the applicable pricing rule for a product.
     *
     * @param  int  $productId  The Bagisto product ID (configurable parent or simple).
     * @param  int|null  $categoryId  The product's primary category ID.
     * @return HigestPricingRule|null The resolved rule, or null if none found.
     */
    public function resolve(int $productId, ?int $categoryId = null): ?HigestPricingRule
    {
        // 1. Product-level override (highest specificity)
        $rule = HigestPricingRule::active()
            ->forProduct($productId)
            ->orderByDesc('priority')
            ->orderByDesc('updated_at')
            ->first();

        if ($rule !== null) {
            return $rule;
        }

        // 2. Category-level rule
        if ($categoryId !== null) {
            $rule = HigestPricingRule::active()
                ->forCategory($categoryId)
                ->orderByDesc('priority')
                ->orderByDesc('updated_at')
                ->first();

            if ($rule !== null) {
                return $rule;
            }
        }

        // 3. Global rule (fallback)
        $rule = HigestPricingRule::active()
            ->global()
            ->orderByDesc('priority')
            ->orderByDesc('updated_at')
            ->first();

        if ($rule === null) {
            Log::channel('aliexpress')->info('PricingRuleResolver: no active pricing rule found in database, defaulting to 0% margin fallback', [
                'product_id' => $productId,
                'category_id' => $categoryId,
            ]);

            return $this->getZeroMarginFallbackRule();
        }

        return $rule;
    }

    /**
     * Get default 0% margin fallback pricing rule.
     */
    public function getZeroMarginFallbackRule(): HigestPricingRule
    {
        return new HigestPricingRule([
            'id' => 0,
            'name' => 'Default 0% Margin Fallback',
            'scope' => 'global',
            'scope_id' => null,
            'type' => 'percentage',
            'value' => 0.0,
            'priority' => 0,
            'version' => 1,
            'status' => true,
            'source_discount_policy' => SourceDiscountPolicy::PASS_TO_CUSTOMER,
        ]);
    }

    /**
     * Resolve the category ID for a Bagisto product.
     *
     * Uses the product's first assigned category. Returns null if the product
     * has no categories.
     */
    public function resolveCategoryId(int $productId): ?int
    {
        $categoryId = DB::table('product_categories')
            ->where('product_id', $productId)
            ->value('category_id');

        return $categoryId !== null ? (int) $categoryId : null;
    }
}
