<?php

namespace Webkul\Procurement\Models;

use DomainException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ProcurementAuditLog extends Model
{
    public $timestamps = false;

    protected $table = 'procurement_audit_logs';

    protected $fillable = [
        'auditable_type',
        'auditable_id',
        'action',
        'actor_id',
        'actor_type',
        'old_state',
        'new_state',
        'details',
        'correlation_id',
        'created_at',
    ];

    protected $casts = [
        'details' => 'array',
        'created_at' => 'datetime',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::updating(function () {
            throw new DomainException('Procurement audit logs are append-only and cannot be updated.');
        });

        static::deleting(function () {
            throw new DomainException('Procurement audit logs are append-only and cannot be deleted.');
        });
    }

    public function auditable(): MorphTo
    {
        return $this->morphTo();
    }
}
