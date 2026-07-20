<?php

namespace Webkul\Fulfillment\Handlers\Procurement;

use Illuminate\Support\Facades\DB;
use Webkul\Fulfillment\Commands\RefreshAccessTokenCommand;
use Webkul\Fulfillment\Models\ProviderAccount;
use Webkul\Fulfillment\Services\Domain\TokenRefreshService;

class RefreshAccessTokenHandler
{
    public function __construct(protected TokenRefreshService $refreshService) {}

    public function handle(RefreshAccessTokenCommand $command): ProviderAccount
    {
        return DB::transaction(function () use ($command) {
            $account = ProviderAccount::findOrFail($command->providerAccountId);

            $this->refreshService->refresh($account);

            return $account;
        });
    }
}
