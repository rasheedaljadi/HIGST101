<?php

namespace Webkul\Fulfillment\Services\Domain;

use Webkul\Fulfillment\Models\ProcurementSession;

class ContractResolver
{
    /**
     * Resolve the contract version to use for a procurement session.
     */
    public function resolve(ProcurementSession $session): string
    {
        return $session->contract_version ?: 'AliExpress Contract v2026-07';
    }
}
