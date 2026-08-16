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
        $stateCode = strtoupper(trim($stateCode));
        $canonicalType = $this->shippingMethodAdapter->canonicalize($deliveryType);

        // If delivery type cannot be canonicalized to home_delivery or delivery_point, it is not eligible
        if (empty($canonicalType)) {
            return false;
        }

        // Fetch active governorate rule for state + delivery type
        $rule = $this->governorateDeliveryValidator->getActiveRule($stateCode, $canonicalType);

        if (! $rule || ! $rule->is_enabled) {
            return false;
        }

        // Strict COD safety guard: COD is NEVER allowed for delivery points or unknown/unconfirmed methods
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

        return in_array($paymentMethod, $allowedMethods, true);
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
