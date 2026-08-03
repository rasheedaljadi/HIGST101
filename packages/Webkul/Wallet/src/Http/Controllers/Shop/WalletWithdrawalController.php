<?php

namespace Webkul\Wallet\Http\Controllers\Shop;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Webkul\Wallet\Models\WalletTransaction;
use Webkul\Wallet\Models\WalletWithdrawalRequest;
use Webkul\Wallet\Notifications\WalletWithdrawalSubmittedNotification;
use Webkul\Wallet\Repositories\WalletAccountRepository;
use Webkul\Wallet\Repositories\WalletWithdrawalRequestRepository;
use Webkul\Wallet\Services\WalletService;

class WalletWithdrawalController extends Controller
{
    public function __construct(
        protected WalletAccountRepository $walletAccountRepository,
        protected WalletWithdrawalRequestRepository $withdrawalRepository,
        protected WalletService $walletService
    ) {}

    /**
     * Withdrawal history.
     */
    public function index()
    {
        $customerId = auth()->guard('customer')->id();

        $wallet = $this->walletAccountRepository
            ->where('customer_id', $customerId)
            ->firstOrFail();

        $withdrawals = $this->withdrawalRepository
            ->where('wallet_id', $wallet->id)
            ->orderByDesc('created_at')
            ->paginate(20);

        return view('wallet::shop.wallet.withdrawals.index', compact('wallet', 'withdrawals'));
    }

    /**
     * Show withdrawal form.
     */
    public function create()
    {
        if (! core()->getConfigData('sales.wallet.enable_withdrawal')) {
            return redirect()->route('shop.customer.wallet.index')
                ->with('error', trans('wallet::app.shop.withdrawal.disabled'));
        }

        $customerId = auth()->guard('customer')->id();
        $wallet = $this->walletAccountRepository->where('customer_id', $customerId)->firstOrFail();
        $minAmount = (float) (core()->getConfigData('sales.wallet.min_withdrawal_amount') ?? 50);

        return view('wallet::shop.wallet.withdrawals.create', compact('wallet', 'minAmount'));
    }

    /**
     * Create withdrawal request and hold the balance atomically.
     *
     * C-03 & H-05 Fix: Wrapped in DB::transaction to prevent frozen funds on failure
     * and pass actual withdrawal ID as referenceId.
     */
    public function store(Request $request)
    {
        $minAmount = (float) (core()->getConfigData('sales.wallet.min_withdrawal_amount') ?? 50);

        $request->validate([
            'amount' => 'required|numeric|min:'.$minAmount,
            'beneficiary_name' => 'required|string|max:255',
            'bank_name' => 'required|string|max:255',
            'iban' => 'required|string|max:34',
            'account_number' => 'nullable|string|max:50',
            'swift_code' => 'nullable|string|max:11',
        ]);

        $customerId = auth()->guard('customer')->id();

        $wallet = $this->walletAccountRepository
            ->where('customer_id', $customerId)
            ->where('status', 'active')
            ->firstOrFail();

        DB::transaction(function () use ($request, $wallet) {
            // Step 1: Create withdrawal request record
            $withdrawal = $this->withdrawalRepository->create([
                'wallet_id' => $wallet->id,
                'amount' => $request->amount,
                'currency_code' => $wallet->currency_code,
                'status' => WalletWithdrawalRequest::STATUS_PENDING,
                'bank_details' => [
                    'beneficiary_name' => $request->beneficiary_name,
                    'bank_name' => $request->bank_name,
                    'iban' => $request->iban,
                    'account_number' => $request->account_number,
                    'swift_code' => $request->swift_code,
                ],
            ]);

            // Step 2: Hold balance with actual withdrawal ID (C-03 / H-05)
            $this->walletService->hold(
                wallet: $wallet,
                amount: (float) $request->amount,
                type: WalletTransaction::TYPE_HOLD_WITHDRAWAL,
                description: 'Withdrawal request #'.$withdrawal->id.' hold',
                referenceType: WalletWithdrawalRequest::class,
                referenceId: $withdrawal->id
            );

            if (auth()->guard('customer')->check()) {
                auth()->guard('customer')->user()->notify(new WalletWithdrawalSubmittedNotification($withdrawal));
            }
        });

        session()->flash('success', trans('wallet::app.shop.withdrawal.submitted'));

        return redirect()->route('shop.wallet.index');
    }
}
