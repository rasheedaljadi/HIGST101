<?php

namespace Webkul\Fulfillment\Listeners;

use App\Enums\PricingTrigger;
use App\Models\AliExpressProductImport;
use App\Models\AliExpressSetting;
use App\Models\HigestSourceOffer;
use App\Services\Pricing\CatalogPriceWriter;
use App\Services\Pricing\DTO\PricingContext;
use App\Services\Pricing\PricingEngine;
use App\Services\Pricing\PricingRuleResolver;
use App\Services\Pricing\SourceOfferRecorder;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Spatie\ResponseCache\Facades\ResponseCache;
use Throwable;
use Webkul\Attribute\Models\Attribute;
use Webkul\Fulfillment\DataObjects\ProjectionDecision;
use Webkul\Fulfillment\Services\Domain\ProjectionVersionGuard;
use Webkul\Product\Helpers\Indexers\Flat as FlatIndexer;
use Webkul\Product\Helpers\Indexers\Price as PriceIndexer;
use Webkul\Product\Models\Product;

class CatalogProjectionListener
{
    /**
     * Create a new listener instance.
     */
    public function __construct(
        protected PriceIndexer $priceIndexer,
        protected FlatIndexer $flatIndexer,
        protected PricingEngine $pricingEngine,
        protected PricingRuleResolver $pricingRuleResolver,
        protected SourceOfferRecorder $sourceOfferRecorder,
        protected CatalogPriceWriter $catalogPriceWriter,
    ) {}

