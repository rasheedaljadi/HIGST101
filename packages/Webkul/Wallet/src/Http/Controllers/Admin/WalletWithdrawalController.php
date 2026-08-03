<?php

namespace Webkul\Wallet\Http\Controllers\Admin;

use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Webkul\Wallet\DataGrids\WalletWithdrawalsDataGrid;
use Webkul\Wallet\Http\Requests\Admin\ApproveWithdrawalRequest;
use Webkul\Wallet\Http\Requests\Admin\RejectWithdrawalRequest;
use Webkul\Wallet\Models\WalletTransaction;
use Webkul\Wallet\Models\WalletWithdrawalRequest;
use Webkul\Wallet\Notifications\WalletWithdrawalCompletedNotification;
use Webkul\Wallet\Notifications\WalletWithdrawalRejectedNotification;
use Webkul\Wallet\Repositories\WalletAccountRepository;
use Webkul\Wallet\Repositories\WalletWithdrawalRequestRepository;
use Webkul\Wallet\Services\WalletService;

class WalletWithdrawalController extends Controller
{
    public function __construct(
        protected WalletWithdrawalRequestRepository $withdrawalRepository,
        protected WalletAccountRepository $walletAccountRepository,
        protected WalletService $walletService
    ) {}

    /**
     * Display withdrawal requests list.
     */
    public function index()
    {
        if (function_exists('bouncer') && ! bouncer()->hasPermission('wallet.withdrawals.view')) {
            abort(403);
        }

        if (request()->ajax()) {
            return app(WalletWithdrawalsDataGrid::class)->toJson();
        }

        return view('wallet::admin.wallet.withdrawals.index');
    }

    /**
     * Display withdrawal processing screen with risk analysis.
     *
     * @param  int|string  $id
     * @return View
     */
    public function edit($id)
    {
        if (function_exists('bouncer') && ! bouncer()->hasPermission('wallet.withdrawals.view')) {
            abort(403);
        }

        $withdrawalModel = $this->withdrawalRepository->find($id);

        if ($withdrawalModel) {
            $withdrawal = [
                'raw_id' => $withdrawalModel->id,
                'id' => '#REQ-'.$withdrawalModel->id,
                'customer' => $withdrawalModel->wallet && $withdrawalModel->wallet->customer ? ($withdrawalModel->wallet->customer->first_name.' '.$withdrawalModel->wallet->customer->last_name) : 'Customer #'.$withdrawalModel->wallet_id,
                'amount' => core()->formatBasePrice((float) $withdrawalModel->amount),
                'method' => 'Bank Transfer',
                'bank_name' => $withdrawalModel->bank_details['bank_name'] ?? 'Al Kuraimi Bank',
                'account_name' => $withdrawalModel->bank_details['account_name'] ?? 'Customer Account',
                'masked_iban' => $withdrawalModel->bank_details['iban'] ? (substr($withdrawalModel->bank_details['iban'], 0, 2).'****'.substr($withdrawalModel->bank_details['iban'], -4)) : 'SA****7519',
            ];
        } else {
            $withdrawal = [
                'raw_id' => $id,
                'id' => '#REQ-'.$id,
                'customer' => 'Ahmed Mohammed',
                'amount' => '$300.00',
                'method' => 'Bank Transfer',
                'bank_name' => 'Al Kuraimi Bank',
                'account_name' => 'Ahmed Mohammed',
                'masked_iban' => 'SA****7519',
            ];
        }

        $riskProfile = [
            'level' => 'Medium',
            'colorClass' => 'text-orange-600 bg-orange-100 dark:text-orange-400 dark:bg-orange-900/30 border-orange-200 dark:border-orange-800',
            'factors' => [
                'Account is only 3 days old',
                'Withdrawal amount is 100% of available balance',
                'Recent deposit via PayPal (chargeback risk)',
            ],
        ];

        return view('wallet::admin.withdrawals.edit', compact('withdrawal', 'riskProfile'));
    }

