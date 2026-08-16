<?php

namespace Webkul\Fulfillment\Listeners;

use App\Models\AliExpressSetting;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Webkul\Attribute\Models\Attribute;
use Webkul\Fulfillment\DataObjects\ProjectionDecision;
use Webkul\Fulfillment\Services\Domain\ProjectionVersionGuard;
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
    public function handle(string $eventName, array $payload, string $correlationId, string $causationId, ?string $outboxEventId = null): void
    {
        $variantId = $payload['variant_id'] ?? null;
        $newStock = $payload['new_stock'] ?? 0;

        if (! $variantId) {
            return;
        }

        $reindexVariantId = DB::transaction(function () use ($variantId, $newStock, $payload) {
            $projection = DB::table('external_variant_projections')
                ->where('variant_product_id', $variantId)
                ->lockForUpdate()
                ->first();

            if ($projection) {
                $decision = ProjectionVersionGuard::shouldApply($projection, $payload);

                if (! $decision->shouldApply()) {
                    if ($decision->isUnsafeJump()) {
                        Log::channel('aliexpress')->warning('Catalog projection stock update flagged as unsafe: '.$decision->reason);

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
                    'product_id' => $variantId,
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
                    'provider_updated_at' => isset($payload['provider_updated_at']) ? new Carbon($payload['provider_updated_at']) : ($projection ? $projection->provider_updated_at : null),
                    'updated_at' => now(),
                ]);

            Log::channel('aliexpress')->info('Metric counter incremented: [projection_events_processed]');

            return $variantId;
        });

        // Reindex OUTSIDE transaction (C6)
        if ($reindexVariantId) {
            $product = Product::find($reindexVariantId);
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
        }
    }

    protected function defaultInventorySourceId(): int
    {
        $channel = core()->getDefaultChannel();
        if ($channel && isset($channel->inventory_sources)) {
            $source = $channel->inventory_sources
                ->where('code', 'default')
                ->where('status', 1)
                ->first();

            if ($source) {
                return (int) $source->id;
            }
        }

        $defaultSource = DB::table('inventory_sources')->where('code', 'default')->first();
        if ($defaultSource) {
            return (int) $defaultSource->id;
        }

        return (int) DB::table('inventory_sources')->insertGetId([
            'code' => 'default',
            'name' => 'Default (External Availability Projection)',
            'status' => 1,
            'country' => 'YE',
            'state' => 'SAN',
            'city' => 'Sanaa',
            'street' => 'External Dropshipping Projection',
            'postcode' => '00000',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
