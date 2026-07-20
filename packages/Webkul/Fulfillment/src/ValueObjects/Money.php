<?php

namespace Webkul\Fulfillment\ValueObjects;

class Money
{
    public function __construct(protected float $amount, protected Currency $currency)
    {
        $this->amount = round($amount, 4);
    }

    public function amount(): float
    {
        return $this->amount;
    }

    public function currency(): Currency
    {
        return $this->currency;
    }

    public function add(Money $other): Money
    {
        if (! $this->currency->equals($other->currency())) {
            throw new \InvalidArgumentException("Cannot add money of different currencies.");
        }

        return new self($this->amount + $other->amount(), $this->currency);
    }

    public function subtract(Money $other): Money
    {
        if (! $this->currency->equals($other->currency())) {
            throw new \InvalidArgumentException("Cannot subtract money of different currencies.");
        }

        return new self($this->amount - $other->amount(), $this->currency);
    }

    public function multiply(float $multiplier): Money
    {
        return new self($this->amount * $multiplier, $this->currency);
    }

    public function equals(Money $other): bool
    {
        return $this->amount === $other->amount() && $this->currency->equals($other->currency());
    }

    public function toArray(): array
    {
        return [
            'amount'   => $this->amount,
            'currency' => $this->currency->code(),
        ];
    }
}
