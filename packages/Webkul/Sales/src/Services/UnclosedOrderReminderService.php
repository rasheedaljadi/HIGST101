<?php

namespace Webkul\Sales\Services;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Webkul\Notification\Repositories\NotificationRepository;
use Webkul\Sales\Models\Order;
use Webkul\Sales\Repositories\OrderRepository;

class UnclosedOrderReminderService
{
    /**
     * Terminal statuses where reminders should not be sent.
     */
    public const TERMINAL_STATUSES = [
        Order::STATUS_COMPLETED,
        Order::STATUS_CANCELED,
        Order::STATUS_CLOSED,
    ];

    /**
     * Create a new service instance.
     */
    public function __construct(
        protected OrderRepository $orderRepository,
        protected NotificationRepository $notificationRepository
    ) {}

    /**
     * Process all unclosed orders and generate reminders.
     */
    public function processReminders(?int $intervalDays = null, bool $dryRun = false): array
    {
        $intervalDays = $intervalDays ?: (int) (core()->getConfigData('sales.order_settings.reminder.interval_days') ?: 5);
        if ($intervalDays <= 0) {
            $intervalDays = 5;
        }

        $isEnabled = core()->getConfigData('sales.order_settings.reminder.enabled');
        if ($isEnabled !== null && ! (bool) $isEnabled && $isEnabled !== '') {
            return [
                'status' => 'skipped',
                'reason' => 'Reminder feature is disabled in settings',
                'scanned_orders' => 0,
                'created_notifications' => 0,
            ];
        }

        $unclosedOrders = $this->getUnclosedOrders();

        $notificationsCreated = 0;
        $details = [];

        foreach ($unclosedOrders as $order) {
            $result = $this->evaluateOrderReminder($order, $intervalDays, $dryRun);

            if ($result['notified']) {
                $notificationsCreated++;
            }

            if ($result['milestone_day'] > 0) {
                $details[] = $result;
            }
        }

        return [
            'status' => 'completed',
            'interval_days' => $intervalDays,
            'dry_run' => $dryRun,
            'scanned_orders' => $unclosedOrders->count(),
            'created_notifications' => $notificationsCreated,
            'details' => $details,
        ];
    }

    /**
     * Evaluate an individual order and create reminder notification if eligible.
     */
    public function evaluateOrderReminder(Order $order, int $intervalDays = 5, bool $dryRun = false): array
    {
        if (in_array($order->status, self::TERMINAL_STATUSES, true)) {
            return [
                'order_id' => $order->id,
                'notified' => false,
                'reason' => 'Order is in terminal state ('.$order->status.')',
                'milestone_day' => 0,
            ];
        }

        $createdAt = Carbon::parse($order->created_at);
        $daysPassed = (int) $createdAt->diffInDays(now());

        if ($daysPassed < $intervalDays) {
            return [
                'order_id' => $order->id,
                'notified' => false,
                'reason' => "Days passed ({$daysPassed}) is less than interval ({$intervalDays})",
                'milestone_day' => 0,
                'days_passed' => $daysPassed,
            ];
        }

        // Milestone day e.g. 5, 10, 15, 20...
        $milestoneDay = (int) (floor($daysPassed / $intervalDays) * $intervalDays);
        $eventKey = "order_reminder_{$order->id}_{$milestoneDay}";

        // Check if notification already created for this order milestone
        $alreadyNotified = $this->notificationRepository
            ->whereNull('customer_id')
            ->where('event_key', $eventKey)
            ->exists();

        if ($alreadyNotified) {
            return [
                'order_id' => $order->id,
                'notified' => false,
                'reason' => "Already notified for milestone day {$milestoneDay}",
                'milestone_day' => $milestoneDay,
                'days_passed' => $daysPassed,
            ];
        }

        if (! $dryRun) {
            $this->createReminderNotification($order, $milestoneDay, $daysPassed, $eventKey);
        }

        return [
            'order_id' => $order->id,
            'notified' => true,
            'milestone_day' => $milestoneDay,
            'days_passed' => $daysPassed,
            'status' => $order->status,
        ];
    }

    /**
     * Get all active unclosed orders.
     */
    public function getUnclosedOrders(): Collection
    {
        return $this->orderRepository
            ->scopeQuery(function ($query) {
                return $query->whereNotIn('status', self::TERMINAL_STATUSES);
            })
            ->all();
    }

    /**
     * Create the admin notification record.
     */
    protected function createReminderNotification(Order $order, int $milestoneDay, int $daysPassed, string $eventKey): void
    {
        $statusLabel = $this->getStatusLabel($order->status);
        $orderTotal = core()->formatBasePrice($order->base_grand_total ?? $order->grand_total);
        $customerName = $order->customer_full_name ?? trim(($order->customer_first_name ?? '').' '.($order->customer_last_name ?? ''));

        $title = "تذكير: مضى {$milestoneDay} أيام على استلام الطلب #{$order->id} ولا يزال غير مقفل";
        $message = "الطلب رقم #{$order->id}".($customerName ? " للعميل ({$customerName})" : '')." بمبلغ {$orderTotal} تم استلامه بتاريخ ".Carbon::parse($order->created_at)->format('Y-m-d')." ومضى عليه {$daysPassed} يوماً وحالته الحالية ({$statusLabel}) وهو غير مقفل، يُرجى المتابعة والإقفال.";

        try {
            $this->notificationRepository->create([
                'type' => 'order_reminder',
                'order_id' => $order->id,
                'title' => $title,
                'message' => $message,
                'action_url' => route('admin.sales.orders.view', $order->id),
                'event_key' => $eventKey,
                'read' => 0,
                'customer_id' => null,
            ]);
        } catch (\Throwable $e) {
            Log::error('Failed to create unclosed order reminder notification: '.$e->getMessage(), [
                'order_id' => $order->id,
                'milestone_day' => $milestoneDay,
            ]);
        }
    }

    /**
     * Translate order status to Arabic label.
     */
    protected function getStatusLabel(string $status): string
    {
        return match ($status) {
            Order::STATUS_PENDING => 'معلق',
            Order::STATUS_PENDING_PAYMENT => 'بانتظار الدفع',
            Order::STATUS_PROCESSING => 'قيد المعالجة',
            Order::STATUS_ACCEPTED => 'مقبول',
            Order::STATUS_FRAUD => 'اشتباه احتيال',
            default => $status,
        };
    }
}
