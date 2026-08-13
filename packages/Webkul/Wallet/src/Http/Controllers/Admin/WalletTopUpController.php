<?php

namespace Webkul\Wallet\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;
use Webkul\Notification\Services\CustomerNotificationService;
use Webkul\Wallet\DataGrids\WalletTopUpsDataGrid;
use Webkul\Wallet\Exceptions\InvalidWalletTransitionException;
use Webkul\Wallet\Models\WalletTopUp;
use Webkul\Wallet\Models\WalletTransaction;
use Webkul\Wallet\Notifications\WalletTopUpApprovedNotification;
use Webkul\Wallet\Notifications\WalletTopUpRejectedNotification;
use Webkul\Wallet\Repositories\WalletAccountRepository;
use Webkul\Wallet\Repositories\WalletTopUpRepository;
use Webkul\Wallet\Services\WalletService;

class WalletTopUpController extends Controller
{
    public function __construct(
        protected WalletTopUpRepository $walletTopUpRepository,
        protected WalletAccountRepository $walletAccountRepository,
        protected WalletService $walletService
    ) {}

    /**
     * Display top-up requests list.
     */
    public function index()
    {
        if (! bouncer()->hasPermission('wallet.deposits.view')) {
            abort(403);
        }

        if (request()->ajax()) {
            return app(WalletTopUpsDataGrid::class)->toJson();
        }

        return view('wallet::admin.wallet.deposits.index');
    }

    /**
     * Approve a top-up request and credit wallet.
     */
    public function approve(Request $request, int $id)
    {
        if (! bouncer()->hasPermission('wallet.deposits.approve')) {
            if (request()->ajax()) {
                return response()->json(['message' => 'غير مصرح لك بإجراء هذه العملية.'], 403);
            }
            abort(403);
        }

        $topup = $this->walletTopUpRepository->findOrFail($id);

        if (! $topup->canTransitionTo(WalletTopUp::STATUS_COMPLETED)) {
            $msg = 'طلب الإيداع مكتمل أو معالج مسبقاً.';
            if (request()->ajax()) {
                return response()->json(['message' => $msg], 400);
            }
            throw new InvalidWalletTransitionException($topup->status, WalletTopUp::STATUS_COMPLETED);
        }

        $wallet = $this->walletAccountRepository->find($topup->wallet_id);

        $this->walletService->credit(
            wallet: $wallet,
            amount: $topup->amount,
            type: WalletTransaction::TYPE_CREDIT_TOPUP,
            description: 'Top-Up #'.$topup->id.' approved',
            referenceType: WalletTopUp::class,
            referenceId: $topup->id,
            createdByType: 'admin',
            createdById: auth()->guard('admin')->id()
        );

        $topup->update([
            'status' => WalletTopUp::STATUS_COMPLETED,
            'admin_user_id' => auth()->guard('admin')->id(),
            'admin_notes' => $request->admin_notes,
            'approved_at' => now(),
        ]);

        if ($wallet?->customer) {
            try {
                $wallet->customer->notify(new WalletTopUpApprovedNotification($topup));
            } catch (\Throwable $e) {
                Log::error('Mail send error on topup approval: '.$e->getMessage());
            }

            try {
                app(CustomerNotificationService::class)->createCustomerNotification(
                    customerId: $wallet->customer->id,
                    type: 'wallet_topup',
                    title: 'تمت الموافقة على إيداع الرصيد',
                    message: "تم اعتماد طلب إيداع الرصيد رقم #{$topup->id} وإضافته لمحفظتك بنجاح.",
                    actionUrl: '/customer/account/wallet',
                    eventKey: "wallet_topup:{$topup->id}:completed"
                );
            } catch (\Throwable $e) {
                Log::error('In-app notification error on topup approval: '.$e->getMessage());
            }
        }

        $message = trans('wallet::app.admin.wallet.deposits.approved') ?? 'تم الموافقة على طلب الإيداع وإضافة الرصيد بنجاح.';

        if (request()->ajax()) {
            return response()->json([
                'message' => $message,
            ]);
        }

        session()->flash('success', $message);

        return redirect()->route('admin.wallet.deposits.index');
    }

    /**
     * Reject a top-up request.
     */
    public function reject(Request $request, int $id)
    {
        if (! bouncer()->hasPermission('wallet.deposits.approve')) {
            if (request()->ajax()) {
                return response()->json(['message' => 'غير مصرح لك بإجراء هذه العملية.'], 403);
            }
            abort(403);
        }

        $topup = $this->walletTopUpRepository->findOrFail($id);

        if (! $topup->canTransitionTo(WalletTopUp::STATUS_FAILED)) {
            $msg = 'طلب الإيداع مكتمل أو مرفوض مسبقاً.';
            if (request()->ajax()) {
                return response()->json(['message' => $msg], 400);
            }
            throw new InvalidWalletTransitionException($topup->status, WalletTopUp::STATUS_FAILED);
        }

        $topup->update([
            'status' => WalletTopUp::STATUS_FAILED,
            'admin_user_id' => auth()->guard('admin')->id(),
            'admin_notes' => $request->admin_notes,
        ]);

        $wallet = $this->walletAccountRepository->find($topup->wallet_id);

        if ($wallet?->customer) {
            try {
                $wallet->customer->notify(new WalletTopUpRejectedNotification($topup));
            } catch (\Throwable $e) {
                Log::error('Mail send error on topup rejection: '.$e->getMessage());
            }

            try {
                app(CustomerNotificationService::class)->createCustomerNotification(
                    customerId: $wallet->customer->id,
                    type: 'wallet_topup',
                    title: 'تم رفض طلب إيداع الرصيد',
                    message: "تم رفض طلب إيداع الرصيد رقم #{$topup->id}.",
                    actionUrl: '/customer/account/wallet',
                    eventKey: "wallet_topup:{$topup->id}:failed"
                );
            } catch (\Throwable $e) {
                Log::error('In-app notification error on topup rejection: '.$e->getMessage());
            }
        }

        $message = trans('wallet::app.admin.wallet.deposits.rejected') ?? 'تم رفض طلب الإيداع بنجاح.';

        if (request()->ajax()) {
            return response()->json([
                'message' => $message,
            ]);
        }

        session()->flash('success', $message);

        return redirect()->route('admin.wallet.deposits.index');
    }
}
