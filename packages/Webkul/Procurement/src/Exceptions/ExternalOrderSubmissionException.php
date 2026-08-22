<?php

namespace Webkul\Procurement\Exceptions;

use DomainException;
use Webkul\Procurement\DTO\ExternalOrderSubmissionFailed;

class ExternalOrderSubmissionException extends DomainException
{
    public function __construct(
        public readonly ExternalOrderSubmissionFailed $failureDto,
        string $message = ''
    ) {
        $msg = $message ?: "AliExpress order submission failed with code [{$failureDto->errorCode}]: {$failureDto->errorMessageMasked}";
        parent::__construct($msg);
    }
}
