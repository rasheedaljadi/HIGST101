<?php

namespace App\Services\AliExpress\Semantic;

use App\Services\AliExpress\DTO\NormalizedVariantAxis;
use App\Services\AliExpress\Semantic\DTO\ClassifiedAxis;

/**
 * Layer 3: Contextual Semantic Classifier.
 *
 * When the pattern classifier (Layer 1) and visual classifier (Layer 2)
 * both fail to classify an axis, this layer uses linguistic analysis of
 * the option values combined with product context (title, category) to
 * determine the axis meaning.
 *
 * This handles cases like:
 *  - "With Camera" / "No Camera"  → feature_toggle
 *  - "Standard" / "Pro" / "Deluxe"  → edition
 *  - "Global Version" / "CN Version"  → region_version
 *  - "Left" / "Right" / "Front"  → position_variant
 *  - "Pack of 3" / "Set of 5"  → bundle_variant
 */
class ContextualSemanticClassifier
{
    /**
     * Toggle patterns: With X / Without X / No X / X included.
     *
     * @var string[]
     */
    protected const TOGGLE_PATTERNS = [
        '/^with\s+/i',
        '/^without\s+/i',
        '/^no\s+/i',
        '/\s+included$/i',
        '/^مع\s+/u',
        '/^بدون\s+/u',
        '/^بلا\s+/u',
    ];

    /**
     * Edition patterns: Standard / Pro / Premium / Deluxe.
     */
    protected const EDITION_PATTERNS = [
        '/^(standard|basic|pro|premium|deluxe|elite|ultimate|lite|plus|max|ultra|mini)\s*(edition|version)?$/i',
        '/^(الإصدار\s+)?(القياسي|الاحترافي|المتقدم|الفاخر|الأساسي)/u',
    ];

    /**
     * Bundle patterns: Pack of N / Set of N / N in 1.
     */
    protected const BUNDLE_PATTERNS = [
        '/^(pack|set|kit|bundle|combo)\s*(of\s*)?\d+$/i',
        '/^\d+\s*(in\s*1|pcs?\s+set|piece\s+set)/i',
        '/^\d+\s*(قطع|قطعة)\s*(طقم)?/u',
    ];

    /**
     * Region / version patterns: Global / CN / EU / US version.
     */
    protected const REGION_PATTERNS = [
        '/^(global|international|cn|china|eu|european|us|uk|ru|russian|india|korean?)\s*(version|rom|firmware|edition)?$/i',
        '/^(النسخة\s+)?(العالمية|الصينية|الأوروبية|الأمريكية)/u',
    ];

    /**
     * Position patterns: Left / Right / Front / Rear.
     */
    protected const POSITION_PATTERNS = [
        '/^(left|right|front|rear|back|upper|lower|top|bottom|inner|outer|middle|center)\s*(side|hand)?$/i',
        '/^(يمين|يسار|أمامي|خلفي|علوي|سفلي|داخلي|خارجي|أوسط)/u',
    ];

    /**
     * Flavor patterns: common food/drink flavor words.
     */
    protected const FLAVOR_WORDS = [
        'vanilla', 'chocolate', 'strawberry', 'mint', 'lemon', 'coffee',
        'caramel', 'blueberry', 'mango', 'grape', 'cherry', 'apple',
        'banana', 'peach', 'watermelon', 'melon', 'honey', 'cinnamon',
        'ginger', 'matcha', 'green tea', 'original', 'unflavored',
        'فانيلا', 'شوكولاتة', 'فراولة', 'نعناع', 'ليمون', 'قهوة',
        'توت', 'مانجو', 'عنب', 'كرز', 'تفاح', 'موز', 'بطيخ', 'عسل',
    ];

    /**
     * Finish / texture patterns (cosmetics, materials).
     */
    protected const FINISH_WORDS = [
        'matte', 'glossy', 'shimmer', 'satin', 'velvet', 'metallic',
        'glitter', 'frosted', 'mirror', 'brushed', 'polished', 'natural',
        'مطفأ', 'لامع', 'ساتان', 'مخملي', 'معدني', 'طبيعي',
    ];

