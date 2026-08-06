<?php

namespace Webkul\Wallet\Http\Controllers\Shop;

use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Webkul\Wallet\Http\Requests\Shop\StoreWithdrawalRequest;
use Webkul\Wallet\Models\WalletTransaction;
use Webkul\Wallet\Models\WalletWithdrawalRequest;
use Webkul\Wallet\Repositories\WalletAccountRepository;
use Webkul\Wallet\Repositories\WalletWithdrawalMethodRepository;
use Webkul\Wallet\Repositories\WalletWithdrawalRequestRepository;
use Webkul\Wallet\Services\WalletService;

class WalletCustomerWithdrawalController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct(
        protected WalletAccountRepository $walletAccountRepository,
        protected WalletWithdrawalRequestRepository $withdrawalRepository,
        protected WalletWithdrawalMethodRepository $withdrawalMethodRepository,
        protected WalletService $walletService
    ) {}

    /**
     * Display customer withdrawal request screen with live balance and recent requests.
     *
     * @return View
     */
    public function create()
    {
        $customerId = auth()->guard('customer')->id();

        $wallet = $this->walletAccountRepository
            ->where('customer_id', $customerId)
            ->first();

        $availableBalance = $wallet ? (float) $wallet->available_balance : 0.00;

        $activeMethods = $this->withdrawalMethodRepository
            ->where('status', 1)
            ->orderBy('sort_order', 'asc')
            ->orderBy('id', 'asc')
            ->get();

        $methods = [];
        if ($activeMethods->isNotEmpty()) {
            foreach ($activeMethods as $method) {
                $methods[$method->name] = $method->name;
            }
        } else {
            $methods = [
                'مصرف الكريمي (حاسب)' => 'مصرف الكريمي (حاسب)',
                'محفظة فلوسك الإلكترونية' => 'محفظة فلوسك الإلكترونية',
                'تحويل بنكي (IBAN / حساب)' => 'تحويل بنكي (IBAN / حساب)',
                'محفظة جوال إلكترونية' => 'محفظة جوال إلكترونية',
            ];
        }

        $recentWithdrawalsQuery = $wallet
            ? $wallet->withdrawalRequests()->latest('id')->take(5)->get()
            : collect([]);

        $recentWithdrawals = $recentWithdrawalsQuery->map(function ($item) {
            $statusColor = match ($item->status) {
                'completed' => 'text-emerald-700 bg-emerald-100 dark:bg-emerald-950/60 dark:text-emerald-300',
                'rejected' => 'text-rose-700 bg-rose-100 dark:bg-rose-950/60 dark:text-rose-300',
                default => 'text-amber-700 bg-amber-100 dark:bg-amber-950/60 dark:text-amber-300',
            };

            $statusText = match ($item->status) {
                'completed' => 'مكتمل',
                'rejected' => 'مرفوض',
                default => 'قيد الانتظار',
            };

            return [
                'id' => '#WD-'.$item->id,
                'date' => $item->created_at ? $item->created_at->format('Y-m-d H:i') : '—',
                'amount' => core()->formatBasePrice((float) $item->amount),
                'status' => $statusText,
                'color' => $statusColor,
            ];
        })->toArray();

        return view('wallet::shop.withdraw.create', compact('availableBalance', 'methods', 'recentWithdrawals'));
    }

    /**
     * Store new customer withdrawal request and hold funds atomically.
     *
     * @return RedirectResponse
     */
    public function store(StoreWithdrawalRequest $request)
    {
        $customerId = auth()->guard('customer')->id();

        $wallet = $this->walletAccountRepository
            ->where('customer_id', $customerId)
            ->firstOrFail();

        $amount = (float) $request->amount;

        DB::transaction(function () use ($request, $wallet, $amount) {
            $withdrawalRequest = $this->withdrawalRepository->create([
                'wallet_id' => $wallet->id,
                'amount' => $amount,
                'currency_code' => $wallet->currency_code ?? 'USD',
                'status' => WalletWithdrawalRequest::STATUS_PENDING,
                'bank_details' => [
                    'bank_name' => $request->method,
                    'account_name' => $request->account_name,
                    'iban' => $request->account_number,
                ],
            ]);

            $this->walletService->hold(
                wallet: $wallet,
                amount: $amount,
                type: WalletTransaction::TYPE_HOLD_WITHDRAWAL,
                description: 'طلب سحب عبر '.$request->method.' (#WD-'.$withdrawalRequest->id.')',
                referenceType: WalletWithdrawalRequest::class,
                referenceId: $withdrawalRequest->id
            );

            try {
                DB::table('notifications')->insert([
                    'type' => 'wallet_withdrawal',
                    'read' => 0,
                    'order_id' => null,
                    'entity_type' => WalletWithdrawalRequest::class,
                    'entity_id' => $withdrawalRequest->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            } catch (\Throwable $e) {
                Log::error('Admin notification creation failed for withdrawal: '.$e->getMessage());
            }
        });

        session()->flash('success', trans('wallet::app.shop.withdraw.submitted') ?? 'تم تقديم طلب السحب بنجاح.');

        return redirect()->route('shop.wallet.index');
    }
}
