<?php

namespace Webkul\Fulfillment\Services\Domain;

class ProviderFeatureService
{
    public function __construct(protected ProviderCapabilityRegistry $registry) {}

    public function supports(string $providerCode, string $contractVersion, string $feature): bool
    {
        $caps = $this->registry->getCapabilities($providerCode, $contractVersion);
        return (bool) ($caps[$feature] ?? false);
    }
}
