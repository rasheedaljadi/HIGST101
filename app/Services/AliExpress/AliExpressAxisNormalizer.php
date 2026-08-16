<?php

namespace App\Services\AliExpress;

use Illuminate\Support\Str;

class AliExpressAxisNormalizer
{
    /**
     * Confirmed canonical aliases mapped to their exact canonical attribute code.
     * Only strictly confirmed aliases are included. Compound/ambiguous names
     * (e.g., color_pattern, color_type, color_classification) are intentionally
     * excluded and keep their own distinct attribute code.
     *
     * @var array<string, string>
     */
    protected static array $canonicalCodeMap = [
        // Colors
        'color' => 'ae_color',
        'colour' => 'ae_color',
        'colors' => 'ae_color',
        'color_name' => 'ae_color',
        'colour_name' => 'ae_color',
        'اللون' => 'ae_color',

        // Sizes
        'size' => 'ae_size',
        'sizes' => 'ae_size',
        'size_name' => 'ae_size',
        'apparel_size' => 'ae_size',
        'المقاس' => 'ae_size',

        // Shoe Sizes
        'shoe_size' => 'ae_shoe_size',
        'shoes_size' => 'ae_shoe_size',
        'مقاس_الحذاء' => 'ae_shoe_size',

        // Ships From
        'ships_from' => 'ae_ships_from',
        'shipping_from' => 'ae_ships_from',
        'dispatched_from' => 'ae_ships_from',
        'يشحن_من' => 'ae_ships_from',
        'يُشحن_من' => 'ae_ships_from',

        // Plug Types
        'plug' => 'ae_plug_type',
        'plug_type' => 'ae_plug_type',
        'socket_type' => 'ae_plug_type',
        'نوع_القابس' => 'ae_plug_type',
    ];

    /**
     * Canonical display names for known canonical codes.
     *
     * @var array<string, string>
     */
    protected static array $canonicalDisplayNameMap = [
        'ae_color' => 'Color',
        'ae_size' => 'Size',
        'ae_shoe_size' => 'Shoe Size',
        'ae_ships_from' => 'Ships From',
        'ae_plug_type' => 'Plug Type',
    ];

    /**
     * Normalize a raw axis name to a canonical attribute code.
     * Returns null if $rawName is null, empty, or normalizes to an empty string.
     */
    public static function normalizeAxisCode(?string $rawName, string $prefix = 'ae_'): ?string
    {
        if ($rawName === null) {
            return null;
        }

        $trimmed = trim($rawName);
        if ($trimmed === '') {
            return null;
        }

        // Step 1: Strip outer prefix if already present for idempotency
        $cleanPrefix = rtrim($prefix, '_').'_';
        if (str_starts_with(strtolower($trimmed), $cleanPrefix)) {
            $trimmed = substr($trimmed, strlen($cleanPrefix));
        }

        // Step 2: Unicode & Symbol normalization
        // Replace brackets, parentheses, slashes, punctuation with spaces to avoid accidental word concatenation
        $sanitized = preg_replace('/[()\[\]{}\/\\\\:,;._\-+*#@!$%^&=|<>?~`]+/', ' ', $trimmed);
        if ($sanitized === null) {
            $sanitized = $trimmed;
        }

        // Collapse multiple whitespaces and convert to lowercase for comparison
        $normalizedSpaces = trim(preg_replace('/\s+/', ' ', $sanitized));
        if ($normalizedSpaces === '') {
            return null;
        }

        $keyWithUnderscore = mb_strtolower(str_replace(' ', '_', $normalizedSpaces), 'UTF-8');

        // Step 3: Strict whitelist lookup before fallback to slug
        if (isset(self::$canonicalCodeMap[$keyWithUnderscore])) {
            return self::$canonicalCodeMap[$keyWithUnderscore];
        }

        // Check if raw trimmed Arabic string matches directly
        $arabicKey = mb_strtolower(str_replace(' ', '_', $trimmed), 'UTF-8');
        if (isset(self::$canonicalCodeMap[$arabicKey])) {
            return self::$canonicalCodeMap[$arabicKey];
        }

        // Step 4: Fallback to slug for unknown attributes
        $slug = Str::slug($trimmed, '_');
        if ($slug === '') {
            $slug = Str::slug($normalizedSpaces, '_', 'en');
        }

        if ($slug === '') {
            return null;
        }

        return $cleanPrefix.$slug;
    }

    /**
     * Get the canonical display name for an axis name or canonical code.
     */
    public static function getCanonicalDisplayName(?string $rawName, ?string $canonicalCode = null): ?string
    {
        if ($rawName === null && $canonicalCode === null) {
            return null;
        }

        $code = $canonicalCode ?? self::normalizeAxisCode($rawName);
        if ($code === null) {
            return null;
        }

        if (isset(self::$canonicalDisplayNameMap[$code])) {
            return self::$canonicalDisplayNameMap[$code];
        }

        return trim($rawName ?? '');
    }

    /**
     * Check if a raw axis name matches any known canonical alias.
     */
    public static function isCanonicalAlias(?string $rawName): bool
    {
        if ($rawName === null || trim($rawName) === '') {
            return false;
        }

        $code = self::normalizeAxisCode($rawName);

        return $code !== null && isset(self::$canonicalDisplayNameMap[$code]);
    }
}
