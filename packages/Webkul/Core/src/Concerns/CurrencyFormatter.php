<?php

namespace Webkul\Core\Concerns;

use Webkul\Core\Contracts\Currency;
use Webkul\Core\Enums\CurrencyPositionEnum;

trait CurrencyFormatter
{
    /**
     * Replace Eastern Arabic numerals (٠١٢٣٤٥٦٧٨٩) with Western Arabic numerals (0123456789).
     */
    public function replaceEasternArabicDigits(string $string): string
    {
        $eastern = ['٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩'];
        $western = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];

        return str_replace($eastern, $western, $string);
    }

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
            $formatted = $this->useCustomCurrencyFormatter($price, $currency);
        } else {
            $formatted = $this->useDefaultCurrencyFormatter($price, $currency);
        }

        return $this->replaceEasternArabicDigits($formatted);
    }

    /**
     * Use default formatter.
     */
    public function useDefaultCurrencyFormatter(?float $price, Currency $currency): string
    {
        $locale = app()->getLocale().'@numbers=latn';
        $formatter = new \NumberFormatter($locale, \NumberFormatter::CURRENCY);

        if (! is_null($currency->decimal)) {
            $formatter->setAttribute(\NumberFormatter::FRACTION_DIGITS, (int) $currency->decimal);
        }

        if ($currency->symbol) {
            if ($this->currencySymbol($currency) == $currency->symbol && is_null($currency->decimal)) {
                return $this->replaceEasternArabicDigits($formatter->formatCurrency($price, $currency->code));
            }

            $formatter->setSymbol(\NumberFormatter::CURRENCY_SYMBOL, $currency->symbol);

            return $this->replaceEasternArabicDigits($formatter->format($price));
        }

        return $this->replaceEasternArabicDigits($formatter->formatCurrency($price, $currency->code));
    }

    /**
     * Use custom formatter.
     */
    public function useCustomCurrencyFormatter(?float $price, Currency $currency): string
    {
        $locale = app()->getLocale().'@numbers=latn';
        $formatter = new \NumberFormatter($locale, \NumberFormatter::CURRENCY);

        $formatter->setSymbol(\NumberFormatter::CURRENCY_SYMBOL, '');

        $decimalDigits = ! is_null($currency->decimal) ? (int) $currency->decimal : 2;

        $formatter->setAttribute(\NumberFormatter::FRACTION_DIGITS, $decimalDigits);

        $formattedCurrency = preg_replace('/^\s+|\s+$/u', '', $formatter->format($price));
        $formattedCurrency = $this->replaceEasternArabicDigits($formattedCurrency);

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

        $result = match ($currency->currency_position) {
            CurrencyPositionEnum::LEFT->value => $symbol.$formattedCurrency,
            CurrencyPositionEnum::LEFT_WITH_SPACE->value => $symbol.' '.$formattedCurrency,
            CurrencyPositionEnum::RIGHT->value => $formattedCurrency.$symbol,
            CurrencyPositionEnum::RIGHT_WITH_SPACE->value => $formattedCurrency.' '.$symbol,
            default => $formattedCurrency.' '.$symbol,
        };

        return $this->replaceEasternArabicDigits($result);
    }

    /**
     * Return currency symbol from currency code.
     *
     * @param  string|Currency  $currency
     */
    public function currencySymbol($currency): string
    {
        $code = $currency instanceof Currency ? $currency->code : $currency;
        $locale = app()->getLocale().'@currency='.$code;

        $formatter = new \NumberFormatter($locale, \NumberFormatter::CURRENCY);

        return $formatter->getSymbol(\NumberFormatter::CURRENCY_SYMBOL);
    }
}
