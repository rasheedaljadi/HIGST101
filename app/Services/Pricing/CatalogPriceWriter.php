<?php

namespace App\Services\Pricing;

use App\Enums\PricingTrigger;
use App\Models\HigestCalculatedPriceHistory;
use App\Models\HigestPricingRule;
use App\Models\HigestProductPriceOverride;
use App\Services\Pricing\DTO\PricingCalculationResult;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Webkul\Attribute\Models\Attribute;
use Webkul\Product\Helpers\Indexers\Flat as FlatIndexer;
use Webkul\Product\Helpers\Indexers\Price as PriceIndexer;
use Webkul\Product\Models\Product;
use Webkul\Product\Models\ProductAttributeValue;

/**
 * Writes calculated selling prices to Bagisto EAV attributes and
 * records domain price calculation history logs.
 */
class CatalogPriceWriter
{
    public function __construct(
        protected PriceIndexer $priceIndexer,
        protected FlatIndexer $flatIndexer,
    ) {}

    /**
     * Write calculated selling price to Bagisto EAV system.
     */
    public function write(
        int $variantId,
        int $productId,
        PricingCalculationResult $result,
        ?float $specialPrice,
        ?float $oldAcquisitionCost,
        ?HigestPricingRule $rule,
        PricingTrigger|string $trigger,
    ): void {
        $triggerValue = $trigger instanceof PricingTrigger ? $trigger->value : (string) $trigger;

        // Get old selling price for audit history
        $oldSellingPrice = $this->getCurrentPrice($variantId);

        // Check if variant has a manual price override active
        $override = HigestProductPriceOverride::where('variant_id', $variantId)->first();

        if ($override !== null && $override->isManual()) {
            $appliedSellingPrice = (float) $override->manual_price;
            $appliedSpecialPrice = $override->manual_special_price !== null ? (float) $override->manual_special_price : null;
            $triggerValue = 'manual_override';

            $breakdown = array_merge($result->breakdown, [
                'manual_override' => [
                    'pricing_mode' => 'MANUAL',
                    'manual_price' => $appliedSellingPrice,
                    'manual_special_price' => $appliedSpecialPrice,
                    'theoretical_selling_price' => $result->sellingPrice,
                    'theoretical_special_price' => $result->specialPrice,
                    'override_reason' => $override->override_reason,
                    'updated_by' => $override->updated_by,
                ],
            ]);
        } else {
            $appliedSellingPrice = $result->sellingPrice;
            $appliedSpecialPrice = $specialPrice ?? $result->specialPrice;
            $breakdown = $result->breakdown;
        }

        // Write price to EAV (regular selling price)
        $this->writeEavPrice($variantId, $appliedSellingPrice);

        // Write special_price (transformed promotional sale price if source discount exists)
        $this->writeEavSpecialPrice($variantId, $appliedSpecialPrice);

        // Record domain price calculation history
        $this->recordHistory(
            variantId: $variantId,
            productId: $productId,
            oldAcquisitionCost: $oldAcquisitionCost,
            newAcquisitionCost: $result->acquisitionCost,
            oldSellingPrice: $oldSellingPrice,
            newSellingPrice: $appliedSellingPrice,
            rule: $rule,
            breakdown: $breakdown,
            trigger: $triggerValue,
        );

        Log::channel('aliexpress')->info('CatalogPriceWriter: price written', [
            'variant_id' => $variantId,
            'product_id' => $productId,
            'selling_price' => $appliedSellingPrice,
            'special_price' => $appliedSpecialPrice,
            'acquisition_cost' => $result->acquisitionCost,
            'trigger' => $triggerValue,
        ]);
    }

