<?php

namespace Webkul\Fulfillment\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Webkul\Fulfillment\Contracts\SyncProviderInterface;
use Webkul\Fulfillment\DataObjects\NormalizedExternalProductBatch;
use Webkul\Fulfillment\DataObjects\ProviderSyncCapabilities;
use Webkul\Fulfillment\DataObjects\SyncCursor;
use Webkul\Fulfillment\Services\Application\SyncEngine;

class BenchmarkSyncCommand extends Command
{
    protected $signature = 'fulfillment:sync-benchmark {provider} {--batch-size=50}';

    protected $description = 'Benchmark the catalog sync engine for a given provider';

    public function handle(SyncEngine $engine): int
    {
        $providerName = $this->argument('provider');
        $batchSize = (int) $this->option('batch-size');

        $this->info("Initializing sync benchmark for provider [{$providerName}]...");

        // Create a mock provider for benchmarking
        $mockProvider = new class($providerName) implements SyncProviderInterface {
            private string $name;
            public function __construct(string $name) { $this->name = $name; }
            public function getCapabilities(): ProviderSyncCapabilities {
                return new ProviderSyncCapabilities(1, true, true, true, true, true);
            }
            public function fetchProductsBatch(SyncCursor $cursor, int $batchSize): NormalizedExternalProductBatch {
                // Generate simulated batch data
                $products = [];
                for ($i = 1; $i <= $batchSize; $i++) {
                    $supplierProductId = "benchmark_sp_" . $i;
                    $products[] = [
                        'id' => $supplierProductId,
                        'variants' => [
                            [
                                'sku_id' => "benchmark_sku_" . $i,
                                'price' => 10.00 + $i,
                                'stock' => 100 + $i,
                                'version' => 1,
                                'options' => []
                            ]
                        ],
                        'metadata' => [
                            'provider_updated_at' => now()->toIso8601String(),
                        ]
                    ];
                }
                return new NormalizedExternalProductBatch($this->name, $products, null, false);
            }
        };

        $startTime = microtime(true);

        try {
            // Ensure necessary test projections exist to prevent skipped products during changes comparison
            // Let's seed a mock record if not exists
            DB::table('external_variant_projections')->updateOrInsert(
                ['provider' => $providerName, 'external_sku_id' => 'benchmark_sku_1'],
                [
                    'product_id' => 1,
                    'variant_product_id' => 1,
                    'external_product_id' => 'benchmark_sp_1',
                    'external_variant_version' => 0,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );

            $run = $engine->execute($providerName, $mockProvider, $batchSize);

            $duration = microtime(true) - $startTime;
            $peakMemory = memory_get_peak_usage(true);

            $scanned = $run->statistics['scanned'] ?? 0;
            $changed = $run->statistics['changed'] ?? 0;
            $published = $run->statistics['published'] ?? 0;

            $throughput = $duration > 0 ? ($scanned / $duration) : 0;
            $latencyAvg = $scanned > 0 ? (int) (($duration * 1000) / $scanned) : 0;

            // Log results to sync_benchmarks table
            DB::table('sync_benchmarks')->insert([
                'date' => now(),
                'provider' => $providerName,
                'throughput' => $throughput,
                'memory_peak_bytes' => $peakMemory,
                'latency_avg_ms' => $latencyAvg,
                'products_changed' => $changed,
                'products_unchanged' => $scanned - $changed,
                'stale_events' => 0,
                'replay_events' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $this->info("Benchmark completed successfully.");
            $this->table(
                ['Metric', 'Value'],
                [
                    ['Total Scanned', $scanned],
                    ['Total Changed', $changed],
                    ['Events Published', $published],
                    ['Throughput', round($throughput, 2) . ' products/sec'],
                    ['Avg Latency', $latencyAvg . ' ms'],
                    ['Peak Memory', round($peakMemory / 1024 / 1024, 2) . ' MB'],
                    ['Duration', round($duration, 2) . ' seconds'],
                ]
            );

        } catch (\Throwable $e) {
            $this->error("Benchmark failed: " . $e->getMessage());
            return 1;
        }

        return 0;
    }
}
