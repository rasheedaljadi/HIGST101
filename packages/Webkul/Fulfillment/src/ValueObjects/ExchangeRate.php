<?php

namespace Webkul\Fulfillment\ValueObjects;

class ExchangeRate
{
    public function __construct(
        protected Currency $from,
        protected Currency $to,
        protected float $rate
    ) {
        if ($rate <= 0) {
            throw new \InvalidArgumentException("Exchange rate must be greater than zero.");
        }
    }

    public function from(): Currency
    {
        return $this->from;
    }

    public function to(): Currency
    {
        return $this->to;
    }

    public function rate(): float
    {
        return $this->rate;
    }

    public function convert(Money $money): Money
    {
        if (! $money->currency()->equals($this->from)) {
            throw new \InvalidArgumentException("Cannot convert money from currency {$money->currency()} using exchange rate for {$this->from}.");
        }

        return new Money($money->amount() * $this->rate, $this->to);
    }
}
