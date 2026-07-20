<?php

namespace Tests\Feature\Fulfillment;

use Tests\TestCase;
use App\Services\AliExpress\AliExpressApiClient;
use Webkul\Fulfillment\Providers\AliExpress\AliExpressHttpClient;

class ProviderBindingContractTest extends TestCase
{
    /**
     * Verify that the container binds the base API client to the resilient HTTP client.
     */
    public function test_api_client_resolves_to_resilient_http_client()
    {
        $resolved = app(AliExpressApiClient::class);

        $this->assertInstanceOf(AliExpressHttpClient::class, $resolved);
        $this->assertInstanceOf(AliExpressApiClient::class, $resolved);
    }

    /**
     * Verify method existence and subclass contract compatibility.
     */
    public function test_resilient_http_client_satisfies_contract_signatures()
    {
        $client = new AliExpressHttpClient('test_app_key', 'test_app_secret');

        $this->assertTrue(method_exists($client, 'call'));
        $this->assertTrue(method_exists($client, 'callResilient'));

        // Verify signature compatibility of overridden call()
        $reflector = new \ReflectionMethod($client, 'call');
        $parameters = $reflector->getParameters();

        $this->assertGreaterThanOrEqual(2, count($parameters));
        $this->assertEquals('method', $parameters[0]->getName());
        $this->assertEquals('accessToken', $parameters[1]->getName());
    }
}
