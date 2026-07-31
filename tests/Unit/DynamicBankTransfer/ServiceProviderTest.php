<?php

namespace Tests\Unit\DynamicBankTransfer;

use Tests\TestCase;
use Webkul\DynamicBankTransfer\Providers\DynamicBankTransferServiceProvider;
use Webkul\DynamicBankTransfer\Providers\ModuleServiceProvider;

class ServiceProviderTest extends TestCase
{
    /**
     * Test that DynamicBankTransferServiceProvider resolves from the IoC container cleanly.
     */
    public function test_service_provider_is_registered_and_resolvable(): void
    {
        $provider = $this->app->getProvider(DynamicBankTransferServiceProvider::class);

        $this->assertNotNull($provider, 'DynamicBankTransferServiceProvider is not registered in application container.');
        $this->assertInstanceOf(DynamicBankTransferServiceProvider::class, $provider);
    }

    /**
     * Test that Concord module provider is registered in concord configuration.
     */
    public function test_concord_module_provider_is_configured(): void
    {
        $modules = config('concord.modules', []);

        $this->assertContains(
            ModuleServiceProvider::class,
            $modules,
            'ModuleServiceProvider is not registered in config/concord.php.'
        );
    }
}
