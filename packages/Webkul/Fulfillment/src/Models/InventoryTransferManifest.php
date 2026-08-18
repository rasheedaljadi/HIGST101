<?php

namespace Webkul\Fulfillment\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Webkul\Fulfillment\Enums\TransferStatus;
use Webkul\Inventory\Models\InventorySource;
use Webkul\User\Models\Admin;

class InventoryTransferManifest extends Model
{
    use HasFactory;

    protected $table = 'inventory_transfer_manifests';

    protected $guarded = ['id'];

    protected $casts = [
        'status' => TransferStatus::class,
        'dispatched_at' => 'datetime',
        'estimated_arrival_at' => 'datetime',
        'received_at' => 'datetime',
        'total_packages' => 'integer',
        'total_items_count' => 'integer',
    ];

    public function source(): BelongsTo
    {
        return $this->belongsTo(InventorySource::class, 'source_inventory_source_id');
    }

    public function destination(): BelongsTo
    {
        return $this->belongsTo(InventorySource::class, 'destination_inventory_source_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(InventoryTransferManifestItem::class, 'inventory_transfer_manifest_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'created_by_admin_id');
    }

    public function receivedBy(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'received_by_admin_id');
    }
}
