<?php

namespace Webkul\Fulfillment\Contracts;

use Illuminate\Http\Request;

interface ExternalWebhookVerifierInterface
{
    /**
     * Authenticate and verify the incoming request signature.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return bool
     */
    public function verify(Request $request): bool;
}
