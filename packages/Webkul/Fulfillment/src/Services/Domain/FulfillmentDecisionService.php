<?php

namespace Webkul\Fulfillment\Services\Domain;

use Webkul\Fulfillment\DataObjects\FulfillmentDecision;

class FulfillmentDecisionService
{
    /**
     * Evaluate rules and output a FulfillmentDecision DTO.
     *
     * @param  int  $orderId
     * @param  array  $context
     * @return \Webkul\Fulfillment\DataObjects\FulfillmentDecision
     */
    public function makeDecision(int $orderId, array $context = []): FulfillmentDecision
    {
        // Decision rules logic
        $hasLocalStock = $context['has_local_stock'] ?? false;
        $profitMargin = $context['profit_margin'] ?? 30.00;

        if ($hasLocalStock && $profitMargin >= 10.00) {
            return new FulfillmentDecision(
                source: 'LOCAL',
                provider: null,
                warehouse: 'warehouse_riyadh',
                reason: 'In-stock locally with profitable margin.',
                confidence: 98,
                decision_version: 'v2.0'
            );
        }

        return new FulfillmentDecision(
            source: 'SUPPLIER',
            provider: 'aliexpress',
            warehouse: null,
            reason: 'Out-of-stock locally; routing to AliExpress dropshipping.',
            confidence: 95,
            decision_version: 'v2.0'
        );
    }
}
