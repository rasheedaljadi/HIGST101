<?php

namespace Webkul\Wallet\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;
use Webkul\Wallet\DataGrids\WalletAccountsDataGrid;
use Webkul\Wallet\Models\WalletTransaction;
use Webkul\Wallet\Repositories\WalletAccountRepository;
use Webkul\Wallet\Services\WalletService;

class WalletAccountController extends Controller
{
    public function __construct(
        protected WalletAccountRepository $walletAccountRepository,
        protected WalletService $walletService
    ) {}

    /**
     * Display wallet accounts list.
     */
    public function index()
    {
        if (! bouncer()->hasPermission('wallet.accounts.view')) {
            abort(403);
        }

        if (request()->ajax()) {
            return app(WalletAccountsDataGrid::class)->toJson();
        }

        return view('wallet::admin.wallet.accounts.index');
    }

    /**
     * Display customer wallet details and narrative timeline with real database records.
     *
     * @param  int  $id
     * @return View
     */
    public function show($id)
    {
        if (function_exists('bouncer') && ! bouncer()->hasPermission('wallet.accounts.view')) {
            abort(403);
        }

        $wallet = $this->walletAccountRepository->with('customer')->findOrFail($id);

        $customer = [
            'name' => $wallet->customer ? ($wallet->customer->first_name.' '.$wallet->customer->last_name) : 'Wallet Customer #'.$wallet->customer_id,
            'email' => $wallet->customer->email ?? '—',
            'status' => ucfirst($wallet->status ?? 'active'),
        ];

        $balances = [
            'total' => (float) $wallet->total_balance,
            'available' => (float) $wallet->available_balance,
            'held' => (float) $wallet->held_balance,
        ];

        $transactions = $wallet->transactions()
            ->latest('id')
            ->get()
            ->map(function ($tx) {
                $isCredit = $tx->direction === 'credit';

                $desc = $tx->description;
                if ($desc) {
                    $desc = str_replace(
                        ['Refund for Order #', 'Payment for Order #', 'Cashback Reward', 'for Order #', 'Withdrawal #', 'completed (Ref:', 'Wallet suspended by admin:', 'Wallet reactivated by admin:'],
                        ['استرداد للطلب #', 'دفع للطلب #', 'مكافأة كاشباك', 'للطلب #', 'طلب سحب #', 'مكتمل (مرجع:', 'تجميد بواسطة الأدمن:', 'تنشيط بواسطة الأدمن:'],
                        $desc
                    );
                }

                return [
                    'id' => $tx->id,
                    'date' => $tx->created_at ? $tx->created_at->format('Y/m/d H:i') : '—',
                    'type' => $tx->type_label ?: 'حركة مالية',
                    'direction' => $tx->direction,
                    'direction_label' => $isCredit ? 'إيداع (+)' : 'خصم (-)',
                    'amount' => (float) $tx->amount,
                    'amount_formatted' => ($isCredit ? '+' : '-').core()->formatBasePrice((float) $tx->amount),
                    'running_balance_formatted' => core()->formatBasePrice((float) $tx->running_balance),
                    'desc' => $desc ?: '—',
                ];
            })->toArray();

        return view('wallet::admin.accounts.show', compact('wallet', 'customer', 'balances', 'transactions'));
    }

    /**
     * Manual balance adjustment.
     */
    public function adjust(Request $request, int $id)
    {
        if (! bouncer()->hasPermission('wallet.accounts.adjust')) {
            abort(403);
        }

        $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'direction' => 'required|in:credit,debit',
            'reason' => 'required|string|max:500',
        ]);

        $wallet = $this->walletAccountRepository->findOrFail($id);

        $this->walletService->adjust(
            wallet: $wallet,
            amount: (float) $request->amount,
            direction: $request->direction,
            reason: $request->reason,
            adminUserId: auth()->guard('admin')->id(),
        );

        session()->flash('success', trans('wallet::app.admin.wallet.accounts.adjusted'));

        return redirect()->route('admin.wallet.accounts.show', $id);
    }

    /**
     * Suspend a wallet account.
     */
    public function suspend(Request $request, int $id)
    {
        if (! bouncer()->hasPermission('wallet.accounts.suspend')) {
            abort(403);
        }

        $request->validate(['reason' => 'required|string|max:500']);

        $wallet = $this->walletAccountRepository->findOrFail($id);

        if ($wallet->status === 'suspended') {
            session()->flash('info', trans('wallet::app.admin.wallet.accounts.already-suspended'));

            return redirect()->route('admin.wallet.accounts.show', $id);
        }

        $wallet->update(['status' => 'suspended']);

        // Log audit transaction row
        WalletTransaction::create([
            'wallet_id' => $wallet->id,
            'type' => WalletTransaction::TYPE_SUSPENSION_FREEZE,
            'direction' => 'debit',
            'amount' => 0,
            'running_balance' => $wallet->available_balance,
            'description' => 'Wallet suspended by admin: '.$request->reason,
            'created_by_type' => 'admin',
            'created_by_id' => auth()->guard('admin')->id(),
        ]);

        session()->flash('success', trans('wallet::app.admin.wallet.accounts.suspended'));

        return redirect()->route('admin.wallet.accounts.show', $id);
    }

    /**
     * Reactivate a suspended wallet account.
     */
    public function reactivate(Request $request, int $id)
    {
        if (! bouncer()->hasPermission('wallet.accounts.suspend')) {
            abort(403);
        }

        $request->validate(['reason' => 'required|string|max:500']);

        $wallet = $this->walletAccountRepository->findOrFail($id);

        if ($wallet->status === 'active') {
            session()->flash('info', trans('wallet::app.admin.wallet.accounts.already-active'));

            return redirect()->route('admin.wallet.accounts.show', $id);
        }

        $wallet->update(['status' => 'active']);

        // Log audit transaction row
        WalletTransaction::create([
            'wallet_id' => $wallet->id,
            'type' => WalletTransaction::TYPE_SUSPENSION_RELEASE,
            'direction' => 'credit',
            'amount' => 0,
            'running_balance' => $wallet->available_balance,
            'description' => 'Wallet reactivated by admin: '.$request->reason,
            'created_by_type' => 'admin',
            'created_by_id' => auth()->guard('admin')->id(),
        ]);

        session()->flash('success', trans('wallet::app.admin.wallet.accounts.reactivated'));

        return redirect()->route('admin.wallet.accounts.show', $id);
    }
}
