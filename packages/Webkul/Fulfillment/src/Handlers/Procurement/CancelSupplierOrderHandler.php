<?php

namespace Webkul\Fulfillment\Handlers\Procurement;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Webkul\Fulfillment\Commands\CancelSupplierOrderCommand;
use Webkul\Fulfillment\Models\ProcurementSession;
use Webkul\Fulfillment\Models\ExternalOrder;
use Webkul\Fulfillment\Services\FulfillmentProviderRegistry;

class CancelSupplierOrderHandler
{
    public function __construct(protected FulfillmentProviderRegistry $registry) {}

    public function handle(CancelSupplierOrderCommand $command): ProcurementSession
    {
        return DB::transaction(function () use ($command) {
            $session = ProcurementSession::findOrFail($command->procurementSessionId);

            $session->transitionTo('CANCEL_REQUESTED');

            $extOrder = ExternalOrder::where('procurement_session_id', $session->id)->first();

            $providerCode = 'aliexpress';
            if (app()->environment('testing')) {
                $providerCode = 'aliexpress_simulator';
            }

            $provider = $this->registry->resolve($providerCode);

            if ($extOrder) {
                $result = $provider->cancelSupplierOrder(
                    $extOrder->external_order_id,
                    $extOrder->provider_account_id,
                    $session->contract_version
                );

                if ($result->ok) {
                    $session->transitionTo('CANCELLED');

                    $extOrder->update(['status' => 'CANCELLED']);

                    DB::table('external_order_projections')
                        ->where('external_order_id', $extOrder->external_order_id)
                        ->update(['status' => 'CANCELLED', 'updated_at' => now()]);

                    DB::table('procurement_timelines')->insert([
                        'procurement_session_id' => $session->id,
                        'purchase_order_id'      => $extOrder->purchase_order_id,
                        'stage'                  => 'CANCELLED',
                        'payload'                => json_encode($result->raw),
                        'correlation_id'         => $command->correlationId,
                        'causation_id'           => $command->causationId,
                        'created_at'             => now(),
                    ]);

                    DB::table('domain_outbox_events')->insert([
                        'event_id'       => (string) Str::uuid(),
                        'event_name'     => 'ProcurementFailed',
                        'event_version'  => 1,
                        'aggregate_type' => 'ProcurementSession',
                        'aggregate_id'   => (string) $session->id,
                        'correlation_id' => $command->correlationId,
                        'causation_id'   => $command->causationId,
                        'payload'        => json_encode([
                            'procurement_session_id' => $session->id,
                            'purchase_order_id'      => $extOrder->purchase_order_id,
                            'error_message'          => $command->reason,
                        ]),
                        'status'         => 'pending',
                        'attempts'       => 0,
                        'created_at'     => now(),
                        'updated_at'     => now(),
                    ]);
                } else {
                    $session->transitionTo('FAILED');
                }
            } else {
                $session->transitionTo('CANCELLED');
            }

            return $session;
        });
    }
}
