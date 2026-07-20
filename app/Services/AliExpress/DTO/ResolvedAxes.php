<?php

namespace App\Services\AliExpress\DTO;

use Webkul\Attribute\Models\Attribute;

/**
 * Result of resolving normalized axes against Bagisto's EAV attributes:
 * find-or-created configurable attributes, their option ids, and lookups
 * used for per-variant assignment.
 */
final class ResolvedAxes
{
    /**
     * @param  array<string, int[]>  $superAttributes  [attributeCode => [optionId, ...]].
     * @param  array<string, array<string, int>>  $optionIdLookup  [attributeCode][optionLabel] => optionId.
     * @param  array<string, Attribute>  $attributesByCode  [attributeCode => Attribute model].
     */
    public function __construct(
        public array $superAttributes,
        public array $optionIdLookup,
        public array $attributesByCode,
    ) {}
}
