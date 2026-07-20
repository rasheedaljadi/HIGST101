<?php

namespace App\Console\Commands;

use App\Exceptions\AliExpress\AliExpressImportException;
use App\Services\AliExpress\AliExpressCategorySynchronizer;
use Illuminate\Console\Command;

/**
 * Syncs the top-level AliExpress categories into the Bagisto catalog (one-off),
 * translating their names to Arabic. Deeper categories are created on demand
 * during product import.
 *
 * Usage: php artisan aliexpress:sync-categories
 */
class AliExpressSyncCategories extends Command
{
    protected $signature = 'aliexpress:sync-categories
        {--retranslate : Re-translate existing synced categories to Arabic (e.g. after quota resets), without re-fetching}
        {--top-only : Sync only the top-level categories instead of the full tree}';

    protected $description = 'Fetch and mirror the AliExpress category tree into the Bagisto catalog (Arabic translated)';

    public function handle(AliExpressCategorySynchronizer $synchronizer): int
    {
        if ($this->option('retranslate')) {
            $this->info('Re-translating existing AliExpress categories to Arabic...');

            $stats = $synchronizer->retranslateExisting();

            $this->info(sprintf('Done. %d of %d categories re-translated.', $stats['updated'], $stats['total']));

            return self::SUCCESS;
        }

        $topOnly = (bool) $this->option('top-only');

        $this->info($topOnly
            ? 'Fetching top-level AliExpress categories...'
            : 'Fetching the full AliExpress category tree (this may take a moment)...');

        try {
            $stats = $topOnly ? $synchronizer->syncTopLevel() : $synchronizer->syncFullTree();
        } catch (AliExpressImportException $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $this->info(sprintf(
            'Done. Fetched %d categories: %d created, %d already existed.',
            $stats['fetched'],
            $stats['created'],
            $stats['skipped'],
        ));

        return self::SUCCESS;
    }
}
