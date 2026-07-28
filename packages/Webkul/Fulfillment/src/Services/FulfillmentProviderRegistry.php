<?php

namespace Webkul\Fulfillment\Services;

use Webkul\Fulfillment\Contracts\ExternalFulfillmentProviderInterface;
use Webkul\Fulfillment\Contracts\FulfillmentProviderInterface;

class FulfillmentProviderRegistry
{
    /**
     * The resolved provider instances.
     *
     * @var array<string, FulfillmentProviderInterface>
     */
    protected array $instances = [];

    /**
     * Resolve a provider code to an instance.
     *
     *
     * @throws \InvalidArgumentException
     */
    public function resolve(string $code): FulfillmentProviderInterface|ExternalFulfillmentProviderInterface
    {
        if (isset($this->instances[$code])) {
            return $this->instances[$code];
        }

        $providers = config('fulfillment.providers', []);

        if (! isset($providers[$code])) {
            throw new \InvalidArgumentException("Fulfillment provider [{$code}] is not registered.");
        }

        $class = $providers[$code];

        if (! class_exists($class)) {
            throw new \InvalidArgumentException("Fulfillment provider class [{$class}] does not exist.");
        }

        $instance = app($class);

        if (! $instance instanceof FulfillmentProviderInterface && ! $instance instanceof ExternalFulfillmentProviderInterface) {
            throw new \InvalidArgumentException("Fulfillment provider class [{$class}] must implement ".FulfillmentProviderInterface::class.' or '.ExternalFulfillmentProviderInterface::class);
        }

        $this->instances[$code] = $instance;

        return $instance;
    }
}
