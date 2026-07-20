<?php

namespace Webkul\Fulfillment\Services\Domain;

use Webkul\Fulfillment\Models\ProcurementSession;

class SupplierValidationService
{
    public function __construct(protected ProcurementPolicyEngine $policyEngine) {}

    public function validate(ProcurementSession $session): array
    {
        $supplierSnap = $session->supplier_snapshot ?? [];
        $originalCost = (float) ($session->price_snapshot['original_cost'] ?? 0.00);
        $currentCost  = (float) ($session->price_snapshot['current_cost'] ?? 0.00);

        $requestedQty = (int) ($supplierSnap['requested_qty'] ?? 1);
        $availableQty = (int) ($supplierSnap['available_qty'] ?? 1);

        $priceDecision = $this->policyEngine->evaluatePriceChange($originalCost, $currentCost);
        $stockDecision = $this->policyEngine->evaluateStock($requestedQty, $availableQty);

        $ok = ($priceDecision === 'AUTO_ACCEPT') && ($stockDecision === 'APPROVE');
        $status = 'VALIDATED';

        if ($priceDecision === 'REJECT' || $stockDecision === 'CANCEL') {
            $status = 'FAILED';
        } elseif ($priceDecision === 'MANUAL_REVIEW' || $stockDecision === 'MANUAL_REVIEW') {
            $status = 'MANUAL_REVIEW';
        }

        return [
            'ok'             => $ok,
            'status'         => $status,
            'price_decision' => $priceDecision,
            'stock_decision' => $stockDecision,
        ];
    }
}
