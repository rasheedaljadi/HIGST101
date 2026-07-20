<?php

namespace Webkul\Fulfillment\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Webkul\Fulfillment\Contracts\PurchaseOrder as PurchaseOrderContract;

class PurchaseOrder extends Model implements PurchaseOrderContract
{
    public const STATE_PENDING = 'pending';
    public const STATE_SUBMITTING = 'submitting';
    public const STATE_SUBMITTED = 'submitted';
    public const STATE_SHIPPED = 'shipped';
    public const STATE_DELIVERED = 'delivered';
    public const STATE_NEEDS_MANUAL_REVIEW = 'needs_manual_review';
    public const STATE_CANCELED = 'canceled';
    public const STATE_AWAITING_PAYMENT = 'awaiting_payment_to_supplier';

    protected $fillable = [
        'order_id',
        'provider',
        'provider_account_id',
        'supplier_signature',
        'idempotency_key',
        'internal_reference',
        'external_order_id',
        'state',
        'supplier_state_raw',
        'supplier_snapshot',
        'attempts',
        'last_error',
        'tracking_number',
        'tracking_company',
        'supplier_cost',
        'supplier_currency',
        'payload_snapshot',
        'submitted_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'payload_snapshot'  => 'array',
            'supplier_snapshot'  => 'array',
            'submitted_at'      => 'datetime',
        ];
    }

    /**
     * Get the customer order associated with this purchase order.
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(\Webkul\Sales\Models\OrderProxy::modelClass(), 'order_id');
    }

    /**
     * Get the items for this purchase order.
     */
    public function items(): HasMany
    {
        return $this->hasMany(PurchaseOrderItemProxy::modelClass());
    }

    /**
     * Get the fulfillment attempts for this purchase order.
     */
    public function fulfillmentAttempts(): HasMany
    {
        return $this->hasMany(FulfillmentAttemptProxy::modelClass());
    }

    /**
     * Get the provider events for this purchase order.
     */
    public function events(): HasMany
    {
        return $this->hasMany(FulfillmentProviderEventProxy::modelClass());
    }

    /**
     * Get the audit logs for this purchase order.
     */
    public function auditLogs(): HasMany
    {
        return $this->hasMany(FulfillmentAuditLogProxy::modelClass());
    }

    /**
     * Get the approval requests for this purchase order.
     */
    public function approvalRequests(): HasMany
    {
        return $this->hasMany(FulfillmentApprovalRequestProxy::modelClass());
    }

    /**
     * Submit the purchase order.
     */
    public function submit(string $externalOrderId, array $rawResponse): void
    {
        $this->ensureCanTransitionTo(self::STATE_SUBMITTED);
        
        $this->state = self::STATE_SUBMITTED;
        $this->external_order_id = $externalOrderId;
        $this->supplier_snapshot = $rawResponse;
        $this->submitted_at = now();
        $this->save();
    }

    /**
     * Cancel the purchase order.
     */
    public function cancel(string $reason): void
    {
        $this->ensureCanTransitionTo(self::STATE_CANCELED);
        
        $this->state = self::STATE_CANCELED;
        $this->last_error = $reason;
        $this->save();
    }

    /**
     * Mark the PO as awaiting payment to the supplier.
     */
    public function markAwaitingPayment(): void
    {
        $this->ensureCanTransitionTo(self::STATE_AWAITING_PAYMENT);
        
        $this->state = self::STATE_AWAITING_PAYMENT;
        $this->save();
    }

    /**
     * Mark the PO as processing by the supplier.
     */
    public function markSupplierProcessing(): void
    {
        $this->ensureCanTransitionTo('supplier_processing');
        
        $this->state = 'supplier_processing';
        $this->save();
    }

    /**
     * Mark the PO as shipped by the supplier.
     */
    public function markSupplierShipped(string $trackingNumber, string $carrier): void
    {
        $this->ensureCanTransitionTo(self::STATE_SHIPPED);
        
        $this->state = self::STATE_SHIPPED;
        $this->tracking_number = $trackingNumber;
        $this->tracking_company = $carrier;
        $this->save();
    }

    /**
     * Mark the PO as delivered by the supplier.
     */
    public function markSupplierDelivered(): void
    {
        $this->ensureCanTransitionTo(self::STATE_DELIVERED);
        
        $this->state = self::STATE_DELIVERED;
        $this->save();
    }

    /**
     * Flag the PO as needing manual review.
     */
    public function markNeedsReview(string $reason): void
    {
        $this->ensureCanTransitionTo(self::STATE_NEEDS_MANUAL_REVIEW);
        
        $this->state = self::STATE_NEEDS_MANUAL_REVIEW;
        $this->last_error = $reason;
        $this->save();
    }

    /**
     * Ensure the state transition is valid.
     *
     * @throws \DomainException
     */
    protected function ensureCanTransitionTo(string $newState): void
    {
        $allowedTransitions = [
            self::STATE_PENDING => [
                self::STATE_SUBMITTING,
                self::STATE_SUBMITTED,
                self::STATE_NEEDS_MANUAL_REVIEW,
                self::STATE_CANCELED,
            ],
            self::STATE_SUBMITTING => [
                self::STATE_SUBMITTED,
                self::STATE_NEEDS_MANUAL_REVIEW,
                self::STATE_CANCELED,
            ],
            self::STATE_SUBMITTED => [
                self::STATE_AWAITING_PAYMENT,
                'supplier_processing',
                self::STATE_SHIPPED,
                self::STATE_NEEDS_MANUAL_REVIEW,
                self::STATE_CANCELED,
            ],
            self::STATE_AWAITING_PAYMENT => [
                'supplier_processing',
                self::STATE_SHIPPED,
                self::STATE_NEEDS_MANUAL_REVIEW,
                self::STATE_CANCELED,
            ],
            'supplier_processing' => [
                self::STATE_SHIPPED,
                self::STATE_DELIVERED,
                self::STATE_NEEDS_MANUAL_REVIEW,
                self::STATE_CANCELED,
            ],
            self::STATE_SHIPPED => [
                self::STATE_DELIVERED,
                self::STATE_NEEDS_MANUAL_REVIEW,
                self::STATE_CANCELED,
            ],
            self::STATE_DELIVERED           => [], // Terminal
            self::STATE_NEEDS_MANUAL_REVIEW => [
                self::STATE_PENDING,
                self::STATE_SUBMITTED,
                self::STATE_SHIPPED,
                self::STATE_CANCELED,
            ],
            self::STATE_CANCELED            => [], // Terminal
        ];

        $current = $this->state ?: self::STATE_PENDING;

        if ($current === $newState) {
            return;
        }

        $allowed = $allowedTransitions[$current] ?? [];

        if (! in_array($newState, $allowed)) {
            throw new \DomainException("Invalid state transition from [{$current}] to [{$newState}]");
        }
    }
}