    /**
     * Classify an axis using linguistic and contextual analysis.
     *
     * Unlike Layers 1 and 2, this classifier always returns a result
     * (falling back to 'generic_variant' when nothing specific matches),
     * because it is the last classifier in the pipeline.
     */
    public function classify(
        NormalizedVariantAxis $axis,
        string $productTitle,
        ?int $categoryId = null,
    ): ClassifiedAxis {
        $values = $axis->values;

        if ($values === []) {
            return $this->buildGeneric($axis);
        }

        // ── 1. Check universal linguistic patterns (category-independent) ──

        // Toggle: With/Without
        if ($this->majorityMatchesAny($values, self::TOGGLE_PATTERNS)) {
            return $this->buildResult($axis, 'feature_toggle', 'الإصدار / الباقة', 'Edition / Bundle', 0.90);
        }

        // Edition: Standard/Pro/Premium
        if ($this->majorityMatchesAny($values, self::EDITION_PATTERNS)) {
            return $this->buildResult($axis, 'edition', 'الإصدار', 'Edition', 0.85);
        }

        // Bundle: Pack of N / Set
        if ($this->majorityMatchesAny($values, self::BUNDLE_PATTERNS)) {
            return $this->buildResult($axis, 'bundle_variant', 'الباقة / المجموعة', 'Bundle / Set', 0.85);
        }

        // Region version: Global/CN/EU
        if ($this->majorityMatchesAny($values, self::REGION_PATTERNS)) {
            return $this->buildResult($axis, 'region_version', 'الإصدار الإقليمي', 'Region Version', 0.90);
        }

        // Position: Left/Right/Front
        if ($this->majorityMatchesAny($values, self::POSITION_PATTERNS)) {
            return $this->buildResult($axis, 'position_variant', 'الموضع / الاتجاه', 'Position', 0.90);
        }

        // ── 2. Check word-list based classifications ───────────────────

        // Flavor words
        if ($this->majorityContainsWords($values, self::FLAVOR_WORDS)) {
            return $this->buildResult($axis, 'flavor', 'النكهة', 'Flavor', 0.85);
        }

        // Finish / texture words
        if ($this->majorityContainsWords($values, self::FINISH_WORDS)) {
            return $this->buildResult($axis, 'finish_type', 'اللمسة النهائية', 'Finish Type', 0.80);
        }

        // ── 3. Check if values look like model names or descriptors ────
        // Single-value axes that made it here are likely feature descriptors
        if (count($values) === 1) {
            return $this->buildResult($axis, 'feature_descriptor', 'المواصفات', 'Specification', 0.70);
        }

        // ── 4. Fallback: generic variant ───────────────────────────────
        return $this->buildGeneric($axis);
    }

    /**
     * Check if ≥50% of values match ANY of the given regex patterns.
     */
    protected function majorityMatchesAny(array $values, array $patterns): bool
    {
        $matchCount = 0;
        $total = count($values);

        foreach ($values as $value) {
            $cleaned = trim($value);

            foreach ($patterns as $pattern) {
                if (preg_match($pattern, $cleaned)) {
                    $matchCount++;

                    break;
                }
            }
        }

        return $total > 0 && ($matchCount / $total) >= 0.50;
    }

    /**
     * Check if ≥50% of values contain words from a word list.
     */
    protected function majorityContainsWords(array $values, array $words): bool
    {
        $matchCount = 0;
        $total = count($values);

        foreach ($values as $value) {
            $normalized = mb_strtolower(trim($value), 'UTF-8');

            foreach ($words as $word) {
                if (str_contains($normalized, $word)) {
                    $matchCount++;

                    break;
                }
            }
        }

        return $total > 0 && ($matchCount / $total) >= 0.50;
    }

    /**
     * Build a classified result.
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
            confidence: $confidence,
            classifiedBy: 'contextual',
        );
    }

    /**
     * Build a generic fallback classification.
     */
    protected function buildGeneric(NormalizedVariantAxis $axis): ClassifiedAxis
    {
        return new ClassifiedAxis(
            originalAxis: $axis,
            semanticType: 'generic_variant',
            arabicLabel: $axis->name,
            englishLabel: $axis->name,
            confidence: 0.50,
            classifiedBy: 'contextual',
        );
    }
}
