<?php

namespace Webkul\Admin\Http\Controllers;

use Illuminate\View\View;
use Webkul\Notification\Repositories\NotificationRepository;
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
     * Update the notification is read or not.
     *
     * @param  int  $id
     * @return mixed
     */
    public function viewedNotifications($id)
    {
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
