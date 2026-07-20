<?php

namespace Webkul\Fulfillment\Handlers\Procurement;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Webkul\Fulfillment\Commands\ValidateSupplierAvailabilityCommand;
use Webkul\Fulfillment\Models\ProcurementSession;
use Webkul\Fulfillment\Services\Domain\SupplierValidationService;

class ValidateSupplierAvailabilityHandler
{
    public function __construct(protected SupplierValidationService $validationService) {}

    public function handle(ValidateSupplierAvailabilityCommand $command): ProcurementSession
    {
        return DB::transaction(function () use ($command) {
            $session = ProcurementSession::findOrFail($command->procurementSessionId);

            $session->transitionTo('VALIDATING');

            $result = $this->validationService->validate($session);

            $session->transitionTo($result['status']);

            DB::table('domain_outbox_events')->insert([
                'event_id'       => (string) Str::uuid(),
                'event_name'     => 'ProcurementValidated',
                'event_version'  => 1,
                'aggregate_type' => 'ProcurementSession',
                'aggregate_id'   => (string) $session->id,
                'correlation_id' => $command->correlationId,
                'causation_id'   => $command->causationId,
                'payload'        => json_encode([
                    'procurement_session_id' => $session->id,
                    'status'                 => $result['status'],
                    'price_decision'         => $result['price_decision'],
                    'stock_decision'         => $result['stock_decision'],
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
