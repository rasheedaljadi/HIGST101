<?php

namespace Webkul\Procurement\DTO;

use SensitiveParameter;

class ResolvedAliExpressAuthorization
{
    public function __construct(
        #[SensitiveParameter]
        public readonly string $accessToken,
        public readonly ?string $accountIdentifier = null,
        public readonly ?string $sellerId = null,
        public readonly ?string $accountMasked = null,
        public readonly ?string $expiresAt = null,
        public readonly bool $isValid = true,
    ) {}

    /**
     * Return safe, secret-free summary for auditing and logs.
     *
     * @return array<string, mixed>
     */
    public function getMaskedSummary(): array
    {
        return [
            'account_identifier' => $this->accountIdentifier ? substr($this->accountIdentifier, 0, 4).'***' : 'default',
            'seller_id' => $this->sellerId ? substr($this->sellerId, 0, 4).'***' : null,
            'account_masked' => $this->accountMasked,
            'expires_at' => $this->expiresAt,
            'is_valid' => $this->isValid,
        ];
    }
}
