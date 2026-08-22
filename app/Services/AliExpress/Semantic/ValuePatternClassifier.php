<?php

namespace App\Services\AliExpress\Semantic;

use App\Services\AliExpress\DTO\NormalizedVariantAxis;
use App\Services\AliExpress\Semantic\DTO\ClassifiedAxis;

/**
 * Layer 1: Value Pattern Classifier.
 *
 * Classifies variant axes by inspecting the physical/mathematical patterns
 * in their option values. Works universally across all product categories
 * because patterns like "128GB", "5000mAh", "EU Plug" are unambiguous
 * regardless of the axis name the seller chose.
 *
 * The classifier applies a "majority rule": if ≥70% of an axis's values
 * match a single pattern, that pattern wins. This tolerates occasional
 * outlier values (e.g. "Other" among "128GB", "256GB").
 */
class ValuePatternClassifier
{
    /** Minimum percentage of values that must match a pattern to classify. */
    protected const MAJORITY_THRESHOLD = 0.70;

    /**
     * Pattern registry: regex => [semanticType, arabicLabel, englishLabel].
     *
     * Order matters — more specific patterns are checked first to avoid
     * false positives (e.g. "8GB+256GB" must match ram_storage_combo
     * before the generic storage_capacity pattern).
     *
     * @var array<string, array{string, string, string}>
     */
    protected const PATTERNS = [
        // RAM + Storage combo (must be before storage_capacity) — matches "8GB 128GB", "8GB+128GB", "16GB/256GB", etc.
        '/^\d+\s*(GB|G)\s*[\+\/\s]\s*\d+\s*(GB|TB|G)\b/i' => ['ram_storage_combo', 'الذاكرة + التخزين', 'RAM + Storage'],

        // Storage capacity
        '/\d+\s*(GB|TB|MB)\b/i' => ['storage_capacity', 'سعة التخزين', 'Storage Capacity'],

        // Battery capacity
        '/\d+\s*mAh\b/i' => ['battery_capacity', 'سعة البطارية', 'Battery Capacity'],

        // Power wattage (exclude mm/cm/kg contexts)
        '/^\d+\.?\d*\s*[Ww]$/i' => ['power_wattage', 'القدرة الكهربائية', 'Power (Watts)'],

        // Voltage
        '/\d+\.?\d*\s*[Vv]\b/i' => ['voltage', 'الجهد الكهربائي', 'Voltage'],

        // Screen / display size (inches)
        '/\d+\.?\d*\s*(inch|inches|"|بوصة)\b/i' => ['screen_size', 'حجم الشاشة', 'Screen Size'],

        // Liquid volume (ml, L, oz)
        '/\d+\.?\d*\s*(ml|ML|مل|liter|litre|L|oz|fl\.?\s*oz)\b/i' => ['liquid_volume', 'حجم العبوة', 'Volume'],

        // Quantity / pack count
        '/^\d+\s*(pcs?|pieces?|قطع|قطعة|pack|set|عبوة)\b/i' => ['quantity_pack', 'عدد القطع', 'Quantity / Pack'],

        // Plug type (exact match)
        '/^(EU|US|UK|AU|CN|KR|JP|BR)\s*(Plug|plug|قابس)?$/i' => ['plug_type', 'نوع القابس', 'Plug Type'],

        // Dimensions (mm, cm, m — when clearly a measurement)
        '/^\d+\.?\d*\s*(mm|cm|m)\b/i' => ['dimension', 'الأبعاد', 'Dimensions'],
    ];

    /**
     * Shoe size detection: numbers 35–48 optionally followed by EU/US/UK.
     */
    protected const SHOE_SIZE_PATTERN = '/^(3[5-9]|4[0-8])\.?\d?\s*(EU|US|UK)?$/i';

    /**
     * Apparel size detection: standard letter codes.
     */
    protected const APPAREL_SIZE_PATTERN = '/^(XXS|XS|S|M|L|XL|XXL|XXXL|[2-6]XL)$/i';

    /**
     * Known color words (a compact set for pattern matching — the full
     * dictionary in AliExpressAttributeDictionary handles translation).
     */
    protected const COLOR_WORDS = [
        'black', 'white', 'red', 'blue', 'green', 'yellow', 'orange', 'pink',
        'purple', 'brown', 'gray', 'grey', 'gold', 'silver', 'beige', 'navy',
        'khaki', 'olive', 'cyan', 'teal', 'maroon', 'ivory', 'coral', 'peach',
        'rose', 'lavender', 'champagne', 'transparent', 'clear', 'rainbow',
        'أسود', 'أبيض', 'أحمر', 'أزرق', 'أخضر', 'أصفر', 'برتقالي', 'وردي',
        'بنفسجي', 'بني', 'رمادي', 'ذهبي', 'فضي',
    ];

    /**
     * Origin / shipping country indicators.
     */
    protected const ORIGIN_PATTERNS = [
        '/^(china|cn|chinese?)\s*(mainland|warehouse)?$/i',
        '/^(russia|russian|ru)\s*(federation|warehouse)?$/i',
        '/^(spain|france|germany|poland|us|usa|uk|brazil|turkey|belgium|czech|italy|saudi|uae|dubai|australia|japan|korea|india|thailand|vietnam|indonesia|malaysia|singapore|philippines)\s*(warehouse)?$/i',
        '/^(الصين|روسيا|إسبانيا|فرنسا|ألمانيا|تركيا|السعودية|الإمارات|دبي)/u',
    ];

