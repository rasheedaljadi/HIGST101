<?php

namespace Webkul\Admin\Http\Controllers;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\View\View;
use Webkul\DeliveryManagement\Models\DeliveryAssignment;
use Webkul\Notification\Repositories\NotificationRepository;
use Webkul\Product\Models\ProductProxy;
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
            if ($notification->type === 'wallet_topup') {
                $topupId = $notification->entity_id ?? $notification->id;
                $topup = class_exists(WalletTopUp::class)
                    ? WalletTopUp::find($notification->entity_id)
                    : null;

                $amountStr = $topup ? core()->formatBasePrice($topup->amount) : '';

                $syntheticOrder = [
                    'id' => 'إيداع محفظة #'.$topupId.($amountStr ? ' ('.$amountStr.')' : ''),
                    'status' => 'pending',
                    'datetime' => $notification->created_at ? $notification->created_at->diffForHumans() : 'الآن',
                ];

                $notification->setAttribute('order', $syntheticOrder);
                $notification->setRelation('order', (object) $syntheticOrder);
                $notification->order_id = $notification->id;
            } elseif ($notification->type === 'wallet_withdrawal') {
                $withdrawalId = $notification->entity_id ?? $notification->id;
                $withdrawal = class_exists(WalletWithdrawalRequest::class)
                    ? WalletWithdrawalRequest::find($notification->entity_id)
                    : null;

                $amountStr = $withdrawal ? core()->formatBasePrice($withdrawal->amount) : '';

                $syntheticOrder = [
                    'id' => 'سحب محفظة #'.$withdrawalId.($amountStr ? ' ('.$amountStr.')' : ''),
                    'status' => 'pending',
                    'datetime' => $notification->created_at ? $notification->created_at->diffForHumans() : 'الآن',
                ];

                $notification->setAttribute('order', $syntheticOrder);
                $notification->setRelation('order', (object) $syntheticOrder);
                $notification->order_id = $notification->id;
            } elseif (in_array($notification->type, ['low_stock', 'out_of_stock'])) {
                $isOutOfStock = ($notification->type === 'out_of_stock');
                $product = class_exists(ProductProxy::class) && $notification->entity_id
                    ? ProductProxy::find($notification->entity_id)
                    : null;

                $productName = $product ? ($product->name ?? $product->sku) : ($notification->title ?? 'المنتج');
                $sku = $product ? $product->sku : '';
                $prefix = $isOutOfStock ? 'نفاد مخزون: ' : 'انخفاض مخزون: ';
                $titleText = $prefix.$productName.($sku ? ' ('.$sku.')' : '');

                $syntheticOrder = [
                    'id' => $titleText,
                    'status' => $isOutOfStock ? 'canceled' : 'pending',
                    'datetime' => $notification->created_at ? $notification->created_at->diffForHumans() : 'الآن',
                ];

                $notification->setAttribute('order', $syntheticOrder);
                $notification->setRelation('order', (object) $syntheticOrder);
                $notification->order_id = $notification->id;
            } elseif ($notification->type === 'scheduled_sync') {
                $isFailed = str_contains($notification->title ?? '', 'فشل');
                $hasWarnings = str_contains($notification->title ?? '', 'تنبيهات') || str_contains($notification->title ?? '', 'ملاحظات');

                $status = $isFailed ? 'canceled' : ($hasWarnings ? 'pending' : 'completed');
                $titleText = $notification->title ?? 'مزامنة مجدولة';

                $syntheticOrder = [
                    'id' => $titleText,
                    'status' => $status,
                    'datetime' => $notification->created_at ? $notification->created_at->diffForHumans() : 'الآن',
                ];

                $notification->setAttribute('order', $syntheticOrder);
                $notification->setRelation('order', (object) $syntheticOrder);
                $notification->order_id = $notification->id;
            } elseif ($notification->type === 'order_reminder') {
                $orderId = $notification->order_id ?? $notification->entity_id;
                $titleText = $notification->title ?? ('تذكير: طلب #'.$orderId.' غير مقفل');

                $syntheticOrder = [
                    'id' => $titleText,
                    'status' => 'pending',
                    'datetime' => $notification->created_at ? $notification->created_at->diffForHumans() : 'الآن',
                ];

                $notification->setAttribute('order', $syntheticOrder);
                $notification->setRelation('order', (object) $syntheticOrder);
                $notification->order_id = $notification->order_id ?: $notification->id;
            }
        }

        $statusCount = isset($searchResults['status_counts']) ? $searchResults['status_counts'] : '';

        return [
            'search_results' => $results,
            'status_count' => $statusCount,
            'total_unread' => $this->notificationRepository->whereNull('customer_id')->where('read', 0)->count(),
        ];
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

            $notifications[] = (object) [
                'id' => $assignment->id,
                'order_id' => $assignment->id,
                'type' => 'delivery_assignment',
                'title' => "{$statusText}: طلب #{$orderNo}",
                'read' => 0,
                'created_at' => $assignment->created_at ? $assignment->created_at->diffForHumans() : 'الآن',
                'order' => (object) [
                    'id' => "{$statusText}: طلب #{$orderNo}",
                    'status' => 'processing',
                    'datetime' => $assignment->created_at ? $assignment->created_at->diffForHumans() : 'الآن',
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

            if ($notification->type === 'scheduled_sync') {
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
     * Update the notification is reade or not.
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
