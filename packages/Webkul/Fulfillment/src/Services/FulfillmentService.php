<?php

namespace Webkul\Fulfillment\Services;

use App\Models\AliExpressProductImport;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Webkul\Fulfillment\Contracts\FulfillmentProviderInterface;
use Webkul\Fulfillment\DataObjects\ShippingAddress;
use Webkul\Fulfillment\DataObjects\SupplierOrderLine;
use Webkul\Fulfillment\DataObjects\SupplierOrderRequest;
use Webkul\Fulfillment\DataObjects\SupplierOrderResult;
use Webkul\Fulfillment\Jobs\CreatePurchaseOrderJob;
use Webkul\Fulfillment\Models\PurchaseOrder;
use Webkul\Fulfillment\Models\PurchaseOrderItem;
use Webkul\Fulfillment\Repositories\FulfillmentAttemptRepository;
use Webkul\Fulfillment\Repositories\PurchaseOrderItemRepository;
use Webkul\Fulfillment\Repositories\PurchaseOrderRepository;
use Webkul\Sales\Contracts\Order;
use Webkul\Sales\Repositories\OrderCommentRepository;
use Webkul\Sales\Repositories\OrderRepository;

class FulfillmentService
{
    /**
     * Create a new service instance.
     */
    public function __construct(
        protected PurchaseOrderRepository $purchaseOrderRepository,
        protected PurchaseOrderItemRepository $purchaseOrderItemRepository,
        protected FulfillmentAttemptRepository $fulfillmentAttemptRepository,
        protected FulfillmentProviderRegistry $registry,
        protected OrderRepository $orderRepository,
        protected OrderCommentRepository $orderCommentRepository
    ) {}

