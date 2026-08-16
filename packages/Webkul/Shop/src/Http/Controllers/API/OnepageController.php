<?php

namespace Webkul\Shop\Http\Controllers\API;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;
use Webkul\CartRule\Exceptions\CouponUsageLimitExceededException;
use Webkul\Checkout\Facades\Cart;
use Webkul\Customer\Repositories\CustomerRepository;
use Webkul\DeliveryManagement\Services\GovernorateDeliveryValidator;
use Webkul\DeliveryManagement\Services\PaymentEligibilityChecker;
use Webkul\DeliveryManagement\Services\ShippingMethodAdapter;
use Webkul\Payment\Facades\Payment;
use Webkul\Sales\Repositories\OrderRepository;
use Webkul\Sales\Transformers\OrderResource;
use Webkul\Shipping\Facades\Shipping;
use Webkul\Shop\Http\Requests\CartAddressRequest;
use Webkul\Shop\Http\Resources\CartResource;

class OnepageController extends APIController
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(
        protected OrderRepository $orderRepository,
        protected CustomerRepository $customerRepository,
        protected ShippingMethodAdapter $shippingMethodAdapter,
        protected GovernorateDeliveryValidator $governorateDeliveryValidator,
        protected PaymentEligibilityChecker $paymentEligibilityChecker
    ) {}

    /**
     * Return cart summary.
     */
    public function summary(): JsonResource
    {
        $cart = Cart::getCart();

        return new CartResource($cart);
    }

    /**
     * Store address.
     */
    public function storeAddress(CartAddressRequest $cartAddressRequest): JsonResource|Response
    {
        $params = $cartAddressRequest->all();

        if (
            ! auth()->guard('customer')->check()
            && ! Cart::getCart()->hasGuestCheckoutItems()
        ) {
            return new JsonResource([
                'redirect' => true,
                'data' => route('shop.customer.session.index'),
            ]);
        }

        if (Cart::hasError()) {
            return new JsonResource([
                'redirect' => true,
                'redirect_url' => route('shop.checkout.cart.index'),
            ]);
        }

        // Validate governorate state code for Yemen
        $shippingState = $params['shipping']['state'] ?? $params['billing']['state'] ?? null;
        if ($shippingState && ! $this->governorateDeliveryValidator->isValidStateCode($shippingState)) {
            return response()->json([
                'message' => 'المحافظة المحددة غير صالحة.',
                'errors' => [
                    'shipping.state' => ['المحافظة المحددة غير صالحة.'],
                ],
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        // Handle delivery point snapshot if delivery_point_id is present
        $deliveryPointId = $params['shipping']['delivery_point_id'] ?? $params['billing']['delivery_point_id'] ?? null;
        if ($deliveryPointId && $shippingState) {
            try {
                $pointSnapshot = $this->governorateDeliveryValidator->validateDeliveryPoint($shippingState, (int) $deliveryPointId);
                $params['shipping']['additional']['delivery_point_id'] = (int) $deliveryPointId;
                $params['shipping']['additional']['delivery_point_snapshot'] = $pointSnapshot;
            } catch (ValidationException $e) {
                return response()->json([
                    'message' => $e->getMessage(),
                    'errors' => $e->errors(),
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }
        }

        Cart::saveAddresses($params);

        $cart = Cart::getCart();

        Cart::collectTotals();

        if ($cart->haveStockableItems()) {
            if (! $rates = Shipping::collectRates()) {
                return new JsonResource([
                    'redirect' => true,
                    'redirect_url' => route('shop.checkout.cart.index'),
                ]);
            }

            return new JsonResource([
                'redirect' => false,
                'data' => $rates,
            ]);
        }

        return new JsonResource([
            'redirect' => false,
            'data' => Payment::getSupportedPaymentMethods(),
        ]);
    }

    /**
     * Store shipping method.
     *
     * @return Response
     */
    public function storeShippingMethod()
    {
        $validatedData = $this->validate(request(), [
            'shipping_method' => 'required',
            'delivery_point_id' => 'nullable|integer',
        ]);

        $cart = Cart::getCart();
        $stateCode = (string) ($cart?->shipping_address?->state ?? '');
        $shippingMethod = $validatedData['shipping_method'];

        // Validate governorate shipping rule
        if (! empty($stateCode) && ! $this->governorateDeliveryValidator->isDeliveryTypeEnabled($stateCode, $shippingMethod)) {
            return response()->json([
                'message' => 'طريقة التوصيل المحددة غير مفعلة في هذه المحافظة.',
                'errors' => [
                    'shipping_method' => ['طريقة التوصيل المحددة غير مفعلة في هذه المحافظة.'],
                ],
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        // Validate delivery point if delivery point method chosen
        if ($this->shippingMethodAdapter->isDeliveryPoint($shippingMethod)) {
            $deliveryPointId = $validatedData['delivery_point_id'] ?? null;
            if (! $deliveryPointId && $cart?->shipping_address) {
                $additional = is_array($cart->shipping_address->additional)
                    ? $cart->shipping_address->additional
                    : json_decode($cart->shipping_address->additional ?? '[]', true);
                $deliveryPointId = $additional['delivery_point_id'] ?? null;
            }

            try {
                $pointSnapshot = $this->governorateDeliveryValidator->validateDeliveryPoint($stateCode, $deliveryPointId ? (int) $deliveryPointId : null);
                if ($cart?->shipping_address) {
                    $additional = is_array($cart->shipping_address->additional)
                        ? $cart->shipping_address->additional
                        : json_decode($cart->shipping_address->additional ?? '[]', true);
                    $additional['delivery_point_id'] = (int) $deliveryPointId;
                    $additional['delivery_point_snapshot'] = $pointSnapshot;
                    $cart->shipping_address->additional = $additional;
                    $cart->shipping_address->save();
                }
            } catch (ValidationException $e) {
                return response()->json([
                    'message' => $e->getMessage(),
                    'errors' => $e->errors(),
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }
        }

        if (
            Cart::hasError()
            || ! $validatedData['shipping_method']
            || ! Cart::saveShippingMethod($validatedData['shipping_method'])
        ) {
            return response()->json([
                'redirect_url' => route('shop.checkout.cart.index'),
            ], Response::HTTP_FORBIDDEN);
        }

        Cart::collectTotals();

        return response()->json(Payment::getSupportedPaymentMethods());
    }

    /**
     * Store payment method.
     *
     * @return array|JsonResponse
     */
    public function storePaymentMethod()
    {
        $validatedData = $this->validate(request(), [
            'payment' => 'required',
        ]);

        $cart = Cart::getCart();
        $paymentMethod = $validatedData['payment']['method'] ?? '';

        // Point 2: Server-side check for payment eligibility (e.g. COD restrictions)
        if (! $this->paymentEligibilityChecker->isCartEligible($paymentMethod, $cart)) {
            $canonicalType = $this->shippingMethodAdapter->canonicalize($cart?->shipping_method ?? '');
            if (strtolower(trim($paymentMethod)) === 'cashondelivery') {
                $errorMsg = ($canonicalType === ShippingMethodAdapter::CANONICAL_DELIVERY_POINT)
                    ? 'الدفع عند الاستلام غير متاح عند الاستلام من نقاط التوصيل. يرجى اختيار وسيلة دفع إلكترونية أو تحويل.'
                    : 'الدفع عند الاستلام غير متاح في المحافظة المحددة.';
            } else {
                $errorMsg = 'طريقة الدفع المحددة غير متاحة للمحافظة أو طريقة التوصيل المختارة.';
            }

            return response()->json([
                'message' => $errorMsg,
                'errors' => [
                    'payment' => [$errorMsg],
                ],
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        if (
            Cart::hasError()
            || ! $validatedData['payment']
            || ! Cart::savePaymentMethod($validatedData['payment'])
        ) {
            return response()->json([
                'redirect_url' => route('shop.checkout.cart.index'),
            ], Response::HTTP_FORBIDDEN);
        }

        Cart::collectTotals();

        $cart = Cart::getCart();

        return [
            'cart' => new CartResource($cart),
        ];
    }

    /**
     * Store order
     */
    public function storeOrder()
    {
        if (Cart::hasError()) {
            return new JsonResource([
                'redirect' => true,
                'redirect_url' => route('shop.checkout.cart.index'),
            ]);
        }

        Cart::collectTotals();

        try {
            $this->validateOrder();
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $cart = Cart::getCart();

        if (request()->hasFile('receipt')) {
            $receiptPath = request()->file('receipt')->store('offline_payments/receipts', 'public');
            session(['checkout_receipt_path' => $receiptPath]);
        }

        if ($redirectUrl = Payment::getRedirectUrl($cart)) {
            return new JsonResource([
                'redirect' => true,
                'redirect_url' => $redirectUrl,
            ]);
        }

        $data = (new OrderResource($cart))->jsonSerialize();

        try {
            $order = $this->orderRepository->create($data);
        } catch (CouponUsageLimitExceededException $e) {
            cart()->removeCouponCode();

            Cart::collectTotals();

            return new JsonResource([
                'redirect' => false,
                'message' => trans('shop::app.checkout.coupon.usage-limit-exceeded'),
            ]);
        }

        Cart::deActivateCart();

        session()->flash('order_id', $order->id);

        return new JsonResource([
            'redirect' => true,
            'redirect_url' => route('shop.checkout.onepage.success'),
        ]);
    }

    /**
     * Validate order before creation.
     *
     * @return void|\Exception
     */
    public function validateOrder()
    {
        $cart = Cart::getCart();

        $minimumOrderAmount = core()->getConfigData('sales.order_settings.minimum_order.minimum_order_amount') ?: 0;

        if (
            auth()->guard('customer')->check()
            && auth()->guard('customer')->user()->is_suspended
        ) {
            throw new \Exception(trans('shop::app.checkout.cart.suspended-account-message'));
        }

        if (
            auth()->guard('customer')->user()
            && ! auth()->guard('customer')->user()->status
        ) {
            throw new \Exception(trans('shop::app.checkout.cart.inactive-account-message'));
        }

        if (! Cart::haveMinimumOrderAmount()) {
            throw new \Exception(trans('shop::app.checkout.cart.minimum-order-message', ['amount' => core()->currency($minimumOrderAmount)]));
        }

        if ($cart->haveStockableItems() && ! $cart->shipping_address) {
            throw new \Exception(trans('shop::app.checkout.onepage.address.check-shipping-address'));
        }

        if (! $cart->billing_address) {
            throw new \Exception(trans('shop::app.checkout.onepage.address.check-billing-address'));
        }

        if (
            $cart->haveStockableItems()
            && ! $cart->selected_shipping_rate
        ) {
            throw new \Exception(trans('shop::app.checkout.cart.specify-shipping-method'));
        }

        if (! $cart->payment) {
            throw new \Exception(trans('shop::app.checkout.cart.specify-payment-method'));
        }

        // Point 3: Final server-side validation against bypasses
        $stateCode = (string) ($cart->shipping_address?->state ?? '');
        $shippingMethod = (string) ($cart->shipping_method ?? $cart->selected_shipping_rate?->method ?? '');
        $paymentMethod = (string) ($cart->payment?->method ?? '');

        if (! empty($stateCode) && ! $this->governorateDeliveryValidator->isDeliveryTypeEnabled($stateCode, $shippingMethod)) {
            throw new \Exception('طريقة التوصيل المحددة غير مفعلة في هذه المحافظة.');
        }

        if ($this->shippingMethodAdapter->isDeliveryPoint($shippingMethod)) {
            $additional = is_array($cart->shipping_address->additional)
                ? $cart->shipping_address->additional
                : json_decode($cart->shipping_address->additional ?? '[]', true);
            $deliveryPointId = isset($additional['delivery_point_id']) ? (int) $additional['delivery_point_id'] : null;

            if (! $deliveryPointId) {
                throw new \Exception('نقطة الاستلام مطلوبة لطريقة التوصيل المحددة.');
            }

            $this->governorateDeliveryValidator->validateDeliveryPoint($stateCode, $deliveryPointId);
        }

        if (! $this->paymentEligibilityChecker->isCartEligible($paymentMethod, $cart)) {
            $canonicalType = $this->shippingMethodAdapter->canonicalize($shippingMethod);
            if (strtolower(trim($paymentMethod)) === 'cashondelivery') {
                $errorMsg = ($canonicalType === ShippingMethodAdapter::CANONICAL_DELIVERY_POINT)
                    ? 'الدفع عند الاستلام غير متاح عند الاستلام من نقاط التوصيل.'
                    : 'الدفع عند الاستلام غير متاح في المحافظة المحددة.';
            } else {
                $errorMsg = 'طريقة الدفع المحددة غير متاحة للمحافظة أو طريقة التوصيل المختارة.';
            }

            throw new \Exception($errorMsg);
        }
    }
}
