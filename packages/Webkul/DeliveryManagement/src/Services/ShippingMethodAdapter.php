<?php

namespace Webkul\DeliveryManagement\Services;

class ShippingMethodAdapter
{
    public const CANONICAL_HOME_DELIVERY = 'home_delivery';

    public const CANONICAL_DELIVERY_POINT = 'delivery_point';

    /**
     * Explicit mapping from known carrier method codes to canonical delivery types.
     */
    protected const METHOD_MAP = [
        'homedelivery_standard' => self::CANONICAL_HOME_DELIVERY,
        'homedelivery' => self::CANONICAL_HOME_DELIVERY,
        'home_delivery' => self::CANONICAL_HOME_DELIVERY,
        'flatrate_flatrate' => self::CANONICAL_HOME_DELIVERY,
        'flatrate' => self::CANONICAL_HOME_DELIVERY,
        'free_free' => self::CANONICAL_HOME_DELIVERY,
        'free' => self::CANONICAL_HOME_DELIVERY,
        'courier_courier' => self::CANONICAL_HOME_DELIVERY,
        'courier' => self::CANONICAL_HOME_DELIVERY,
        'mpcourier_mpcourier' => self::CANONICAL_HOME_DELIVERY,
        'mpcourier' => self::CANONICAL_HOME_DELIVERY,
        'highest_shipping' => self::CANONICAL_HOME_DELIVERY,

        'deliverypoint_pickup' => self::CANONICAL_DELIVERY_POINT,
        'deliverypoint' => self::CANONICAL_DELIVERY_POINT,
        'delivery_point' => self::CANONICAL_DELIVERY_POINT,
        'pickup' => self::CANONICAL_DELIVERY_POINT,
    ];

    /**
     * Convert any shipping method code or delivery type string to its canonical equivalent.
     * Returns home_delivery by default if not delivery point.
     */
    public function canonicalize(?string $code): ?string
    {
        if (empty($code)) {
            return null;
        }

        $normalized = strtolower(trim($code));

        if (isset(self::METHOD_MAP[$normalized])) {
            return self::METHOD_MAP[$normalized];
        }

        if (str_contains($normalized, 'point') || str_contains($normalized, 'pickup')) {
            return self::CANONICAL_DELIVERY_POINT;
        }

        return self::CANONICAL_HOME_DELIVERY;
    }

    /**
     * Check if a shipping method corresponds to home delivery.
     */
    public function isHomeDelivery(?string $code): bool
    {
        return $this->canonicalize($code) === self::CANONICAL_HOME_DELIVERY;
    }

    /**
     * Check if a shipping method corresponds to delivery point pickup.
     */
    public function isDeliveryPoint(?string $code): bool
    {
        return $this->canonicalize($code) === self::CANONICAL_DELIVERY_POINT;
    }

    /**
     * Get all supported canonical delivery types.
     *
     * @return string[]
     */
    public function getCanonicalDeliveryTypes(): array
    {
        return [
            self::CANONICAL_HOME_DELIVERY,
            self::CANONICAL_DELIVERY_POINT,
        ];
    }

    /**
     * Map canonical delivery type to carrier method string.
     */
    public function getCarrierMethodForCanonical(string $canonicalType): string
    {
        return match ($canonicalType) {
            self::CANONICAL_DELIVERY_POINT => 'deliverypoint_pickup',
            default => 'homedelivery_standard',
        };
    }
}
