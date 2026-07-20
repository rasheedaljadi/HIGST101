<?php

namespace Webkul\Fulfillment\Listeners;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
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
        protected FlatIndexer $flatIndexer
    ) {}

    /**
     * Handle supplier price change events.
     */
    public function handle(string $eventName, array $payload, string $correlationId, string $causationId, string $outboxEventId = null): void
    {
        $variantId = $payload['variant_id'] ?? null;
        $newPrice = $payload['new_price'] ?? 0;

        if (!$variantId) {
            return;
        }

        DB::transaction(function () use ($variantId, $newPrice, $eventName, $payload) {
            $projection = DB::table('external_variant_projections')
                ->where('variant_product_id', $variantId)
                ->lockForUpdate()
                ->first();

            if ($projection) {
                $decision = \Webkul\Fulfillment\Services\Domain\ProjectionVersionGuard::shouldApply($projection, $payload);

                if (! $decision->shouldApply()) {
                    if ($decision->isUnsafeJump()) {
                        Log::channel('aliexpress')->warning("Catalog projection price update flagged as unsafe: " . $decision->reason);
                        
                        // Flag for review & disable variant
                        $attributeId = (int) (\Webkul\Attribute\Models\Attribute::where('code', 'needs_review')->value('id') ?? 0);
                        if ($attributeId > 0) {
                            $uniqueId = "||{$variantId}|{$attributeId}";
                            DB::table('product_attribute_values')->updateOrInsert(
                                [
                                    'product_id'   => $variantId,
                                    'attribute_id' => $attributeId,
                                    'channel'      => null,
                                    'locale'       => null,
                                ],
                                [
                                    'boolean_value' => true,
                                    'unique_id'     => $uniqueId,
                                ]
                            );
                        }
                        
                        $statusAttrId = (int) (\Webkul\Attribute\Models\Attribute::where('code', 'status')->value('id') ?? 0);
                        if ($statusAttrId > 0) {
                            $uniqueId = "||{$variantId}|{$statusAttrId}";
                            DB::table('product_attribute_values')->updateOrInsert(
                                [
                                    'product_id'   => $variantId,
                                    'attribute_id' => $statusAttrId,
                                    'channel'      => null,
                                    'locale'       => null,
                                ],
                                [
                                    'boolean_value' => false,
                                    'unique_id'     => $uniqueId,
                                ]
                            );
                        }
                        
                        Log::channel('aliexpress')->info("Metric counter incremented: [projection_events_review]");
                        return;
                    }

                    if ($decision->status === \Webkul\Fulfillment\DataObjects\ProjectionDecision::STATUS_STALE) {
                        Log::channel('aliexpress')->info("Metric counter incremented: [projection_events_stale]");
                    } elseif ($decision->status === \Webkul\Fulfillment\DataObjects\ProjectionDecision::STATUS_REPLAY) {
                        Log::channel('aliexpress')->info("Metric counter incremented: [projection_events_replayed]");
                    }
                    return;
                }
            }

            Log::channel('aliexpress')->info("Catalog projection update for Variant ID {$variantId}: Price={$newPrice}");

            // Update product price EAV attribute
            $priceAttrId = (int) (\Webkul\Attribute\Models\Attribute::where('code', 'price')->value('id') ?? 0);
            if ($priceAttrId > 0) {
                $uniqueId = "||{$variantId}|{$priceAttrId}";
                DB::table('product_attribute_values')->updateOrInsert(
                    [
                        'product_id'   => $variantId,
                        'attribute_id' => $priceAttrId,
                        'channel'      => null,
                        'locale'       => null,
                    ],
                    [
                        'float_value' => $newPrice,
                        'unique_id'   => $uniqueId,
                    ]
                );
            }

            // Update projection table
            DB::table('external_variant_projections')
                ->where('variant_product_id', $variantId)
                ->update([
                    'external_variant_version' => $payload['external_variant_version'] ?? ($projection ? $projection->external_variant_version : null),
                    'provider_updated_at'      => isset($payload['provider_updated_at']) ? new \Carbon\Carbon($payload['provider_updated_at']) : ($projection ? $projection->provider_updated_at : null),
                    'updated_at'               => now(),
                ]);

            Log::channel('aliexpress')->info("Metric counter incremented: [projection_events_processed]");

            // Reindex price and flat table
            $product = Product::find($variantId);
            if ($product) {
                $toIndex = [$product];
                if ($product->parent_id) {
                    $parent = Product::find($product->parent_id);
                    if ($parent) {
                        $toIndex[] = $parent;
                    }
                }

                $this->priceIndexer->reindexBatch($toIndex);
                foreach ($toIndex as $indexable) {
                    $this->flatIndexer->refresh($indexable);
                }
            }
        });
    }
}