    /**
     * Classify an axis by inspecting the values against known patterns.
     *
     * Returns null when no pattern matches with sufficient confidence,
     * allowing the next classifier layer to attempt classification.
     */
    public function classify(NormalizedVariantAxis $axis): ?ClassifiedAxis
    {
        $values = $axis->values;

        if ($values === []) {
            return null;
        }

        $totalValues = count($values);

        // ── 1. Try specific type patterns first ────────────────────────

        // Shoe sizes (35–48 range)
        $shoeSizeMatches = $this->countMatches($values, self::SHOE_SIZE_PATTERN);
        if ($shoeSizeMatches / $totalValues >= self::MAJORITY_THRESHOLD) {
            return $this->buildResult($axis, 'shoe_size', 'مقاس الحذاء', 'Shoe Size', $shoeSizeMatches / $totalValues);
        }

        // Apparel sizes (S/M/L/XL)
        $apparelMatches = $this->countMatches($values, self::APPAREL_SIZE_PATTERN);
        if ($apparelMatches / $totalValues >= self::MAJORITY_THRESHOLD) {
            return $this->buildResult($axis, 'apparel_size', 'المقاس', 'Size', $apparelMatches / $totalValues);
        }

        // Color words
        $colorMatches = $this->countColorMatches($values);
        if ($colorMatches / $totalValues >= self::MAJORITY_THRESHOLD) {
            return $this->buildResult($axis, 'color', 'اللون / الموديل', 'Color / Model', $colorMatches / $totalValues);
        }

        // Origin / shipping country
        $originMatches = $this->countOriginMatches($values);
        if ($originMatches / $totalValues >= self::MAJORITY_THRESHOLD) {
            return $this->buildResult($axis, 'origin_country', 'يُشحن من', 'Ships From', $originMatches / $totalValues);
        }

        // ── 2. Try general measurement/unit patterns ───────────────────

        $patternScores = [];

        foreach (self::PATTERNS as $regex => [$semanticType, $arabicLabel, $englishLabel]) {
            $matchCount = $this->countMatches($values, $regex);

            if ($matchCount > 0) {
                $patternScores[$semanticType] = [
                    'count' => $matchCount,
                    'ratio' => $matchCount / $totalValues,
                    'arabicLabel' => $arabicLabel,
                    'englishLabel' => $englishLabel,
                ];
            }
        }

        if ($patternScores === []) {
            return null;
        }

        // Find the dominant pattern (highest match count)
        uasort($patternScores, fn ($a, $b) => $b['count'] <=> $a['count']);
        $dominant = array_key_first($patternScores);
        $dominantData = $patternScores[$dominant];

        if ($dominantData['ratio'] >= self::MAJORITY_THRESHOLD) {
            return $this->buildResult(
                $axis,
                $dominant,
                $dominantData['arabicLabel'],
                $dominantData['englishLabel'],
                $dominantData['ratio'],
            );
        }

        return null;
    }

    /**
     * Count how many values match a regex pattern.
     */
    protected function countMatches(array $values, string $regex): int
    {
        $count = 0;

        foreach ($values as $value) {
            $cleaned = trim($value);

            if ($cleaned !== '' && preg_match($regex, $cleaned)) {
                $count++;
            }
        }

        return $count;
    }

    /**
     * Count how many values are recognized color words.
     */
    protected function countColorMatches(array $values): int
    {
        $count = 0;

        foreach ($values as $value) {
            $normalized = mb_strtolower(trim($value), 'UTF-8');

            // Direct match
            if (in_array($normalized, self::COLOR_WORDS, true)) {
                $count++;

                continue;
            }

            // Partial match (e.g. "sky blue", "dark green", "wine red")
            foreach (self::COLOR_WORDS as $colorWord) {
                if (str_contains($normalized, $colorWord)) {
                    $count++;

                    break;
                }
            }
        }

        return $count;
    }

    /**
     * Count how many values match shipping origin patterns.
     */
    protected function countOriginMatches(array $values): int
    {
        $count = 0;

        foreach ($values as $value) {
            $cleaned = trim($value);

            foreach (self::ORIGIN_PATTERNS as $pattern) {
                if (preg_match($pattern, $cleaned)) {
                    $count++;

                    break;
                }
            }
        }

        return $count;
    }

    /**
     * Build a ClassifiedAxis result.
     */
    protected function buildResult(
        NormalizedVariantAxis $axis,
        string $semanticType,
        string $arabicLabel,
        string $englishLabel,
        float $confidence,
    ): ClassifiedAxis {
        return new ClassifiedAxis(
            originalAxis: $axis,
            semanticType: $semanticType,
            arabicLabel: $arabicLabel,
            englishLabel: $englishLabel,
            confidence: round($confidence, 2),
            classifiedBy: 'pattern',
        );
    }
}
