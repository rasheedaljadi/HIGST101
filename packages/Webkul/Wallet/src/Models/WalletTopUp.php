<?php

namespace Webkul\Wallet\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Webkul\Wallet\Contracts\WalletTopUp as WalletTopUpContract;

class WalletTopUp extends Model implements WalletTopUpContract
{
    protected $table = 'wallet_topups';

    protected $fillable = [
        'wallet_id',
        'amount',
        'currency_code',
        'payment_method',
        'payment_transaction_id',
        'status',
        'admin_user_id',
        'admin_notes',
        'approved_at',
        'meta',
    ];

    protected $casts = [
        'amount' => 'float',
        'meta' => 'array',
        'approved_at' => 'datetime',
    ];

    // Status Constants
    const STATUS_PENDING = 'pending';

    const STATUS_PENDING_PAYMENT = 'pending_payment';

    const STATUS_PAYMENT_RECEIVED = 'payment_received';

    const STATUS_UNDER_REVIEW = 'under_review';

    const STATUS_COMPLETED = 'completed';

    const STATUS_FAILED = 'failed';

    const STATUS_CANCELLED = 'cancelled';

    const STATUS_EXPIRED = 'expired';

    /**
     * Valid status transitions.
     *
     * Allow admin approval directly from pending, pending_payment, payment_received, or under_review.
     *
     * @var array<string, array<string>>
     */
    public static array $transitions = [
        'pending' => ['payment_received', 'under_review', 'completed', 'failed', 'cancelled', 'expired'],
        'pending_payment' => ['payment_received', 'under_review', 'completed', 'failed', 'cancelled', 'expired'],
        'payment_received' => ['under_review', 'completed', 'failed', 'cancelled'],
        'under_review' => ['completed', 'failed', 'cancelled'],
        'completed' => [],
        'failed' => [],
        'cancelled' => [],
        'expired' => [],
    ];

    public function wallet(): BelongsTo
    {
        return $this->belongsTo(WalletAccountProxy::modelClass(), 'wallet_id');
    }

    public function getPaymentMethodTitleAttribute(): string
    {
        $methods = [
            'cashondelivery' => 'الدفع عند الاستلام',
            'moneytransfer' => 'تحويل بنكي / إيداع مباشر',
            'paypal' => 'بايبال (PayPal)',
            'stripe' => 'بطاقة ائتمانية (Stripe)',
            'razorpay' => 'Razorpay',
            'payu' => 'PayU',
        ];

        return $methods[$this->payment_method] ?? core()->getConfigData("sales.payment_methods.{$this->payment_method}.title") ?? $this->payment_method ?? 'غير محدد';
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'pending', 'pending_payment', 'under_review' => 'قيد المراجعة والانتظار',
            'completed', 'payment_received' => 'مكتمل ومضاف للمحفظة',
            'failed' => 'مرفوض',
            'cancelled' => 'ملغي',
            'expired' => 'منتهي الصلاحية',
            default => $this->status,
        };
    }

    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    public function canTransitionTo(string $newStatus): bool
    {
        return in_array($newStatus, self::$transitions[$this->status] ?? []);
    }
}
