<?php

namespace App\Console\Commands;

use App\Models\AliExpressProductImport;
use App\Services\AliExpress\Learning\AliExpressLearningEngine;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Webkul\Product\Models\Product;

class TrainAliExpressLearningEngine extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'aliexpress:train-engine {--reset : Reset existing learned weights before training}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Bootstrap and train the AliExpress Continuous Learning Categorization Engine from existing products.';

    /**
     * Execute the console command.
     */
    public function handle(AliExpressLearningEngine $engine): int
    {
        $this->info('Starting Continuous Learning Categorization Engine training...');

        if ($this->option('reset')) {
            $this->warn('Resetting learned category mappings and keyword weights...');
            DB::table('aliexpress_category_mappings')->truncate();
            DB::table('aliexpress_keyword_weights')->truncate();
        }

        // 1. Train from AliExpress Product Imports where products are assigned to valid categories
        $imports = AliExpressProductImport::where('status', 'success')
            ->whereNotNull('product_id')
            ->get();

        $this->info(sprintf('Found %d completed AliExpress import records.', $imports->count()));

        $trainedCount = 0;

        foreach ($imports as $import) {
            /** @var Product|null $product */
            $product = Product::with(['categories', 'attribute_values'])->find($import->product_id);

            if (! $product) {
                continue;
            }

            // Find valid category (exclude root #1 and 'other' fallback #714)
            $targetCategory = $product->categories
                ->whereNotIn('id', [1, 714])
                ->sortByDesc('id')
                ->first();

            if (! $targetCategory) {
                continue;
            }

            $name = $product->name ?? '';
            $aeCatId = null;

            if (! empty($import->payload_raw) && is_array($import->payload_raw)) {
                $aeCatId = $import->payload_raw['category_id'] ?? null;
            }

            $engine->learnFromProduct([
                'title' => $name,
                'name' => $name,
                'aliexpress_category_id' => $aeCatId,
            ], (int) $targetCategory->id, 'bootstrap_import');

            $trainedCount++;
        }

        // 2. Also train from general catalog products with categories
        $catalogProducts = Product::with(['categories'])
            ->whereNotIn('id', $imports->pluck('product_id')->filter()->all())
            ->limit(500)
            ->get();

        foreach ($catalogProducts as $catProduct) {
            $targetCategory = $catProduct->categories
                ->whereNotIn('id', [1, 714])
                ->sortByDesc('id')
                ->first();

            if (! $targetCategory || empty($catProduct->name)) {
                continue;
            }

            $engine->learnFromProduct([
                'title' => $catProduct->name,
                'name' => $catProduct->name,
            ], (int) $targetCategory->id, 'bootstrap_catalog');

            $trainedCount++;
        }

        $mappingsCount = DB::table('aliexpress_category_mappings')->count();
        $keywordsCount = DB::table('aliexpress_keyword_weights')->count();

        $this->newLine();
        $this->info(' Training Completed Successfully!');
        $this->table(
            ['Metric', 'Count'],
            [
                ['Products Processed', $trainedCount],
                ['Learned AliExpress Category Bridges', $mappingsCount],
                ['Learned Keywords & N-Grams', $keywordsCount],
            ]
        );

        return self::SUCCESS;
    }
}
