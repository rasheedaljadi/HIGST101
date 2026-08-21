<?php

namespace Webkul\Procurement\Services;

use DomainException;
use Illuminate\Support\Facades\DB;
use Webkul\Procurement\Models\ProcurementAuditLog;
use Webkul\Procurement\Models\ProcurementBatch;
use Webkul\Procurement\Models\ProcurementManualPaymentConfirmation;
use Webkul\Procurement\Models\SupplierPurchaseOrder;
use Webkul\Procurement\Security\ProcurementAcl;

class ProcurementManualPaymentService
{
    /**
     * Record an administrative declaration of manual payment executed on AliExpress.
     *
     * @throws DomainException
     */
    public function declarePayment(
        int $spoId,
        int $actorId,
        string $externalReference,
        float $declaredTotal,
        string $currency = 'USD',
        ?string $evidenceReference = null,
        ?string $notes = null
    ): ProcurementManualPaymentConfirmation {
        ProcurementAcl::authorizeActor($actorId, ProcurementAcl::PERMISSION_PAYMENT_CONFIRM);

        if ($declaredTotal <= 0) {
            throw new DomainException('Declared payment total must be greater than zero.');
        }

        if (strtoupper($currency) !== 'USD') {
            throw new DomainException("Declared payment currency ({$currency}) must be USD.");
        }

        return DB::transaction(function () use ($spoId, $actorId, $externalReference, $declaredTotal, $evidenceReference, $notes) {
            /** @var SupplierPurchaseOrder $spo */
            $spo = SupplierPurchaseOrder::where('id', $spoId)->lockForUpdate()->firstOrFail();

            if (! in_array($spo->state, [
                SupplierPurchaseOrder::STATE_AWAITING_MANUAL_PAYMENT,
                SupplierPurchaseOrder::STATE_SUBMITTED,
                SupplierPurchaseOrder::STATE_PAYMENT_DECLARED,
            ], true)) {
                throw new DomainException("Cannot declare manual payment for order in '{$spo->state}' state.");
            }

            $confirmation = ProcurementManualPaymentConfirmation::create([
                'supplier_purchase_order_id' => $spo->id,
                'confirmed_by' => $actorId,
                'confirmed_at' => now(),
                'external_reference' => $externalReference,
                'declared_total' => $declaredTotal,
                'currency_code' => 'USD',
                'evidence_reference' => $evidenceReference,
                'notes' => $notes,
                'state' => ProcurementManualPaymentConfirmation::STATE_PENDING_VERIFICATION,
            ]);

            $spo->update([
                'state' => SupplierPurchaseOrder::STATE_PAYMENT_DECLARED,
                'payment_state' => 'declared',
            ]);

            if ($spo->batch) {
                $spo->batch->update([
                    'state' => ProcurementBatch::STATE_PAYMENT_DECLARED,
                ]);
            }

            ProcurementAuditLog::create([
                'auditable_type' => SupplierPurchaseOrder::class,
                'auditable_id' => $spo->id,
                'action' => 'manual_payment_declared',
                'actor_id' => $actorId,
                'actor_type' => 'admin',
                'old_state' => SupplierPurchaseOrder::STATE_AWAITING_MANUAL_PAYMENT,
                'new_state' => SupplierPurchaseOrder::STATE_PAYMENT_DECLARED,
                'details' => [
                    'declared_total' => $declaredTotal,
                    'external_reference' => $externalReference,
                    'evidence_reference' => $evidenceReference,
                ],
                'correlation_id' => "spo-{$spo->id}-pay-decl",
            ]);

            return $confirmation;
        });
    }
}
