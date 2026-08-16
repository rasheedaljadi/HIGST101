<?php

namespace Webkul\DeliveryManagement\Listeners;

use Illuminate\Support\Facades\Log;
use Webkul\DeliveryManagement\Models\DeliveryAssignment;
use Webkul\DeliveryManagement\Services\GovernorateDeliveryValidator;
use Webkul\DeliveryManagement\Services\ShippingMethodAdapter;
use Webkul\Sales\Contracts\Order;

class OrderCreatedListener
{
    public function __construct(
        protected ShippingMethodAdapter $shippingMethodAdapter,
        protected GovernorateDeliveryValidator $governorateDeliveryValidator
    ) {}

    /**
     * Handle the order created event and store immutable delivery snapshots.
     */
    public function handle(Order $order): void
    {
        try {
            $shippingAddress = $order->shipping_address;

            if (! $shippingAddress) {
                return;
            }

            $stateCode = (string) $shippingAddress->state;
            $shippingMethod = (string) $order->shipping_method;
            $deliveryType = $this->shippingMethodAdapter->canonicalize($shippingMethod);

            // If the shipping method does not map to home_delivery or delivery_point (e.g. legacy/external carrier), do not force a delivery assignment
            if (empty($deliveryType)) {
                return;
            }

            $additional = is_array($shippingAddress->additional)
                ? $shippingAddress->additional
                : json_decode($shippingAddress->additional ?? '[]', true);

            $deliveryPointId = isset($additional['delivery_point_id']) ? (int) $additional['delivery_point_id'] : null;
            $deliveryPointSnapshot = $additional['delivery_point_snapshot'] ?? null;

            if ($deliveryType === ShippingMethodAdapter::CANONICAL_DELIVERY_POINT && empty($deliveryPointSnapshot) && $deliveryPointId) {
                try {
                    $deliveryPointSnapshot = $this->governorateDeliveryValidator->validateDeliveryPoint($stateCode, $deliveryPointId);
                } catch (\Throwable $e) {
                    Log::channel('delivery')->warning("Could not build live delivery point snapshot for Order #{$order->id}: ".$e->getMessage());
                }
            }

            $customerAddressSnapshot = [
                'first_name' => $shippingAddress->first_name,
                'last_name' => $shippingAddress->last_name,
                'email' => $shippingAddress->email,
                'phone' => $shippingAddress->phone,
                'address' => $shippingAddress->address,
                'city' => $shippingAddress->city,
                'state' => $shippingAddress->state,
                'country' => $shippingAddress->country,
                'postcode' => $shippingAddress->postcode,
            ];

            DeliveryAssignment::updateOrCreate(
                ['order_id' => $order->id],
                [
                    'delivery_type' => $deliveryType,
                    'delivery_point_id' => $deliveryPointId,
                    'customer_address_snapshot' => $customerAddressSnapshot,
                    'delivery_point_snapshot' => $deliveryPointSnapshot,
                    'status' => DeliveryAssignment::STATUS_READY_FOR_ASSIGNMENT,
                    'attempt_count' => 0,
                    'max_attempts' => 3,
                ]
            );
        } catch (\Throwable $e) {
            Log::channel('delivery')->error("Failed to initialize DeliveryAssignment for Order #{$order->id}: ".$e->getMessage());
        }
    }
}
