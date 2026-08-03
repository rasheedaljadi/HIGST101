<?php

namespace Webkul\Wallet\Http\Controllers\Admin;

use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;
use Illuminate\View\View;
use Webkul\Wallet\Http\Requests\Admin\WalletAdjustmentRequest;
use Webkul\Wallet\Repositories\WalletAccountRepository;
use Webkul\Wallet\Services\WalletService;

class WalletAdjustmentController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct(
        protected WalletAccountRepository $walletAccountRepository,
        protected WalletService $walletService
    ) {}

    /**
     * Display the manual wallet adjustment form.
     *
     * @param  int|string  $id
     * @return View
     */
    public function create($id)
    {
        if (function_exists('bouncer') && ! bouncer()->hasPermission('wallet.accounts.adjust')) {
            abort(403);
        }

        $wallet = $this->walletAccountRepository->with('customer')->findOrFail($id);

        $customer = [
            'name' => $wallet->customer ? ($wallet->customer->first_name.' '.$wallet->customer->last_name) : 'Wallet Customer #'.$wallet->customer_id,
            'email' => $wallet->customer->email ?? '—',
        ];

        $walletData = [
            'id' => $wallet->id,
            'current_balance' => (float) $wallet->available_balance,
        ];

        return view('wallet::admin.accounts.adjust', [
            'customer' => $customer,
            'wallet' => $walletData,
        ]);
    }

    /**
     * Store manual adjustment in wallet ledger via WalletService.
     *
     * @param  int|string  $id
     * @return RedirectResponse
     */
    public function store(WalletAdjustmentRequest $request, $id)
    {
        if (function_exists('bouncer') && ! bouncer()->hasPermission('wallet.accounts.adjust')) {
            abort(403);
        }

        $wallet = $this->walletAccountRepository->findOrFail($id);

        $direction = in_array($request->type, ['increase', 'credit']) ? 'credit' : 'debit';
        $adminUserId = auth()->guard('admin')->id() ?: 1;

        $reason = $request->reason;
        if ($request->filled('reference')) {
            $reason .= ' (Ref: '.$request->reference.')';
        }

        $this->walletService->adjust(
            wallet: $wallet,
            amount: (float) $request->amount,
            direction: $direction,
            reason: $reason,
            adminUserId: $adminUserId
        );

        session()->flash('success', trans('wallet::app.admin.wallet.accounts.adjusted') ?? 'Wallet balance successfully adjusted.');

        return redirect()->route('admin.wallet.accounts.show', $wallet->id);
    }
}
