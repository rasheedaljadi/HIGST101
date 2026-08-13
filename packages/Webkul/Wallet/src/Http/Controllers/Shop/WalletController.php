<?php

namespace Webkul\Wallet\Http\Controllers\Shop;

use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;
use Webkul\Wallet\Repositories\WalletAccountRepository;

class WalletController extends Controller
{
    public function __construct(
        protected WalletAccountRepository $walletAccountRepository
    ) {}

    /**
     * My Wallet — main page with balance summary and recent transactions.
     *
     * @return View
     */
    public function index()
    {
        $customer = auth()->guard('customer')->user();

        if ($customer) {
            $currency = core()->getBaseCurrencyCode() ?? 'USD';
            $wallet = $this->walletAccountRepository->getOrCreateForCustomer($customer->id, $currency);

            $balances = [
                'total' => core()->formatBasePrice($wallet->total_balance),
                'cash' => core()->formatBasePrice($wallet->cash_balance),
                'promotional' => core()->formatBasePrice($wallet->promo_balance),
                'available' => core()->formatBasePrice($wallet->available_balance),
                'withdrawable' => core()->formatBasePrice(max(0, (float) $wallet->cash_balance - (float) $wallet->held_balance)),
                'held' => core()->formatBasePrice($wallet->held_balance),
                'raw_cash' => (float) $wallet->cash_balance,
                'raw_promo' => (float) $wallet->promo_balance,
            ];

            $transactions = $wallet->transactions()->latest()->take(10)->get();
            $topups = $wallet->topups()->latest()->take(10)->get();
            $withdrawals = $wallet->withdrawalRequests()->latest()->take(10)->get();

            $pendingTopups = $wallet->topups()->whereIn('status', ['pending', 'pending_payment', 'under_review'])->latest()->get();
            $rejectedTopups = $wallet->topups()->where('status', 'failed')->latest()->take(3)->get();

            $pendingWithdrawals = $wallet->withdrawalRequests()->where('status', 'pending')->latest()->get();
            $rejectedWithdrawals = $wallet->withdrawalRequests()->where('status', 'rejected')->latest()->take(3)->get();
        } else {
            $balances = [
                'total' => core()->formatBasePrice(0),
                'cash' => core()->formatBasePrice(0),
                'promotional' => core()->formatBasePrice(0),
                'available' => core()->formatBasePrice(0),
                'withdrawable' => core()->formatBasePrice(0),
                'held' => core()->formatBasePrice(0),
                'raw_cash' => 0.0,
                'raw_promo' => 0.0,
            ];

            $transactions = collect();
            $topups = collect();
            $withdrawals = collect();
            $pendingTopups = collect();
            $rejectedTopups = collect();
            $pendingWithdrawals = collect();
            $rejectedWithdrawals = collect();
        }

        return view('wallet::shop.index', compact(
            'balances', 'transactions', 'topups', 'withdrawals',
            'pendingTopups', 'rejectedTopups', 'pendingWithdrawals', 'rejectedWithdrawals'
        ));
    }

    /**
     * Full transaction history.
     */
    public function transactions()
    {
        $customerId = auth()->guard('customer')->id();

        $wallet = $this->walletAccountRepository
            ->where('customer_id', $customerId)
            ->firstOrFail();

        $transactions = $wallet->transactions()->paginate(20);

        return view('wallet::shop.wallet.transactions', compact('wallet', 'transactions'));
    }

    /**
     * Customer wallet statement view.
     */
    public function statement(Request $request)
    {
        $customerId = auth()->guard('customer')->id();

        $wallet = $this->walletAccountRepository
            ->where('customer_id', $customerId)
            ->firstOrFail();

        $startDate = $request->input('start_date', now()->startOfMonth()->toDateString());
        $endDate = $request->input('end_date', now()->toDateString());

        $data = $this->calculateStatementData($wallet, $startDate, $endDate);

        return view('wallet::shop.wallet.statement', array_merge(['wallet' => $wallet], $data));
    }

