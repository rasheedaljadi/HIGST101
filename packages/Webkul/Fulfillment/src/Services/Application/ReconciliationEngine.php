<?php

namespace Webkul\Fulfillment\Services\Application;

use Illuminate\Support\Facades\DB;
use Webkul\Fulfillment\Models\ExternalOrder;
use Webkul\Fulfillment\Services\FulfillmentProviderRegistry;

class ReconciliationEngine
{
    public function __construct(protected FulfillmentProviderRegistry $registry) {}

    /**
     * Reconcile Purchase Orders with supplier status.
     * Returns: Difference Report array
     */
    public function reconcile(): array
    {
        $activeOrders = ExternalOrder::all();
        $report = [];

        foreach ($activeOrders as $extOrder) {
            try {
                $provider = $this->registry->resolve($extOrder->provider);
                $status = $provider->getSupplierOrderStatus(
                    $extOrder->external_order_id,
                    $extOrder->provider_account_id
                );

                if (strtolower($extOrder->status) !== strtolower($status->mappedState)) {
                    $report[] = [
                        'external_order_id' => $extOrder->external_order_id,
                        'purchase_order_id' => $extOrder->purchase_order_id,
                        'provider'          => $extOrder->provider,
                        'field'             => 'status',
                        'internal_value'    => $extOrder->status,
                        'external_value'    => $status->mappedState,
                    ];

                    $extOrder->update(['status' => $status->mappedState]);

                    DB::table('external_order_projections')
                        ->where('external_order_id', $extOrder->external_order_id)
                        ->update([
                            'status'          => $status->mappedState,
                            'tracking_number' => $status->trackingNumber,
                            'carrier'         => $status->trackingCompany,
                            'updated_at'      => now(),
                        ]);
                }
            } catch (\Throwable $e) {
                $report[] = [
                    'external_order_id' => $extOrder->external_order_id,
                    'purchase_order_id' => $extOrder->purchase_order_id,
                    'provider'          => $extOrder->provider,
                    'field'             => 'error',
                    'internal_value'    => 'sync',
                    'external_value'    => $e->getMessage(),
                ];
            }
        }

        return $report;
    }
}
