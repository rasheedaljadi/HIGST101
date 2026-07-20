<?php

namespace App\Services\AliExpress\DTO;

/**
 * A single configurable axis (e.g. "Color", "Size") derived from the
 * AliExpress SKU property definitions.
 */
final class NormalizedVariantAxis
{
    /**
     * @param  string  $name  AliExpress property name, e.g. "Color".
     * @param  string  $code  Normalized Bagisto attribute code, e.g. "ae_color".
     * @param  string[]  $values  Distinct option labels for this axis.
     */
    public function __construct(
        public string $name,
        public string $code,
        public array $values,
    ) {}
}
