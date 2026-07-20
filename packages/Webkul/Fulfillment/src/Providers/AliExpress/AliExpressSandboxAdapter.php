<?php

namespace Webkul\Fulfillment\Providers\AliExpress;

class AliExpressSandboxAdapter extends AliExpressProcurementAdapter
{
    public function code(): string
    {
        return 'aliexpress_sandbox';
    }

    public function isConfigured(int $providerAccountId): bool
    {
        return true;
    }
}
