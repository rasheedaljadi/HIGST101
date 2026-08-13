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
        $query = $this->model->whereNull('customer_id')->with('order');

        if (isset($params['status']) && $params['status'] != 'All') {
            $query->whereHas('order', function ($q) use ($params) {
                $q->where(['status' => $params['status']]);
            });
        }

        if (isset($params['read']) && isset($params['limit'])) {
            $query->where('read', $params['read'])->limit($params['limit']);
        } elseif (isset($params['limit'])) {
            $query->limit($params['limit']);
        }

        $notifications = $query->latest()->paginate($params['limit'] ?? 10);

        $statusCounts = $this->model->whereNull('notifications.customer_id')
            ->join('orders', 'notifications.order_id', '=', 'orders.id')
            ->select('orders.status', DB::raw('COUNT(*) as status_count'))
            ->groupBy('orders.status')
            ->get();

        return ['notifications' => $notifications, 'status_counts' => $statusCounts];
    }

    /**
     * Return Notification resources for Admin.
     *
     * @return array
     */
    public function getAll(array $params = [])
    {
        $query = $this->model->whereNull('customer_id')->with('order');

        $notifications = $query->latest()->paginate($params['limit'] ?? 10);

        $statusCounts = $this->model->whereNull('notifications.customer_id')
            ->join('orders', 'notifications.order_id', '=', 'orders.id')
            ->select('orders.status', DB::raw('COUNT(*) as status_count'))
            ->groupBy('orders.status')
            ->get();

        return ['notifications' => $notifications, 'status_counts' => $statusCounts];
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

        return $query->latest()->paginate($params['limit'] ?? 10);
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
