<?php

namespace Webkul\Fulfillment\Listeners;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Webkul\Product\Models\Product;
use Webkul\Product\Models\ProductAttributeValue;
use App\Models\AliExpressSetting;

class AliExpressSyncReviewListener
{
    /**
     * Handle variant sync reviews.
     */
    public function handle(string $eventName, array $payload, string $correlationId, string $causationId, string $outboxEventId = null): void
    {
        $productId = $payload['product_id'] ?? null;
        $variantId = $payload['variant_id'] ?? null;

        if (!$variantId) {
            return;
        }

        $attributeId = (int) (\Webkul\Attribute\Models\Attribute::where('code', 'needs_review')->value('id') ?? 0);
        if ($attributeId === 0) {
            return;
        }

        DB::transaction(function () use ($variantId, $productId, $attributeId, $eventName, $payload) {
            $projection = DB::table('external_variant_projections')
                ->where('variant_product_id', $variantId)
                ->lockForUpdate()
                ->first();

            if ($projection) {
                $decision = \Webkul\Fulfillment\Services\Domain\ProjectionVersionGuard::shouldApply($projection, $payload);

                if (! $decision->shouldApply()) {
                    if ($decision->isUnsafeJump()) {
                        Log::channel('aliexpress')->warning("Catalog projection sync review update flagged as unsafe: " . $decision->reason);
                        
                        $this->setNeedsReview($variantId, $attributeId, true);
                        $this->disableVariant($variantId);
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

            if ($eventName === 'VariantIdentityChanged') {
                Log::channel('aliexpress')->warning("Variant Identity Changed: Variant ID {$variantId}. Flagging for review.", $payload);

                $this->setNeedsReview($variantId, $attributeId, true);
                $this->disableVariant($variantId);

                // Update external_sku_id only on identity events
                DB::table('external_variant_projections')
                    ->where('variant_product_id', $variantId)
                    ->update([
                        'external_sku_id' => $payload['new_sku'] ?? ($projection ? $projection->external_sku_id : ''),
                    ]);
            }

            if ($eventName === 'SupplierPriceChanged') {
                $priceChangePct = $payload['price_change_percentage'] ?? 0;
                $setting = AliExpressSetting::current();
                $limit = $setting->price_change_limit ?? 20.00;

                if ($priceChangePct >= $limit) {
                    Log::channel('aliexpress')->warning("Supplier Price Changed beyond limit ({$priceChangePct}% >= {$limit}%): Variant ID {$variantId}. Flagging for review.", $payload);

                    $this->setNeedsReview($variantId, $attributeId, true);
                    $this->disableVariant($variantId);
                } else {
                    Log::channel('aliexpress')->info("Supplier Price Changed within limit ({$priceChangePct}% < {$limit}%): Variant ID {$variantId}.", $payload);
                }
            }

            // Update projection version and timestamp inside transaction
            DB::table('external_variant_projections')
                ->where('variant_product_id', $variantId)
                ->update([
                    'external_variant_version' => $payload['external_variant_version'] ?? ($projection ? $projection->external_variant_version : null),
                    'provider_updated_at'      => isset($payload['provider_updated_at']) ? new \Carbon\Carbon($payload['provider_updated_at']) : ($projection ? $projection->provider_updated_at : null),
                    'updated_at'               => now(),
                ]);

            Log::channel('aliexpress')->info("Metric counter incremented: [projection_events_processed]");
        });
    }

    protected function setNeedsReview(int $productId, int $attributeId, bool $value): void
    {
        $uniqueId = implode('|', array_filter([
            null,
            null,
            $productId,
            $attributeId,
        ]));

        ProductAttributeValue::updateOrCreate(
            [
                'product_id'   => $productId,
                'attribute_id' => $attributeId,
                'channel'      => null,
                'locale'       => null,
            ],
            [
                'boolean_value' => $value,
                'unique_id'     => $uniqueId,
            ]
        );
    }

    protected function disableVariant(int $productId): void
    {
        $statusAttrId = (int) (\Webkul\Attribute\Models\Attribute::where('code', 'status')->value('id') ?? 0);
        if ($statusAttrId > 0) {
            $uniqueId = "||{$productId}|{$statusAttrId}";
            DB::table('product_attribute_values')->updateOrInsert(
                [
                    'product_id'   => $productId,
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
    }
}
