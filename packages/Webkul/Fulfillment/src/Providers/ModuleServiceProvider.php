<?php

namespace Webkul\Fulfillment\Providers;

use Webkul\Core\Providers\CoreModuleServiceProvider;
use Webkul\Fulfillment\Contracts\AllocationLog;
use Webkul\Fulfillment\Contracts\ExternalOrder;
use Webkul\Fulfillment\Contracts\ExternalPayloadArchive;
use Webkul\Fulfillment\Contracts\FinancialTimeline;
use Webkul\Fulfillment\Contracts\FulfillmentApprovalRequest;
use Webkul\Fulfillment\Contracts\FulfillmentAttempt;
use Webkul\Fulfillment\Contracts\FulfillmentAuditLog;
use Webkul\Fulfillment\Contracts\FulfillmentProviderEvent;
use Webkul\Fulfillment\Contracts\LedgerEntry;
use Webkul\Fulfillment\Contracts\OrderAllocation;
use Webkul\Fulfillment\Contracts\OrderProcess;
use Webkul\Fulfillment\Contracts\OutgoingRequest;
use Webkul\Fulfillment\Contracts\ProcessedEvent;
use Webkul\Fulfillment\Contracts\ProcurementAggregate;
use Webkul\Fulfillment\Contracts\ProcurementDashboardProjection;
use Webkul\Fulfillment\Contracts\ProcurementSaga;
use Webkul\Fulfillment\Contracts\ProcurementSession;
use Webkul\Fulfillment\Contracts\ProcurementTimeline;
use Webkul\Fulfillment\Contracts\ProviderAccount;
use Webkul\Fulfillment\Contracts\PurchaseOrder;
use Webkul\Fulfillment\Contracts\PurchaseOrderItem;

class ModuleServiceProvider extends CoreModuleServiceProvider
{
    /**
     * Models.
     *
     * @var array
     */
    protected $models = [
        PurchaseOrder::class => \Webkul\Fulfillment\Models\PurchaseOrder::class,
        PurchaseOrderItem::class => \Webkul\Fulfillment\Models\PurchaseOrderItem::class,
        FulfillmentAttempt::class => \Webkul\Fulfillment\Models\FulfillmentAttempt::class,
        FulfillmentProviderEvent::class => \Webkul\Fulfillment\Models\FulfillmentProviderEvent::class,
        FulfillmentAuditLog::class => \Webkul\Fulfillment\Models\FulfillmentAuditLog::class,
        FulfillmentApprovalRequest::class => \Webkul\Fulfillment\Models\FulfillmentApprovalRequest::class,
        OrderAllocation::class => \Webkul\Fulfillment\Models\OrderAllocation::class,
        AllocationLog::class => \Webkul\Fulfillment\Models\AllocationLog::class,
        ProcessedEvent::class => \Webkul\Fulfillment\Models\ProcessedEvent::class,
        FinancialTimeline::class => \Webkul\Fulfillment\Models\FinancialTimeline::class,
        LedgerEntry::class => \Webkul\Fulfillment\Models\LedgerEntry::class,
        OrderProcess::class => \Webkul\Fulfillment\Models\OrderProcess::class,
        ProviderAccount::class => \Webkul\Fulfillment\Models\ProviderAccount::class,
        ProcurementSaga::class => \Webkul\Fulfillment\Models\ProcurementSaga::class,
        ProcurementAggregate::class => \Webkul\Fulfillment\Models\ProcurementAggregate::class,
        ExternalPayloadArchive::class => \Webkul\Fulfillment\Models\ExternalPayloadArchive::class,
        ProcurementSession::class => \Webkul\Fulfillment\Models\ProcurementSession::class,
        OutgoingRequest::class => \Webkul\Fulfillment\Models\OutgoingRequest::class,
        ExternalOrder::class => \Webkul\Fulfillment\Models\ExternalOrder::class,
        ProcurementDashboardProjection::class => \Webkul\Fulfillment\Models\ProcurementDashboardProjection::class,
        ProcurementTimeline::class => \Webkul\Fulfillment\Models\ProcurementTimeline::class,
    ];
}
