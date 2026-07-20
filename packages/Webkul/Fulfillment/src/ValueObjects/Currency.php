<?php

namespace Webkul\Fulfillment\ValueObjects;

class Currency
{
    public function __construct(protected string $code)
    {
        $this->code = strtoupper(trim($code));
        if (strlen($this->code) !== 3) {
            throw new \InvalidArgumentException("Currency code must be exactly 3 characters.");
        }
    }

    public function code(): string
    {
        return $this->code;
    }

    public function equals(Currency $other): bool
    {
        return $this->code === $other->code();
    }

    public function __toString(): string
    {
        return $this->code;
    }
}
