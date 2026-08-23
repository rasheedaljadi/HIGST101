<?php

namespace Webkul\Procurement\Services;

use App\Services\AliExpress\AliExpressOAuthService;
use Throwable;
use Webkul\Procurement\Contracts\AliExpressAuthorizationContextResolver;
use Webkul\Procurement\DTO\ResolvedAliExpressAuthorization;
use Webkul\Procurement\Exceptions\AliExpressAuthorizationUnavailableException;

class AliExpressAuthorizationResolver implements AliExpressAuthorizationContextResolver
{
    public function __construct(
        protected ?AliExpressOAuthService $oauthService = null
    ) {
        $this->oauthService ??= app(AliExpressOAuthService::class);
    }

    /**
     * {@inheritdoc}
     */
    public function resolveForDropshipperSubmission(?string $logicalAccountKey = null): ResolvedAliExpressAuthorization
    {
        $token = $this->oauthService->latestToken();

        if ($token === null) {
            throw new AliExpressAuthorizationUnavailableException(
                'No AliExpress OAuth authorization grant is stored. Please complete OAuth authorization in Key Management.'
            );
        }

        if (! $token->isAccessTokenValid()) {
            throw new AliExpressAuthorizationUnavailableException(
                'AliExpress OAuth access token is expired and could not be refreshed.'
            );
        }

        $accountRaw = (string) ($token->account ?? '');
        $accountMasked = ! empty($accountRaw)
            ? (str_contains($accountRaw, '@')
                ? preg_replace('/(^.).*(@.*$)/', '$1***$2', $accountRaw)
                : substr($accountRaw, 0, 2).'***')
            : null;

        return new ResolvedAliExpressAuthorization(
            accessToken: $token->access_token,
            accountIdentifier: (string) ($token->account_id ?? $token->seller_id ?? 'default'),
            sellerId: $token->seller_id ? (string) $token->seller_id : null,
            accountMasked: $accountMasked,
            expiresAt: $token->access_token_expires_at?->toIso8601String(),
            isValid: true
        );
    }

    /**
     * {@inheritdoc}
     */
    public function hasValidAuthorization(?string $logicalAccountKey = null): bool
    {
        try {
            $this->resolveForDropshipperSubmission($logicalAccountKey);

            return true;
        } catch (Throwable) {
            return false;
        }
    }
}
