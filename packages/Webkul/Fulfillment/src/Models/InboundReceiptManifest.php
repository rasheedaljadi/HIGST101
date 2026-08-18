<?php

namespace Webkul\Fulfillment\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Webkul\Inventory\Models\InventorySource;
use Webkul\User\Models\Admin;

class InboundReceiptManifest extends Model
{
    use HasFactory;

    protected $table = 'inbound_receipt_manifests';

    protected $guarded = ['id'];

    protected $casts = [
        'total_received_good' => 'integer',
        'total_received_damaged' => 'integer',
        'total_received_missing' => 'integer',
    ];

    public function transferManifest(): BelongsTo
    {
        return $this->belongsTo(InventoryTransferManifest::class, 'inventory_transfer_manifest_id');
    }

    public function destination(): BelongsTo
    {
        return $this->belongsTo(InventorySource::class, 'destination_inventory_source_id');
    }

    public function quarantine(): BelongsTo
    {
        return $this->belongsTo(InventorySource::class, 'quarantine_inventory_source_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(InboundReceiptManifestItem::class, 'inbound_receipt_manifest_id');
    }

    public function receivedBy(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'received_by_admin_id');
    }
}
