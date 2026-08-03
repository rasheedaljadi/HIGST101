<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;
use Webkul\Attribute\Models\Attribute;
use Webkul\Product\Models\ProductAttributeValue;

/**
 * Backfills Bagisto's native `cost` EAV attribute for all products that have
 * an acquisition cost recorded in higest_source_offers but no `cost` EAV row.
 *
 * Safe to run multiple times (idempotent via updateOrCreate).
 *
 * Usage:
 *   php artisan higest:backfill-cost-field
 *   php artisan higest:backfill-cost-field --dry-run   (preview only)
 *   php artisan higest:backfill-cost-field --force      (overwrite existing values too)
 */
class BackfillCostFieldCommand extends Command
{
    protected $signature = 'higest:backfill-cost-field
                            {--dry-run : Preview what would be updated without writing}
                            {--force : Overwrite existing cost values (default: skip if already set)}';

    protected $description = 'Backfill Bagisto cost EAV field from higest_source_offers acquisition_cost';

    public function handle(): int
    {
        $isDryRun = $this->option('dry-run');
        $force = $this->option('force');

        $costAttributeId = (int) (Attribute::where('code', 'cost')->value('id') ?? 0);

        if ($costAttributeId === 0) {
            $this->error('Bagisto "cost" attribute not found in the attributes table.');

            return self::FAILURE;
        }

        $this->info("Cost attribute ID: {$costAttributeId}");

        // Load all source offers, optionally skipping those already having a cost EAV row.
        $query = DB::table('higest_source_offers as o')
            ->select('o.variant_id', 'o.product_id', 'o.acquisition_cost', 'o.source_currency');

        if (! $force) {
            // Left-join to find variants that already have a cost EAV value set.
            $query
                ->leftJoin('product_attribute_values as pav', function ($join) use ($costAttributeId) {
                    $join->on('pav.product_id', '=', 'o.variant_id')
                        ->where('pav.attribute_id', '=', $costAttributeId)
                        ->whereNull('pav.channel')
                        ->whereNull('pav.locale');
                })
                ->whereNull('pav.id'); // only variants without an existing cost row
        }

        $offers = $query->orderBy('o.id')->get();

        $total = $offers->count();
        $updated = 0;
        $skipped = 0;
        $failed = 0;

        if ($total === 0) {
            $this->info('Nothing to backfill — all variants already have a cost value set.');

            return self::SUCCESS;
        }

        $this->info("Found {$total} variant(s) to backfill".($isDryRun ? ' [DRY RUN]' : '').'.');

        $bar = $this->output->createProgressBar($total);
        $bar->start();

        foreach ($offers as $offer) {
            $variantId = (int) $offer->variant_id;
            $acquisitionCost = (float) $offer->acquisition_cost;
            $uniqueId = "||{$variantId}|{$costAttributeId}";

            if ($isDryRun) {
                $this->newLine();
                $this->line("  [DRY] variant_id={$variantId}  cost={$acquisitionCost} {$offer->source_currency}");
                $bar->advance();
                $updated++;

                continue;
            }

            try {
                ProductAttributeValue::updateOrCreate(
                    [
                        'product_id' => $variantId,
                        'attribute_id' => $costAttributeId,
                        'channel' => null,
                        'locale' => null,
                    ],
                    [
                        'float_value' => $acquisitionCost,
                        'unique_id' => $uniqueId,
                    ]
                );
                $updated++;
            } catch (Throwable $e) {
                $failed++;
                Log::channel('aliexpress')->error('BackfillCostField: failed to write cost EAV', [
                    'variant_id' => $variantId,
                    'error' => $e->getMessage(),
                ]);
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        $this->table(
            ['Metric', 'Count'],
            [
                ['Total variants', $total],
                ['Updated',        $updated],
                ['Skipped',        $skipped],
                ['Failed',         $failed],
            ]
        );

        if ($isDryRun) {
            $this->warn('Dry run complete — nothing was written.');
        } else {
            $this->info('Backfill complete.');
            Log::channel('aliexpress')->info('BackfillCostField: backfill completed', [
                'total' => $total,
                'updated' => $updated,
                'failed' => $failed,
            ]);
        }

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }
}
