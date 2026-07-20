<?php

namespace App\Console\Commands;

use App\Models\AliExpressProductImport;
use App\Services\AliExpress\AliExpressFreightService;
use Illuminate\Console\Command;

/**
 * Backfills / refreshes the cached AliExpress shipping data on imported products
 * (base cost, delivery window, carrier) by querying aliexpress.ds.freight.query
 * for ship-to SA / store currency. Run once after deploying shipping support to
 * populate products imported before it existed, or periodically to refresh.
 *
 * Usage:
 *   php artisan aliexpress:sync-shipping            (only products missing shipping)
 *   php artisan aliexpress:sync-shipping --all      (refresh every successful import)
 */
class AliExpressSyncShipping extends Command
{
    protected $signature = 'aliexpress:sync-shipping
        {--all : Refresh shipping for all successful imports, not just those missing it}';

    protected $description = 'Fetch & cache AliExpress shipping cost/ETA for imported products (offline storefront pricing)';

    public function handle(AliExpressFreightService $freight): int
    {
        $query = AliExpressProductImport::query()
            ->where('status', 'success')
            ->whereNotNull('product_id');

        if (! $this->option('all')) {
            $query->whereNull('base_shipping_cost');
        }

        $imports = $query->get();

        if ($imports->isEmpty()) {
            $this->info('No imports need a shipping sync.');

            return self::SUCCESS;
        }

        $this->info("Syncing shipping for {$imports->count()} product(s)...");

        $updated = 0;
        $failed = 0;

        foreach ($imports as $import) {
            // Use the representative variant SKU from the cached snapshot.
            $skuId = data_get($import->payload_snapshot, 'variants.0.sku_id');

            $shipping = $freight->quote($import->aliexpress_product_id, $skuId ? (string) $skuId : null);

            if ($shipping === null) {
                $failed++;
                $this->line("  ✖ {$import->aliexpress_product_id} — no shipping resolved");

                continue;
            }

            $import->forceFill([
                'base_shipping_cost' => $shipping['cost'],
                'shipping_currency' => $shipping['currency'],
                'shipping_min_days' => $shipping['min_days'],
                'shipping_max_days' => $shipping['max_days'],
                'shipping_company' => $shipping['company'],
                'shipping_tracking' => $shipping['tracking'],
                'shipping_synced_at' => now(),
            ])->save();

            $updated++;
            $this->line(sprintf(
                '  ✓ %s — %.2f %s, %s–%s days',
                $import->aliexpress_product_id,
                $shipping['cost'],
                $shipping['currency'],
                $shipping['min_days'] ?? '?',
                $shipping['max_days'] ?? '?',
            ));
        }

        $this->newLine();
        $this->info("Done. {$updated} updated, {$failed} could not be resolved.");

        return self::SUCCESS;
    }
}
