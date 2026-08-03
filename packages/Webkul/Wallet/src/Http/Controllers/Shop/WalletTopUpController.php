<?php

namespace Webkul\Wallet\Http\Controllers\Shop;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;
use Webkul\Payment\Facades\Payment;
use Webkul\Wallet\Models\WalletTopUp;
use Webkul\Wallet\Repositories\WalletAccountRepository;
use Webkul\Wallet\Repositories\WalletTopUpRepository;

class WalletTopUpController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct(
        protected WalletAccountRepository $walletAccountRepository,
        protected WalletTopUpRepository $walletTopUpRepository
    ) {}

    /**
     * Show interactive 3-step top-up form for active payment methods in Arabic.
     *
     * @return View
     */
    public function create()
    {
        $quickAmounts = [20, 50, 100, 200];

        // Fetch active payment methods configured in the system (excluding wallet itself)
        $paymentMethods = collect(Payment::getPaymentMethods())
            ->reject(fn ($method) => $method['method'] === 'wallet')
            ->values()
            ->all();

        return view('wallet::shop.topup.create', compact('quickAmounts', 'paymentMethods'));
    }

    /**
     * Store pending top-up payment request.
     *
     * Note: Funds are NOT added to the wallet here; they are added only when approved by Admin or verified by Gateway Webhook.
     *
     * @return RedirectResponse
     */
    public function store(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:1',
            'method' => 'required|string',
        ]);

        $customerId = auth()->guard('customer')->id();

        $wallet = $this->walletAccountRepository
            ->where('customer_id', $customerId)
            ->firstOrFail();

        $topup = $this->walletTopUpRepository->create([
            'wallet_id' => $wallet->id,
            'amount' => (float) $request->amount,
            'currency_code' => $wallet->currency_code ?? 'USD',
            'payment_method' => $request->method,
            'status' => WalletTopUp::STATUS_PENDING_PAYMENT,
        ]);

        session()->flash('info', 'تم تقديم طلب إيداع الرصيد بنجاح، وهو قيد التحقق أو توجيه الدفع.');

        return redirect()->route('shop.wallet.index');
    }

    /**
     * Initiate top-up alias for backward compatibility.
     *
     * @return RedirectResponse
     */
    public function initiate(Request $request)
    {
        return $this->store($request);
    }

    /**
     * Payment gateway callback.
     */
    public function callback(Request $request)
    {
        return redirect()->route('shop.wallet.index')
            ->with('info', 'عملية الدفع قيد المراجعة والتحقق.');
    }

    /**
     * Customer cancels before payment.
     */
    public function cancel(Request $request)
    {
        return redirect()->route('shop.wallet.index')
            ->with('info', 'تم إلغاء عملية شحن الرصيد.');
    }
}
