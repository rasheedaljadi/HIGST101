<?php

namespace Webkul\Fulfillment\Handlers\Procurement;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Webkul\Fulfillment\Commands\SyncSupplierOrderStatusCommand;
use Webkul\Fulfillment\Models\ProcurementSession;
use Webkul\Fulfillment\Models\ExternalOrder;
use Webkul\Fulfillment\Services\FulfillmentProviderRegistry;

class SyncSupplierOrderStatusHandler
{
    public function __construct(protected FulfillmentProviderRegistry $registry) {}

    public function handle(SyncSupplierOrderStatusCommand $command): ProcurementSession
    {
        return DB::transaction(function () use ($command) {
            $session = ProcurementSession::findOrFail($command->procurementSessionId);

            $extOrder = ExternalOrder::where('procurement_session_id', $session->id)->first();
            if (! $extOrder) {
                return $session;
            }

            $providerCode = 'aliexpress';
            if (app()->environment('testing')) {
                $providerCode = 'aliexpress_simulator';
            }

            $provider = $this->registry->resolve($providerCode);

            $status = $provider->getSupplierOrderStatus(
                $extOrder->external_order_id,
                $extOrder->provider_account_id,
                $session->contract_version
            );

            $extOrder->update(['status' => $status->mappedState]);

            DB::table('external_order_projections')->updateOrInsert(
                ['external_order_id' => $extOrder->external_order_id],
                [
                    'purchase_order_id' => $extOrder->purchase_order_id,
                    'status'            => $status->mappedState,
                    'tracking_number'   => $status->trackingNumber,
                    'carrier'           => $status->trackingCompany,
                    'updated_at'        => now(),
                ]
            );

            if ($status->mappedState === 'SHIPPED' && $session->state !== 'SHIPPED') {
                $session->transitionTo('SHIPPED');

                DB::table('procurement_timelines')->insert([
                    'procurement_session_id' => $session->id,
                    'purchase_order_id'      => $extOrder->purchase_order_id,
                    'stage'                  => 'SHIPPED',
                    'payload'                => json_encode([
                        'tracking_number' => $status->trackingNumber,
                        'carrier'         => $status->trackingCompany,
                    ]),
                    'correlation_id'         => $command->correlationId,
                    'causation_id'           => $command->causationId,
                    'created_at'             => now(),
                ]);

                DB::table('domain_outbox_events')->insert([
                    'event_id'       => (string) Str::uuid(),
                    'event_name'     => 'ProcurementShipped',
                    'event_version'  => 1,
                    'aggregate_type' => 'ProcurementSession',
                    'aggregate_id'   => (string) $session->id,
                    'correlation_id' => $command->correlationId,
                    'causation_id'   => $command->causationId,
                    'payload'        => json_encode([
                        'procurement_session_id' => $session->id,
                        'purchase_order_id'      => $extOrder->purchase_order_id,
                        'tracking_number'        => $status->trackingNumber,
                        'carrier'                => $status->trackingCompany,
                    ]),
                    'status'         => 'pending',
                    'attempts'       => 0,
                    'created_at'     => now(),
                    'updated_at'     => now(),
                ]);

                $po = \Webkul\Fulfillment\Models\PurchaseOrder::find($extOrder->purchase_order_id);
                $poCost = $po ? (float) $po->items->sum(fn($i) => $i->qty * $i->supplier_unit_cost) : 100.00;

                DB::table('domain_outbox_events')->insert([
                    'event_id'       => (string) Str::uuid(),
                    'event_name'     => 'SupplierOrderPaid',
                    'event_version'  => 1,
                    'aggregate_type' => 'ProcurementSession',
                    'aggregate_id'   => (string) $session->id,
                    'correlation_id' => $command->correlationId,
                    'causation_id'   => $command->causationId,
                    'payload'        => json_encode([
                        'procurement_session_id' => $session->id,
                        'purchase_order_id'      => $extOrder->purchase_order_id,
                        'external_order_id'      => $extOrder->external_order_id,
                        'supplier_cost'          => $poCost,
                    ]),
                    'status'         => 'pending',
                    'attempts'       => 0,
                    'created_at'     => now(),
                    'updated_at'     => now(),
                ]);

            } elseif ($status->mappedState === 'COMPLETED' && $session->state !== 'COMPLETED') {
                if ($session->state === 'SUBMITTED' || $session->state === 'WAITING_PAYMENT') {
                    $session->state = 'SHIPPED';
                }

                $session->transitionTo('COMPLETED');

                DB::table('procurement_timelines')->insert([
                    'procurement_session_id' => $session->id,
                    'purchase_order_id'      => $extOrder->purchase_order_id,
                    'stage'                  => 'COMPLETED',
                    'payload'                => null,
                    'correlation_id'         => $command->correlationId,
                    'causation_id'           => $command->causationId,
                    'created_at'             => now(),
                ]);

                DB::table('domain_outbox_events')->insert([
                    'event_id'       => (string) Str::uuid(),
                    'event_name'     => 'ProcurementCompleted',
                    'event_version'  => 1,
                    'aggregate_type' => 'ProcurementSession',
                    'aggregate_id'   => (string) $session->id,
                    'correlation_id' => $command->correlationId,
                    'causation_id'   => $command->causationId,
                    'payload'        => json_encode([
                        'procurement_session_id' => $session->id,
                        'purchase_order_id'      => $extOrder->purchase_order_id,
                    ]),
                    'status'         => 'pending',
                    'attempts'       => 0,
                    'created_at'     => now(),
                    'updated_at'     => now(),
                ]);
            }

            return $session;
        });
    }
}