    /**
     * Download customer wallet statement PDF.
     */
    public function downloadStatement(Request $request)
    {
        $customerId = auth()->guard('customer')->id();

        $wallet = $this->walletAccountRepository
            ->where('customer_id', $customerId)
            ->firstOrFail();

        $startDate = $request->input('start_date', now()->startOfMonth()->toDateString());
        $endDate = $request->input('end_date', now()->toDateString());

        $data = $this->calculateStatementData($wallet, $startDate, $endDate);

        $pdf = Pdf::loadView('wallet::shop.wallet.statement-pdf', array_merge(['wallet' => $wallet], $data));

        return $pdf->download("HIGEST_Wallet_Statement_{$startDate}_to_{$endDate}.pdf");
    }

    /**
     * Export customer wallet statement as CSV.
     */
    public function exportCsvStatement(Request $request)
    {
        $customerId = auth()->guard('customer')->id();

        $wallet = $this->walletAccountRepository
            ->where('customer_id', $customerId)
            ->firstOrFail();

        $startDate = $request->input('start_date', now()->startOfMonth()->toDateString());
        $endDate = $request->input('end_date', now()->toDateString());

        $data = $this->calculateStatementData($wallet, $startDate, $endDate);

        $filename = "HIGEST_Wallet_Statement_{$startDate}_to_{$endDate}.csv";

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        $callback = function () use ($data) {
            $file = fopen('php://output', 'w');
            fwrite($file, "\xEF\xBB\xBF");

            fputcsv($file, ['HIGEST WALLET STATEMENT / كشف حساب محفظة هيجست']);
            fputcsv($file, ['Start Date / من تاريخ', $data['startDate']]);
            fputcsv($file, ['End Date / إلى تاريخ', $data['endDate']]);
            fputcsv($file, ['Opening Balance / الرصيد الافتتاحي', number_format($data['openingBalance'], 2)]);
            fputcsv($file, ['Period Credits / إجمالي الإيداعات', number_format($data['periodCredits'], 2)]);
            fputcsv($file, ['Period Debits / إجمالي السحوبات والدفوعات', number_format($data['periodDebits'], 2)]);
            fputcsv($file, ['Closing Balance / الرصيد الختامي', number_format($data['closingBalance'], 2)]);
            fputcsv($file, []);

            fputcsv($file, ['ID / الرقم المرجعي', 'Date / التاريخ', 'Type / النوع', 'Direction / الاتجاه', 'Amount / المبلغ', 'Description / البيان']);

            foreach ($data['transactions'] as $tx) {
                fputcsv($file, [
                    $tx->id,
                    $tx->created_at->format('Y-m-d H:i:s'),
                    $tx->type,
                    strtoupper($tx->direction),
                    number_format((float) $tx->amount, 2),
                    $tx->description,
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Calculate opening balance, closing balance and filter transactions.
     */
    private function calculateStatementData($wallet, string $startDate, string $endDate): array
    {
        $start = Carbon::parse($startDate)->startOfDay();
        $end = Carbon::parse($endDate)->endOfDay();

        $transactions = $wallet->transactions()
            ->whereBetween('created_at', [$start, $end])
            ->get();

        // Calculate credits & debits after start date to find opening balance
        $creditsAfterStart = (float) $wallet->transactions()
            ->where('created_at', '>=', $start)
            ->where('direction', 'credit')
            ->sum('amount');

        $debitsAfterStart = (float) $wallet->transactions()
            ->where('created_at', '>=', $start)
            ->where('direction', 'debit')
            ->sum('amount');

        $openingBalance = (float) $wallet->available_balance - $creditsAfterStart + $debitsAfterStart;

        $periodCredits = (float) $transactions->where('direction', 'credit')->sum('amount');
        $periodDebits = (float) $transactions->where('direction', 'debit')->sum('amount');

        $closingBalance = $openingBalance + $periodCredits - $periodDebits;

        return [
            'startDate' => $startDate,
            'endDate' => $endDate,
            'openingBalance' => $openingBalance,
            'closingBalance' => $closingBalance,
            'periodCredits' => $periodCredits,
            'periodDebits' => $periodDebits,
            'transactions' => $transactions,
        ];
    }
}
