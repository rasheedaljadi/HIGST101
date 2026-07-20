<?php

namespace Webkul\Fulfillment\Handlers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Webkul\Fulfillment\Commands\CancelAllocationCommand;
use Webkul\Fulfillment\Models\OrderAllocation;

class CancelAllocationHandler
{
    /**
     * Handle the allocation cancellation command.
     *
     * @param  \Webkul\Fulfillment\Commands\CancelAllocationCommand  $command
     * @return \Webkul\Fulfillment\Models\OrderAllocation
     */
    public function handle(CancelAllocationCommand $command): OrderAllocation
    {
        return DB::transaction(function () use ($command) {
            $allocation = OrderAllocation::findOrFail($command->allocationId);
            
            // Perform the cancellation state and quantities change inside the model
            $allocation->cancel($command->quantity, $command->reason);

            // Append domain event to outbox
            DB::table('domain_outbox_events')->insert([
                'event_id'       => (string) Str::uuid(),
                'event_name'     => 'OrderAllocationReleased',
                'event_version'  => 1,
                'aggregate_type' => 'OrderAllocation',
                'aggregate_id'   => (string) $allocation->id,
                'correlation_id' => $command->correlationId,
                'causation_id'   => $command->causationId,
                'payload'        => json_encode([
                    'allocation_id' => $allocation->id,
                    'order_id'      => $allocation->order_id,
                    'quantity'      => $command->quantity,
                    'reason'        => $command->reason,
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