    /**
     * Trigger re-indexing for product and parent.
     */
    public function reindex(int $productId): void
    {
        $product = Product::with([
            'variants',
            'attribute_family',
            'attribute_values',
            'variants.attribute_family',
            'variants.attribute_values',
            'price_indices',
            'inventory_indices',
            'variants.price_indices',
            'variants.inventory_indices',
            'customer_group_prices',
            'variants.customer_group_prices',
            'catalog_rule_prices',
            'variants.catalog_rule_prices',
        ])->find($productId);

        if ($product === null) {
            return;
        }

        $toIndex = [$product];

        if ($product->parent_id) {
            $parent = Product::with([
                'variants',
                'attribute_family',
                'attribute_values',
                'variants.attribute_family',
                'variants.attribute_values',
                'price_indices',
                'inventory_indices',
                'variants.price_indices',
                'variants.inventory_indices',
                'customer_group_prices',
                'variants.customer_group_prices',
                'catalog_rule_prices',
                'variants.catalog_rule_prices',
            ])->find($product->parent_id);

            if ($parent !== null) {
                $toIndex[] = $parent;
            }
        }

        $this->priceIndexer->reindexBatch($toIndex);

        foreach ($toIndex as $indexable) {
            $this->flatIndexer->refresh($indexable);
        }
    }

    protected function getCurrentPrice(int $variantId): ?float
    {
        $priceAttributeId = $this->priceAttributeId();
        if ($priceAttributeId === null) {
            return null;
        }

        $value = ProductAttributeValue::where('product_id', $variantId)
            ->where('attribute_id', $priceAttributeId)
            ->value('float_value');

        return $value !== null ? (float) $value : null;
    }

    protected function writeEavPrice(int $variantId, float $price): void
    {
        $attributeId = $this->priceAttributeId();
        if ($attributeId === null) {
            return;
        }

        $uniqueId = "||{$variantId}|{$attributeId}";

        ProductAttributeValue::updateOrCreate(
            [
                'product_id' => $variantId,
                'attribute_id' => $attributeId,
                'channel' => null,
                'locale' => null,
            ],
            [
                'float_value' => $price,
                'unique_id' => $uniqueId,
            ]
        );
    }

    protected function writeEavSpecialPrice(int $variantId, ?float $specialPrice): void
    {
        $attributeId = $this->specialPriceAttributeId();
        if ($attributeId === null) {
            return;
        }

        if ($specialPrice === null) {
            ProductAttributeValue::where('product_id', $variantId)
                ->where('attribute_id', $attributeId)
                ->delete();

            return;
        }

        $uniqueId = "||{$variantId}|{$attributeId}";

        ProductAttributeValue::updateOrCreate(
            [
                'product_id' => $variantId,
                'attribute_id' => $attributeId,
                'channel' => null,
                'locale' => null,
            ],
            [
                'float_value' => $specialPrice,
                'unique_id' => $uniqueId,
            ]
        );
    }

    protected function recordHistory(
        int $variantId,
        int $productId,
        ?float $oldAcquisitionCost,
        float $newAcquisitionCost,
        ?float $oldSellingPrice,
        float $newSellingPrice,
        ?HigestPricingRule $rule,
        array $breakdown,
        string $trigger,
    ): void {
        HigestCalculatedPriceHistory::create([
            'variant_id' => $variantId,
            'product_id' => $productId,
            'old_acquisition_cost' => $oldAcquisitionCost,
            'new_acquisition_cost' => $newAcquisitionCost,
            'old_selling_price' => $oldSellingPrice,
            'new_selling_price' => $newSellingPrice,
            'pricing_rule_id' => $rule?->id,
            'rule_version' => $rule?->version,
            'rule_snapshot' => $rule?->toSnapshot(),
            'calculation_breakdown' => $breakdown,
            'trigger' => $trigger,
            'created_at' => Carbon::now(),
        ]);
    }

    protected function priceAttributeId(): ?int
    {
        static $id;
        if ($id === null) {
            $id = (int) (Attribute::where('code', 'price')->value('id') ?? 0);
        }

        return $id > 0 ? $id : null;
    }

    protected function specialPriceAttributeId(): ?int
    {
        static $id;
        if ($id === null) {
            $id = (int) (Attribute::where('code', 'special_price')->value('id') ?? 0);
        }

        return $id > 0 ? $id : null;
    }
}
