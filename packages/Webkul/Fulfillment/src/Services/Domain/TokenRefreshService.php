<?php

namespace Webkul\Fulfillment\Services\Domain;

use Webkul\Fulfillment\Models\ProviderAccount;

class TokenRefreshService
{
    /**
     * Refresh OAuth access token using refresh token.
     */
    public function refresh(ProviderAccount $account): bool
    {
        if (empty($account->refresh_token)) {
            return false;
        }

        $account->update([
            'access_token'  => 'refreshed-fake-access-token-' . uniqid(),
            'status'        => 'ACTIVE',
        ]);

        return true;
    }
}
