<?php

namespace Webkul\Procurement\Console\Commands;

use App\Models\ExternalVariantProjection;
use Illuminate\Console\Command;
use Webkul\Procurement\Models\ProcurementAuditLog;
use Webkul\Procurement\Models\ProcurementDemand;
use Webkul\Procurement\Models\SupplierPurchaseOrderItem;

class BackfillDemandSkuIds extends Command
{
    protected $signature = 'procurement:backfill-demand-sku-ids
                            {--dry-run : Show what would be updated without making changes}';

    protected $description = 'Backfill procurement_demands and SPO items with correct numeric AliExpress SKU IDs from external_variant_projections.';

    public function handle(): int
    {
        $isDryRun = $this->option('dry-run');

        if ($isDryRun) {
            $this->info('=== DRY RUN MODE ===');
        }

        // 1. Fix procurement_demands with textual supplier_sku_id (ae-*)
        $demands = ProcurementDemand::where('supplier_sku_id', 'like', 'ae-%')
            ->whereNotNull('variant_product_id')
            ->where('provider', 'aliexpress')
            ->get();

        $this->info("Found {$demands->count()} demands with textual SKU IDs to process.");

        $demandUpdated = 0;
        $demandSkipped = 0;

        foreach ($demands as $demand) {
            $projection = ExternalVariantProjection::where('variant_product_id', $demand->variant_product_id)
                ->where('provider', 'aliexpress')
                ->first();

            if (! $projection || empty($projection->external_sku_id)) {
                $this->warn("  Demand #{$demand->id}: No projection found for variant_product_id={$demand->variant_product_id}. Skipped.");
                $demandSkipped++;

                continue;
            }

            $oldSkuId = $demand->supplier_sku_id;
            $newSkuId = $projection->external_sku_id;

            if ($oldSkuId === $newSkuId) {
                $demandSkipped++;

                continue;
            }

            $this->line("  Demand #{$demand->id}: {$oldSkuId} -> {$newSkuId}");

            if (! $isDryRun) {
                // Update the demand
                $demand->update([
                    'supplier_sku_id' => $newSkuId,
                ]);

                // Update source_snapshot
                $snapshot = $demand->source_snapshot ?? [];
                $snapshot['supplier_sku_id'] = $newSkuId;
                $snapshot['external_sku_id'] = $newSkuId;
                $snapshot['backfill_old_sku_id'] = $oldSkuId;
                $snapshot['backfill_at'] = now()->toIso8601String();
                $demand->update(['source_snapshot' => $snapshot]);

                // Audit log
                ProcurementAuditLog::create([
                    'auditable_type' => ProcurementDemand::class,
                    'auditable_id' => $demand->id,
                    'action' => 'sku_id_backfilled',
                    'old_state' => $demand->state,
                    'new_state' => $demand->state,
                    'details' => [
                        'old_supplier_sku_id' => $oldSkuId,
                        'new_supplier_sku_id' => $newSkuId,
                        'variant_product_id' => $demand->variant_product_id,
                        'projection_id' => $projection->id,
                    ],
                    'correlation_id' => "backfill-demand-{$demand->id}",
                ]);
            }

            $demandUpdated++;
        }

        $this->info("Demands: {$demandUpdated} updated, {$demandSkipped} skipped.");

        // 2. Fix SPO items that inherited the textual SKU
        $spoItems = SupplierPurchaseOrderItem::where('supplier_sku_id', 'like', 'ae-%')
            ->whereNotNull('variant_product_id')
            ->get();

        $this->info("Found {$spoItems->count()} SPO items with textual SKU IDs to process.");

        $spoUpdated = 0;
        $spoSkipped = 0;

        foreach ($spoItems as $spoItem) {
            $projection = ExternalVariantProjection::where('variant_product_id', $spoItem->variant_product_id)
                ->where('provider', 'aliexpress')
                ->first();

            if (! $projection || empty($projection->external_sku_id)) {
                $this->warn("  SPO Item #{$spoItem->id}: No projection found for variant_product_id={$spoItem->variant_product_id}. Skipped.");
                $spoSkipped++;

                continue;
            }

            $oldSkuId = $spoItem->supplier_sku_id;
            $newSkuId = $projection->external_sku_id;

            if ($oldSkuId === $newSkuId) {
                $spoSkipped++;

                continue;
            }

            $this->line("  SPO Item #{$spoItem->id}: {$oldSkuId} -> {$newSkuId}");

            if (! $isDryRun) {
                $spoItem->update([
                    'supplier_sku_id' => $newSkuId,
                ]);

                // Update snapshots
                $snapshots = $spoItem->snapshots ?? [];
                $snapshots['backfill_old_sku_id'] = $oldSkuId;
                $snapshots['sku'] = $newSkuId;
                $snapshots['backfill_at'] = now()->toIso8601String();
                $spoItem->update(['snapshots' => $snapshots]);
            }

            $spoUpdated++;
        }

        $this->info("SPO Items: {$spoUpdated} updated, {$spoSkipped} skipped.");
        $this->info('Backfill complete.');

        return self::SUCCESS;
    }
}
