<?php

namespace Webkul\Admin\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\View\View;
use Webkul\DeliveryManagement\Models\DeliveryAssignment;
use Webkul\Notification\Repositories\NotificationRepository;
use Webkul\Product\Models\ProductProxy;
use Webkul\Sales\Models\Order;
use Webkul\Wallet\Models\WalletTopUp;
use Webkul\Wallet\Models\WalletWithdrawalRequest;

class NotificationController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(protected NotificationRepository $notificationRepository) {}

    /**
     * Display a listing of the resource.
     *
     * @return View
     */
    public function index()
    {
        return view('admin::notifications.index');
    }

    /**
     * Display a listing of the resource.
     *
     * @return array
     */
    public function getNotifications()
    {
        $user = auth()->guard('admin')->user();

        // 1. Isolated delivery tasks notifications for Couriers & Point Agents
        if ($user && in_array($user->role?->name, ['Courier', 'PointAgent'])) {
            return $this->getCourierNotifications($user);
        }

        $params = request()->except('page');

        $searchResults = count($params)
            ? $this->notificationRepository->getParamsData($params)
            : $this->notificationRepository->getAll();

        $results = isset($searchResults['notifications']) ? $searchResults['notifications'] : $searchResults;
        $items = isset($results->items) ? $results->items() : (is_iterable($results) ? $results : []);

        foreach ($items as $notification) {
            $this->enrichNotification($notification);
        }

        $statusCount = isset($searchResults['status_counts']) ? $searchResults['status_counts'] : [];
        $categoryCounts = isset($searchResults['category_counts'])
            ? $searchResults['category_counts']
            : $this->notificationRepository->getCategoryCounts();

        return [
            'search_results' => $results,
            'status_count' => $statusCount,
            'category_counts' => $categoryCounts,
            'total_unread' => $this->notificationRepository->whereNull('customer_id')->where('read', 0)->count(),
        ];
    }

    /**
     * Enrich notification object with structured Arabic titles, rich descriptions, links, and icons.
     */
    protected function enrichNotification($notification): void
    {
        $type = $notification->type ?? 'order_status';
        $timeAgo = $notification->created_at ? $this->arabicDiffForHumans($notification->created_at) : 'الآن';

        $displayTitle = '';
        $displayMessage = '';
        $category = 'orders';
        $iconClass = 'icon-information';
        $badgeClass = 'bg-blue-100 text-blue-600 dark:bg-blue-900/40 dark:text-blue-400';
        $actionUrl = route('admin.notification.viewed_notification', $notification->id);

        if ($type === 'wallet_topup') {
            $category = 'finance';
            $topupId = $notification->entity_id ?? $notification->id;
            $topup = class_exists(WalletTopUp::class) && $notification->entity_id
                ? WalletTopUp::with('customer')->find($notification->entity_id)
                : null;

            $amountStr = $topup ? core()->formatBasePrice($topup->amount) : '';
            $customerName = $topup?->customer ? ($topup->customer->first_name.' '.$topup->customer->last_name) : 'العميل';

            $displayTitle = 'طلب شحن محفظة #'.$topupId.($amountStr ? ' ('.$amountStr.')' : '');
            $displayMessage = 'العميل: '.$customerName.' | بانتظار المراجعة والاعتماد';
            $iconClass = 'icon-dollar-circle';
            $badgeClass = 'bg-emerald-100 text-emerald-600 dark:bg-emerald-900/40 dark:text-emerald-400';
            $actionUrl = route('admin.notification.viewed_notification', $notification->id);

        } elseif ($type === 'wallet_withdrawal') {
            $category = 'finance';
            $withdrawalId = $notification->entity_id ?? $notification->id;
            $withdrawal = class_exists(WalletWithdrawalRequest::class) && $notification->entity_id
                ? WalletWithdrawalRequest::with('customer')->find($notification->entity_id)
                : null;

            $amountStr = $withdrawal ? core()->formatBasePrice($withdrawal->amount) : '';
            $customerName = $withdrawal?->customer ? ($withdrawal->customer->first_name.' '.$withdrawal->customer->last_name) : 'العميل';

            $displayTitle = 'طلب سحب رصيد #'.$withdrawalId.($amountStr ? ' ('.$amountStr.')' : '');
            $displayMessage = 'العميل: '.$customerName.' | بانتظار التحويل والاعتماد';
            $iconClass = 'icon-dollar-circle';
            $badgeClass = 'bg-amber-100 text-amber-600 dark:bg-amber-900/40 dark:text-amber-400';
            $actionUrl = route('admin.notification.viewed_notification', $notification->id);

        } elseif (in_array($type, ['low_stock', 'out_of_stock'])) {
            $category = 'inventory';
            $isOutOfStock = ($type === 'out_of_stock');
            $product = class_exists(ProductProxy::class) && $notification->entity_id
                ? ProductProxy::with('attribute_values.attribute')->find($notification->entity_id)
                : null;

            $productName = $product ? ($product->name ?? $product->sku) : 'منتج';
            $sku = $product ? $product->sku : '';
            $stock = $product ? ($product->total_quantity ?? $product->inventories()->sum('qty') ?? 0) : 0;

            if ($isOutOfStock) {
                $displayTitle = 'نفاد مخزون: '.$productName;
                $displayMessage = 'نفدت الكمية بالكامل (0 قطعة)'.($sku ? ' | الرمز: '.$sku : '');
                $iconClass = 'icon-cancel-1';
                $badgeClass = 'bg-red-100 text-red-600 dark:bg-red-900/40 dark:text-red-400';
            } else {
                $displayTitle = 'انخفاض مخزون: '.$productName;
                $displayMessage = 'الكمية المتبقية: '.$stock.' قطعة'.($sku ? ' | الرمز: '.$sku : '');
                $iconClass = 'icon-information';
                $badgeClass = 'bg-amber-100 text-amber-600 dark:bg-amber-900/40 dark:text-amber-400';
            }

            $actionUrl = route('admin.notification.viewed_notification', $notification->id);

        } elseif ($type === 'scheduled_sync' || $type === 'sync') {
            $category = 'sync';
            $isFailed = str_contains($notification->title ?? '', 'فشل') || str_contains($notification->message ?? '', 'فشل');
            $hasWarnings = str_contains($notification->title ?? '', 'تنبيهات') || str_contains($notification->message ?? '', 'ملاحظات');

            if ($isFailed) {
                $displayTitle = 'فشل في المزامنة المجدولة';
                $displayMessage = $notification->message ?: 'تعذر استكمال المزامنة، يرجى مراجعة السجلات';
                $iconClass = 'icon-cancel-1';
                $badgeClass = 'bg-red-100 text-red-600 dark:bg-red-900/40 dark:text-red-400';
            } elseif ($hasWarnings) {
                $displayTitle = 'اكتملت المزامنة مع ملاحظات';
                $displayMessage = $notification->message ?: 'اكتملت المزامنة مع وجود بعض التنبيهات';
                $iconClass = 'icon-information';
                $badgeClass = 'bg-amber-100 text-amber-600 dark:bg-amber-900/40 dark:text-amber-400';
            } else {
                $displayTitle = 'اكتملت مزامنة الموردين (AliExpress)';
                $displayMessage = $notification->message ?: 'تم تحديث الأسعار والمخزون بنجاح';
                $iconClass = 'icon-processing';
                $badgeClass = 'bg-blue-100 text-blue-600 dark:bg-blue-900/40 dark:text-blue-400';
            }

            $actionUrl = route('admin.notification.viewed_notification', $notification->id);

        } elseif ($type === 'order_reminder') {
            $category = 'orders';
            $orderId = $notification->order_id ?? $notification->entity_id;
            $displayTitle = 'تنبيه طلب غير مقفل #'.$orderId;
            $displayMessage = 'الطلب بحاجة لمتابعة التوريد والتنفيذ';
            $iconClass = 'icon-information';
            $badgeClass = 'bg-amber-100 text-amber-600 dark:bg-amber-900/40 dark:text-amber-400';
            $actionUrl = route('admin.notification.viewed_notification', $notification->id);

        } else {
            // Default: Sales Orders (order, order_status, invoice, shipment)
            $category = 'orders';
            $order = $notification->order;
            if (! $order && $notification->order_id) {
                $order = Order::find($notification->order_id);
            }

            if ($order) {
                $orderNo = $order->increment_id ?: $order->id;
                $customerName = trim($order->customer_full_name ?: ($order->customer_first_name.' '.$order->customer_last_name));
                if (! $customerName && $order->customer) {
                    $customerName = trim($order->customer->first_name.' '.$order->customer->last_name);
                }
                $customerName = $customerName ?: 'عميل زائر';

                $statusLabel = $this->getOrderStatusLabel($order->status);
                $totalFormatted = core()->formatBasePrice($order->grand_total);
                $itemsCount = $order->total_item_count ?? ($order->items ? $order->items->count() : 1);

                if ($type === 'invoice') {
                    $displayTitle = "إصدار فاتورة لطلب #{$orderNo} - {$customerName}";
                    $displayMessage = "المبلغ: {$totalFormatted} | الفاتورة جاهزة";
                    $iconClass = 'icon-done';
                    $badgeClass = 'bg-emerald-100 text-emerald-600 dark:bg-emerald-900/40 dark:text-emerald-400';
                } elseif ($type === 'shipment') {
                    $displayTitle = "شحن طلب #{$orderNo} - {$customerName}";
                    $displayMessage = 'تم تجهيز الشحنة وإرسالها للعميل';
                    $iconClass = 'icon-truck';
                    $badgeClass = 'bg-blue-100 text-blue-600 dark:bg-blue-900/40 dark:text-blue-400';
                } else {
                    $displayTitle = "طلب جديد #{$orderNo} - {$customerName}";
                    $displayMessage = "المبلغ: {$totalFormatted} | الحالة: {$statusLabel} ({$itemsCount} منتج)";
                    $iconClass = match ($order->status) {
                        'completed' => 'icon-done',
                        'canceled', 'closed' => 'icon-cancel-1',
                        'processing' => 'icon-sort-right',
                        default => 'icon-information',
                    };
                    $badgeClass = match ($order->status) {
                        'completed' => 'bg-emerald-100 text-emerald-600 dark:bg-emerald-900/40 dark:text-emerald-400',
                        'canceled', 'closed' => 'bg-red-100 text-red-600 dark:bg-red-900/40 dark:text-red-400',
                        'processing' => 'bg-blue-100 text-blue-600 dark:bg-blue-900/40 dark:text-blue-400',
                        default => 'bg-amber-100 text-amber-600 dark:bg-amber-900/40 dark:text-amber-400',
                    };
                }
            } else {
                $displayTitle = $notification->title ?: ('إشعار #'.$notification->id);
                $displayMessage = $notification->message ?: '';
                $iconClass = 'icon-information';
                $badgeClass = 'bg-blue-100 text-blue-600 dark:bg-blue-900/40 dark:text-blue-400';
            }

            $actionUrl = route('admin.notification.viewed_notification', $notification->order_id ?: $notification->id);
        }

        // Assign formatted fields directly to notification object
        $notification->display_title = $displayTitle;
        $notification->display_message = $displayMessage;
        $notification->category = $category;
        $notification->icon_class = $iconClass;
        $notification->badge_class = $badgeClass;
        $notification->action_url = $actionUrl;
        $notification->time_ago = $timeAgo;

        // Maintain synthetic backward compatibility for legacy templates
        $syntheticOrder = [
            'id' => $displayTitle,
            'status' => $category,
            'datetime' => $timeAgo,
            'message' => $displayMessage,
        ];
        $notification->setAttribute('order', $syntheticOrder);
        $notification->setRelation('order', (object) $syntheticOrder);
    }

    /**
     * Get Arabic label for order statuses.
     */
    protected function getOrderStatusLabel(?string $status): string
    {
        return match ($status) {
            'pending' => 'بانتظار الدفع',
            'processing' => 'قيد المعالجة',
            'completed' => 'مكتمل',
            'canceled' => 'ملغي',
            'closed' => 'مغلق',
            'pending_payment' => 'بانتظار السداد',
            default => $status ?: 'جديد',
        };
    }

    /**
     * Convert Carbon date to natural Arabic human time.
     */
    protected function arabicDiffForHumans($date): string
    {
        if (! $date instanceof Carbon) {
            $date = Carbon::parse($date);
        }

        return $date->locale('ar')->diffForHumans();
    }

    /**
     * Get isolated notifications for Courier and Point Agent.
     */
    protected function getCourierNotifications($user): array
    {
        $assignments = collect();

        if (class_exists(DeliveryAssignment::class)) {
            $query = DeliveryAssignment::with('order');

            if (isset($user->delivery_point_id) && $user->delivery_point_id) {
                $query->forDeliveryPoint($user->delivery_point_id);
            } else {
                $query->forAgent($user->id);
            }

            $assignments = $query->whereIn('status', ['assigned', 'picked_up', 'out_for_delivery'])
                ->orderBy('id', 'desc')
                ->take(5)
                ->get();
        }

        $notifications = [];
        foreach ($assignments as $assignment) {
            $statusText = match ($assignment->status) {
                'assigned' => 'مهمة مسندة جديدة',
                'picked_up' => 'مستلمة من المستودع',
                'out_for_delivery' => 'في الطريق للعميل',
                default => 'مهمة شحن'
            };

            $orderNo = $assignment->order?->increment_id ?? $assignment->order_id;
            $timeAgo = $assignment->created_at ? $this->arabicDiffForHumans($assignment->created_at) : 'الآن';
            $title = "{$statusText}: طلب #{$orderNo}";
            $message = 'انقر لعرض تفاصيل التوصيل وتحديث الحالة';

            $notifications[] = (object) [
                'id' => $assignment->id,
                'order_id' => $assignment->id,
                'type' => 'delivery_assignment',
                'category' => 'delivery',
                'title' => $title,
                'display_title' => $title,
                'display_message' => $message,
                'icon_class' => 'icon-truck',
                'badge_class' => 'bg-blue-100 text-blue-600 dark:bg-blue-900/40 dark:text-blue-400',
                'action_url' => route('admin.courier.show', $assignment->id),
                'read' => 0,
                'created_at' => $assignment->created_at,
                'time_ago' => $timeAgo,
                'order' => (object) [
                    'id' => $title,
                    'status' => 'processing',
                    'datetime' => $timeAgo,
                ],
            ];
        }

        $paginator = new LengthAwarePaginator(
            $notifications,
            count($notifications),
            5,
            1
        );

        return [
            'search_results' => $paginator,
            'status_count' => [],
            'category_counts' => [
                'all' => count($notifications),
                'delivery' => count($notifications),
            ],
            'total_unread' => count($notifications),
        ];
    }

    /**
     * Update the notification is read or not.
     *
     * @param  int  $id
     * @return mixed
     */
    public function viewedNotifications($id)
    {
        $user = auth()->guard('admin')->user();
        if ($user && in_array($user->role?->name, ['Courier', 'PointAgent'])) {
            return redirect()->route('admin.courier.show', $id);
        }

        $notification = $this->notificationRepository->whereNull('customer_id')->where('id', $id)->first()
            ?? $this->notificationRepository->whereNull('customer_id')->where('order_id', $id)->first();

        if ($notification) {
            $notification->read = 1;
            $notification->save();

            if ($notification->type === 'wallet_topup') {
                return redirect()->route('admin.wallet.deposits.index');
            }

            if ($notification->type === 'wallet_withdrawal') {
                return redirect()->route('admin.wallet.withdrawals.index');
            }

            if ($notification->type === 'scheduled_sync' || $notification->type === 'sync') {
                return redirect()->route('admin.dropshipping.sync.index');
            }

            if ($notification->type === 'order_reminder' && $notification->order_id) {
                return redirect()->route('admin.sales.orders.view', $notification->order_id);
            }

            if (in_array($notification->type, ['low_stock', 'out_of_stock'])) {
                $productId = $notification->entity_id;
                if ($productId) {
                    $product = class_exists(ProductProxy::class)
                        ? ProductProxy::find($productId)
                        : null;
                    $targetId = ($product && $product->parent_id) ? $product->parent_id : $productId;

                    return redirect()->route('admin.catalog.products.edit', $targetId);
                }

                return redirect()->route('admin.catalog.products.index');
            }

            if ($notification->order_id) {
                return redirect()->route('admin.sales.orders.view', $notification->order_id);
            }

            return redirect()->back();
        }

        abort(404);
    }

    /**
     * Update the notification is read or not.
     *
     * @return array
     */
    public function readAllNotifications()
    {
        $user = auth()->guard('admin')->user();
        if ($user && in_array($user->role?->name, ['Courier', 'PointAgent'])) {
            return [
                'search_results' => new LengthAwarePaginator([], 0, 5, 1),
                'total_unread' => 0,
                'success_message' => trans('admin::app.notifications.marked-success'),
            ];
        }

        $this->notificationRepository->whereNull('customer_id')->where('read', 0)->update(['read' => 1]);

        $searchResults = $this->notificationRepository->getParamsData([
            'limit' => 5,
            'read' => 0,
        ]);

        return [
            'search_results' => $searchResults,
            'total_unread' => $this->notificationRepository->whereNull('customer_id')->where('read', 0)->count(),
            'success_message' => trans('admin::app.notifications.marked-success'),
        ];
    }
}
