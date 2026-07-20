<?php

namespace Webkul\Fulfillment\Services\Domain;

class ShippingSelectionService
{
    /**
     * Choose the best shipping option from a list of options.
     * Sourced from: shipping option id, service code, price, ETA, currency.
     */
    public function select(array $shippingOptions): ?array
    {
        if (empty($shippingOptions)) {
            return null;
        }

        usort($shippingOptions, function ($a, $b) {
            $priceA = (float) ($a['price'] ?? 0);
            $priceB = (float) ($b['price'] ?? 0);

            if ($priceA === $priceB) {
                $etaA = (int) ($a['eta_days'] ?? 999);
                $etaB = (int) ($b['eta_days'] ?? 999);
                return $etaA <=> $etaB;
            }

            return $priceA <=> $priceB;
        });

        return $shippingOptions[0];
    }
}
