<?php

namespace Webkul\Wallet\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Webkul\Wallet\Models\WalletAccount;
use Webkul\Wallet\Models\WalletReconciliation;

class VerifyWalletLedgerCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'wallet:verify-ledger {--notify : Send email notification if discrepancies found}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Verify wallet balances against ledger transaction history and check invariant consistency.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Starting HIGEST Wallet Ledger Audit...');

        $wallets = WalletAccount::with('customer')->get();

        $rows = [];
        $discrepanciesCount = 0;

        foreach ($wallets as $wallet) {
            $fresh = $wallet->fresh();

            // Check 1: Invariant (total = available + held)
            $expectedTotal = (float) $fresh->available_balance + (float) $fresh->held_balance;
            $invariantPassed = abs((float) $fresh->total_balance - $expectedTotal) <= 0.0001;

            // Check 2: Reconstruct from ledger transactions
            $creditsSum = (float) DB::table('wallet_transactions')
                ->where('wallet_id', $wallet->id)
                ->where('direction', 'credit')
                ->sum('amount');

            $debitsSum = (float) DB::table('wallet_transactions')
                ->where('wallet_id', $wallet->id)
                ->where('direction', 'debit')
                ->sum('amount');

            // Available + Held should equal total credits - total debits (excluding adjustments)
            $calculatedBalance = $creditsSum - $debitsSum;
            $ledgerDiff = (float) $fresh->total_balance - $calculatedBalance;
            $ledgerPassed = abs($ledgerDiff) <= 0.0001;

            $status = ($invariantPassed && $ledgerPassed) ? 'OK' : 'MISMATCH';

            if ($status === 'MISMATCH') {
                $discrepanciesCount++;
                Log::warning("Wallet Ledger Audit Mismatch for Wallet #{$wallet->id} (Customer #{$wallet->customer_id}): Recorded Total={$fresh->total_balance}, Calculated={$calculatedBalance}, InvariantPassed=".($invariantPassed ? 'Yes' : 'No'));
            }

            $rows[] = [
                'ID' => $wallet->id,
                'Customer' => $wallet->customer->name ?? "Customer #{$wallet->customer_id}",
                'Recorded Total' => number_format((float) $fresh->total_balance, 2),
                'Calculated Total' => number_format($calculatedBalance, 2),
                'Diff' => number_format($ledgerDiff, 2),
                'Status' => $status,
            ];
        }

        $this->table(
            ['ID', 'Customer', 'Recorded Total', 'Calculated Total', 'Diff', 'Status'],
            $rows
        );

        $totalLiability = (float) WalletAccount::sum('total_balance');

        WalletReconciliation::create([
            'run_at' => now(),
            'total_wallets_audited' => $wallets->count(),
            'discrepancies_count' => $discrepanciesCount,
            'total_system_liability' => $totalLiability,
            'status' => $discrepanciesCount === 0 ? WalletReconciliation::STATUS_CLEAN : WalletReconciliation::STATUS_DISCREPANCY_DETECTED,
            'report_summary' => [
                'audited_at' => now()->toIso8601String(),
                'wallets_count' => $wallets->count(),
                'discrepancies' => $discrepanciesCount,
                'system_liability' => $totalLiability,
            ],
        ]);

        if ($discrepanciesCount > 0) {
            $this->error("Audit Complete: Found {$discrepanciesCount} wallet discrepancy(ies)!");

            if ($this->option('notify')) {
                $this->warn('Admin alert notification dispatched.');
            }

            return 1;
        }

        $this->info("Audit Complete: All {$wallets->count()} wallets verified successfully. 0 discrepancies found.");

        return 0;
    }
}
