<?php

namespace Webkul\Notification\Services;

use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Log;
use Webkul\Notification\Repositories\NotificationRepository;

class CustomerNotificationService
{
    /**
     * Create a new service instance.
     */
    public function __construct(protected NotificationRepository $notificationRepository) {}

    /**
     * Safely create a notification for a customer with deduplication support.
     *
     * @return mixed
     */
    public function createCustomerNotification(
        int $customerId,
        string $type,
        string $title,
        string $message,
        string $actionUrl,
        ?string $eventKey = null,
        ?int $orderId = null
    ) {
        try {
            if ($eventKey && $this->notificationRepository->where('customer_id', $customerId)->where('event_key', $eventKey)->exists()) {
                return null;
            }

            return $this->notificationRepository->create([
                'customer_id' => $customerId,
                'type' => $type,
                'title' => $title,
                'message' => $message,
                'action_url' => $actionUrl,
                'event_key' => $eventKey,
                'order_id' => $orderId,
                'read' => 0,
            ]);
        } catch (QueryException $e) {
            if ($e->getCode() == '23000' || str_contains($e->getMessage(), 'Duplicate entry')) {
                Log::info("Duplicate notification suppressed for customer {$customerId} with key {$eventKey}");

                return null;
            }
            Log::error('Database error creating customer notification: '.$e->getMessage());

            return null;
        } catch (\Throwable $e) {
            Log::error('Failed to create customer notification: '.$e->getMessage());

            return null;
        }
    }
}
