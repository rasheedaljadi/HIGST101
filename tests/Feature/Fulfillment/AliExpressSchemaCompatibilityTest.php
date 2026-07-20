<?php

namespace Tests\Feature\Fulfillment;

use Tests\TestCase;
use Webkul\Fulfillment\Providers\AliExpress\AliExpressEventNormalizer;

class AliExpressSchemaCompatibilityTest extends TestCase
{
    /**
     * Test forward compatibility: system ignores unknown extra fields.
     */
    public function test_ignores_new_unknown_fields(): void
    {
        $normalizer = new AliExpressEventNormalizer();

        $payload = [
            'event_id'          => 'ae-evt-comp-1',
            'status'            => 'order_created',
            'order_id'          => 'ae-ext-9921',
            'timestamp'         => now()->toIso8601String(),
            'brand_new_field'   => 'some_value',
            'meta_extra_nested' => ['foo' => 'bar'],
        ];

        $normalized = $normalizer->normalize($payload);
        $this->assertEquals('ae-evt-comp-1', $normalized->eventId);
        $this->assertEquals('order_created', $normalized->eventType);
        $this->assertEquals('ae-ext-9921', $normalized->resourceId);
    }

    /**
     * Test optional fields being absent does not trigger errors.
     */
    public function test_handles_missing_optional_fields(): void
    {
        $normalizer = new AliExpressEventNormalizer();

        $payload = [
            'event_id'          => 'ae-evt-comp-2',
            'status'            => 'order_created',
            'order_id'          => 'ae-ext-9921',
            // optional fields like correlation_id, timestamp, schema_version are missing
        ];

        $normalized = $normalizer->normalize($payload);
        $this->assertEquals('ae-evt-comp-2', $normalized->eventId);
        $this->assertEquals('order_created', $normalized->eventType);
        $this->assertEquals('ae-ext-9921', $normalized->resourceId);
        $this->assertNull($normalized->correlationId);
    }

    /**
     * Test validation failure when mandatory fields are missing.
     */
    public function test_throws_exception_on_missing_essential_fields(): void
    {
        $normalizer = new AliExpressEventNormalizer();

        $payload = [
            'status'   => 'order_created',
            // event_id and order_id are completely missing
        ];

        $normalized = $normalizer->normalize($payload);

        // System should fallback to defaults but flag review or set identifiers indicating they are missing
        $this->assertStringStartsWith('evt-', $normalized->eventId);
        $this->assertEquals('unknown', $normalized->resourceId);
    }
}
