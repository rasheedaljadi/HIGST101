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

        'deliverypoint_pickup' => self::CANONICAL_DELIVERY_POINT,
        'deliverypoint' => self::CANONICAL_DELIVERY_POINT,
        'delivery_point' => self::CANONICAL_DELIVERY_POINT,
        'pickup' => self::CANONICAL_DELIVERY_POINT,
    ];

    /**
     * Convert any shipping method code or delivery type string to its canonical equivalent.
     * Returns null if the code is not explicitly recognized as home delivery or delivery point.
     */
    public function canonicalize(?string $code): ?string
    {
        if (empty($code)) {
            return null;
        }

        $normalized = strtolower(trim($code));

        return self::METHOD_MAP[$normalized] ?? null;
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
