<?php

namespace Webkul\Fulfillment\Providers;

use Webkul\Core\Providers\CoreModuleServiceProvider;

class ModuleServiceProvider extends CoreModuleServiceProvider
{
    /**
     * Models.
     *
     * @var array
     */
    protected $models = [
        \Webkul\Fulfillment\Contracts\PurchaseOrder::class               => \Webkul\Fulfillment\Models\PurchaseOrder::class,
        \Webkul\Fulfillment\Contracts\PurchaseOrderItem::class           => \Webkul\Fulfillment\Models\PurchaseOrderItem::class,
        \Webkul\Fulfillment\Contracts\FulfillmentAttempt::class           => \Webkul\Fulfillment\Models\FulfillmentAttempt::class,
        \Webkul\Fulfillment\Contracts\FulfillmentProviderEvent::class     => \Webkul\Fulfillment\Models\FulfillmentProviderEvent::class,
        \Webkul\Fulfillment\Contracts\FulfillmentAuditLog::class          => \Webkul\Fulfillment\Models\FulfillmentAuditLog::class,
        \Webkul\Fulfillment\Contracts\FulfillmentApprovalRequest::class   => \Webkul\Fulfillment\Models\FulfillmentApprovalRequest::class,
        \Webkul\Fulfillment\Contracts\OrderAllocation::class             => \Webkul\Fulfillment\Models\OrderAllocation::class,
        \Webkul\Fulfillment\Contracts\AllocationLog::class               => \Webkul\Fulfillment\Models\AllocationLog::class,
        \Webkul\Fulfillment\Contracts\ProcessedEvent::class              => \Webkul\Fulfillment\Models\ProcessedEvent::class,
        \Webkul\Fulfillment\Contracts\FinancialTimeline::class           => \Webkul\Fulfillment\Models\FinancialTimeline::class,
        \Webkul\Fulfillment\Contracts\LedgerEntry::class                 => \Webkul\Fulfillment\Models\LedgerEntry::class,
        \Webkul\Fulfillment\Contracts\OrderProcess::class                => \Webkul\Fulfillment\Models\OrderProcess::class,
        \Webkul\Fulfillment\Contracts\ProviderAccount::class             => \Webkul\Fulfillment\Models\ProviderAccount::class,
        \Webkul\Fulfillment\Contracts\ProcurementSaga::class             => \Webkul\Fulfillment\Models\ProcurementSaga::class,
        \Webkul\Fulfillment\Contracts\ProcurementAggregate::class        => \Webkul\Fulfillment\Models\ProcurementAggregate::class,
        \Webkul\Fulfillment\Contracts\ExternalPayloadArchive::class     => \Webkul\Fulfillment\Models\ExternalPayloadArchive::class,
        \Webkul\Fulfillment\Contracts\ProcurementSession::class          => \Webkul\Fulfillment\Models\ProcurementSession::class,
        \Webkul\Fulfillment\Contracts\OutgoingRequest::class             => \Webkul\Fulfillment\Models\OutgoingRequest::class,
        \Webkul\Fulfillment\Contracts\ExternalOrder::class               => \Webkul\Fulfillment\Models\ExternalOrder::class,
        \Webkul\Fulfillment\Contracts\ProcurementDashboardProjection::class => \Webkul\Fulfillment\Models\ProcurementDashboardProjection::class,
        \Webkul\Fulfillment\Contracts\ProcurementTimeline::class            => \Webkul\Fulfillment\Models\ProcurementTimeline::class,
    ];
}
