<?php

namespace Webkul\Fulfillment\Handlers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Webkul\Fulfillment\Commands\ReserveAllocationCommand;
use Webkul\Fulfillment\Models\OrderAllocation;

class ReserveAllocationHandler
{
    /**
     * Handle the allocation reservation command.
     *
     * @param  \Webkul\Fulfillment\Commands\ReserveAllocationCommand  $command
     * @return \Webkul\Fulfillment\Models\OrderAllocation
     */
    public function handle(ReserveAllocationCommand $command): OrderAllocation
    {
        return DB::transaction(function () use ($command) {
            $orderItem = DB::table('order_items')->where('id', $command->orderItemId)->first();
            $productId = null;
            $variantProductId = null;

            if ($orderItem) {
                if ($orderItem->parent_id) {
                    $parentItem = DB::table('order_items')->where('id', $orderItem->parent_id)->first();
                    $productId = $parentItem ? $parentItem->product_id : $orderItem->product_id;
                    $variantProductId = $orderItem->product_id;
                } else {
                    $childItem = DB::table('order_items')->where('parent_id', $orderItem->id)->first();
                    if ($childItem) {
                        $productId = $orderItem->product_id;
                        $variantProductId = $childItem->product_id;
                    } else {
                        $productId = $orderItem->product_id;
                        $variantProductId = $orderItem->product_id;
                    }
                }
            }

            $supplierSnapshot = null;
            if ($command->allocationType === 'supplier' && $productId) {
                $import = DB::table('aliexpress_product_imports')
                    ->where('product_id', $productId)
                    ->where('status', 'success')
                    ->first();
                
                if ($import && $import->payload_snapshot) {
                    $payload = json_decode($import->payload_snapshot, true);
                    $variants = $payload['variants'] ?? [];
                    
                    $projection = DB::table('external_variant_projections')
                        ->where('variant_product_id', $variantProductId)
                        ->first();
                    
                    $supplierSkuId = $projection ? $projection->external_sku_id : null;
                    
                    $matchedVariant = null;
                    if ($supplierSkuId) {
                        foreach ($variants as $v) {
                            if (($v['sku_id'] ?? null) === $supplierSkuId) {
                                $matchedVariant = $v;
                                break;
                            }
                        }
                    } elseif (count($variants) === 1) {
                        $matchedVariant = $variants[0];
                        $supplierSkuId = $matchedVariant['sku_id'] ?? null;
                    }
                    
                    if ($matchedVariant) {
                        $supplierCost = (float) ($matchedVariant['price'] ?? 0.0);
                        $supplierCurrency = $payload['currency'] ?? 'USD';
                        
                        $exchangeRate = 1.0000;
                        try {
                            $rateRow = DB::table('currency_rates')
                                ->join('currencies', 'currency_rates.target_currency', '=', 'currencies.id')
                                ->where('currencies.code', $supplierCurrency)
                                ->first();
                            $exchangeRate = $rateRow ? (float) $rateRow->rate : 1.0000;
                        } catch (\Throwable $e) {
                            // Ignore
                        }
                        
                        $landedCost = $supplierCost * $exchangeRate;

                        $optionsText = '';
                        if (!empty($matchedVariant['options_by_axis'])) {
                            $optParts = [];
                            foreach ($matchedVariant['options_by_axis'] as $axis => $val) {
                                $optParts[] = "{$axis}: {$val}";
                            }
                            $optionsText = implode(', ', $optParts);
                        }

                        $supplierProductId = $payload['aliexpress_product_id'] ?? '';
                        $supplierImage = !empty($matchedVariant['image_urls']) ? $matchedVariant['image_urls'][0] : ($payload['image_urls'][0] ?? '');
                        
                        $hashSource = implode('|', [
                            $supplierProductId,
                            $supplierSkuId,
                            (string) $supplierCost,
                            $optionsText,
                            $supplierImage
                        ]);
                        $snapshotHash = hash('sha256', $hashSource);

                        $supplierSnapshot = [
                            'supplier_product_id' => $supplierProductId,
                            'supplier_sku_id'     => $supplierSkuId,
                            'supplier_cost'       => $supplierCost,
                            'supplier_currency'   => $supplierCurrency,
                            'exchange_rate'       => $exchangeRate,
                            'landed_cost'         => $landedCost,
                            'supplier_title'      => $payload['title'] ?? '',
                            'supplier_variant'    => $optionsText,
                            'supplier_image'      => $supplierImage,
                            'snapshot_hash'       => $snapshotHash,
                            'snapshot_version'    => '1.0.0',
                        ];
                    }
                }
            }

            $allocation = OrderAllocation::create([
                'order_id'           => $command->orderId,
                'order_item_id'      => $command->orderItemId,
                'product_id'         => $productId,
                'variant_product_id' => $variantProductId,
                'allocation_type'    => $command->allocationType,
                'source_code'        => $command->sourceCode,
                'reserved_qty'       => $command->quantity,
                'supplier_snapshot'  => $supplierSnapshot,
                'state'              => 'reserved',
                'version'            => 1,
            ]);

            // Persist the domain event inside the Outbox in the same transaction
            DB::table('domain_outbox_events')->insert([
                'event_id'       => (string) Str::uuid(),
                'event_name'     => 'OrderAllocationReserved',
                'event_version'  => 1,
                'aggregate_type' => 'OrderAllocation',
                'aggregate_id'   => (string) $allocation->id,
                'correlation_id' => $command->correlationId,
                'causation_id'   => $command->causationId,
                'payload'        => json_encode([
                    'allocation_id'   => $allocation->id,
                    'order_id'        => $command->orderId,
                    'order_item_id'   => $command->orderItemId,
                    'allocation_type' => $command->allocationType,
                    'source_code'     => $command->sourceCode,
                    'quantity'        => $command->quantity,
                    'revenue_amount'  => $orderItem ? (float) $orderItem->total : 0.00,
                    'supplier_cost'   => isset($supplierSnapshot['landed_cost']) ? (float) ($supplierSnapshot['landed_cost'] * $command->quantity) : 0.00,
                ]),
                'status'         => 'pending',
                'attempts'       => 0,
                'created_at'     => now(),
                'updated_at'     => now(),
            ]);

            return $allocation;
        });
    }
}
