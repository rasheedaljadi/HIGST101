<?php

namespace Webkul\Fulfillment\Services\Application;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Webkul\Fulfillment\Commands\CreatePurchaseOrderCommand;
use Webkul\Fulfillment\Commands\ReserveAllocationCommand;
use Webkul\Fulfillment\DataObjects\ShippingAddress;
use Webkul\Fulfillment\DataObjects\SupplierOrderLine;
use Webkul\Fulfillment\DataObjects\SupplierOrderRequest;
use Webkul\Fulfillment\Exceptions\FulfillmentSagaException;
use Webkul\Fulfillment\Handlers\CreatePurchaseOrderHandler;
use Webkul\Fulfillment\Handlers\ReserveAllocationHandler;
use Webkul\Fulfillment\Services\FulfillmentProviderRegistry;
use Webkul\Sales\Models\OrderProxy;

class SupplierProcurementWorkflow
{
    /**
     * Create a new workflow instance.
     */
    public function __construct(
        protected FulfillmentProviderRegistry $providerRegistry,
        protected ReserveAllocationHandler $reserveHandler,
        protected CreatePurchaseOrderHandler $poHandler
    ) {}

    /**
     * Coordinate supplier dropshipping procurement.
     *
     * @param  int  $orderId
     * @param  int  $orderItemId
     * @param  int  $qty
     * @param  string  $providerCode
     * @param  string  $correlationId
     * @param  string  $causationId
     * @return array
     *
     * @throws \Webkul\Fulfillment\Exceptions\FulfillmentSagaException
     */
    public function processSupplier(int $orderId, int $orderItemId, int $qty, string $providerCode, string $correlationId, string $causationId): array
    {
        // 1. Reserve Allocation and Create PO in a single transaction
        [$allocation, $po, $session] = DB::transaction(function () use ($orderId, $orderItemId, $qty, $providerCode, $correlationId, $causationId) {
            $reserveCommand = new ReserveAllocationCommand(
                orderId: $orderId,
                orderItemId: $orderItemId,
                allocationType: 'supplier',
                sourceCode: $providerCode,
                quantity: $qty,
                correlationId: $correlationId,
                causationId: $causationId
            );
            $allocation = $this->reserveHandler->handle($reserveCommand);

            $poCommand = new CreatePurchaseOrderCommand(
                orderId: $orderId,
                orderAllocationId: $allocation->id,
                providerCode: $providerCode,
                correlationId: $correlationId,
                causationId: $causationId
            );
            $po = $this->poHandler->handle($poCommand);

            // Explicitly initialize ProcurementSession here
            $aggregate = \Webkul\Fulfillment\Models\ProcurementAggregate::firstOrCreate([
                'purchase_order_id' => $po->id,
            ]);

            $session = \Webkul\Fulfillment\Models\ProcurementSession::create([
                'procurement_aggregate_id' => $aggregate->id,
                'order_allocation_id'      => $allocation->id,
                'provider_account_id'      => $po->provider_account_id,
                'state'                    => 'CREATED',
                'correlation_id'           => $correlationId,
                'causation_id'             => $causationId,
            ]);

            return [$allocation, $po, $session];
        });

        // 2. Dispatch to API Provider outside database transaction boundary
        try {
            $provider = $this->providerRegistry->resolve($providerCode);
            
            $order = OrderProxy::modelClass()::findOrFail($orderId);
            
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

                $shippingAddress = new ShippingAddress(
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
                $shippingAddress = new ShippingAddress(
                    firstName: $order->customer_first_name ?? 'Guest',
                    lastName: $order->customer_last_name ?? 'User',
                    address: $order->shipping_address?->address1 ?? '123 Test St',
                    city: $order->shipping_address?->city ?? 'Riyadh',
                    state: $order->shipping_address?->state ?? 'Riyadh',
                    postcode: $order->shipping_address?->postcode ?? '11564',
                    country: $order->shipping_address?->country ?? 'SA',
                    phone: $order->shipping_address?->phone ?? '0500000000',
                    email: $order->customer_email ?? 'guest@example.com'
                );
            }

            $snapshot = $allocation->supplier_snapshot;
            $supplierProductId = $snapshot['supplier_product_id'] ?? 'ae_prod_123';
            $supplierSkuId = $snapshot['supplier_sku_id'] ?? 'ae_sku_456';
            $supplierCurrency = $snapshot['supplier_currency'] ?? 'USD';

            $items = [
                new SupplierOrderLine(
                    aliexpressProductId: $supplierProductId,
                    skuId: $supplierSkuId,
                    qty: $qty
                )
            ];

            $request = new SupplierOrderRequest(
                internalReference: $po->internal_reference,
                idempotencyKey: 'idemp-' . $po->internal_reference,
                shippingAddress: $shippingAddress,
                items: $items,
                currency: $supplierCurrency
            );

            $session->transitionTo('VALIDATING');
            $session->transitionTo('READY_TO_SUBMIT');
            $session->transitionTo('SUBMITTING');

            $result = $provider->createSupplierOrder($request);

            if (! $result->ok) {
                throw new \RuntimeException($result->message ?? 'Supplier API dispatch failed');
            }

            // Update PO status to submitted inside its own small transaction
            DB::transaction(function () use ($po, $result, $orderId, $correlationId, $causationId, $session) {
                $po->state = 'submitted';
                $po->external_order_id = $result->externalOrderId;
                $po->save();

                $session->transitionTo('SUBMITTED');
                $session->update([
                    'supplier_snapshot' => $result->raw,
                    'snapshot_finalized_at' => now(),
                ]);

                // Append outbox event: SupplierOrderSubmitted
                DB::table('domain_outbox_events')->insert([
                    'event_id'       => (string) Str::uuid(),
                    'event_name'     => 'SupplierOrderSubmitted',
                    'event_version'  => 1,
                    'aggregate_type' => 'PurchaseOrder',
                    'aggregate_id'   => (string) $po->id,
                    'correlation_id' => $correlationId,
                    'causation_id'   => $causationId,
                    'payload'        => json_encode([
                        'purchase_order_id' => $po->id,
                        'external_order_id' => $result->externalOrderId,
                        'order_id'          => $orderId,
                        'supplier_cost'     => (float) $po->items->sum(fn($i) => $i->qty * $i->supplier_unit_cost),
                    ]),
                    'status'         => 'pending',
                    'attempts'       => 0,
                    'created_at'     => now(),
                    'updated_at'     => now(),
                ]);
            });

            return [$allocation, $po];

        } catch (\Exception $e) {
            // Append outbox event: SupplierOrderFailed in its own transaction
            DB::transaction(function () use ($po, $allocation, $orderId, $correlationId, $causationId, $e, $session) {
                $po->update([
                    'state'      => 'needs_manual_review',
                    'last_error' => $e->getMessage(),
                ]);

                $session->transitionTo('FAILED');
                $session->update(['error_message' => $e->getMessage()]);

                DB::table('domain_outbox_events')->insert([
                    'event_id'       => (string) Str::uuid(),
                    'event_name'     => 'SupplierOrderFailed',
                    'event_version'  => 1,
                    'aggregate_type' => 'PurchaseOrder',
                    'aggregate_id'   => (string) $po->id,
                    'correlation_id' => $correlationId,
                    'causation_id'   => $causationId,
                    'payload'        => json_encode([
                        'purchase_order_id'   => $po->id,
                        'order_allocation_id' => $allocation->id,
                        'order_id'            => $orderId,
                        'error_message'       => $e->getMessage(),
                    ]),
                    'status'         => 'pending',
                    'attempts'       => 0,
                    'created_at'     => now(),
                    'updated_at'     => now(),
                ]);
            });

            throw new FulfillmentSagaException("Supplier procurement failed: " . $e->getMessage(), 0, $e);
        }
    }
}
