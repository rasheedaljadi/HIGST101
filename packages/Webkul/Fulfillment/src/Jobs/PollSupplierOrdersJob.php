<?php

namespace Webkul\Fulfillment\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Webkul\Fulfillment\Models\PurchaseOrder;
use Webkul\Fulfillment\Services\FulfillmentProviderRegistry;
use Webkul\Fulfillment\Services\FulfillmentService;
use Webkul\Fulfillment\Services\SecretRedactor;
use Webkul\Sales\Repositories\OrderCommentRepository;

class PollSupplierOrdersJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(): void
    {
        if (! config('fulfillment.poll.enabled', true)) {
            return;
        }

        $nonFinalStates = [
            PurchaseOrder::STATE_PENDING,
            PurchaseOrder::STATE_SUBMITTING,
            PurchaseOrder::STATE_SUBMITTED,
            PurchaseOrder::STATE_SHIPPED,
            PurchaseOrder::STATE_AWAITING_PAYMENT,
        ];

        // Find all non-final purchase orders that have an external order ID
        $purchaseOrders = PurchaseOrder::whereIn('state', $nonFinalStates)
            ->whereNotNull('external_order_id')
            ->get();

        $registry = app(FulfillmentProviderRegistry::class);
        $fulfillmentService = app(FulfillmentService::class);
        $orderCommentRepository = app(OrderCommentRepository::class);

        foreach ($purchaseOrders as $po) {
            try {
                $provider = $registry->resolve($po->provider);
                $status = $provider->getSupplierOrderStatus($po->external_order_id, $po->provider_account_id);

                // Log poll response as provider event
                \Webkul\Fulfillment\Models\FulfillmentProviderEvent::create([
                    'purchase_order_id' => $po->id,
                    'provider'          => $po->provider,
                    'external_state'    => $status->rawState ?? 'unknown',
                    'source_type'       => 'poll',
                    'payload'           => [
                        'mapped_state'     => $status->mappedState,
                        'raw_state'        => $status->rawState,
                        'tracking_number'  => $status->trackingNumber,
                        'tracking_company' => $status->trackingCompany,
                    ],
                    'received_at'       => now(),
                    'processed_at'      => now(),
                ]);

                if ($status->mappedState === 'failed_transient') {
                    // Keep existing state on transient failures
                    continue;
                }

                if ($status->mappedState === PurchaseOrder::STATE_NEEDS_MANUAL_REVIEW) {
                    $po->update([
                        'state'              => PurchaseOrder::STATE_NEEDS_MANUAL_REVIEW,
                        'supplier_state_raw' => $status->rawState,
                    ]);

                    $orderCommentRepository->create([
                        'order_id'          => $po->order_id,
                        'comment'           => trans('fulfillment::app.status_check_failed', ['po' => $po->id, 'external' => $po->external_order_id]),
                        'customer_notified' => 0,
                    ]);

                    SecretRedactor::logFailure("Polling status failed permanently for PO #{$po->id}", [
                        'po_id'             => $po->id,
                        'external_order_id' => $po->external_order_id,
                        'raw_state'         => $status->rawState,
                    ]);

                    continue;
                }

                $oldState = $po->state;

                $po->update([
                    'state'              => $status->mappedState,
                    'supplier_state_raw' => $status->rawState,
                    'tracking_number'    => $status->trackingNumber ?? $po->tracking_number,
                    'tracking_company'   => $status->trackingCompany ?? $po->tracking_company,
                ]);

                if ($oldState !== $status->mappedState) {
                    $orderCommentRepository->create([
                        'order_id'          => $po->order_id,
                        'comment'           => trans('fulfillment::app.state_updated', ['po' => $po->id, 'old' => $oldState, 'new' => $status->mappedState, 'raw' => $status->rawState]),
                        'customer_notified' => 0,
                    ]);

                    $fulfillmentService->reflectOnCustomerOrder($po->order);
                }
            } catch (\Throwable $e) {
                Log::channel('aliexpress')->error("Error polling status for PO #{$po->id}", [
                    'po_id'   => $po->id,
                    'message' => $e->getMessage(),
                ]);
            }
        }
    }
}