    /**
     * Group order items and plan purchase orders (Task 5.1).
     *
     * @param  Order  $order
     * @return array<PurchaseOrder>
     */
    public function planPurchaseOrders(Order $order): array
    {
        if (! $order->items || $order->items->isEmpty()) {
            return [];
        }

        return DB::transaction(function () use ($order) {
            // Resolve active token for provider_account_id
            $token = \App\Models\AliExpressToken::query()->latest('id')->first();
            $providerAccountId = $token ? $token->id : null;

            // Grouping: [provider_code => [supplier_signature => [items]]]
            $groups = [];

            foreach ($order->items as $item) {
                // Double fulfillment prevention check: check if this order item is already linked to an active purchase order
                $alreadyFulfilled = PurchaseOrderItem::where('order_item_id', $item->id)
                    ->whereHas('purchaseOrder', function ($q) {
                        $q->where('state', '!=', PurchaseOrder::STATE_CANCELED);
                    })
                    ->exists();

                if ($alreadyFulfilled) {
                    Log::channel('aliexpress')->info('Fulfillment planning skipped: Order item already has an active purchase order', [
                        'order_item_id' => $item->id,
                        'order_id'      => $order->id,
                    ]);
                    continue;
                }

                // Find AliExpressProductImport
                $parentProductId = $item->product?->parent_id ?? $item->product_id;
                $import = AliExpressProductImport::where('product_id', $parentProductId)->first();

                $provider = 'aliexpress';
                $supplierSignature = 'aliexpress_default';

                if ($import === null || $import->status !== 'success') {
                    $supplierSignature = 'needs_manual_review';
                }

                $groups[$provider][$supplierSignature][] = [
                    'item'   => $item,
                    'import' => $import,
                ];
            }

            if (empty($groups)) {
                return PurchaseOrder::where('order_id', $order->id)
                    ->where('state', '!=', PurchaseOrder::STATE_CANCELED)
                    ->get()
                    ->all();
            }

            $purchaseOrders = [];

            foreach ($groups as $provider => $suppliers) {
                foreach ($suppliers as $supplierSignature => $groupedItems) {
                    $idempotencyKey = hash('sha256', $order->id . '|' . $provider . '|' . $supplierSignature);
                    $internalReference = $order->increment_id . '-' . substr($idempotencyKey, 0, 8);

                    $isReview = ($supplierSignature === 'needs_manual_review');

                    $po = PurchaseOrder::firstOrCreate([
                        'idempotency_key' => $idempotencyKey,
                    ], [
                        'order_id'            => $order->id,
                        'provider'            => $provider,
                        'provider_account_id' => $providerAccountId,
                        'supplier_signature'  => $supplierSignature,
                        'internal_reference'  => $internalReference,
                        'state'               => $isReview ? PurchaseOrder::STATE_NEEDS_MANUAL_REVIEW : PurchaseOrder::STATE_PENDING,
                        'last_error'          => $isReview ? 'Missing AliExpress product import source for one or more items.' : null,
                    ]);

                    foreach ($groupedItems as $groupItem) {
                        $item = $groupItem['item'];
                        $import = $groupItem['import'];

                        $allocation = DB::table('order_allocations')
                            ->where('order_item_id', $item->id)
                            ->where('state', 'reserved')
                            ->first();

                        $skuId = null;
                        $supplierCost = $item->price;
                        $supplierProductId = $import?->aliexpress_product_id;

                        if ($allocation && $allocation->supplier_snapshot) {
                            $snapshot = json_decode($allocation->supplier_snapshot, true);
                            $skuId = $snapshot['supplier_sku_id'] ?? null;
                            $supplierCost = $snapshot['supplier_cost'] ?? $item->price;
                            $supplierProductId = $snapshot['supplier_product_id'] ?? $supplierProductId;
                        } elseif ($import !== null) {
                            $variantProduct = $item->child ? $item->child->product : $item->product;
                            $skuId = $this->resolveSkuId($variantProduct, $import);
                        }

                        PurchaseOrderItem::firstOrCreate([
                            'purchase_order_id' => $po->id,
                            'order_item_id'     => $item->id,
                        ], [
                            'aliexpress_product_id' => $supplierProductId,
                            'sku_id'                => $skuId,
                            'qty'                   => (int) $item->qty_ordered,
                            'supplier_unit_cost'    => $supplierCost,
                        ]);
                    }

                    if ($po->wasRecentlyCreated) {
                        if ($po->state === PurchaseOrder::STATE_PENDING) {
                            CreatePurchaseOrderJob::dispatch($po);
                        } else {
                            // Log and comment manual review
                            $this->addOrderComment(
                                $order->id,
                                trans('fulfillment::app.manual_review_missing_source', ['po' => $po->id])
                            );
                        }
                    }

                    $purchaseOrders[] = $po;
                }
            }

            return $purchaseOrders;
        });
    }

