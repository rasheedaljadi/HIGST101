<?php

namespace Webkul\Fulfillment\Services\Domain;

class ProcurementPolicyEngine
{
    public function getVersion(): string
    {
        return 'pricing-policy-v3-shipping-policy-v2';
    }

    public function getHash(): string
    {
        return hash('sha256', $this->getVersion() . '|auto_accept_limit=0.05|review_limit=0.15');
    }

    public function getSnapshot(): array
    {
        return [
            'policy_version'    => $this->getVersion(),
            'auto_accept_limit' => 0.05,
            'review_limit'      => 0.15,
            'partial_allowed'   => true,
        ];
    }

    /**
     * Evaluate price changes.
     * Returns: 'AUTO_ACCEPT', 'MANUAL_REVIEW', 'REJECT'
     */
    public function evaluatePriceChange(float $originalCost, float $currentCost): string
    {
        if ($originalCost <= 0) {
            return 'AUTO_ACCEPT';
        }

        $drift = ($currentCost - $originalCost) / $originalCost;

        if ($drift <= 0.05) {
            return 'AUTO_ACCEPT';
        }

        if ($drift <= 0.15) {
            return 'MANUAL_REVIEW';
        }

        return 'REJECT';
    }

    /**
     * Evaluate stock changes.
     * Returns: 'APPROVE', 'MANUAL_REVIEW', 'CANCEL'
     */
    public function evaluateStock(int $requestedQty, int $availableQty): string
    {
        if ($availableQty >= $requestedQty) {
            return 'APPROVE';
        }

        if ($availableQty > 0) {
            return 'MANUAL_REVIEW';
        }

        return 'CANCEL';
    }
}
