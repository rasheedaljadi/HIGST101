<?php

namespace Webkul\Fulfillment\Services\Application;

use Webkul\Fulfillment\Models\ProcurementSession;
use Webkul\Fulfillment\Commands\SyncSupplierOrderStatusCommand;
use Webkul\Fulfillment\Handlers\Procurement\SyncSupplierOrderStatusHandler;

class ProcurementScheduler
{
    public function __construct(protected SyncSupplierOrderStatusHandler $syncHandler) {}

    /**
     * Run regular scheduling tasks for procurement.
     */
    public function run(): void
    {
        $activeSessions = ProcurementSession::whereIn('state', ['SUBMITTED', 'WAITING_PAYMENT', 'PROCESSING', 'SHIPPED'])->get();

        foreach ($activeSessions as $session) {
            $command = new SyncSupplierOrderStatusCommand(
                procurementSessionId: $session->id,
                correlationId: $session->correlation_id,
                causationId: $session->causation_id
            );

            try {
                $this->syncHandler->handle($command);
            } catch (\Throwable $e) {
                // log or ignore
            }
        }
    }
}
