<?php

namespace Webkul\Fulfillment\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Webkul\Fulfillment\Contracts\FulfillmentApprovalRequest as FulfillmentApprovalRequestContract;

class FulfillmentApprovalRequest extends Model implements FulfillmentApprovalRequestContract
{
    protected $fillable = [
        'purchase_order_id',
        'requested_by',
        'action',
        'reason',
        'changes_payload',
        'status',
        'approved_by',
        'decision_reason',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'changes_payload' => 'array',
        ];
    }

    /**
     * Get the purchase order associated with the approval request.
     */
    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrderProxy::modelClass(), 'purchase_order_id');
    }

    /**
     * Get the administrator user who requested the approval.
     */
    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(\Webkul\User\Models\AdminProxy::modelClass(), 'requested_by');
    }

    /**
     * Get the administrator user who approved/rejected the request.
     */
    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(\Webkul\User\Models\AdminProxy::modelClass(), 'approved_by');
    }
}
