<?php

namespace Webkul\DeliveryManagement\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Webkul\DeliveryManagement\Contracts\DeliveryAuditLog as DeliveryAuditLogContract;
use Webkul\User\Models\AdminProxy;

class DeliveryAuditLog extends Model implements DeliveryAuditLogContract
{
    protected $table = 'delivery_audit_logs';

    protected $fillable = [
        'delivery_assignment_id',
        'delivery_governorate_rule_id',
        'delivery_point_id',
        'delivery_settlement_id',
        'user_id',
        'user_name',
        'action',
        'entity_type',
        'entity_id',
        'reason',
        'old_values',
        'new_values',
        'ip_address',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
    ];

    public function assignment(): BelongsTo
    {
        return $this->belongsTo(DeliveryAssignmentProxy::modelClass(), 'delivery_assignment_id');
    }

    public function governorateRule(): BelongsTo
    {
        return $this->belongsTo(DeliveryGovernorateRuleProxy::modelClass(), 'delivery_governorate_rule_id');
    }

    public function deliveryPoint(): BelongsTo
    {
        return $this->belongsTo(DeliveryPointProxy::modelClass(), 'delivery_point_id');
    }

    public function settlement(): BelongsTo
    {
        return $this->belongsTo(DeliverySettlementProxy::modelClass(), 'delivery_settlement_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(AdminProxy::modelClass(), 'user_id');
    }

    /**
     * Helper to log an event statically.
     */
    public static function log(
        string $action,
        ?string $entityType = null,
        ?int $entityId = null,
        ?string $reason = null,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?int $userId = null,
        ?string $userName = null,
        ?int $assignmentId = null,
        ?int $ruleId = null,
        ?int $pointId = null,
        ?int $settlementId = null
    ): self {
        return self::create([
            'action' => $action,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'reason' => $reason,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'user_id' => $userId ?? auth()->guard('admin')->id(),
            'user_name' => $userName ?? (auth()->guard('admin')->user()?->name ?? 'System'),
            'delivery_assignment_id' => $assignmentId,
            'delivery_governorate_rule_id' => $ruleId,
            'delivery_point_id' => $pointId,
            'delivery_settlement_id' => $settlementId,
            'ip_address' => request()->ip(),
        ]);
    }
}
