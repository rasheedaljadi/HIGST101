<?php

namespace Webkul\Wallet\Http\Controllers\Shop;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;
use Webkul\Wallet\Models\WalletTopUp;
use Webkul\Wallet\Models\WalletTransaction;
use Webkul\Wallet\Notifications\WalletTopUpApprovedNotification;
use Webkul\Wallet\Repositories\WalletAccountRepository;
use Webkul\Wallet\Repositories\WalletTopUpRepository;
use Webkul\Wallet\Services\WalletService;

class WalletTopUpWebhookController extends Controller
{
    public function __construct(
        protected WalletTopUpRepository $walletTopUpRepository,
        protected WalletAccountRepository $walletAccountRepository,
        protected WalletService $walletService
    ) {}

    /**
     * Handle incoming gateway webhook for wallet top-up verification.
     */
    public function handleWebhook(Request $request, string $gateway)
    {
        Log::info("HIGEST Wallet: Webhook received for gateway [{$gateway}]", $request->all());

        $secret = config("payment_methods.{$gateway}.webhook_secret") ?? env(strtoupper($gateway).'_WEBHOOK_SECRET');
        $signature = $request->header('X-Signature') ?? $request->header('Stripe-Signature') ?? $request->header('X-Webhook-Signature') ?? $request->input('signature');

        if ($secret) {
            if (! $signature || ! $this->validateSignature($request->getContent(), $signature, $secret)) {
                Log::warning("HIGEST Wallet: Unauthorized webhook signature for gateway [{$gateway}]");

                return response()->json(['error' => 'Invalid or missing webhook signature.'], 401);
            }
        }

        $topupId = $request->input('topup_id') ?? $request->input('reference_id');
        $paymentTransactionId = $request->input('transaction_id') ?? $request->input('payment_id');

        if (! $topupId) {
            return response()->json(['error' => 'Missing topup_id parameter.'], 400);
        }

        $topup = $this->walletTopUpRepository->find($topupId);

        if (! $topup) {
            return response()->json(['error' => "WalletTopUp #{$topupId} not found."], 404);
        }

        // Prevent duplicate processing
        if ($topup->isCompleted()) {
            return response()->json(['message' => 'Top-up already completed.'], 200);
        }

        // Process completion
        try {
            $wallet = $this->walletAccountRepository->find($topup->wallet_id);

            $this->walletService->credit(
                wallet: $wallet,
                amount: $topup->amount,
                type: WalletTransaction::TYPE_CREDIT_TOPUP,
                description: "Automated Gateway Top-Up ({$gateway}) #{$topup->id}",
                referenceType: WalletTopUp::class,
                referenceId: $topup->id,
                createdByType: 'webhook',
                createdById: null
            );

            $topup->update([
                'status' => WalletTopUp::STATUS_COMPLETED,
                'payment_transaction_id' => $paymentTransactionId,
                'approved_at' => now(),
                'admin_notes' => "Automatically approved via {$gateway} webhook.",
            ]);

            if ($wallet->customer) {
                $wallet->customer->notify(new WalletTopUpApprovedNotification($topup));
            }

            return response()->json([
                'success' => true,
                'topup_id' => $topup->id,
                'status' => 'completed',
            ], 200);

        } catch (\Throwable $e) {
            Log::error("HIGEST Wallet: Webhook processing failed for TopUp #{$topupId}: ".$e->getMessage());

            return response()->json(['error' => 'Webhook processing failed.'], 500);
        }
    }

    /**
     * Validate HMAC webhook signature.
     */
    private function validateSignature(string $payload, string $signature, string $secret): bool
    {
        $computed = hash_hmac('sha256', $payload, $secret);

        return hash_equals($computed, $signature);
    }
}
