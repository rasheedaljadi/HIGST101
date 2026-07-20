<?php

namespace Webkul\Fulfillment\Services\Application;

use Webkul\Fulfillment\Contracts\SyncProviderInterface;
use Webkul\Fulfillment\DataObjects\SyncCursor;
use Webkul\Fulfillment\DataObjects\SyncResult;
use Illuminate\Support\Facades\DB;

class SyncPipeline
{
    protected ChangeDetector $changeDetector;

    public function __construct(ChangeDetector $changeDetector)
    {
        $this->changeDetector = $changeDetector;
    }

    /**
     * Run the batch processing pipeline.
     */
    public function process(SyncProviderInterface $provider, SyncCursor $cursor, int $batchSize): SyncResult
    {
        $errors = [];
        $warnings = [];
        $changeSets = [];
        $processedCount = 0;
        $changedCount = 0;

        try {
            // 1. Fetch step
            $batch = $provider->fetchProductsBatch($cursor, $batchSize);

            // 2. Normalize step & 3. Detect Changes step
            foreach ($batch->products as $aeProduct) {
                $processedCount++;

                $supplierProductId = $aeProduct['id'] ?? '';
                if (empty($supplierProductId)) {
                    $warnings[] = "Product skipped: missing external supplier ID.";
                    continue;
                }

                // Find local product ID mapping
                $projection = DB::table('external_variant_projections')
                    ->where('provider', $batch->provider)
                    ->where('external_product_id', $supplierProductId)
                    ->first();

                if (!$projection) {
                    $warnings[] = "Supplier product [{$supplierProductId}] not mapped to any local product.";
                    continue;
                }

                $productId = $projection->product_id;

                // Detect changes
                $changeSet = $this->changeDetector->detect(
                    $productId,
                    $supplierProductId,
                    $batch->provider,
                    $aeProduct['variants'] ?? [],
                    $aeProduct['metadata'] ?? null
                );

                if ($changeSet->hasChanges()) {
                    $changedCount++;
                    $changeSets[] = $changeSet;
                }
            }

            // 4. Update Cursor step
            $newCursor = $cursor;
            if ($batch->next_page_token) {
                $newCursor = $newCursor->withNextPage($batch->next_page_token);
            }
            if (!empty($batch->products)) {
                $productsCopy = $batch->products;
                $lastProduct = end($productsCopy);
                if (isset($lastProduct['id'])) {
                    $newCursor = $newCursor->withLastProductId($lastProduct['id']);
                }
            }

            return new SyncResult(
                $processedCount,
                $changedCount,
                0, // Publisher updates event count later
                $errors,
                $warnings,
                $newCursor,
                $changeSets
            );

        } catch (\Throwable $e) {
            $errors[] = $e->getMessage();

            return new SyncResult(
                $processedCount,
                $changedCount,
                0,
                $errors,
                $warnings,
                $cursor,
                $changeSets
            );
        }
    }
}
