<?php

namespace Webkul\FlashDeal\Console\Commands;

use Illuminate\Console\Command;
use Webkul\FlashDeal\Helpers\SmartThumbnailHelper;
use Webkul\Product\Models\Product;

class GenerateSmartSquareThumbnailsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'product:images:smart-generate 
                            {--all : Process all products, including square ones}
                            {--limit= : Limit the number of products to process}
                            {--chunk=100 : Number of products per chunk}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate 1:1 Smart Thumbnails for product cards (prioritizing non-square images)';

    /**
     * Execute the console command.
     */
    public function handle(SmartThumbnailHelper $helper): int
    {
        $this->info('=== Starting Smart 1:1 Thumbnail Generation ===');

        $processAll = (bool) $this->option('all');
        $limit = $this->option('limit') ? (int) $this->option('limit') : null;
        $chunkSize = (int) $this->option('chunk');

        $query = Product::with('images')
            ->whereHas('images')
            ->orderBy('id', 'desc');

        if ($limit) {
            $query->limit($limit);
        }

        $totalProcessed = 0;
        $nonSquareCount = 0;
        $fastPathCount = 0;
        $failedCount = 0;

        $bar = $this->output->createProgressBar($limit ?: $query->count());
        $bar->start();

        $query->chunk($chunkSize, function ($products) use ($helper, $processAll, &$totalProcessed, &$nonSquareCount, &$fastPathCount, &$failedCount, $bar) {
            foreach ($products as $product) {
                $baseImage = $product->images->first();
                if (! $baseImage || ! $baseImage->path) {
                    $bar->advance();

                    continue;
                }

                $sourcePath = storage_path('app/public/'.$baseImage->path);
                if (! file_exists($sourcePath)) {
                    $bar->advance();

                    continue;
                }

                // Check aspect ratio if not processing all
                if (! $processAll) {
                    $size = @getimagesize($sourcePath);
                    if ($size && $size[1] > 0) {
                        $ratio = $size[0] / $size[1];
                        // If it's already square (0.97 <= ratio <= 1.03), skip it unless --all is passed
                        if (abs($ratio - 1.0) <= 0.03) {
                            $fastPathCount++;
                            $bar->advance();

                            continue;
                        }
                    }
                }

                try {
                    $url = $helper->getSquareThumbnailUrl($product);
                    if ($url) {
                        $nonSquareCount++;
                    } else {
                        $failedCount++;
                    }
                } catch (\Throwable $e) {
                    $failedCount++;
                }

                $totalProcessed++;
                $bar->advance();
            }
        });

        $bar->finish();
        $this->newLine(2);

        $this->info('Completed!');
        $this->table(
            ['Metric', 'Count'],
            [
                ['Non-Square Images Processed & Squared', $nonSquareCount],
                ['Square Images Skipped (Fast-Path)', $fastPathCount],
                ['Failed / Skipped', $failedCount],
                ['Total Handled', $totalProcessed + $fastPathCount],
            ]
        );

        return 0;
    }
}
