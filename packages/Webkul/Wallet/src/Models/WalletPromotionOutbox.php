<?php

namespace Webkul\Wallet\Models;

use Illuminate\Database\Eloquent\Model;
use Webkul\Wallet\Contracts\WalletPromotionOutbox as WalletPromotionOutboxContract;

class WalletPromotionOutbox extends Model implements WalletPromotionOutboxContract
{
    public $timestamps = false;

    protected $table = 'wallet_promotion_outbox';

    protected $fillable = [
        'event_type',
        'event_key',
        'payload',
        'status',
        'locked_at',
        'locked_by',
        'lease_expires_at',
        'attempts',
        'last_error',
        'created_at',
        'processed_at',
    ];

    protected $casts = [
        'payload' => 'array',
        'attempts' => 'integer',
        'locked_at' => 'datetime',
        'lease_expires_at' => 'datetime',
        'created_at' => 'datetime',
        'processed_at' => 'datetime',
    ];

    public const STATUS_PENDING = 'pending';

    public const STATUS_PROCESSING = 'processing';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_FAILED = 'failed';

    /**
     * The "booted" method of the model.
     * Enforces strict accounting preservation: physical deletion is strictly forbidden.
     */
    protected static function booted(): void
    {
        static::deleting(function (self $model) {
            throw new \LogicException('Physical deletion of WalletPromotionOutbox records is strictly forbidden to preserve event audit history.');
        });
    }
}
