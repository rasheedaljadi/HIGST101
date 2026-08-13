<?php

namespace Webkul\Shop\Http\Controllers\Customer\Account;

use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Webkul\Notification\Repositories\NotificationRepository;
use Webkul\Shop\Http\Controllers\Controller;

class NotificationController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct(protected NotificationRepository $notificationRepository) {}

    /**
     * Display a listing of customer notifications.
     */
    public function index(): View
    {
        return view('shop::customers.account.notifications.index');
    }

    /**
     * Get customer notifications list (AJAX / API).
     */
    public function getNotifications(): JsonResponse
    {
        $customerId = auth()->guard('customer')->id();
        $params = request()->except('page');

        $results = $this->notificationRepository->getForCustomer($customerId, $params);
        $totalUnread = $this->notificationRepository->getUnreadCountForCustomer($customerId);

        return response()->json([
            'notifications' => $results,
            'total_unread' => $totalUnread,
        ]);
    }

    /**
     * Get unread notifications count only.
     */
    public function unreadCount(): JsonResponse
    {
        $customerId = auth()->guard('customer')->id();
        $totalUnread = $this->notificationRepository->getUnreadCountForCustomer($customerId);

        return response()->json([
            'total_unread' => $totalUnread,
        ]);
    }

    /**
     * Mark a single notification as read.
     */
    public function markAsRead(int $id): JsonResponse
    {
        $customerId = auth()->guard('customer')->id();

        $notification = $this->notificationRepository
            ->where('customer_id', $customerId)
            ->where('id', $id)
            ->firstOrFail();

        $notification->update(['read' => 1]);

        $totalUnread = $this->notificationRepository->getUnreadCountForCustomer($customerId);

        $redirectUrl = $this->getSafeActionUrl($notification->action_url);

        return response()->json([
            'success' => true,
            'total_unread' => $totalUnread,
            'redirect_url' => $redirectUrl,
        ]);
    }

    /**
     * Mark all customer notifications as read.
     */
    public function markAllAsRead(): JsonResponse
    {
        $customerId = auth()->guard('customer')->id();

        $this->notificationRepository
            ->where('customer_id', $customerId)
            ->where('read', 0)
            ->update(['read' => 1]);

        return response()->json([
            'success' => true,
            'total_unread' => 0,
            'message' => trans('shop::app.notifications.marked_all_as_read_success') ?? 'تم تعليم جميع الإشعارات كمقروءة بنجاح.',
        ]);
    }

    /**
     * Validate and return safe relative action URL (Open Redirect protection).
     */
    protected function getSafeActionUrl(?string $url): string
    {
        if (empty($url)) {
            return route('shop.customers.account.notifications.index');
        }

        if (preg_match('#^/(?!/)#', $url)) {
            return $url;
        }

        return route('shop.customers.account.notifications.index');
    }
}