    /**
     * Handle source acquisition price change events.
     */
    public function handle(string $eventName, array $payload, string $correlationId, string $causationId, ?string $outboxEventId = null): void
    {
        $variantId = $payload['variant_id'] ?? null;
        $newPrice = $payload['new_cost'] ?? $payload['new_price'] ?? 0;

        if (! $variantId) {
            return;
        }

        $reindexParentId = DB::transaction(function () use ($variantId, $newPrice, $payload) {
            $projection = DB::table('external_variant_projections')
                ->where('variant_product_id', $variantId)
                ->lockForUpdate()
                ->first();

            if ($projection) {
                $decision = ProjectionVersionGuard::shouldApply($projection, $payload);

                if (! $decision->shouldApply()) {
                    if ($decision->isUnsafeJump()) {
                        Log::channel('aliexpress')->warning('Catalog projection price update flagged as unsafe: '.$decision->reason);

                        // Flag for review & disable variant
                        $attributeId = (int) (Attribute::where('code', 'needs_review')->value('id') ?? 0);
                        if ($attributeId > 0) {
                            $uniqueId = "||{$variantId}|{$attributeId}";
                            DB::table('product_attribute_values')->updateOrInsert(
                                [
                                    'product_id' => $variantId,
                                    'attribute_id' => $attributeId,
                                    'channel' => null,
                                    'locale' => null,
                                ],
                                [
                                    'boolean_value' => true,
                                    'unique_id' => $uniqueId,
                                ]
                            );
                        }

                        $statusAttrId = (int) (Attribute::where('code', 'status')->value('id') ?? 0);
                        if ($statusAttrId > 0) {
                            $uniqueId = "||{$variantId}|{$statusAttrId}";
                            DB::table('product_attribute_values')->updateOrInsert(
                                [
                                    'product_id' => $variantId,
                                    'attribute_id' => $statusAttrId,
                                    'channel' => null,
                                    'locale' => null,
                                ],
                                [
                                    'boolean_value' => false,
                                    'unique_id' => $uniqueId,
                                ]
                            );
                        }

                        Log::channel('aliexpress')->info('Metric counter incremented: [projection_events_review]');

                        return null;
                    }

                    if ($decision->status === ProjectionDecision::STATUS_STALE) {
                        Log::channel('aliexpress')->info('Metric counter incremented: [projection_events_stale]');
                    } elseif ($decision->status === ProjectionDecision::STATUS_REPLAY) {
                        Log::channel('aliexpress')->info('Metric counter incremented: [projection_events_replayed]');
                    }

                    return null;
                }
            }

            Log::channel('aliexpress')->info("Catalog projection sync update for Variant ID {$variantId}: Acquisition Cost={$newPrice}");

            $variantProduct = Product::find($variantId);
            $parentId = $variantProduct?->parent_id ?? $variantId;

            // Load existing offer to get real old cost and fallback original cost
            $existingOffer = HigestSourceOffer::forVariant($variantId, 'aliexpress')->first();
            $oldAcquisitionCost = isset($payload['old_cost'])
                ? (float) $payload['old_cost']
                : ($existingOffer?->acquisition_cost !== null ? (float) $existingOffer->acquisition_cost : null);

            $newOriginalCost = isset($payload['new_original_cost'])
                ? (float) $payload['new_original_cost']
                : ($payload['original_price'] ?? ($existingOffer?->acquisition_original_cost !== null ? (float) $existingOffer->acquisition_original_cost : null));

            // 1. Record source offer update & track history (C2, C4)
            $offer = $this->sourceOfferRecorder->record(
                variantId: $variantId,
                productId: $parentId,
                acquisitionCost: (float) $newPrice,
                acquisitionOriginalCost: $newOriginalCost,
                sourceCurrency: $payload['currency'] ?? 'USD',
                sourceSkuId: $payload['supplier_sku_id'] ?? null,
                sourceProvider: 'aliexpress',
                trigger: 'sync',
            );

            // 2. Resolve rule & classify change event
            $categoryId = $this->pricingRuleResolver->resolveCategoryId($parentId);
            $rule = $this->pricingRuleResolver->resolve($parentId, $categoryId);

            $costChanged = ($oldAcquisitionCost === null) || ($oldAcquisitionCost !== (float) $newPrice);
            $onlyOriginalCostChanged = ! $costChanged && ($existingOffer?->acquisition_original_cost !== $newOriginalCost);
            $policy = $rule?->source_discount_policy?->value ?? 'PASS_TO_CUSTOMER';

            // Event Classification Optimization: If ONLY original cost changed under ABSORB_BY_HIGEST,
            // source offer facts are updated above, but storefront customer price is unaffected.
            // Skip unnecessary catalog writes & reindexing.
            if ($onlyOriginalCostChanged && $policy === 'ABSORB_BY_HIGEST') {
                Log::channel('aliexpress')->info('CatalogProjectionListener: original cost changed under ABSORB_BY_HIGEST. Source facts updated; catalog write skipped.', [
                    'variant_id' => $variantId,
                    'old_original_cost' => $existingOffer?->acquisition_original_cost,
                    'new_original_cost' => $newOriginalCost,
                ]);

                DB::table('external_variant_projections')
                    ->where('variant_product_id', $variantId)
                    ->update([
                        'external_variant_version' => $payload['external_variant_version'] ?? ($projection ? $projection->external_variant_version : null),
                        'provider_updated_at' => isset($payload['provider_updated_at']) ? new Carbon($payload['provider_updated_at']) : ($projection ? $projection->provider_updated_at : null),
                        'updated_at' => now(),
                    ]);

                return null;
            }

            $settings = AliExpressSetting::current();
            $shippingCost = 0.0;
            if ($settings->include_shipping_in_price) {
                $aeImport = AliExpressProductImport::where('product_id', $parentId)->first();
                if ($aeImport && $aeImport->base_shipping_cost !== null) {
                    $shippingCost = (float) $aeImport->base_shipping_cost;
                }
            }

            if ($rule !== null) {
                $context = new PricingContext(
                    sourceProvider: 'aliexpress',
                    currency: $offer->source_currency,
                    acquisitionOriginalCost: $offer->acquisition_original_cost !== null ? (float) $offer->acquisition_original_cost : null,
                    shippingCost: $shippingCost,
                );
                $result = $this->pricingEngine->calculate((float) $newPrice, $rule, $context);

                $trigger = $onlyOriginalCostChanged ? 'sync_source_discount_change' : PricingTrigger::SYNC;

                // 3. Write selling price to Bagisto EAV + record price history
                $this->catalogPriceWriter->write(
                    variantId: $variantId,
                    productId: $parentId,
                    result: $result,
                    specialPrice: $result->specialPrice,
                    oldAcquisitionCost: $oldAcquisitionCost,
                    rule: $rule,
                    trigger: $trigger,
                );
            } else {
                Log::channel('aliexpress')->warning('CatalogProjectionListener: no pricing rule found during sync', [
                    'variant_id' => $variantId,
                    'acquisition_cost' => $newPrice,
                ]);
            }

            // Update projection table
            DB::table('external_variant_projections')
                ->where('variant_product_id', $variantId)
                ->update([
                    'external_variant_version' => $payload['external_variant_version'] ?? ($projection ? $projection->external_variant_version : null),
                    'provider_updated_at' => isset($payload['provider_updated_at']) ? new Carbon($payload['provider_updated_at']) : ($projection ? $projection->provider_updated_at : null),
                    'updated_at' => now(),
                ]);

            Log::channel('aliexpress')->info('Metric counter incremented: [projection_events_processed]');

            return $parentId;
        });

        // Reindex price and flat table OUTSIDE transaction (C6)
        if ($reindexParentId) {
            $this->catalogPriceWriter->reindex($reindexParentId);

            try {
                if (class_exists(ResponseCache::class)) {
                    ResponseCache::clear();
                }
            } catch (Throwable $e) {
                // Ignore silent cache clear errors
            }
        }
    }
}
