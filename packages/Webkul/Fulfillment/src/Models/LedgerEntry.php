<?php

namespace Webkul\Fulfillment\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Webkul\Fulfillment\Contracts\LedgerEntry as LedgerEntryContract;

class LedgerEntry extends Model implements LedgerEntryContract
{
    protected $fillable = [
        'order_id',
        'purchase_order_id',
        'correlation_id',
        'account_code',
        'debit',
        'credit',
        'reference',
    ];

    /**
     * Get the customer order associated with this ledger entry.
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(\Webkul\Sales\Models\OrderProxy::modelClass(), 'order_id');
    }
}
