<?php

namespace Webkul\Fulfillment\Services\Domain;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Webkul\Fulfillment\Models\ProcurementSession;
use Webkul\Fulfillment\Models\PurchaseOrder;

class SupplierFailureCompensationService
{
    public function compensate(ProcurementSession $session, string $reason): void
    {
        DB::transaction(function () use ($session, $reason) {
            $session->transitionTo('FAILED');
            $session->update(['error_message' => $reason]);

            $allocation = $session->allocation;
            if ($allocation && $allocation->state !== 'canceled') {
                $allocation->cancel($allocation->reserved_qty ?: 1, $reason);
            }

            $po = PurchaseOrder::where('id', $session->aggregate?->purchase_order_id)->first();
            if ($po && $po->state !== PurchaseOrder::STATE_CANCELED) {
                $po->cancel($reason);
            }

            DB::table('domain_outbox_events')->insert([
                'event_id'       => (string) Str::uuid(),
                'event_name'     => 'SupplierOrderRefunded',
                'event_version'  => 1,
                'aggregate_type' => 'ProcurementSession',
                'aggregate_id'   => (string) $session->id,
                'correlation_id' => $session->correlation_id,
                'causation_id'   => $session->causation_id,
                'payload'        => json_encode([
                    'procurement_session_id' => $session->id,
                    'purchase_order_id'      => $po?->id,
                    'order_id'               => $allocation?->order_id,
                    'supplier_cost'          => $session->price_snapshot['current_cost'] ?? 0.00,
                    'reason'                 => $reason,
                ]),
                'status'         => 'pending',
                'attempts'       => 0,
                'created_at'     => now(),
                'updated_at'     => now(),
            ]);
        });
    }
}
