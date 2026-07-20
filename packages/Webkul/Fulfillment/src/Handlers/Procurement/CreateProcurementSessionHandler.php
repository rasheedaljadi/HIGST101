<?php

namespace Webkul\Fulfillment\Handlers\Procurement;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Webkul\Fulfillment\Commands\CreateProcurementSessionCommand;
use Webkul\Fulfillment\Models\ProcurementAggregate;
use Webkul\Fulfillment\Models\ProcurementSession;
use Webkul\Fulfillment\Models\OrderAllocation;
use Webkul\Fulfillment\Services\Domain\ProcurementPolicyEngine;

class CreateProcurementSessionHandler
{
    public function __construct(protected ProcurementPolicyEngine $policyEngine) {}

    public function handle(CreateProcurementSessionCommand $command): ProcurementSession
    {
        return DB::transaction(function () use ($command) {
            $aggregate = ProcurementAggregate::firstOrCreate([
                'purchase_order_id' => $command->purchaseOrderId
            ]);

            $allocation = OrderAllocation::findOrFail($command->orderAllocationId);
            $supplierSnap = $allocation->supplier_snapshot ? json_decode($allocation->supplier_snapshot, true) : [];

            $policyVersion = $this->policyEngine->getVersion();
            $policyHash = $this->policyEngine->getHash();
            $policySnapshot = $this->policyEngine->getSnapshot();

            $session = ProcurementSession::create([
                'procurement_aggregate_id' => $aggregate->id,
                'order_allocation_id'      => $allocation->id,
                'provider_account_id'      => null,
                'state'                    => 'CREATED',
                'contract_version'         => 'AliExpress Contract v2026-07',
                'policy_version'           => $policyVersion,
                'policy_hash'              => $policyHash,
                'policy_snapshot'          => $policySnapshot,
                'supplier_snapshot'        => $supplierSnap,
                'shipping_snapshot'        => null,
                'price_snapshot'           => [
                    'original_cost' => $supplierSnap['supplier_cost'] ?? 0.00,
                    'current_cost'  => $supplierSnap['supplier_cost'] ?? 0.00,
                ],
                'snapshot_hash'            => hash('sha256', json_encode($supplierSnap)),
                'correlation_id'           => $command->correlationId,
                'causation_id'             => $command->causationId,
                'trace_id'                 => $command->traceId,
                'span_id'                  => $command->spanId,
            ]);

            DB::table('domain_outbox_events')->insert([
                'event_id'       => (string) Str::uuid(),
                'event_name'     => 'ProcurementStarted',
                'event_version'  => 1,
                'aggregate_type' => 'ProcurementSession',
                'aggregate_id'   => (string) $session->id,
                'correlation_id' => $command->correlationId,
                'causation_id'   => $command->causationId,
                'payload'        => json_encode([
                    'procurement_session_id' => $session->id,
                    'purchase_order_id'      => $command->purchaseOrderId,
                    'allocation_id'          => $allocation->id,
                    'state'                  => 'CREATED',
                ]),
                'status'         => 'pending',
                'attempts'       => 0,
                'created_at'     => now(),
                'updated_at'     => now(),
            ]);

            return $session;
        });
    }
}
