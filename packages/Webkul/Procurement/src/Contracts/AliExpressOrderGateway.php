<?php

namespace Webkul\Procurement\Contracts;

use Webkul\Procurement\DTO\AliExpressOrderPreflight;
use Webkul\Procurement\DTO\AliExpressOrderSnapshot;
use Webkul\Procurement\DTO\ExternalOrderDraft;
use Webkul\Procurement\DTO\ExternalOrderSubmissionFailed;
use Webkul\Procurement\DTO\VerifiedExternalOrderCreated;

interface AliExpressOrderGateway
{
    /**
     * Preflight check: validates SKU, live product state, and queries live freight options to Saudi Arabia.
     */
    public function preflight(ExternalOrderDraft $draft): AliExpressOrderPreflight;

    /**
     * Submit unpaid order to AliExpress with strict response verification.
     */
    public function submitUnpaid(ExternalOrderDraft $draft): VerifiedExternalOrderCreated|ExternalOrderSubmissionFailed;

    /**
     * Fetch order status from AliExpress using official external order ID.
     */
    public function getOrder(string $officialExternalOrderId, ?int $providerAccountId = null): AliExpressOrderSnapshot;
}
