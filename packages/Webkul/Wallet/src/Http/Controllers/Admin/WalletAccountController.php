<?php

namespace Webkul\Wallet\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Str;
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

        $recentTransactions = $wallet->transactions()
            ->latest('id')
            ->take(15)
            ->get();

        $timeline = $recentTransactions->map(function ($tx) {
            $isCredit = $tx->direction === 'credit';
            $prefix = $isCredit ? '+' : '-';
            $amountFormatted = $prefix.core()->formatBasePrice((float) $tx->amount);

            $icon = match ($tx->type) {
                'CREDIT_REFUND' => 'icon-down-stat',
                'CREDIT_TOPUP' => 'icon-down-stat',
                'DEBIT_PAYMENT' => 'icon-up-stat',
                'DEBIT_WITHDRAWAL' => 'icon-up-stat',
                default => $isCredit ? 'icon-star' : 'icon-up-stat',
            };

            return [
                'date' => $tx->created_at ? $tx->created_at->format('d M Y') : '—',
                'type' => $tx->type_label ?: Str::title(str_replace('_', ' ', $tx->type ?? '')),
                'amount' => $amountFormatted,
                'color' => $isCredit ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400',
                'desc' => $tx->description ?: ($tx->reference_type ? class_basename($tx->reference_type).' #'.$tx->reference_id : 'System Entry'),
                'icon' => $icon,
            ];
        })->toArray();

        return view('wallet::admin.accounts.show', compact('wallet', 'customer', 'balances', 'timeline'));
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
