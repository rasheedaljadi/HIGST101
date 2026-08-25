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

                // Helper to normalize strings for comparison
                $normalize = function (string $text): string {
                    $text = preg_replace('/[\x{00A0}\x{200B}\s]+/u', ' ', $text);
                    $text = preg_replace('/[+\-\/_:,\(\)\[\]]/u', ' ', $text);
                    $text = preg_replace('/\s+/', ' ', trim($text));

                    return mb_strtolower($text, 'UTF-8');
                };

                // Fetch option labels for each child variant
                $variantOptionLabels = [];
                foreach ($childVariants as $variant) {
                    $labels = DB::table('product_attribute_values')
                        ->where('product_attribute_values.product_id', $variant->id)
                        ->join('attribute_options', 'attribute_options.id', '=', 'product_attribute_values.integer_value')
                        ->join('attribute_option_translations', 'attribute_option_translations.attribute_option_id', '=', 'attribute_options.id')
                        ->pluck('attribute_option_translations.label')
                        ->map(fn ($l) => $normalize((string) $l))
                        ->unique()
                        ->values()
                        ->all();

                    $variantOptionLabels[$variant->id] = $labels;
                }

                $mappedForProduct = 0;
                foreach ($childVariants as $variant) {
                    $localLabels = $variantOptionLabels[$variant->id] ?? [];
                    $matchedAeVariant = null;

                    // Match against snapshot variants by option labels
                    foreach ($snapshotVariants as $aeVar) {
                        $aeOptions = array_map(fn ($opt) => $normalize((string) $opt), array_values($aeVar['options_by_axis'] ?? []));
                        if (! empty($localLabels) && ! empty($aeOptions)) {
                            // Check if all local labels match the AE variant options
                            $match = true;
                            foreach ($localLabels as $localLabel) {
                                if (! in_array($localLabel, $aeOptions, true)) {
                                    $match = false;
                                    break;
                                }
                            }
                            if ($match) {
                                $matchedAeVariant = $aeVar;
                                break;
                            }
                        }
                    }

                    if ($matchedAeVariant !== null) {
                        $skuId = (string) ($matchedAeVariant['sku_id'] ?? $matchedAeVariant['skuId'] ?? '');

                        if ($skuId !== '') {
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
                            $this->line("  ✓ [Configurable] Parent ID: {$productId} | Variant ID: {$variant->id} | Matched External SKU ID: {$skuId}");
                        }
                    } else {
                        $this->warn("  ⚠ [Configurable] Parent ID: {$productId} | Variant ID: {$variant->id} could not be matched to snapshot options.");
                    }
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
