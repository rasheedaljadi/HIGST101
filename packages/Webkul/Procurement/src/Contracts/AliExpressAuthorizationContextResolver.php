<?php

namespace Webkul\Procurement\Contracts;

use Webkul\Procurement\DTO\ResolvedAliExpressAuthorization;
use Webkul\Procurement\Exceptions\AliExpressAuthorizationUnavailableException;

interface AliExpressAuthorizationContextResolver
{
    /**
     * Resolve the active, verified AliExpress OAuth authorization context for dropshipper submission.
     *
     * @throws AliExpressAuthorizationUnavailableException
     */
    public function resolveForDropshipperSubmission(?string $logicalAccountKey = null): ResolvedAliExpressAuthorization;

    /**
     * Check if a valid authorization context currently exists without throwing.
     */
    public function hasValidAuthorization(?string $logicalAccountKey = null): bool;
}
