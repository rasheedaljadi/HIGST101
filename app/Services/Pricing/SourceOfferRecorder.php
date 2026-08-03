<?php

namespace App\Services\Pricing;

use App\Models\HigestSourceOffer;
use App\Models\HigestSourceOfferHistory;
use Illuminate\Support\Carbon;

/**
 * Variant-centric source offer recorder.
 *
 * Records acquisition costs into higest_source_offers and tracks historical
 * cost trends in higest_source_offer_histories.
 */
class SourceOfferRecorder
{
    /**
     * Record or update a source acquisition offer for a variant.
     *
     * @param  int  $variantId  Strict Variant product_id (for simple products, variantId = productId).
     * @param  int  $productId  Parent configurable product_id (or simple product_id).
     * @param  float  $acquisitionCost  Effective acquisition cost (sale price).
     * @param  float|null  $acquisitionOriginalCost  List price before discount.
     * @param  string  $sourceCurrency  ISO currency code.
     * @param  string|null  $sourceSkuId  Source SKU identifier.
     * @param  string  $sourceProvider  Source provider identifier ('aliexpress', '1688', 'cj', etc.).
     * @param  string  $trigger  Change trigger ('import', 'sync', 'manual').
     */
    public function record(
        int $variantId,
        int $productId,
        float $acquisitionCost,
        ?float $acquisitionOriginalCost = null,
        string $sourceCurrency = 'USD',
        ?string $sourceSkuId = null,
        string $sourceProvider = 'aliexpress',
        string $trigger = 'import',
    ): HigestSourceOffer {
        $existing = HigestSourceOffer::forVariant($variantId, $sourceProvider)->first();

        $oldCost = $existing?->acquisition_cost !== null ? (float) $existing->acquisition_cost : null;
        $oldOriginalCost = $existing?->acquisition_original_cost !== null ? (float) $existing->acquisition_original_cost : null;

        $offer = HigestSourceOffer::updateOrCreate(
            [
                'variant_id' => $variantId,
                'source_provider' => $sourceProvider,
            ],
            [
                'product_id' => $productId,
                'source_sku_id' => $sourceSkuId,
                'acquisition_cost' => $acquisitionCost,
                'acquisition_original_cost' => $acquisitionOriginalCost,
                'source_currency' => $sourceCurrency,
                'captured_at' => Carbon::now(),
                'synced_at' => $trigger === 'sync' ? Carbon::now() : $existing?->synced_at,
            ]
        );

        // Track historical trend if cost changed or on initial import
        if ($oldCost !== $acquisitionCost) {
            HigestSourceOfferHistory::create([
                'source_offer_id' => $offer->id,
                'variant_id' => $variantId,
                'old_acquisition_cost' => $oldCost,
                'new_acquisition_cost' => $acquisitionCost,
                'old_acquisition_original_cost' => $oldOriginalCost,
                'new_acquisition_original_cost' => $acquisitionOriginalCost,
                'source_currency' => $sourceCurrency,
                'change_trigger' => $trigger,
                'created_at' => Carbon::now(),
            ]);
        }

        return $offer;
    }

    /**
     * Get current source offer for a variant.
     */
    public function getOffer(int $variantId, string $sourceProvider = 'aliexpress'): ?HigestSourceOffer
    {
        return HigestSourceOffer::forVariant($variantId, $sourceProvider)->first();
    }
}
