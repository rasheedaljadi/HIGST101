<?php

namespace Webkul\Sales\Services\Lifecycle;

use Illuminate\Support\Facades\DB;
use Webkul\Sales\Models\Order;
use Webkul\Sales\Models\OrderItemLifecycleStageView;
use Webkul\Sales\Models\OrderLifecycleStageView;

class OrderLifecycleProjector
{
    public function __construct(
        protected OrderLifecycleStageResolver $resolver
    ) {}

    /**
     * Project / rebuild the Read Model views for a single Order.
     * Guaranteed to be 100% IDEMPOTENT.
     *
     * @param  Order|int  $order
     */
    public function project($order): OrderLifecycleStageView
    {
        if (! $order instanceof Order) {
            $order = Order::findOrFail($order);
        }

        return DB::transaction(function () use ($order) {
            $hasImportedItems = false;
            $hasInternalItems = false;
            $itemRanks = [];
            $allCanceled = true;

            $items = $order->items;

            foreach ($items as $item) {
                $resolved = $this->resolver->resolveItemStage($item, $order);

                if ($resolved['origin_type'] === 'imported') {
                    $hasImportedItems = true;
                } else {
                    $hasInternalItems = true;
                }

                if (! $resolved['is_exception']) {
                    $allCanceled = false;
                    $itemRanks[] = [
                        'stage_code' => $resolved['current_stage_code'],
                        'rank' => $resolved['rank'],
                    ];
                }

                // Update or create item view (Idempotent updateOrCreate)
                OrderItemLifecycleStageView::updateOrCreate(
                    ['order_item_id' => $item->id],
                    [
                        'order_id' => $order->id,
                        'origin_type' => $resolved['origin_type'],
                        'current_stage_code' => $resolved['current_stage_code'],
                        'source_type' => $resolved['source_type'] ?? null,
                        'is_exception' => $resolved['is_exception'],
                        'exception_reason' => $resolved['exception_reason'],
                        'computed_at' => now(),
                    ]
                );
            }

            $isMixedOrder = $hasImportedItems && $hasInternalItems;

            // Determine Bottleneck Stage (minimum rank among active fulfillable items)
            if ($allCanceled || empty($itemRanks)) {
                $bottleneckStageCode = 'new';
                $currentStageCode = 'new';
                $isException = true;
                $exceptionReason = 'canceled';
            } else {
                usort($itemRanks, fn ($a, $b) => $a['rank'] <=> $b['rank']);
                $bottleneckStageCode = $itemRanks[0]['stage_code'];
                $currentStageCode = $bottleneckStageCode;
                $isException = false;
                $exceptionReason = null;
            }

            // Update or create order stage view
            return OrderLifecycleStageView::updateOrCreate(
                ['order_id' => $order->id],
                [
                    'current_stage_code' => $currentStageCode,
                    'bottleneck_stage_code' => $bottleneckStageCode,
                    'is_mixed_order' => $isMixedOrder,
                    'has_imported_items' => $hasImportedItems,
                    'has_internal_items' => $hasInternalItems,
                    'is_exception' => $isException,
                    'exception_reason' => $exceptionReason,
                    'computed_at' => now(),
                    'source_version' => 'v1.0',
                ]
            );
        });
    }
}
