<?php

namespace Webkul\Fulfillment\Services\Application;

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

            if (!$projection) {
                // Identity changed or new variant
                $changeSet->addChange('identityChanged', null, [
                    'product_id'               => $productId,
                    'variant_id'               => null,
                    'old_sku'                  => null,
                    'new_sku'                  => $skuId,
                    'old_options'              => [],
                    'new_options'              => $aeVariant['options'] ?? [],
                    'supplier_product_id'      => $supplierProductId,
                    'external_variant_version' => $aeVariant['version'] ?? null,
                    'provider_updated_at'      => $providerMetadata['provider_updated_at'] ?? null,
                    'occurred_at'              => now()->toIso8601String(),
                ]);
                continue;
            }

            $variantId = $projection->variant_product_id;
            $processedVariantIds[] = $variantId;

            // Load local variant to compare details
            $localVariant = Product::with('inventories')->find($variantId);
            if (!$localVariant) {
                continue;
            }

            // Compare Price
            $localPrice = (float) $localVariant->price;
            $newPrice = (float) ($aeVariant['price'] ?? 0);
            if ($localPrice !== $newPrice) {
                $pct = $localPrice > 0 ? (($newPrice - $localPrice) / $localPrice) * 100 : 0;
                $changeSet->addChange('priceChanged', $variantId, [
                    'product_id'               => $productId,
                    'variant_id'               => $variantId,
                    'old_price'                => $localPrice,
                    'new_price'                => $newPrice,
                    'price_change_percentage'  => round($pct, 2),
                    'supplier_product_id'      => $supplierProductId,
                    'supplier_sku_id'          => $skuId,
                    'external_variant_version' => $aeVariant['version'] ?? null,
                    'provider_updated_at'      => $providerMetadata['provider_updated_at'] ?? null,
                    'occurred_at'              => now()->toIso8601String(),
                ]);
            }

            // Compare Stock
            $localStock = (int) $localVariant->inventories->sum('qty');
            $newStock = (int) ($aeVariant['stock'] ?? 0);
            if ($localStock !== $newStock) {
                $changeSet->addChange('stockChanged', $variantId, [
                    'product_id'               => $productId,
                    'variant_id'               => $variantId,
                    'old_stock'                => $localStock,
                    'new_stock'                => $newStock,
                    'supplier_product_id'      => $supplierProductId,
                    'supplier_sku_id'          => $skuId,
                    'external_variant_version' => $aeVariant['version'] ?? null,
                    'provider_updated_at'      => $providerMetadata['provider_updated_at'] ?? null,
                    'occurred_at'              => now()->toIso8601String(),
                ]);
            }

            // Compare identity version directly
            $currentVersion = $projection->external_variant_version;
            $newVersion = $aeVariant['version'] ?? null;
            if ($currentVersion !== null && $newVersion !== null && $currentVersion !== $newVersion) {
                $changeSet->addChange('identityChanged', $variantId, [
                    'product_id'               => $productId,
                    'variant_id'               => $variantId,
                    'old_sku'                  => $projection->external_sku_id,
                    'new_sku'                  => $skuId,
                    'old_options'              => [],
                    'new_options'              => $aeVariant['options'] ?? [],
                    'supplier_product_id'      => $supplierProductId,
                    'external_variant_version' => $newVersion,
                    'provider_updated_at'      => $providerMetadata['provider_updated_at'] ?? null,
                    'occurred_at'              => now()->toIso8601String(),
                ]);
            }
        }

        // Deletion Detection (find local variants not present in supplier active list)
        $allLocalVariantProjections = DB::table('external_variant_projections')
            ->where('product_id', $productId)
            ->where('provider', $provider)
            ->get();

        foreach ($allLocalVariantProjections as $localProj) {
            if (!in_array($localProj->external_sku_id, $processedExternalSkuIds)) {
                // Variant was removed by the supplier
                $changeSet->addChange('removed', $localProj->variant_product_id, [
                    'product_id'               => $productId,
                    'variant_id'               => $localProj->variant_product_id,
                    'old_sku'                  => $localProj->external_sku_id,
                    'new_sku'                  => null,
                    'old_options'              => [],
                    'new_options'              => [],
                    'supplier_product_id'      => $supplierProductId,
                    'external_variant_version' => null,
                    'provider_updated_at'      => $providerMetadata['provider_updated_at'] ?? null,
                    'occurred_at'              => now()->toIso8601String(),
                ]);
            }
        }

        return $changeSet;
    }
}
