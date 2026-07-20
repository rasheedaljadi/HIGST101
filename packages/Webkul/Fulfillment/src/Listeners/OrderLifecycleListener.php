<?php

namespace Webkul\Fulfillment\Listeners;

use Webkul\Sales\Contracts\Order;
use Webkul\Sales\Contracts\Invoice;
use Webkul\Sales\Contracts\Shipment;
use Webkul\Sales\Contracts\Refund;
use Webkul\Fulfillment\Services\Application\OrderLifecycleCoordinator;
use Webkul\Fulfillment\Services\Domain\FinancialSettlementService;

class OrderLifecycleListener
{
    /**
     * Create a new listener instance.
     */
    public function __construct(
        protected OrderLifecycleCoordinator $lifecycleCoordinator,
        protected FinancialSettlementService $settlementService
    ) {}

    /**
     * Handle sales order placed.
     */
    public function handleOrderPlaced(Order $order): void
    {
        $this->lifecycleCoordinator->initiate($order);
    }

    /**
     * Handle invoice paid (Prepaid).
     */
    public function handleInvoiceSaved(Invoice $invoice): void
    {
        $order = $invoice->order;
        if (! $order) {
            return;
        }

        $isCOD = ($order->payment->method === 'cashondelivery');

        if (! $isCOD) {
            // Ensure order process is initialized first to establish correlation_id for ledger entries
            $process = \Webkul\Fulfillment\Models\OrderProcess::where('order_id', $order->id)->first();
            if (! $process) {
                $this->lifecycleCoordinator->initiate($order);
            }

            // Settle online invoice paid & gateway clearing deposits
            $total = (float) $invoice->grand_total;
            $this->settlementService->settlePrepaidInvoice($order->id, $total);
            
            // Settle payment gateway commission dynamically based on payment method config
            $paymentMethod = $order->payment->method;
            $rate = config("fulfillment.commission_rates.{$paymentMethod}", config("fulfillment.commission_rates.default", 0.03));
            $commission = $total * $rate;
            $this->settlementService->settlePrepaidCommission($order->id, $commission);

            // Accept order and trigger saga
            $this->lifecycleCoordinator->receivePrepaidPayment($order->id, $invoice->id);
        } else {
            // Settle COD invoice / order confirmation
            // If the order process was not initialized (e.g. due to historical gap), initialize it first.
            $process = \Webkul\Fulfillment\Models\OrderProcess::where('order_id', $order->id)->first();
            if (! $process) {
                $this->lifecycleCoordinator->initiate($order);
            }

            $this->lifecycleCoordinator->acceptCODOrder($order->id, 'admin_manual');
        }
    }

    /**
     * Handle shipment completed (Revenue Recognition).
     */
    public function handleShipmentSaved(Shipment $shipment): void
    {
        $order = $shipment->order;
        if (! $order) {
            return;
        }

        $isCOD = ($order->payment->method === 'cashondelivery');
        $total = (float) $order->grand_total;

        if ($isCOD) {
            $this->settlementService->settleOrderShipmentCOD($order->id, $total);
        } else {
            $this->settlementService->settleOrderShipmentPrepaid($order->id, $total);
        }
    }

    /**
     * Handle refund issued (RMA Flow).
     */
    public function handleRefundSaved(Refund $refund): void
    {
        $order = $refund->order;
        if (! $order) {
            return;
        }

        $isShipped = $order->shipments->isNotEmpty();
        $total = (float) $refund->grand_total;

        if (! $isShipped) {
            // Prepaid refund prior to shipping
            $this->settlementService->settleRefundPrepaidBeforeShip($order->id, $total);
        } else {
            // Refund after shipping (via RMA returns)
            $po = \Webkul\Fulfillment\Models\PurchaseOrder::where('order_id', $order->id)->first();
            $actualCogs = $po ? (float) $po->items->sum(fn($i) => $i->qty * $i->supplier_unit_cost) : ($total * 0.5);

            $this->settlementService->settleRefundAfterShip(
                orderId: $order->id,
                total: $total,
                restock: true,
                cost: $actualCogs
            );
        }
    }
}
