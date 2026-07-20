<?php

namespace Webkul\Fulfillment\Handlers\Procurement;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Webkul\Fulfillment\Commands\SubmitSupplierOrderCommand;
use Webkul\Fulfillment\Models\ProcurementSession;
use Webkul\Fulfillment\Models\ExternalPayloadArchive;
use Webkul\Fulfillment\Models\ExternalOrder;
use Webkul\Fulfillment\Services\FulfillmentProviderRegistry;
use Webkul\Fulfillment\Services\Domain\ExternalIdentityMapper;
use Webkul\Fulfillment\DataObjects\SupplierOrderRequest;
use Webkul\Fulfillment\DataObjects\SupplierOrderLine;
use Webkul\Fulfillment\DataObjects\ShippingAddress;

class SubmitSupplierOrderHandler
{
    public function __construct(
        protected FulfillmentProviderRegistry $registry,
        protected ExternalIdentityMapper $identityMapper
    ) {}

    public function handle(SubmitSupplierOrderCommand $command): ProcurementSession
    {
        return DB::transaction(function () use ($command) {
            $session = ProcurementSession::findOrFail($command->procurementSessionId);

            $session->transitionTo('SUBMITTING');

            $aggregate = $session->aggregate;
            $purchaseOrderId = $aggregate->purchase_order_id;

            $account = \Webkul\Fulfillment\Models\ProviderAccount::firstOrCreate([
                'provider' => 'aliexpress',
                'name'     => 'Main Account'
            ], [
                'status'        => 'ACTIVE',
                'access_token'  => 'fake-access-token',
                'refresh_token' => 'fake-refresh-token',
            ]);

            $session->update([
                'provider_account_id' => $account->id
            ]);

            $providerCode = 'aliexpress';
            if (app()->environment('testing')) {
                $providerCode = 'aliexpress_simulator';
            }

            $provider = $this->registry->resolve($providerCode);

            $allocation = $session->allocation;
            $order = $allocation->orderItem->order;

            $shipping = $order->shipping_address;
            $warehouse = \Illuminate\Support\Facades\DB::table('inventory_sources')
                ->where('code', 'default')
                ->first();

            if ($warehouse) {
                $street = $warehouse->street ?? '';
                $firstName = $warehouse->contact_name ?? 'Higesto';
                $lastName = 'Warehouse';
                $addressStr = $warehouse->street ?? '123 Warehouse St';
                $city = $warehouse->city ?? 'Riyadh';
                $state = $warehouse->state ?? 'Riyadh';

                // Auto-translate Arabic Miftah/Aziziyah warehouse to English
                if (mb_strpos($street, 'العزيزية') !== false || mb_strpos($street, 'المفتاح') !== false) {
                    $firstName = 'Al-Miftah';
                    $lastName = 'Transport Office';
                    $addressStr = 'Southern Ring Road, Al-Shabab District, Al-Aziziyah';
                    $city = 'Riyadh';
                    $state = 'Riyadh';
                }

                $addressObj = new ShippingAddress(
                    firstName: $firstName,
                    lastName: $lastName,
                    address: $addressStr,
                    city: $city,
                    state: $state,
                    postcode: $warehouse->postcode ?? '11564',
                    country: $warehouse->country ?? 'SA',
                    phone: $warehouse->contact_number ?? '0500000000',
                    email: $warehouse->contact_email ?? 'warehouse@example.com',
                    companyName: $warehouse->name ?? 'Higesto Warehouse'
                );
            } else {
                $addressObj = new ShippingAddress(
                    firstName: $shipping->first_name ?? 'John',
                    lastName: $shipping->last_name ?? 'Doe',
                    address: $shipping->address1 ?? '123 Test St',
                    city: $shipping->city ?? 'Riyadh',
                    state: $shipping->state ?? 'Riyadh',
                    postcode: $shipping->postcode ?? '12345',
                    country: $shipping->country ?? 'SA',
                    phone: $shipping->phone ?? '123456789',
                    email: $shipping->email ?? 'john@example.com',
                    companyName: $shipping->company_name ?? 'Test Co'
                );
            }

            $supplierSnap = $session->supplier_snapshot;
            $lines = [
                new SupplierOrderLine(
                    aliexpressProductId: $supplierSnap['supplier_product_id'] ?? 'unknown',
                    skuId: $supplierSnap['supplier_sku_id'] ?? 'unknown',
                    qty: (int) ($supplierSnap['requested_qty'] ?? 1)
                )
            ];

            $outOrderId = $this->identityMapper->mapInternalToExternal($purchaseOrderId, $allocation->id);

            $requestObj = new SupplierOrderRequest(
                internalReference: $outOrderId,
                idempotencyKey: $command->correlationId,
                shippingAddress: $addressObj,
                items: $lines,
                currency: $order->order_currency_code ?: 'USD',
                providerAccountId: $account->id
            );

            $result = $provider->createSupplierOrder($requestObj, $session->contract_version);

            if ($result->ok) {
                $archive = ExternalPayloadArchive::create([
                    'request_payload'  => $requestObj->toArray(),
                    'response_payload' => $result->raw,
                    'normalized_dto'   => [
                        'external_order_id' => $result->externalOrderId,
                        'code'              => $result->code,
                        'message'           => $result->message,
                    ],
                    'request_hash'     => hash('sha256', json_encode($requestObj->toArray())),
                    'response_hash'    => hash('sha256', json_encode($result->raw)),
                    'provider_version' => 'v2',
                    'contract_version' => $session->contract_version,
                ]);

                $session->external_payload_archive_id = $archive->id;
                $session->snapshot_finalized_at = now();
                $session->save();

                $session->transitionTo('SUBMITTED');

                ExternalOrder::create([
                    'provider'               => 'aliexpress',
                    'provider_account_id'    => $account->id,
                    'external_order_id'      => $result->externalOrderId,
                    'purchase_order_id'      => $purchaseOrderId,
                    'procurement_session_id' => $session->id,
                    'status'                 => 'SUBMITTED',
                ]);

                DB::table('external_order_projections')->updateOrInsert(
                    ['external_order_id' => $result->externalOrderId],
                    [
                        'purchase_order_id' => $purchaseOrderId,
                        'status'            => 'SUBMITTED',
                        'updated_at'        => now(),
                        'created_at'        => now(),
                    ]
                );

                DB::table('procurement_timelines')->insert([
                    'procurement_session_id' => $session->id,
                    'purchase_order_id'      => $purchaseOrderId,
                    'stage'                  => 'SUBMITTED',
                    'payload'                => json_encode($result->raw),
                    'correlation_id'         => $command->correlationId,
                    'causation_id'           => $command->causationId,
                    'created_at'             => now(),
                ]);

                DB::table('domain_outbox_events')->insert([
                    'event_id'       => (string) Str::uuid(),
                    'event_name'     => 'ProcurementSubmitted',
                    'event_version'  => 1,
                    'aggregate_type' => 'ProcurementSession',
                    'aggregate_id'   => (string) $session->id,
                    'correlation_id' => $command->correlationId,
                    'causation_id'   => $command->causationId,
                    'payload'        => json_encode([
                        'procurement_session_id' => $session->id,
                        'purchase_order_id'      => $purchaseOrderId,
                        'external_order_id'      => $result->externalOrderId,
                        'status'                 => 'SUBMITTED',
                    ]),
                    'status'         => 'pending',
                    'attempts'       => 0,
                    'created_at'     => now(),
                    'updated_at'     => now(),
                ]);

                DB::table('domain_outbox_events')->insert([
                    'event_id'       => (string) Str::uuid(),
                    'event_name'     => 'SupplierOrderSubmitted',
                    'event_version'  => 1,
                    'aggregate_type' => 'ProcurementSession',
                    'aggregate_id'   => (string) $session->id,
                    'correlation_id' => $command->correlationId,
                    'causation_id'   => $command->causationId,
                    'payload'        => json_encode([
                        'procurement_session_id' => $session->id,
                        'purchase_order_id'      => $purchaseOrderId,
                        'external_order_id'      => $result->externalOrderId,
                        'supplier_cost'          => $session->price_snapshot['current_cost'] ?? 0.00,
                    ]),
                    'status'         => 'pending',
                    'attempts'       => 0,
                    'created_at'     => now(),
                    'updated_at'     => now(),
                ]);

            } else {
                $session->transitionTo('FAILED');
                $session->update([
                    'error_message' => $result->message
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
                        'purchase_order_id'      => $purchaseOrderId,
                        'error_message'          => $result->message,
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