    /**
     * Execute a planned purchase order (Task 6.1).
     *
     * @param  PurchaseOrder  $po
     * @return void
     *
     * @throws \Throwable
     */
    public function executePurchaseOrder(PurchaseOrder $po): void
    {
        $lock = Cache::lock("fulfillment-po-{$po->id}", config('fulfillment.lock_ttl', 600));

        if (! $lock->get()) {
            Log::channel('aliexpress')->warning('Fulfillment skipped: PO already locked', ['po' => $po->id]);
            return;
        }

        try {
            $po->refresh();

            // Already handled checks
            if (in_array($po->state, [PurchaseOrder::STATE_SUBMITTED, PurchaseOrder::STATE_SHIPPED, PurchaseOrder::STATE_DELIVERED], true)) {
                return;
            }

            $provider = $this->registry->resolve($po->provider);

            // Reconcile before submit if state is submitting or if a previous attempt was made
            if ($po->state === PurchaseOrder::STATE_SUBMITTING || $po->attempts > 0) {
                $externalOrderId = $po->external_order_id ?? $this->reconcileBeforeSubmit($po, $provider);

                if ($externalOrderId !== null) {
                    $this->updatePoSuccess($po, $externalOrderId);
                    return;
                }
            }

            // Transition to submitting
            $po->update(['state' => PurchaseOrder::STATE_SUBMITTING]);

            $aggregate = \Webkul\Fulfillment\Models\ProcurementAggregate::firstOrCreate([
                'purchase_order_id' => $po->id,
            ]);
            $allocation = \Webkul\Fulfillment\Models\OrderAllocation::where('order_id', $po->order_id)->first();
            $session = \Webkul\Fulfillment\Models\ProcurementSession::firstOrCreate([
                'procurement_aggregate_id' => $aggregate->id,
            ], [
                'order_allocation_id'      => $allocation?->id ?: 1,
                'provider_account_id'      => $po->provider_account_id,
                'state'                    => 'CREATED',
                'correlation_id'           => $po->idempotency_key,
                'causation_id'             => $po->idempotency_key,
            ]);

            if ($po->attempts > 0) {
                $session->transitionTo('SUBMIT_RETRY');
            }

            $session->transitionTo('VALIDATING');
            $session->transitionTo('READY_TO_SUBMIT');
            $session->transitionTo('SUBMITTING');

            // Construct DTO
            $request = $this->buildSupplierOrderRequest($po);

            // Call API
            $result = $provider->createSupplierOrder($request);

            $po->increment('attempts');
            $attemptNo = $po->attempts;

            // Log raw response as provider event
            \Webkul\Fulfillment\Models\FulfillmentProviderEvent::create([
                'purchase_order_id' => $po->id,
                'provider'          => $po->provider,
                'external_state'    => $result->ok ? 'submitted' : 'failed_attempt',
                'source_type'       => 'system_recovery',
                'payload'           => $result->raw ?? [],
                'received_at'       => now(),
                'processed_at'      => now(),
            ]);

            if ($result->ok) {
                $this->fulfillmentAttemptRepository->create([
                    'purchase_order_id' => $po->id,
                    'attempt_no'        => $attemptNo,
                    'result'            => 'success',
                    'error_type'        => null,
                    'provider_code'     => $result->code,
                    'message'           => 'Order created successfully.',
                ]);

                $this->updatePoSuccess($po, $result->externalOrderId, $result->raw);
            } else {
                $isRetryable = $result->isRetryable;
                $resultType = $isRetryable ? 'transient' : 'permanent';

                // Map error classification enum
                $errorType = $isRetryable
                    ? \Webkul\Fulfillment\Enums\FulfillmentErrorType::NETWORK_ERROR->value
                    : \Webkul\Fulfillment\Enums\FulfillmentErrorType::BUSINESS_RULE_ERROR->value;

                // Redact and truncate error message (1000 characters custom limit for attempt logs)
                $sanitizedMessage = SecretRedactor::sanitize($result->message ?? 'Unknown error', [], 1000);

                $this->fulfillmentAttemptRepository->create([
                    'purchase_order_id' => $po->id,
                    'attempt_no'        => $attemptNo,
                    'result'            => $resultType,
                    'error_type'        => $errorType,
                    'provider_code'     => $result->code,
                    'message'           => $sanitizedMessage,
                ]);

                $po->update([
                    'last_error'       => $sanitizedMessage,
                    'payload_snapshot' => $result->raw,
                ]);

                $maxAttempts = (int) config('fulfillment.retry.max_attempts', 3);

                $session = \Webkul\Fulfillment\Models\ProcurementSession::where('procurement_aggregate_id', function ($query) use ($po) {
                    $query->select('id')->from('procurement_aggregates')->where('purchase_order_id', $po->id);
                })->first();

                if (! $isRetryable || $attemptNo >= $maxAttempts) {
                    // Update state to needs manual review
                    $po->update(['state' => PurchaseOrder::STATE_NEEDS_MANUAL_REVIEW]);

                    if ($session) {
                        $session->transitionTo('FAILED');
                        $session->update(['error_message' => $sanitizedMessage]);
                    }

                    $this->addOrderComment(
                        $po->order_id,
                        trans('fulfillment::app.fulfillment_failed', ['po' => $po->id, 'error' => $sanitizedMessage])
                    );

                    // Dispatch error alert notification
                    \Webkul\Fulfillment\Services\FulfillmentAlertService::sendAlert(
                        'error',
                        "Fulfillment failed permanently for PO #{$po->id}. Error: {$sanitizedMessage}",
                        $po
                    );

                    // Log permanent failure
                    SecretRedactor::logFailure("Fulfillment failed permanently for PO #{$po->id}", [
                        'po_id'       => $po->id,
                        'order_id'    => $po->order_id,
                        'attempts'    => $attemptNo,
                        'error_code'  => $result->code,
                    ]);
                } else {
                    // Log transient failure and throw exception to retry
                    $po->update(['state' => PurchaseOrder::STATE_PENDING]);

                    if ($session) {
                        $session->transitionTo('FAILED');
                        $session->update(['error_message' => $sanitizedMessage]);
                    }

                    throw new \RuntimeException("Transient failure placing order for PO #{$po->id}: {$sanitizedMessage}");
                }
            }
        } finally {
            $lock->release();
        }
    }