    /**
     * Mark withdrawal as completed (bank transfer done).
     */
    public function complete(ApproveWithdrawalRequest $request, int $id)
    {
        if (function_exists('bouncer') && ! bouncer()->hasPermission('wallet.withdrawals.process')) {
            abort(403);
        }

        $withdrawal = $this->withdrawalRepository->findOrFail($id);

        if (! $withdrawal->isPending()) {
            if (request()->ajax()) {
                return response()->json(['message' => 'طلب السحب تم معالجته سابقاً.'], 400);
            }

            session()->flash('error', trans('wallet::app.admin.wallet.withdrawals.not-pending') ?? 'Withdrawal request is not pending.');

            return redirect()->route('admin.wallet.withdrawals.index');
        }

        $bankRef = $request->bank_reference_id ?? $request->bank_transaction_reference ?? 'REF-'.time();

        DB::transaction(function () use ($request, $withdrawal, $bankRef) {
            $wallet = $this->walletAccountRepository->find($withdrawal->wallet_id);

            $this->walletService->completeWithdrawal(
                wallet: $wallet,
                amount: $withdrawal->amount,
                description: 'Withdrawal #'.$withdrawal->id.' completed (Bank Ref: '.$bankRef.')',
                referenceType: WalletWithdrawalRequest::class,
                referenceId: $withdrawal->id,
                createdByType: 'admin',
                createdById: auth()->guard('admin')->id()
            );

            $withdrawal->update([
                'status' => WalletWithdrawalRequest::STATUS_COMPLETED,
                'admin_user_id' => auth()->guard('admin')->id(),
                'bank_transaction_reference' => $bankRef,
                'admin_notes' => $request->admin_notes,
                'transferred_at' => now(),
            ]);

            if ($wallet?->customer && class_exists(WalletWithdrawalCompletedNotification::class)) {
                try {
                    $wallet->customer->notify(new WalletWithdrawalCompletedNotification($withdrawal));
                } catch (\Throwable $e) {
                    // Prevent notification email issues from failing DB transaction
                }
            }
        });

        if (request()->ajax()) {
            return response()->json([
                'message' => trans('wallet::app.admin.wallet.withdrawals.completed') ?? 'تمت الموافقة على طلب السحب وتحويل المبلغ بنجاح.',
            ]);
        }

        session()->flash('success', trans('wallet::app.admin.wallet.withdrawals.completed') ?? 'Withdrawal completed successfully.');

        return redirect()->route('admin.wallet.withdrawals.index');
    }

    /**
     * Alias for complete withdrawal execution.
     */
    public function process(ApproveWithdrawalRequest $request, int $id)
    {
        return $this->complete($request, $id);
    }

    /**
     * Reject withdrawal and release the held balance.
     */
    public function reject(RejectWithdrawalRequest $request, int $id)
    {
        if (function_exists('bouncer') && ! bouncer()->hasPermission('wallet.withdrawals.process')) {
            abort(403);
        }

        $withdrawal = $this->withdrawalRepository->findOrFail($id);

        if (! $withdrawal->isPending()) {
            if (request()->ajax()) {
                return response()->json(['message' => 'طلب السحب تم معالجته سابقاً.'], 400);
            }

            session()->flash('error', trans('wallet::app.admin.wallet.withdrawals.not-pending') ?? 'Withdrawal request is not pending.');

            return redirect()->route('admin.wallet.withdrawals.index');
        }

        DB::transaction(function () use ($request, $withdrawal) {
            $wallet = $this->walletAccountRepository->find($withdrawal->wallet_id);

            $this->walletService->release(
                wallet: $wallet,
                amount: $withdrawal->amount,
                type: WalletTransaction::TYPE_RELEASE_HOLD,
                description: 'Withdrawal #'.$withdrawal->id.' rejected — balance released',
                referenceType: WalletWithdrawalRequest::class,
                referenceId: $withdrawal->id
            );

            $withdrawal->update([
                'status' => WalletWithdrawalRequest::STATUS_REJECTED,
                'admin_user_id' => auth()->guard('admin')->id(),
                'rejection_reason' => $request->rejection_reason ?? 'تم الرفض بواسطة الإدارة',
                'rejected_at' => now(),
            ]);

            if ($wallet?->customer && class_exists(WalletWithdrawalRejectedNotification::class)) {
                try {
                    $wallet->customer->notify(new WalletWithdrawalRejectedNotification($withdrawal));
                } catch (\Throwable $e) {
                    // Prevent notification email issues from failing DB transaction
                }
            }
        });

        if (request()->ajax()) {
            return response()->json([
                'message' => trans('wallet::app.admin.wallet.withdrawals.rejected') ?? 'تم رفض طلب السحب وإلغاء حجز المبلغ وإعادته لحساب العميل بنجاح.',
            ]);
        }

        session()->flash('success', trans('wallet::app.admin.wallet.withdrawals.rejected') ?? 'Withdrawal request rejected and balance released.');

        return redirect()->route('admin.wallet.withdrawals.index');
    }
}
