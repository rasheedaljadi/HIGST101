<?php

namespace Webkul\DeliveryManagement\Services;

class ShippingMethodAdapter
{
    public const CANONICAL_HOME_DELIVERY = 'home_delivery';

    public const CANONICAL_DELIVERY_POINT = 'delivery_point';

    /**
     * Known mapping from carrier method codes or legacy aliases to canonical delivery types.
     */
    protected const METHOD_MAP = [
        'homedelivery_standard' => self::CANONICAL_HOME_DELIVERY,
        'homedelivery' => self::CANONICAL_HOME_DELIVERY,
        'home_delivery' => self::CANONICAL_HOME_DELIVERY,
        'flatrate_flatrate' => self::CANONICAL_HOME_DELIVERY,
        'flatrate' => self::CANONICAL_HOME_DELIVERY,
        'free_free' => self::CANONICAL_HOME_DELIVERY,
        'free' => self::CANONICAL_HOME_DELIVERY,
        'dropshipping_dropshipping' => self::CANONICAL_HOME_DELIVERY,
        'dropshipping' => self::CANONICAL_HOME_DELIVERY,

        'deliverypoint_pickup' => self::CANONICAL_DELIVERY_POINT,
        'deliverypoint' => self::CANONICAL_DELIVERY_POINT,
        'delivery_point' => self::CANONICAL_DELIVERY_POINT,
        'pickup' => self::CANONICAL_DELIVERY_POINT,
    ];

    /**
     * Convert any shipping method code or delivery type string to its canonical equivalent.
     */
    public function canonicalize(?string $code): string
    {
        if (empty($code)) {
            return self::CANONICAL_HOME_DELIVERY;
        }

        $normalized = strtolower(trim($code));

        return self::METHOD_MAP[$normalized] ?? self::CANONICAL_HOME_DELIVERY;
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
