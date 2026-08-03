<?php

namespace App\Services\Pricing;

use App\Enums\PricingTrigger;
use App\Models\HigestPricingRule;
use App\Models\HigestSourceOffer;
use App\Services\Pricing\DTO\PricingContext;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Orchestrates batch price recalculation across the catalog using variant-centric
 * source offers and the PricingEngine pipeline.
 */
class PriceRecalculationService
{
    public function __construct(
        protected PricingEngine $engine,
        protected PricingRuleResolver $resolver,
        protected CatalogPriceWriter $writer,
    ) {}

    /**
     * Recalculate selling prices for all products affected by a rule change.
     */
    public function recalculateForRule(HigestPricingRule $rule): int
    {
        $query = HigestSourceOffer::query();

        if ($rule->scope === 'product' && $rule->scope_id !== null) {
            $query->where('product_id', $rule->scope_id);
        } elseif ($rule->scope === 'category' && $rule->scope_id !== null) {
            $productIds = DB::table('product_categories')
                ->where('category_id', $rule->scope_id)
                ->pluck('product_id')
                ->toArray();

            if (empty($productIds)) {
                return 0;
            }

            $query->whereIn('product_id', $productIds);
        }

        return $this->recalculateBatch($query, PricingTrigger::RULE_CHANGE);
    }

    /**
     * Recalculate selling prices for ALL variant offers in the catalog.
     */
    public function recalculateAll(PricingTrigger|string $trigger = PricingTrigger::MIGRATION): int
    {
        return $this->recalculateBatch(HigestSourceOffer::query(), $trigger);
    }

    /**
     * Recalculate selling price for a single variant offer.
     */
    public function recalculateOne(
        int $variantId,
        PricingTrigger|string $trigger = PricingTrigger::MANUAL,
    ): ?float {
        $offer = HigestSourceOffer::forVariant($variantId)->first();

        if ($offer === null) {
            Log::channel('aliexpress')->warning('PriceRecalculationService: no source offer found', [
                'variant_id' => $variantId,
            ]);

            return null;
        }

        $categoryId = $this->resolver->resolveCategoryId($offer->product_id);
        $rule = $this->resolver->resolve($offer->product_id, $categoryId);

        if ($rule === null) {
            return null;
        }

        $context = new PricingContext(
            sourceProvider: $offer->source_provider,
            currency: $offer->source_currency,
            acquisitionOriginalCost: $offer->acquisition_original_cost !== null ? (float) $offer->acquisition_original_cost : null,
        );
        $result = $this->engine->calculate((float) $offer->acquisition_cost, $rule, $context);

        $this->writer->write(
            variantId: $variantId,
            productId: $offer->product_id,
            result: $result,
            specialPrice: $result->specialPrice,
            oldAcquisitionCost: (float) $offer->acquisition_cost,
            rule: $rule,
            trigger: $trigger,
        );

        $this->writer->reindex($offer->product_id);

        return $result->sellingPrice;
    }

    /**
     * Process a batch of source offers and recalculate selling prices.
     */
    protected function recalculateBatch($query, PricingTrigger|string $trigger): int
    {
        $this->syncMissingCatalogOffers();

        $count = 0;
        $reindexProductIds = [];

        $query->orderBy('id')->chunk(100, function ($offers) use (&$count, &$reindexProductIds, $trigger) {
            foreach ($offers as $offer) {
                $categoryId = $this->resolver->resolveCategoryId($offer->product_id);
                $rule = $this->resolver->resolve($offer->product_id, $categoryId);

                if ($rule === null) {
                    continue;
                }

                $context = new PricingContext(
                    sourceProvider: $offer->source_provider,
                    currency: $offer->source_currency,
                    acquisitionOriginalCost: $offer->acquisition_original_cost !== null ? (float) $offer->acquisition_original_cost : null,
                );
                $result = $this->engine->calculate((float) $offer->acquisition_cost, $rule, $context);

                $this->writer->write(
                    variantId: $offer->variant_id,
                    productId: $offer->product_id,
                    result: $result,
                    specialPrice: $result->specialPrice,
                    oldAcquisitionCost: (float) $offer->acquisition_cost,
                    rule: $rule,
                    trigger: $trigger,
                );

                $reindexProductIds[$offer->product_id] = true;
                $count++;
            }
        });

        foreach (array_keys($reindexProductIds) as $productId) {
            $this->writer->reindex($productId);
        }

        Log::channel('aliexpress')->info('PriceRecalculationService: batch recalculation complete', [
            'count' => $count,
            'products' => count($reindexProductIds),
            'trigger' => $trigger instanceof PricingTrigger ? $trigger->value : $trigger,
        ]);

        return $count;
    }

    /**
     * Ensure all catalog products have a corresponding source offer record.
     */
    public function syncMissingCatalogOffers(): int
    {
        $missingProducts = DB::table('products as p')
            ->leftJoin('higest_source_offers as hso', 'p.id', '=', 'hso.variant_id')
            ->leftJoin('product_flat as pf', function ($join) {
                $join->on('p.id', '=', 'pf.product_id')
                    ->where('pf.channel', '=', 'default')
                    ->where('pf.locale', '=', 'ar');
            })
            ->whereNull('hso.id')
            ->where('p.type', '!=', 'configurable')
            ->select(
                'p.id as variant_id',
                DB::raw('COALESCE(p.parent_id, p.id) as product_id'),
                'pf.price',
                'pf.special_price'
            )
            ->groupBy('p.id')
            ->get();

        if ($missingProducts->isEmpty()) {
            return 0;
        }

        $now = Carbon::now();
        $insertData = [];
        $batchSize = 1000;
        $totalInserted = 0;

        foreach ($missingProducts as $prod) {
            $price = (float) ($prod->price ?? 0);
            $specialPrice = $prod->special_price !== null ? (float) $prod->special_price : null;

            if ($specialPrice !== null && $specialPrice > 0 && $specialPrice < $price) {
                $acqCost = $specialPrice;
                $acqOrigCost = $price;
            } else {
                $acqCost = $price > 0 ? $price : 0.00;
                $acqOrigCost = null;
            }

            $insertData[] = [
                'variant_id' => $prod->variant_id,
                'product_id' => $prod->product_id,
                'source_provider' => 'aliexpress',
                'source_sku_id' => null,
                'acquisition_cost' => $acqCost,
                'acquisition_original_cost' => $acqOrigCost,
                'source_currency' => 'USD',
                'captured_at' => $now,
                'synced_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ];

            if (count($insertData) >= $batchSize) {
                DB::table('higest_source_offers')->insertOrIgnore($insertData);
                $totalInserted += count($insertData);
                $insertData = [];
            }
        }

        if (! empty($insertData)) {
            DB::table('higest_source_offers')->insertOrIgnore($insertData);
            $totalInserted += count($insertData);
        }

        return $totalInserted;
    }
}
