<?php

namespace Webkul\DeliveryManagement\Services;

use Illuminate\Validation\ValidationException;

class PaymentEligibilityChecker
{
    public function __construct(
        protected ShippingMethodAdapter $shippingMethodAdapter,
        protected GovernorateDeliveryValidator $governorateDeliveryValidator
    ) {}

    /**
     * Check if a payment method is eligible given the parameters.
     */
    public function isEligible(
        string $paymentMethod,
        string $stateCode,
        ?string $deliveryType,
        ?int $deliveryPointId = null,
        float $cartAmount = 0.0
    ): bool {
        $paymentMethod = strtolower(trim($paymentMethod));

        // Digital wallet is an internal prepaid balance - always eligible for all delivery types & locations
        if ($paymentMethod === 'wallet') {
            return true;
        }

        // Aliases mapping for offline/bank transfers
        $paymentAliases = match ($paymentMethod) {
            'offline_payments', 'moneytransfer' => ['offline_payments', 'moneytransfer'],
            'cashondelivery', 'cod' => ['cashondelivery', 'cod'],
            default => [$paymentMethod],
        };

        $canonicalType = $this->shippingMethodAdapter->canonicalize($deliveryType) ?? ShippingMethodAdapter::CANONICAL_HOME_DELIVERY;

        // Fetch active governorate rule for state + delivery type
        $rule = $this->governorateDeliveryValidator->getActiveRule($stateCode, $canonicalType);

        if (! $rule || ! $rule->is_enabled) {
            // Electronic/Transfer payments are available by default if no restrictive governorate rule exists
            return $paymentMethod !== 'cashondelivery';
        }

        // Strict COD safety guard: COD is NEVER allowed for delivery points or unconfirmed rules
        if ($paymentMethod === 'cashondelivery') {
            if ($canonicalType !== ShippingMethodAdapter::CANONICAL_HOME_DELIVERY) {
                return false;
            }
        }

        // Check min order amount if configured
        if ($rule->min_order_amount > 0 && $cartAmount < (float) $rule->min_order_amount) {
            return false;
        }

        $allowedMethods = (array) ($rule->allowed_payment_methods ?? []);
        $allowedMethods = array_map(fn ($m) => strtolower(trim($m)), $allowedMethods);

        // Check if any alias is directly in allowed methods
        foreach ($paymentAliases as $alias) {
            if (in_array($alias, $allowedMethods, true)) {
                return true;
            }
        }

        // If rule allows moneytransfer or offline_payments, any bank/offline transfer is eligible
        if (in_array($paymentMethod, ['offline_payments', 'moneytransfer'], true)) {
            if (in_array('moneytransfer', $allowedMethods, true) || in_array('offline_payments', $allowedMethods, true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if a payment method is eligible for a Cart instance.
     */
    public function isCartEligible(string $paymentMethod, mixed $cart): bool
    {
        if (! $cart) {
            return true;
        }

        $shippingAddress = $cart->shipping_address;

        if (! $shippingAddress || empty($shippingAddress->state)) {
            // When address is not yet filled, default to available unless explicitly restricted
            return true;
        }

        $stateCode = (string) $shippingAddress->state;
        $shippingMethod = (string) ($cart->shipping_method ?? $cart->selected_shipping_rate?->method ?? '');

        // Extract delivery point id if stored in address additional
        $additional = is_array($shippingAddress->additional) ? $shippingAddress->additional : json_decode($shippingAddress->additional ?? '[]', true);
        $deliveryPointId = isset($additional['delivery_point_id']) ? (int) $additional['delivery_point_id'] : null;

        $cartAmount = (float) ($cart->grand_total ?? $cart->base_grand_total ?? 0.0);

        return $this->isEligible(
            paymentMethod: $paymentMethod,
            stateCode: $stateCode,
            deliveryType: $shippingMethod,
            deliveryPointId: $deliveryPointId,
            cartAmount: $cartAmount
        );
    }

    /**
     * Validate cart payment eligibility or throw a ValidationException with clear message.
     *
     * @throws ValidationException
     */
    public function validateCartPaymentOrThrow(mixed $cart, string $paymentMethod): void
    {
        if (! $this->isCartEligible($paymentMethod, $cart)) {
            $canonicalType = $this->shippingMethodAdapter->canonicalize($cart?->shipping_method ?? '');

            if (strtolower(trim($paymentMethod)) === 'cashondelivery') {
                if ($canonicalType === ShippingMethodAdapter::CANONICAL_DELIVERY_POINT) {
                    $message = 'الدفع عند الاستلام غير متاح عند الاستلام من نقاط التوصيل. يرجى اختيار وسيلة دفع إلكترونية أو تحويل.';
                } else {
                    $message = 'الدفع عند الاستلام غير متاح في المحافظة أو لطريقة التوصيل المحددة.';
                }
            } else {
                $message = 'طريقة الدفع المحددة غير متاحة للمحافظة أو طريقة التوصيل المختارة.';
            }

            throw ValidationException::withMessages([
                'payment' => [$message],
            ]);
        }
    }
}
