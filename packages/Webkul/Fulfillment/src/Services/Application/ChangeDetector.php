<?php

namespace Webkul\Fulfillment\Services\Application;

use App\Models\HigestSourceOffer;
use Illuminate\Support\Facades\DB;
use Webkul\Fulfillment\DataObjects\ChangeSet;
use Webkul\Product\Models\Product;

class ChangeDetector
{
    /**
     * Detect changes for a single product and its variants.
     */
    public function detect(int $productId, string $supplierProductId, string $provider, array $incomingVariants, ?array $providerMetadata = null): ChangeSet
    {
        $changeSet = new ChangeSet($productId, $supplierProductId);
        $processedExternalSkuIds = [];
        $processedVariantIds = [];

        foreach ($incomingVariants as $aeVariant) {
            $skuId = $aeVariant['sku_id'] ?? '';
            $processedExternalSkuIds[] = $skuId;

            $projection = DB::table('external_variant_projections')
                ->where('provider', $provider)
                ->where('external_sku_id', $skuId)
                ->first();

            if (! $projection) {
                // Identity changed or new variant
                $changeSet->addChange('identityChanged', null, [
                    'product_id' => $productId,
                    'variant_id' => null,
                    'old_sku' => null,
                    'new_sku' => $skuId,
                    'old_options' => [],
                    'new_options' => $aeVariant['options'] ?? [],
                    'supplier_product_id' => $supplierProductId,
                    'external_variant_version' => $aeVariant['version'] ?? null,
                    'provider_updated_at' => $providerMetadata['provider_updated_at'] ?? null,
                    'occurred_at' => now()->toIso8601String(),
                ]);

                continue;
            }

            $variantId = $projection->variant_product_id;
            $processedVariantIds[] = $variantId;

            // Load local variant to compare details
            $localVariant = Product::with('inventories')->find($variantId);
            if (! $localVariant) {
                continue;
            }

            // Compare Acquisition Cost (C1, C2, C7)
            $sourceOffer = HigestSourceOffer::forVariant($variantId, $provider)->first();
            $oldCost = $sourceOffer?->acquisition_cost !== null ? (float) $sourceOffer->acquisition_cost : null;
            $oldOriginalCost = $sourceOffer?->acquisition_original_cost !== null ? (float) $sourceOffer->acquisition_original_cost : null;

            $newCost = (float) ($aeVariant['price'] ?? $aeVariant['offer_sale_price'] ?? $aeVariant['sale_price'] ?? 0);
            $newOriginalCost = isset($aeVariant['original_price'])
                ? (float) $aeVariant['original_price']
                : (isset($aeVariant['originalPrice'])
                    ? (float) $aeVariant['originalPrice']
                    : (isset($aeVariant['sku_price']) ? (float) $aeVariant['sku_price'] : null));

            $costChanged = ($oldCost === null) || ($oldCost !== $newCost) || ($newOriginalCost !== null && $oldOriginalCost !== $newOriginalCost);

            if ($costChanged) {
                $pct = ($oldCost !== null && $oldCost > 0) ? (($newCost - $oldCost) / $oldCost) * 100 : 0;
                $changeSet->addChange('priceChanged', $variantId, [
                    'product_id' => $productId,
                    'variant_id' => $variantId,
                    'old_price' => $oldCost ?? (float) $localVariant->price,
                    'new_price' => $newCost,
                    'old_cost' => $oldCost,
                    'new_cost' => $newCost,
                    'old_original_cost' => $oldOriginalCost,
                    'new_original_cost' => $newOriginalCost,
                    'price_change_percentage' => round($pct, 2),
                    'currency' => $aeVariant['currency'] ?? 'USD',
                    'supplier_product_id' => $supplierProductId,
                    'supplier_sku_id' => $skuId,
                    'external_variant_version' => $aeVariant['version'] ?? null,
                    'provider_updated_at' => $providerMetadata['provider_updated_at'] ?? null,
                    'occurred_at' => now()->toIso8601String(),
                ]);
            }

            // Compare Stock
            $localStock = (int) $localVariant->inventories->sum('qty');
            $newStock = (int) ($aeVariant['stock'] ?? 0);
            if ($localStock !== $newStock) {
                $changeSet->addChange('stockChanged', $variantId, [
                    'product_id' => $productId,
                    'variant_id' => $variantId,
                    'old_stock' => $localStock,
                    'new_stock' => $newStock,
                    'supplier_product_id' => $supplierProductId,
                    'supplier_sku_id' => $skuId,
                    'external_variant_version' => $aeVariant['version'] ?? null,
                    'provider_updated_at' => $providerMetadata['provider_updated_at'] ?? null,
                    'occurred_at' => now()->toIso8601String(),
                ]);
            }

            // Compare identity version directly
            $currentVersion = $projection->external_variant_version;
            $newVersion = $aeVariant['version'] ?? null;
            if ($currentVersion !== null && $newVersion !== null && $currentVersion !== $newVersion) {
                $changeSet->addChange('identityChanged', $variantId, [
                    'product_id' => $productId,
                    'variant_id' => $variantId,
                    'old_sku' => $projection->external_sku_id,
                    'new_sku' => $skuId,
                    'old_options' => [],
                    'new_options' => $aeVariant['options'] ?? [],
                    'supplier_product_id' => $supplierProductId,
                    'external_variant_version' => $newVersion,
                    'provider_updated_at' => $providerMetadata['provider_updated_at'] ?? null,
                    'occurred_at' => now()->toIso8601String(),
                ]);
            }
        }

        // Deletion Detection (find local variants not present in supplier active list)
        $allLocalVariantProjections = DB::table('external_variant_projections')
            ->where('product_id', $productId)
            ->where('provider', $provider)
            ->get();

        foreach ($allLocalVariantProjections as $localProj) {
            if (! in_array($localProj->external_sku_id, $processedExternalSkuIds)) {
                // Variant was removed by the supplier
                $changeSet->addChange('removed', $localProj->variant_product_id, [
                    'product_id' => $productId,
                    'variant_id' => $localProj->variant_product_id,
                    'old_sku' => $localProj->external_sku_id,
                    'new_sku' => null,
                    'old_options' => [],
                    'new_options' => [],
                    'supplier_product_id' => $supplierProductId,
                    'external_variant_version' => null,
                    'provider_updated_at' => $providerMetadata['provider_updated_at'] ?? null,
                    'occurred_at' => now()->toIso8601String(),
                ]);
            }
        }

        return $changeSet;
    }
}
