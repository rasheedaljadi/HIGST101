<?php

namespace Webkul\Wallet\Http\Controllers\Shop;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Webkul\OfflinePayments\Repositories\OfflinePaymentDestinationRepository;
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

        // Resolve active payment methods from system configuration (excluding wallet itself)
        $configuredMethods = config('payment_methods') ?? [];
        $paymentMethods = [];

        foreach ($configuredMethods as $code => $config) {
            if ($code === 'wallet') {
                continue;
            }

            $isActive = core()->getConfigData("sales.payment_methods.{$code}.active");

            if (is_null($isActive)) {
                $isActive = ! empty($config['active']);
            }

            if ($code === 'offline_payments' || $code === 'moneytransfer') {
                $isActive = true;
            }

            if ($isActive) {
                $title = trans($config['title'] ?? '');
                if (empty($title) || str_starts_with($title, 'offline_payments::')) {
                    $title = 'تحويل مالي (حسابات الدفع اليدوي)';
                }

                $description = trans($config['description'] ?? '');
                if (empty($description) || str_starts_with($description, 'offline_payments::')) {
                    $description = 'التحويل المالي المباشر إلى الحسابات البنكية والإلكترونية المعرفة لدى الإدارة.';
                }

                $paymentMethods[] = [
                    'method' => $code,
                    'method_title' => $title,
                    'description' => $description,
                    'image' => $config['image'] ?? null,
                ];
            }
        }

        $currentCurrency = core()->getCurrentCurrencyCode() ?? core()->getBaseCurrencyCode() ?? 'USD';

        // Fetch active manual/offline payment accounts filtered by current operation currency (e.g. USD)
        $offlineAccounts = collect();
        if (class_exists(OfflinePaymentDestinationRepository::class)) {
            $offlineAccounts = app(OfflinePaymentDestinationRepository::class)
                ->scopeQuery(function ($query) use ($currentCurrency) {
                    return $query->where('is_active', true)
                        ->whereHas('account', function ($q) {
                            $q->where('is_active', true);
                        })
                        ->whereHas('currency', function ($q) use ($currentCurrency) {
                            $q->where('code', $currentCurrency);
                        })
                        ->with(['account', 'currency'])
                        ->orderBy('sort_order', 'asc');
                })
                ->get();

            // Fallback: If no accounts exist matching current currency specifically, fetch all active accounts
            if ($offlineAccounts->isEmpty()) {
                $offlineAccounts = app(OfflinePaymentDestinationRepository::class)
                    ->scopeQuery(function ($query) {
                        return $query->where('is_active', true)
                            ->whereHas('account', function ($q) {
                                $q->where('is_active', true);
                            })
                            ->with(['account', 'currency'])
                            ->orderBy('sort_order', 'asc');
                    })
                    ->get();
            }
        }

        return view('wallet::shop.topup.create', compact('quickAmounts', 'paymentMethods', 'offlineAccounts'));
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
        $isOffline = in_array($request->input('method'), ['moneytransfer', 'offline_payments']);

        $request->validate([
            'amount' => 'required|numeric|min:1',
            'method' => 'required|string',
            'offline_account_id' => $isOffline ? 'required' : 'nullable',
            'receipt' => ($isOffline ? 'required' : 'nullable').'|image|mimes:jpeg,png,jpg,webp|max:10240',
        ], [
            'amount.required' => 'يرجى إدخال مبلغ الإيداع المطلوب.',
            'amount.min' => 'يجب أن يكون مبلغ الإيداع 1 على الأقل.',
            'offline_account_id.required' => 'يرجى اختيار حساب التحويل المراد الدفع إليه.',
            'receipt.required' => 'إرفاق صورة إشعار التحويل المالي الزامي لإتمام العملية.',
            'receipt.image' => 'يجب أن يكون الملف المرفق صورة واضحة.',
            'receipt.mimes' => 'يجب أن تكون صورة الإشعار بتنسيق JPG, PNG, أو WEBP.',
            'receipt.max' => 'حجم صورة الإشعار لا يجب أن يتجاوز 10 ميجابايت.',
        ]);

        $customerId = auth()->guard('customer')->id();

        $wallet = $this->walletAccountRepository
            ->where('customer_id', $customerId)
            ->firstOrFail();

        $meta = [];

        if ($request->offline_account_id && class_exists(OfflinePaymentDestinationRepository::class)) {
            $dest = app(OfflinePaymentDestinationRepository::class)->find($request->offline_account_id);
            if ($dest) {
                $meta['offline_account'] = [
                    'id' => $dest->id,
                    'account_name' => $dest->account->display_name ?? '',
                    'provider_name' => $dest->account->provider_name ?? '',
                    'recipient_name' => $dest->account->recipient_name ?? '',
                    'account_identifier' => $dest->account_identifier ?? '',
                    'swift_code' => $dest->swift_code ?? '',
                    'currency_code' => $dest->currency->code ?? '',
                    'transfer_instructions' => $dest->transfer_instructions ?? '',
                ];
            }
        }

        if ($request->hasFile('receipt')) {
            $receiptPath = $request->file('receipt')->store('wallet/receipts', 'public');
            $meta['receipt_path'] = $receiptPath;
        }

        $topup = $this->walletTopUpRepository->create([
            'wallet_id' => $wallet->id,
            'amount' => (float) $request->amount,
            'currency_code' => $wallet->currency_code ?? 'USD',
            'payment_method' => $request->method,
            'status' => WalletTopUp::STATUS_PENDING_PAYMENT,
            'meta' => $meta,
        ]);

        try {
            DB::table('notifications')->insert([
                'type' => 'wallet_topup',
                'read' => 0,
                'order_id' => null,
                'entity_type' => WalletTopUp::class,
                'entity_id' => $topup->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } catch (\Throwable $e) {
            Log::error('Admin notification creation failed for topup: '.$e->getMessage());
        }

        session()->flash('info', 'تم تقديم طلب إيداع الرصيد وإرفاق إشعار التحويل بنجاح، وهو قيد المراجعة والاعتماد.');

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
