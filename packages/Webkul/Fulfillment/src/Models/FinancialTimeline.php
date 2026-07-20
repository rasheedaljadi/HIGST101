<?php

namespace Webkul\Fulfillment\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;
use Webkul\Fulfillment\Contracts\FinancialTimeline as FinancialTimelineContract;
use Webkul\Fulfillment\Exceptions\ImmutableTimelineException;

class FinancialTimeline extends Model implements FinancialTimelineContract
{
    protected $table = 'financial_timeline';

    protected $fillable = [
        'event_id',
        'order_id',
        'event_type',
        'amount',
        'currency',
        'metadata',
        'recorded_at',
    ];

    /**
     * Bootstrap the model and register validation and immutability hooks.
     */
    public static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            // Enforce required fields
            $required = ['order_id', 'event_type', 'amount', 'currency'];
            foreach ($required as $field) {
                if (is_null($model->{$field}) || $model->{$field} === '') {
                    throw new \InvalidArgumentException("Field {$field} is required for FinancialTimeline.");
                }
            }

            // Generate unique internal UUID if not set
            if (empty($model->event_id)) {
                $model->event_id = (string) Str::uuid();
            }
        });

        static::updating(function ($model) {
            throw new ImmutableTimelineException("FinancialTimeline is immutable. Updates are blocked.");
        });

        static::deleting(function ($model) {
            throw new ImmutableTimelineException("FinancialTimeline is immutable. Deletions are blocked.");
        });
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'metadata' => 'array',
        ];
    }

    /**
     * Domain factory method to build a timeline event.
     *
     * @param  int  $orderId
     * @param  string  $eventType
     * @param  float  $amount
     * @param  string  $currency
     * @param  array  $metadata
     * @return self
     */
    public static function appendEvent(int $orderId, string $eventType, float $amount, string $currency, array $metadata = [], ?string $eventId = null): self
    {
        return new self([
            'event_id'   => $eventId ?? (string) Str::uuid(),
            'order_id'   => $orderId,
            'event_type' => $eventType,
            'amount'     => $amount,
            'currency'   => $currency,
            'metadata'   => $metadata,
        ]);
    }

    /**
     * Get the customer order associated with this timeline event.
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(\Webkul\Sales\Models\OrderProxy::modelClass(), 'order_id');
    }
}