    /**
     * Perform reconciliation before submitting (Task 6.1).
     *
     * @param  PurchaseOrder  $po
     * @param  FulfillmentProviderInterface  $provider
     * @return string|null
     */
    protected function reconcileBeforeSubmit(PurchaseOrder $po, $provider): ?string
    {
        try {
            return $provider->findByReference($po->internal_reference, $po->provider_account_id);
        } catch (\Throwable $e) {
            Log::channel('aliexpress')->error('Reconciliation check failed', [
                'po'      => $po->id,
                'message' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Build the SupplierOrderRequest DTO.
     *
     * @param  PurchaseOrder  $po
     * @return SupplierOrderRequest
     */
    protected function buildSupplierOrderRequest(PurchaseOrder $po): SupplierOrderRequest
    {
        $order = $po->order;
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

            $address = new ShippingAddress(
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
            $address = new ShippingAddress(
                firstName: $shipping->first_name,
                lastName: $shipping->last_name,
                address: $shipping->address ?? '',
                city: $shipping->city,
                state: $shipping->state,
                postcode: $shipping->postcode,
                country: $shipping->country,
                phone: $shipping->phone,
                email: $shipping->email,
                companyName: $shipping->company_name
            );
        }

        $items = [];
        foreach ($po->items as $item) {
            $items[] = new SupplierOrderLine(
                aliexpressProductId: $item->aliexpress_product_id,
                skuId: $item->sku_id,
                qty: (int) $item->qty
            );
        }

        return new SupplierOrderRequest(
            internalReference: $po->internal_reference,
            idempotencyKey: $po->idempotency_key,
            shippingAddress: $address,
            items: $items,
            currency: $order->order_currency_code ?: 'USD',
            providerAccountId: $po->provider_account_id
        );
    }

    /**
     * Resolve the AliExpress SKU ID from the product model and import snapshot.
     *
     * @param  mixed  $variantProduct
     * @param  AliExpressProductImport  $import
     * @return string|null
     */
    protected function resolveSkuId(mixed $variantProduct, AliExpressProductImport $import): ?string
    {
        $snapshot = $import->payload_snapshot;
        if (empty($snapshot['variants'])) {
            return null;
        }

        if (empty($snapshot['is_configurable'])) {
            return $snapshot['variants'][0]['sku_id'] ?? null;
        }

        $optionLabels = [];
        $parent = $variantProduct->parent;

        if ($parent) {
            foreach ($parent->super_attributes as $attribute) {
                $optionId = $variantProduct->getAttribute($attribute->code);
                if ($optionId) {
                    $option = \Webkul\Attribute\Models\AttributeOption::find($optionId);
                    if ($option) {
                        $label = null;
                        foreach (['en', 'en_US', core()->getDefaultLocaleCodeFromDefaultChannel()] as $loc) {
                            $trans = $option->translate($loc);
                            if ($trans && ! empty($trans->name)) {
                                $label = $trans->name;
                                break;
                            }
                        }
                        if (! $label) {
                            $label = $option->admin_name ?: $option->name;
                        }
                        if ($label) {
                            $optionLabels[] = strtolower(trim($label));
                        }
                    }
                }
            }
        }

        foreach ($snapshot['variants'] as $v) {
            $snapshotLabels = [];
            if (! empty($v['options_by_axis'])) {
                foreach ($v['options_by_axis'] as $axisName => $val) {
                    $snapshotLabels[] = strtolower(trim($val));
                }
            }

            sort($optionLabels);
            sort($snapshotLabels);

            if ($optionLabels === $snapshotLabels) {
                return $v['sku_id'];
            }
        }

        return $snapshot['variants'][0]['sku_id'] ?? null;
    }

    /**
     * Update PO state on success.
     *
     * @param  PurchaseOrder  $po
     * @param  string  $externalOrderId
     * @param  array|null  $raw
     * @return void
     */
    protected function updatePoSuccess(PurchaseOrder $po, string $externalOrderId, ?array $raw = null): void
    {
        $po->update([
            'state'             => PurchaseOrder::STATE_SUBMITTED,
            'external_order_id' => $externalOrderId,
            'submitted_at'      => now(),
            'payload_snapshot'  => $raw ?? $po->payload_snapshot,
        ]);

        $session = \Webkul\Fulfillment\Models\ProcurementSession::where('procurement_aggregate_id', function ($query) use ($po) {
            $query->select('id')->from('procurement_aggregates')->where('purchase_order_id', $po->id);
        })->first();

        if ($session) {
            $session->transitionTo('SUBMITTED');
            $session->update([
                'supplier_snapshot' => $raw,
                'snapshot_finalized_at' => now(),
            ]);
        }

        $this->reflectOnCustomerOrder($po->order);
    }

    /**
     * Reflect state onto the Customer Order (Task 7.1).
     *
     * @param  Order  $order
     * @return void
     */
    public function reflectOnCustomerOrder(Order $order): void
    {
        $purchaseOrders = PurchaseOrder::where('order_id', $order->id)->get();

        if ($purchaseOrders->isEmpty()) {
            return;
        }

        $allStates = $purchaseOrders->pluck('state')->toArray();

        // Totality State Mapping (Totality checking)
        $isAllDelivered = true;
        $isAnySubmittedOrShipped = false;

        foreach ($allStates as $state) {
            if ($state !== PurchaseOrder::STATE_DELIVERED) {
                $isAllDelivered = false;
            }
            if (in_array($state, [PurchaseOrder::STATE_SUBMITTED, PurchaseOrder::STATE_SHIPPED, PurchaseOrder::STATE_AWAITING_PAYMENT], true)) {
                $isAnySubmittedOrShipped = true;
            }
        }

        // Single Source of Truth rules:
        // NEVER perform raw database queries or direct writes to orders/invoices financial fields.
        // Update ONLY status via updateOrderStatus.
        if ($isAllDelivered) {
            if ($order->status !== 'completed') {
                $this->orderRepository->updateOrderStatus($order, 'completed');
                $this->addOrderComment($order->id, trans('fulfillment::app.fulfillment_completed'));
            }
        } elseif ($isAnySubmittedOrShipped) {
            if ($order->status !== 'processing') {
                $this->orderRepository->updateOrderStatus($order, 'processing');
                $this->addOrderComment($order->id, trans('fulfillment::app.fulfillment_started'));
            }
        }
    }

    /**
     * Add comment to customer order.
     *
     * @param  int  $orderId
     * @param  string  $comment
     * @return void
     */
    protected function addOrderComment(int $orderId, string $comment): void
    {
        try {
            $this->orderCommentRepository->create([
                'order_id'          => $orderId,
                'comment'           => $comment,
                'customer_notified' => 0,
            ]);
        } catch (\Throwable $e) {
            Log::channel('aliexpress')->error('Failed to create order comment', [
                'order_id' => $orderId,
                'comment'  => $comment,
                'error'    => $e->getMessage(),
            ]);
        }
    }
}
