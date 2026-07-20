<?php

namespace Webkul\Fulfillment\Services\Application;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Webkul\Sales\Repositories\OrderRepository;
use Webkul\Fulfillment\Models\OrderProcess;
use Webkul\Fulfillment\Events\OrderAccepted;

class OrderLifecycleCoordinator
{
    /**
     * Create a new coordinator instance.
     */
    public function __construct(
        protected OrderRepository $orderRepository,
        protected FulfillmentSagaCoordinator $sagaCoordinator
    ) {}

    /**
     * Initialize order fulfillment process.
     */
    public function initiate($order): OrderProcess
    {
        $correlationId = (string) Str::uuid();
        $isCOD = ($order->payment->method === 'cashondelivery');

        return DB::transaction(function () use ($order, $isCOD, $correlationId) {
            $process = OrderProcess::create([
                'order_id'        => $order->id,
                'payment_mode'    => $isCOD ? 'cod' : 'prepaid',
                'lifecycle_state' => $isCOD ? 'pending_acceptance' : 'waiting_payment',
                'correlation_id'  => $correlationId,
            ]);

            // Save trace log in timeline
            DB::table('financial_timeline')->insert([
                'event_id'       => (string) Str::uuid(),
                'order_id'       => $order->id,
                'event_type'     => 'OrderProcessInitialized',
                'amount'         => 0.00,
                'currency'       => $order->order_currency_code ?: 'USD',
                'metadata'       => json_encode([
                    'order_id'     => $order->id,
                    'payment_mode' => $isCOD ? 'cod' : 'prepaid',
                ]),
                'recorded_at'    => now(),
                'correlation_id' => $correlationId,
                'causation_id'   => $correlationId,
                'created_at'     => now(),
                'updated_at'     => now(),
            ]);

            return $process;
        });
    }

    /**
     * Accept a COD order manually.
     */
    public function acceptCODOrder(int $orderId, string $by): void
    {
        DB::transaction(function () use ($orderId, $by) {
            $process = OrderProcess::where('order_id', $orderId)->firstOrFail();

            if ($process->payment_mode !== 'cod') {
                throw new \DomainException("Only COD orders require manual operations acceptance.");
            }

            $process->accept($by);

            // Dispatch domain event to start fulfillment
            event(new OrderAccepted(
                orderId: $process->order_id,
                paymentMode: 'cod',
                correlationId: $process->correlation_id
            ));
        });
    }

    /**
     * Receive prepaid invoice payment.
     */
    public function receivePrepaidPayment(int $orderId, string $transactionId): void
    {
        DB::transaction(function () use ($orderId, $transactionId) {
            $process = OrderProcess::where('order_id', $orderId)->first();

            // If not initialized yet (e.g. race condition), initialize it
            if (! $process) {
                $order = $this->orderRepository->find($orderId);
                $process = $this->initiate($order);
            }

            if ($process->lifecycle_state === 'waiting_payment') {
                $process->accept('system_prepaid');

                event(new OrderAccepted(
                    orderId: $process->order_id,
                    paymentMode: 'prepaid',
                    correlationId: $process->correlation_id
                ));
            }
        });
    }

    /**
     * Trigger downstream Saga execution for all order items.
     */
    public function triggerFulfillmentSaga(int $orderId, string $correlationId): void
    {
        $order = $this->orderRepository->find($orderId);
        $process = OrderProcess::where('order_id', $orderId)->firstOrFail();

        $process->startFulfillment();

        foreach ($order->items as $item) {
            $eventId = (string) Str::uuid();

            $this->sagaCoordinator->coordinate(
                orderId: $order->id,
                orderItemId: $item->id,
                qty: (int) $item->qty_ordered,
                eventId: $eventId,
                correlationId: $correlationId,
                decisionContext: []
            );
        }
    }
}
