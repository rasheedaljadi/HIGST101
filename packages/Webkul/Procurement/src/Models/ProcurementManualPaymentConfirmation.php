<?php

namespace Webkul\Procurement\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Webkul\User\Models\AdminProxy;

class ProcurementManualPaymentConfirmation extends Model
{
    public const STATE_PENDING_VERIFICATION = 'pending_verification';

    public const STATE_VERIFIED = 'verified';

    public const STATE_REJECTED = 'rejected';

    protected $table = 'procurement_manual_payment_confirmations';

    protected $fillable = [
        'supplier_purchase_order_id',
        'confirmed_by',
        'confirmed_at',
        'external_reference',
        'declared_total',
        'currency_code',
        'evidence_reference',
        'notes',
        'state',
    ];

    protected $casts = [
        'declared_total' => 'decimal:4',
        'confirmed_at' => 'datetime',
    ];

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(SupplierPurchaseOrderProxy::modelClass(), 'supplier_purchase_order_id');
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(AdminProxy::modelClass(), 'confirmed_by');
    }
}
