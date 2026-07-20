<?php

namespace Webkul\Fulfillment\Listeners;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\AliExpressSetting;
use Webkul\Product\Helpers\Indexers\Inventory as InventoryIndexer;
use Webkul\Product\Models\Product;

class AliExpressStockListener
{
    /**
     * Create a new listener instance.
     */
    public function __construct(
        protected InventoryIndexer $inventoryIndexer
    ) {}

    /**
     * Handle supplier stock change events.
     */
    public function handle(string $eventName, array $payload, string $correlationId, string $causationId, string $outboxEventId = null): void
    {
        $variantId = $payload['variant_id'] ?? null;
        $newStock = $payload['new_stock'] ?? 0;

        if (!$variantId) {
            return;
        }

        DB::transaction(function () use ($variantId, $newStock, $eventName, $payload) {
            $projection = DB::table('external_variant_projections')
                ->where('variant_product_id', $variantId)
                ->lockForUpdate()
                ->first();

            if ($projection) {
                $decision = \Webkul\Fulfillment\Services\Domain\ProjectionVersionGuard::shouldApply($projection, $payload);

                if (! $decision->shouldApply()) {
                    if ($decision->isUnsafeJump()) {
                        Log::channel('aliexpress')->warning("Catalog projection stock update flagged as unsafe: " . $decision->reason);
                        
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

            $setting = AliExpressSetting::current();
            $buffer = $setting->inventory_buffer ?? 5;

            // Calculate reservations
            $reservations = (int) DB::table('order_allocations')
                ->where('variant_product_id', $variantId)
                ->where('state', 'reserved')
                ->sum('reserved_qty');

            // Sellable stock = max(0, newStock - reservations - buffer)
            $sellableStock = max(0, $newStock - $reservations - $buffer);

            Log::channel('aliexpress')->info("Recalculating sellable stock for Variant ID {$variantId}: Supplier Stock={$newStock}, Reservations={$reservations}, Buffer={$buffer} => Sellable Stock={$sellableStock}");

            // Find default inventory source
            $defaultSourceId = $this->defaultInventorySourceId();

            // Update product_inventories
            DB::table('product_inventories')->updateOrInsert(
                [
                    'product_id'          => $variantId,
                    'inventory_source_id' => $defaultSourceId,
                ],
                [
                    'qty' => $sellableStock,
                ]
            );

            // Update projection table
            DB::table('external_variant_projections')
                ->where('variant_product_id', $variantId)
                ->update([
                    'external_variant_version' => $payload['external_variant_version'] ?? ($projection ? $projection->external_variant_version : null),
                    'provider_updated_at'      => isset($payload['provider_updated_at']) ? new \Carbon\Carbon($payload['provider_updated_at']) : ($projection ? $projection->provider_updated_at : null),
                    'updated_at'               => now(),
                ]);

            Log::channel('aliexpress')->info("Metric counter incremented: [projection_events_processed]");

            // Reindex
            $product = Product::find($variantId);
            if ($product) {
                $toIndex = [$product];
                if ($product->parent_id) {
                    $parent = Product::find($product->parent_id);
                    if ($parent) {
                        $toIndex[] = $parent;
                    }
                }
                $this->inventoryIndexer->reindexBatch($toIndex);
            }
        });
    }

    protected function defaultInventorySourceId(): int
    {
        $source = core()->getDefaultChannel()
            ->inventory_sources
            ->where('status', 1)
            ->first();

        return (int) ($source->id ?? 1);
    }
}
