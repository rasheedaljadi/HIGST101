<?php

namespace Webkul\Procurement\DTO;

class ExternalOrderSubmissionFailed
{
    /**
     * @param  array<string, mixed>  $rawResponse
     */
    public function __construct(
        public readonly string $errorCode,
        public readonly string $errorMessageMasked,
        public readonly ?string $providerRequestId = null,
        public readonly string $retryClassification = 'non_retryable',
        public readonly array $rawResponse = []
    ) {}
}
