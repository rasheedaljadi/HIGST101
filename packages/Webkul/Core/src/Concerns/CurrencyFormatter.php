<?php

namespace Webkul\Core\Concerns;

use Webkul\Core\Contracts\Currency;
use Webkul\Core\Enums\CurrencyPositionEnum;

trait CurrencyFormatter
{
    /**
     * Format currency.
     */
    public function formatCurrency(?float $price, Currency $currency): string
    {
        if (
            $currency->currency_position
            || ! is_null($currency->decimal)
            || ! empty($currency->group_separator)
            || ! empty($currency->decimal_separator)
        ) {
            return $this->useCustomCurrencyFormatter($price, $currency);
        }

        return $this->useDefaultCurrencyFormatter($price, $currency);
    }

    /**
     * Use default formatter.
     */
    public function useDefaultCurrencyFormatter(?float $price, Currency $currency): string
    {
        $formatter = new \NumberFormatter(app()->getLocale(), \NumberFormatter::CURRENCY);

        if (! is_null($currency->decimal)) {
            $formatter->setAttribute(\NumberFormatter::FRACTION_DIGITS, (int) $currency->decimal);
        }

        if ($currency->symbol) {
            if ($this->currencySymbol($currency) == $currency->symbol && is_null($currency->decimal)) {
                return $formatter->formatCurrency($price, $currency->code);
            }

            $formatter->setSymbol(\NumberFormatter::CURRENCY_SYMBOL, $currency->symbol);

            return $formatter->format($price);
        }

        return $formatter->formatCurrency($price, $currency->code);
    }

    /**
     * Use custom formatter.
     */
    public function useCustomCurrencyFormatter(?float $price, Currency $currency): string
    {
        $formatter = new \NumberFormatter(app()->getLocale(), \NumberFormatter::CURRENCY);

        $formatter->setSymbol(\NumberFormatter::CURRENCY_SYMBOL, '');

        $decimalDigits = ! is_null($currency->decimal) ? (int) $currency->decimal : 2;

        $formatter->setAttribute(\NumberFormatter::FRACTION_DIGITS, $decimalDigits);

        $formattedCurrency = preg_replace('/^\s+|\s+$/u', '', $formatter->format($price));

        if (! empty($currency->group_separator)) {
            $formattedCurrency = str_replace(
                $formatter->getSymbol(\NumberFormatter::GROUPING_SEPARATOR_SYMBOL),
                $currency->group_separator,
                $formattedCurrency
            );
        }

        if (
            $decimalDigits > 0
            && ! empty($currency->decimal_separator)
        ) {
            $formattedCurrency = str_replace(
                $formatter->getSymbol(\NumberFormatter::DECIMAL_SEPARATOR_SYMBOL),
                $currency->decimal_separator,
                $formattedCurrency
            );
        }

        $symbol = ! empty($currency->symbol)
            ? $currency->symbol
            : $currency->code;

        return match ($currency->currency_position) {
            CurrencyPositionEnum::LEFT->value => $symbol.$formattedCurrency,
            CurrencyPositionEnum::LEFT_WITH_SPACE->value => $symbol.' '.$formattedCurrency,
            CurrencyPositionEnum::RIGHT->value => $formattedCurrency.$symbol,
            CurrencyPositionEnum::RIGHT_WITH_SPACE->value => $formattedCurrency.' '.$symbol,
            default => $formattedCurrency.' '.$symbol,
        };
    }

    /**
     * Return currency symbol from currency code.
     *
     * @param  string|Currency  $currency
     */
    public function currencySymbol($currency): string
    {
        $code = $currency instanceof Currency ? $currency->code : $currency;

        $formatter = new \NumberFormatter(app()->getLocale().'@currency='.$code, \NumberFormatter::CURRENCY);

        return $formatter->getSymbol(\NumberFormatter::CURRENCY_SYMBOL);
    }
}
