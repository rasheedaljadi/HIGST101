<?php

namespace Webkul\Wallet\Http\Controllers\Admin;

use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Webkul\Wallet\Models\WalletAccount;
use Webkul\Wallet\Models\WalletReconciliation;
use Webkul\Wallet\Models\WalletTransaction;
use Webkul\Wallet\Models\WalletWithdrawalRequest;

class WalletReportController extends Controller
{
    /**
     * Display financial dashboard and liability metrics.
     */
    public function index()
    {
        if (! bouncer()->hasPermission('wallet.reporting.view')) {
            abort(403);
        }

        $totalLiability = (float) WalletAccount::sum('total_balance');
        $availableLiability = (float) WalletAccount::sum('available_balance');
        $heldLiability = (float) WalletAccount::sum('held_balance');

        $pendingWithdrawalTotal = (float) WalletWithdrawalRequest::where('status', WalletWithdrawalRequest::STATUS_PENDING)->sum('amount');
        $pendingWithdrawalCount = WalletWithdrawalRequest::where('status', WalletWithdrawalRequest::STATUS_PENDING)->count();

        $monthlyTopUps = (float) DB::table('wallet_transactions')
            ->where('type', WalletTransaction::TYPE_CREDIT_TOPUP)
            ->where('created_at', '>=', now()->startOfMonth())
            ->sum('amount');

        $monthlyRefunds = (float) DB::table('wallet_transactions')
            ->where('type', WalletTransaction::TYPE_CREDIT_REFUND)
            ->where('created_at', '>=', now()->startOfMonth())
            ->sum('amount');

        $monthlyWalletPayments = (float) DB::table('wallet_transactions')
            ->where('type', WalletTransaction::TYPE_DEBIT_PAYMENT)
            ->where('created_at', '>=', now()->startOfMonth())
            ->sum('amount');

        $recentReconciliations = WalletReconciliation::latest('run_at')->take(5)->get();

        return view('wallet::admin.wallet.reports.index', compact(
            'totalLiability',
            'availableLiability',
            'heldLiability',
            'pendingWithdrawalTotal',
            'pendingWithdrawalCount',
            'monthlyTopUps',
            'monthlyRefunds',
            'monthlyWalletPayments',
            'recentReconciliations'
        ));
    }
}
