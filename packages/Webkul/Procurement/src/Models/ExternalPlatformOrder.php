<?php

namespace Webkul\Procurement\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ExternalPlatformOrder extends Model
{
    public const STATUS_DRAFT = 'draft';

    public const STATUS_WAIT_BUYER_PAY = 'wait_buyer_pay';

    public const STATUS_PAYMENT_CONFIRMED = 'payment_confirmed';

    public const STATUS_PROCESSING = 'processing';

    public const STATUS_SHIPPED = 'shipped';

    public const STATUS_DELIVERED = 'delivered';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_CANCELLED = 'cancelled';

    public const STATUS_SUBMISSION_FAILED = 'submission_failed';

    public const STATUS_SUBMISSION_EXCEPTION = 'submission_exception';

    protected $table = 'external_platform_orders';

    protected $fillable = [
        'supplier_purchase_order_id',
        'provider',
        'provider_account_id',
        'supplier_store_id',
        'external_order_id',
        'correlation_key',
        'provider_request_id',
        'failure_code',
        'failure_message',
        'raw_status',
        'normalized_status',
        'currency_code',
        'tracking_number',
        'carrier_name',
        'last_synced_at',
        'payment_deadline_at',
        'payload_archive_id',
        'snapshots',
    ];

    protected $casts = [
        'snapshots' => 'array',
        'last_synced_at' => 'datetime',
        'payment_deadline_at' => 'datetime',
    ];

    public function supplierPurchaseOrder(): BelongsTo
    {
        return $this->belongsTo(SupplierPurchaseOrderProxy::modelClass(), 'supplier_purchase_order_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(ExternalPlatformOrderItem::class, 'external_platform_order_id');
    }
}
