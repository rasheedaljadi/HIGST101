<?php

namespace Webkul\Notification\Listeners;

use Webkul\Notification\Events\CreateOrderNotification;
use Webkul\Notification\Events\UpdateOrderNotification;
use Webkul\Notification\Repositories\NotificationRepository;
use Webkul\Notification\Services\CustomerNotificationService;

class Order
{
    /**
     * Create a new listener instance.
     *
     * @return void
     */
    public function __construct(
        protected NotificationRepository $notificationRepository,
        protected CustomerNotificationService $customerNotificationService
    ) {}

    /**
     * Create a new resource.
     *
     * @return void
     */
    public function createOrder($order)
    {
        // Admin notification
        $this->notificationRepository->create(['type' => 'order', 'order_id' => $order->id]);

        // Customer notification
        if (! empty($order->customer_id)) {
            $this->customerNotificationService->createCustomerNotification(
                customerId: $order->customer_id,
                type: 'order',
                title: trans('shop::app.notifications.order_created_title') ?? 'تم إنشاء طلبك بنجاح',
                message: trans('shop::app.notifications.order_created_message', ['order_id' => $order->id]) ?? "تم استلام طلبك رقم #{$order->id} بنجاح وهو قيد المراجعة.",
                actionUrl: "/customer/account/orders/view/{$order->id}",
                eventKey: "order:{$order->id}:created",
                orderId: $order->id
            );
        }

        event(new CreateOrderNotification);
    }

    /**
     * Fire an Event when the order status is updated.
     *
     * @return void
     */
    public function updateOrder($order)
    {
        if (! empty($order->customer_id) && ! empty($order->status)) {
            $statusText = trans('shop::app.notifications.order_status_'.$order->status) ?? $order->status;
            $this->customerNotificationService->createCustomerNotification(
                customerId: $order->customer_id,
                type: 'order_status',
                title: trans('shop::app.notifications.order_status_title') ?? 'تحديث على حالة الطلب',
                message: trans('shop::app.notifications.order_status_message', ['order_id' => $order->id, 'status' => $statusText]) ?? "تم تغيير حالة طلبك رقم #{$order->id} إلى {$statusText}.",
                actionUrl: "/customer/account/orders/view/{$order->id}",
                eventKey: "order:{$order->id}:status:{$order->status}",
                orderId: $order->id
            );
        }

        event(new UpdateOrderNotification([
            'id' => $order->id,
            'status' => $order->status,
        ]));
    }

    /**
     * Create notification when invoice is generated.
     */
    public function createInvoice($invoice)
    {
        $order = $invoice->order;
        if ($order && ! empty($order->customer_id)) {
            $this->customerNotificationService->createCustomerNotification(
                customerId: $order->customer_id,
                type: 'invoice',
                title: trans('shop::app.notifications.invoice_created_title') ?? 'تم إصدار فاتورة لطلبك',
                message: trans('shop::app.notifications.invoice_created_message', ['order_id' => $order->id]) ?? "تم إصدار فاتورة الشراء لطلبك رقم #{$order->id}.",
                actionUrl: "/customer/account/orders/view/{$order->id}",
                eventKey: "invoice:{$invoice->id}:created",
                orderId: $order->id
            );
        }
    }

    /**
     * Create notification when shipment is saved.
     */
    public function createShipment($shipment)
    {
        $order = $shipment->order;
        if ($order && ! empty($order->customer_id)) {
            $this->customerNotificationService->createCustomerNotification(
                customerId: $order->customer_id,
                type: 'shipment',
                title: trans('shop::app.notifications.shipment_created_title') ?? 'تم شحن طلبك',
                message: trans('shop::app.notifications.shipment_created_message', ['order_id' => $order->id]) ?? "تم شحن طلبك رقم #{$order->id} وهو في طريقه إليك.",
                actionUrl: "/customer/account/orders/view/{$order->id}",
                eventKey: "shipment:{$shipment->id}:created",
                orderId: $order->id
            );
        }
    }

    /**
     * Create notification when refund is saved.
     */
    public function createRefund($refund)
    {
        $order = $refund->order;
        if ($order && ! empty($order->customer_id)) {
            $this->customerNotificationService->createCustomerNotification(
                customerId: $order->customer_id,
                type: 'refund',
                title: trans('shop::app.notifications.refund_created_title') ?? 'تم استرداد مبلغ من طلبك',
                message: trans('shop::app.notifications.refund_created_message', ['order_id' => $order->id]) ?? "تم إصدار مرتجع مالي لطلبك رقم #{$order->id}.",
                actionUrl: "/customer/account/orders/view/{$order->id}",
                eventKey: "refund:{$refund->id}:created",
                orderId: $order->id
            );
        }
    }
}
