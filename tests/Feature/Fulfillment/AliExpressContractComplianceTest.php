<?php

namespace Tests\Feature\Fulfillment;

use Tests\TestCase;
use Webkul\Fulfillment\Providers\AliExpress\AliExpressEventNormalizer;
use Webkul\Fulfillment\Providers\AliExpress\AliExpressRetryPolicy;
use Webkul\Fulfillment\Services\Domain\ExternalStateMapper;
use Webkul\Fulfillment\Contracts\FulfillmentProviderInterface;
use Webkul\Fulfillment\Providers\AliExpress\AliExpressFulfillmentProvider;

class AliExpressContractComplianceTest extends TestCase
{
    /**
     * Test endpoint compliance.
     */
    public function test_endpoints_match_contract(): void
    {
        // Reflection check that contract functions are implemented and expect correct signatures
        $reflector = new \ReflectionClass(AliExpressFulfillmentProvider::class);
        $this->assertTrue($reflector->implementsInterface(FulfillmentProviderInterface::class));

        // Get methods we expect
        $createMethod = $reflector->getMethod('createSupplierOrder');
        $this->assertEquals(1, $createMethod->getNumberOfParameters()); // takes 1 parameter: SupplierOrderRequest

        $statusMethod = $reflector->getMethod('getSupplierOrderStatus');
        $this->assertEquals(2, $statusMethod->getNumberOfParameters());
    }

    /**
     * Test that AliExpressEventNormalizer maps all expected AliExpress statuses.
     */
    public function test_normalizer_status_mappings_coverage(): void
    {
        $normalizer = new AliExpressEventNormalizer();

        $statuses = [
            'place_order_success',
            'order_created',
            'payment_success',
            'order_paid',
            'wait_receive',
            'shipped',
            'order_shipped',
            'seller_send_goods',
            'cancelled',
            'closed',
            'order_cancelled',
            'finish',
            'completed',
            'order_delivered',
        ];

        foreach ($statuses as $status) {
            $payload = [
                'event_id'   => 'evt-compliance-1',
                'order_id'   => 'ae-ext-9921',
                'status'     => $status,
                'timestamp'  => now()->toIso8601String(),
            ];

            $normalized = $normalizer->normalize($payload);
            $this->assertNotEmpty($normalized->eventType);
            $this->assertEquals('aliexpress', $normalized->externalSystem);
        }
    }

    /**
     * Test state mapping compliance.
     */
    public function test_status_mapping_and_casing_compliance(): void
    {
        $mapper = new ExternalStateMapper();
        $normalizer = new AliExpressEventNormalizer();

        $shippedPayload = [
            'event_id'     => 'evt-compliance-2',
            'order_id'     => 'ae-ext-9921',
            'status'       => 'SELLER_SEND_GOODS',
            'timestamp'    => now()->toIso8601String(),
            'carrier_code' => 'aliexpress_standard',
        ];

        $normalized = $normalizer->normalize($shippedPayload);
        $action = $mapper->map($normalized);

        $this->assertEquals('MARK_SHIPPED', $action->action);
    }

    /**
     * Test retry policy compliance.
     */
    public function test_retry_policy_compliance(): void
    {
        $policy = new AliExpressRetryPolicy();
        $this->assertEquals(3, $policy->maxAttempts());
        $this->assertEquals([5, 20, 60], $policy->delays());
    }
}
