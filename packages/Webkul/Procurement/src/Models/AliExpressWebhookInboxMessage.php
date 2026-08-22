<?php

namespace Webkul\Procurement\Models;

use Illuminate\Database\Eloquent\Model;

class AliExpressWebhookInboxMessage extends Model
{
    public const STATUS_RECEIVED = 'received';

    public const STATUS_IGNORED = 'ignored';

    public const STATUS_PROCESSING = 'processing';

    public const STATUS_PROCESSED = 'processed';

    public const STATUS_FAILED = 'failed';

    protected $table = 'aliexpress_webhook_inbox_messages';

    protected $fillable = [
        'provider',
        'event_type',
        'external_event_id',
        'external_order_id',
        'payload_hash',
        'fingerprint',
        'payload',
        'occurred_at',
        'received_at',
        'status',
        'attempts',
        'processed_at',
        'failure_code',
        'failure_message',
    ];

    protected $casts = [
        'payload' => 'array',
        'occurred_at' => 'datetime',
        'received_at' => 'datetime',
        'processed_at' => 'datetime',
        'event_type' => 'integer',
        'attempts' => 'integer',
    ];

    /**
     * Compute a deterministic unique fingerprint for deduplication.
     */
    public static function computeFingerprint(
        string $provider,
        int $eventType,
        ?string $externalEventId,
        ?string $externalOrderId,
        string $payloadHash
    ): string {
        if (! empty($externalEventId)) {
            return hash('sha256', "{$provider}:event_id:{$externalEventId}");
        }

        $orderPart = ! empty($externalOrderId) ? $externalOrderId : 'no_order';

        return hash('sha256', "{$provider}:type:{$eventType}:order:{$orderPart}:hash:{$payloadHash}");
    }
}
