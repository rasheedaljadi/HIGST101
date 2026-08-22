<?php

namespace Webkul\Procurement\Services;

use DomainException;
use Illuminate\Support\Facades\DB;
use Webkul\Procurement\Models\ExternalPlatformOrder;
use Webkul\Procurement\Models\ProcurementAuditLog;
use Webkul\Procurement\Models\SupplierPurchaseOrder;
use Webkul\Procurement\Security\ProcurementAcl;

class ProcurementExternalRemediationService
{
    /**
     * Mark a failed external order submission, cleanse synthetic fallback identifiers,
     * and transition the records to supplier_exception / submission_failed safely.
     *
     * @throws DomainException
     */
    public function markFailedExternalSubmission(
        int $supplierPurchaseOrderId,
        int $actorId,
        string $errorCode = 'IllegalAccessToken',
        string $errorMessage = 'The specified API Path or access token is invalid or ungranted on AliExpress IOP gateway',
        ?string $providerRequestId = null,
        ?string $rejectedSyntheticId = null
    ): SupplierPurchaseOrder {
        ProcurementAcl::authorizeActor($actorId, ProcurementAcl::PERMISSION_SUBMIT, allowSystem: true);

        return DB::transaction(function () use (
            $supplierPurchaseOrderId,
            $actorId,
            $errorCode,
            $errorMessage,
            $providerRequestId,
            $rejectedSyntheticId
        ) {
            /** @var SupplierPurchaseOrder $spo */
            $spo = SupplierPurchaseOrder::where('id', $supplierPurchaseOrderId)->lockForUpdate()->firstOrFail();

            /** @var ExternalPlatformOrder|null $platformOrder */
            $platformOrder = ExternalPlatformOrder::where('supplier_purchase_order_id', $spo->id)->lockForUpdate()->first();

            $priorExternalId = $platformOrder?->external_order_id;
            $syntheticIdToReject = $rejectedSyntheticId ?: $priorExternalId;

            // Idempotency check: if already remediated and external_order_id is null, do not duplicate
            if ($platformOrder && is_null($platformOrder->external_order_id) && $platformOrder->normalized_status === ExternalPlatformOrder::STATUS_SUBMISSION_FAILED) {
                return $spo->fresh(['platformOrders']);
            }

            if ($platformOrder) {
                $snapshots = $platformOrder->snapshots ?? [];
                $snapshots['synthetic_fallback_rejected'] = $syntheticIdToReject;
                $snapshots['remediated_at'] = now()->toIso8601String();
                $snapshots['remediation_actor_id'] = $actorId;

                $platformOrder->update([
                    'external_order_id' => null,
                    'correlation_key' => $spo->purchase_order_number,
                    'provider_request_id' => $providerRequestId ?: ($snapshots['api_request_id'] ?? null),
                    'failure_code' => $errorCode,
                    'failure_message' => $errorMessage,
                    'raw_status' => 'SUBMISSION_FAILED',
                    'normalized_status' => ExternalPlatformOrder::STATUS_SUBMISSION_FAILED,
                    'snapshots' => $snapshots,
                ]);
            }

            $oldState = $spo->state;

            $spo->update([
                'state' => SupplierPurchaseOrder::STATE_SUPPLIER_EXCEPTION,
                'payment_state' => 'submission_failed',
                'external_sync_state' => 'submission_failed',
            ]);

            ProcurementAuditLog::create([
                'auditable_type' => SupplierPurchaseOrder::class,
                'auditable_id' => $spo->id,
                'action' => 'synthetic_external_order_remediated',
                'actor_id' => $actorId,
                'actor_type' => 'admin',
                'old_state' => $oldState,
                'new_state' => SupplierPurchaseOrder::STATE_SUPPLIER_EXCEPTION,
                'details' => [
                    'synthetic_reference_rejected' => $syntheticIdToReject,
                    'failure_code' => $errorCode,
                    'failure_message' => $errorMessage,
                    'provider_request_id' => $providerRequestId,
                    'external_order_created' => false,
                    'remediation_notes' => 'Cleansed synthetic fallback external ID; order not created on AliExpress Open Platform.',
                ],
                'correlation_id' => "spo-{$spo->id}-remediation",
            ]);

            return $spo->fresh(['platformOrders']);
        });
    }
}
