<?php

namespace Webkul\Notification\Repositories;

use Illuminate\Support\Facades\DB;
use Webkul\Core\Eloquent\Repository;

class NotificationRepository extends Repository
{
    /**
     * Specify Model class name
     */
    public function model(): string
    {
        return 'Webkul\Notification\Contracts\Notification';
    }

    /**
     * Return Filtered Notification resources for Admin.
     */
    public function getParamsData(array $params): array
    {
        $query = $this->model->whereNull('customer_id')->with(['order.items', 'order.customer']);

        // 1. Filter by category tab
        if (! empty($params['category']) && $params['category'] !== 'all') {
            $this->applyCategoryFilter($query, (string) $params['category']);
        } elseif (isset($params['status']) && $params['status'] !== 'all' && $params['status'] !== 'All') {
            $query->whereHas('order', function ($q) use ($params) {
                $q->where(['status' => $params['status']]);
            });
        }

        // 2. Read filter
        if (isset($params['read'])) {
            $query->where('read', (int) $params['read']);
        }

        $notifications = $query->latest('id')->paginate($params['limit'] ?? 10);

        $categoryCounts = $this->getCategoryCounts();
        $statusCounts = $this->getStatusCounts();

        return [
            'notifications' => $notifications,
            'category_counts' => $categoryCounts,
            'status_counts' => $statusCounts,
        ];
    }

    /**
     * Return Notification resources for Admin.
     *
     * @return array
     */
    public function getAll(array $params = [])
    {
        return $this->getParamsData($params);
    }

    /**
     * Apply category filter on query.
     */
    protected function applyCategoryFilter($query, string $category): void
    {
        match ($category) {
            'orders' => $query->where(function ($q) {
                $q->whereNull('type')
                    ->orWhereIn('type', ['order_status', 'order', 'invoice', 'shipment', 'order_reminder']);
            }),
            'inventory' => $query->whereIn('type', ['low_stock', 'out_of_stock']),
            'sync' => $query->whereIn('type', ['scheduled_sync', 'sync']),
            'finance' => $query->whereIn('type', ['wallet_topup', 'wallet_withdrawal']),
            default => null,
        };
    }

    /**
     * Calculate counts per category tab for admin.
     */
    public function getCategoryCounts(): array
    {
        $baseQuery = $this->model->whereNull('customer_id');

        return [
            'all' => (clone $baseQuery)->count(),
            'orders' => (clone $baseQuery)->where(function ($q) {
                $q->whereNull('type')
                    ->orWhereIn('type', ['order_status', 'order', 'invoice', 'shipment', 'order_reminder']);
            })->count(),
            'inventory' => (clone $baseQuery)->whereIn('type', ['low_stock', 'out_of_stock'])->count(),
            'sync' => (clone $baseQuery)->whereIn('type', ['scheduled_sync', 'sync'])->count(),
            'finance' => (clone $baseQuery)->whereIn('type', ['wallet_topup', 'wallet_withdrawal'])->count(),
        ];
    }

    /**
     * Legacy status counts for orders.
     */
    public function getStatusCounts()
    {
        return $this->model->whereNull('notifications.customer_id')
            ->join('orders', 'notifications.order_id', '=', 'orders.id')
            ->select('orders.status', DB::raw('COUNT(*) as status_count'))
            ->groupBy('orders.status')
            ->get();
    }

    /**
     * Get paginated notifications for a specific customer.
     */
    public function getForCustomer(int $customerId, array $params = [])
    {
        $query = $this->model->where('customer_id', $customerId);

        if (isset($params['read'])) {
            $query->where('read', (int) $params['read']);
        }

        return $query->latest('id')->paginate($params['limit'] ?? 10);
    }

    /**
     * Get unread notification count for a specific customer.
     */
    public function getUnreadCountForCustomer(int $customerId): int
    {
        return $this->model->where('customer_id', $customerId)
            ->where('read', 0)
            ->count();
    }
}
