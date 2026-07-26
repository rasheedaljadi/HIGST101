<?php

namespace App\Console\Commands;

use App\Models\AliExpressProductImport;
use App\Models\ExternalVariantProjection;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class AliExpressRebuildProjections extends Command
{
    protected $signature = 'aliexpress:rebuild-projections
        {--id= : Rebuild projection for a specific local product ID}
        {--dry-run : Dry run mode without database modification}';

    protected $description = 'Rebuild external_variant_projections using payload_snapshot as the Source of Truth';

    public function handle(): int
    {
        $id = $this->option('id');
        $dryRun = $this->option('dry-run');

        $query = AliExpressProductImport::query()->where('status', 'success')->whereNotNull('product_id');

        if ($id) {
            $query->where('product_id', $id);
        }

        $imports = $query->get();

        if ($imports->isEmpty()) {
            $this->warn('No successful product imports found.');

            return self::SUCCESS;
        }

        $this->info("Processing {$imports->count()} import record(s) using payload_snapshot as Source of Truth...");
        if ($dryRun) {
            $this->comment('=== DRY RUN MODE — No database changes will be saved ===');
        }

        $rebuiltCount = 0;
        $variantsMapped = 0;

        foreach ($imports as $import) {
            $productId = $import->product_id;
            $aeProductId = (string) $import->aliexpress_product_id;

            $product = DB::table('products')->where('id', $productId)->first();
            if (! $product) {
                $this->warn("Product ID {$productId} not found in database.");

                continue;
            }

            $snapshot = [];
            if (! empty($import->payload_snapshot)) {
                $snapshot = is_array($import->payload_snapshot)
                    ? $import->payload_snapshot
                    : (json_decode($import->payload_snapshot, true) ?? []);
            }

            $snapshotVariants = $snapshot['variants'] ?? [];

            if ($product->type === 'simple') {
                $aeVariant = $snapshotVariants[0] ?? [];
                $skuId = (string) ($aeVariant['sku_id'] ?? $aeVariant['skuId'] ?? $aeProductId);

                if (! $dryRun) {
                    ExternalVariantProjection::updateOrCreate(
                        [
                            'variant_product_id' => $productId,
                        ],
                        [
                            'product_id' => $productId,
                            'provider' => 'aliexpress',
                            'external_sku_id' => $skuId,
                            'external_product_id' => $aeProductId,
                            'external_variant_version' => null,
                            'projection_version' => 1,
                            'provider_updated_at' => null,
                        ]
                    );
                }

                $rebuiltCount++;
                $variantsMapped++;
                $this->line("  ✓ [Simple] Product ID: {$productId} | External SKU ID: {$skuId}");
            } elseif ($product->type === 'configurable') {
                $childVariants = DB::table('products')->where('parent_id', $productId)->orderBy('id', 'asc')->get();

                if ($childVariants->isEmpty()) {
                    $this->warn("Configurable Product ID {$productId} has no child variants.");

                    continue;
                }

                $mappedForProduct = 0;
                foreach ($childVariants as $idx => $variant) {
                    $aeVariant = $snapshotVariants[$idx] ?? [];
                    $skuId = (string) ($aeVariant['sku_id'] ?? $aeVariant['skuId'] ?? "{$aeProductId}-{$variant->id}");

                    if (! $dryRun) {
                        ExternalVariantProjection::updateOrCreate(
                            [
                                'variant_product_id' => $variant->id,
                            ],
                            [
                                'product_id' => $productId,
                                'provider' => 'aliexpress',
                                'external_sku_id' => $skuId,
                                'external_product_id' => $aeProductId,
                                'external_variant_version' => null,
                                'projection_version' => 1,
                                'provider_updated_at' => null,
                            ]
                        );
                    }

                    $mappedForProduct++;
                    $variantsMapped++;
                    $this->line("  ✓ [Configurable] Parent ID: {$productId} | Variant ID: {$variant->id} | External SKU ID: {$skuId}");
                }

                if ($mappedForProduct > 0) {
                    $rebuiltCount++;
                }
            }
        }

        $this->newLine();
        $this->info("✓ Rebuild complete. {$rebuiltCount} product(s) processed, {$variantsMapped} variant projection(s) mapped.");

        return self::SUCCESS;
    }
}
