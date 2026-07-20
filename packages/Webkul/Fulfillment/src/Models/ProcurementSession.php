<?php

namespace Webkul\Fulfillment\Models;

use Illuminate\Database\Eloquent\Model;
use Webkul\Fulfillment\Contracts\ProcurementSession as ProcurementSessionContract;
use Webkul\Fulfillment\Exceptions\InvalidProcurementTransitionException;

class ProcurementSession extends Model implements ProcurementSessionContract
{
    protected $table = 'procurement_sessions';

    protected $fillable = [
        'procurement_aggregate_id',
        'order_allocation_id',
        'provider_account_id',
        'external_payload_archive_id',
        'state',
        'contract_version',
        'policy_version',
        'policy_hash',
        'policy_snapshot',
        'supplier_snapshot',
        'shipping_snapshot',
        'price_snapshot',
        'snapshot_hash',
        'snapshot_finalized_at',
        'metrics',
        'error_message',
        'correlation_id',
        'causation_id',
        'trace_id',
        'span_id',
    ];

    protected $casts = [
        'policy_snapshot'       => 'array',
        'supplier_snapshot'     => 'array',
        'shipping_snapshot'     => 'array',
        'price_snapshot'        => 'array',
        'metrics'               => 'array',
        'snapshot_finalized_at' => 'datetime',
    ];

    public function aggregate()
    {
        return $this->belongsTo(ProcurementAggregateProxy::modelClass(), 'procurement_aggregate_id');
    }

    public function allocation()
    {
        return $this->belongsTo(OrderAllocationProxy::modelClass(), 'order_allocation_id');
    }

    public function providerAccount()
    {
        return $this->belongsTo(ProviderAccountProxy::modelClass(), 'provider_account_id');
    }

    public function payloadArchive()
    {
        return $this->belongsTo(ExternalPayloadArchiveProxy::modelClass(), 'external_payload_archive_id');
    }

    public function transitionTo(string $newState): void
    {
        $allowedTransitions = [
            'CREATED' => [
                'VALIDATING',
                'FAILED',
                'MANUAL_REVIEW'
            ],
            'VALIDATING' => [
                'VALIDATED',
                'READY_TO_SUBMIT',
                'FAILED',
                'MANUAL_REVIEW'
            ],
            'VALIDATED' => [
                'SHIPPING_SELECTED',
                'READY_TO_SUBMIT',
                'FAILED',
                'MANUAL_REVIEW'
            ],
            'READY_TO_SUBMIT' => [
                'SUBMITTING',
                'FAILED',
                'MANUAL_REVIEW'
            ],
            'SUBMITTING' => [
                'SUBMITTED',
                'FAILED',
                'MANUAL_REVIEW',
                'SUBMIT_RETRY'
            ],
            'SUBMIT_RETRY' => [
                'SUBMITTING',
                'FAILED',
                'MANUAL_REVIEW'
            ],
            'SUBMITTED' => [
                'WAITING_PAYMENT',
                'PROCESSING',
                'FAILED',
                'CANCEL_REQUESTED',
                'MANUAL_REVIEW'
            ],
            'WAITING_PAYMENT' => [
                'PAYMENT_CONFIRMED',
                'WAITING_SUPPLIER',
                'PROCESSING',
                'FAILED',
                'CANCEL_REQUESTED',
                'MANUAL_REVIEW'
            ],
            'PAYMENT_CONFIRMED' => [
                'WAITING_SUPPLIER',
                'PROCESSING',
                'FAILED',
                'CANCEL_REQUESTED',
                'MANUAL_REVIEW'
            ],
            'WAITING_SUPPLIER' => [
                'PROCESSING',
                'FAILED',
                'CANCEL_REQUESTED',
                'MANUAL_REVIEW'
            ],
            'PROCESSING' => [
                'SHIPPED',
                'FAILED',
                'CANCEL_REQUESTED',
                'MANUAL_REVIEW'
            ],
            'SHIPPED' => [
                'COMPLETED',
                'FAILED',
                'CANCEL_REQUESTED',
                'MANUAL_REVIEW'
            ],
            'COMPLETED' => [], // Terminal state
            'FAILED' => [
                'CREATED',
                'VALIDATING',
                'READY_TO_SUBMIT',
                'SUBMITTING'
            ],
            'CANCEL_REQUESTED' => [
                'CANCELLED',
                'FAILED',
                'MANUAL_REVIEW'
            ],
            'CANCELLED' => [], // Terminal state
            'MANUAL_REVIEW' => [
                'CREATED',
                'VALIDATING',
                'READY_TO_SUBMIT',
                'SUBMITTING',
                'FAILED',
                'CANCEL_REQUESTED',
                'CANCELLED'
            ],
        ];

        $current = $this->state ?: 'CREATED';

        if ($current === $newState) {
            return;
        }

        $allowed = $allowedTransitions[$current] ?? [];

        if (! in_array($newState, $allowed, true)) {
            throw new InvalidProcurementTransitionException("Invalid procurement transition from [{$current}] to [{$newState}]");
        }

        $this->state = $newState;
        $this->save();
    }

    protected static function boot()
    {
        parent::boot();

        static::updating(function ($model) {
            if ($model->getOriginal('snapshot_finalized_at') !== null) {
                $snapshotFields = [
                    'supplier_snapshot',
                    'shipping_snapshot',
                    'price_snapshot',
                    'snapshot_hash',
                    'snapshot_version',
                ];

                foreach ($snapshotFields as $field) {
                    if ($model->isDirty($field)) {
                        throw new \RuntimeException("Cannot modify finalized snapshots for session ID {$model->id}.");
                    }
                }
            }
        });
    }
}
