<?php

namespace Webkul\Fulfillment\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Webkul\Fulfillment\Contracts\FulfillmentAuditLog as FulfillmentAuditLogContract;

class FulfillmentAuditLog extends Model implements FulfillmentAuditLogContract
{
    protected $fillable = [
        'purchase_order_id',
        'user_id',
        'action',
        'reason',
        'ip_address',
        'changes_payload',
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
     * Get the purchase order associated with the audit log.
     */
    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrderProxy::modelClass(), 'purchase_order_id');
    }

    /**
     * Get the administrator user associated with the audit log.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(\Webkul\User\Models\AdminProxy::modelClass(), 'user_id');
    }
}
