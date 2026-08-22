<?php

namespace App\Services\AliExpress\Semantic;

use App\Services\AliExpress\DTO\NormalizedVariant;
use App\Services\AliExpress\DTO\NormalizedVariantAxis;
use App\Services\AliExpress\Semantic\DTO\AxisClassificationResult;
use App\Services\AliExpress\Semantic\DTO\ClassifiedAxis;
use Illuminate\Support\Facades\Log;

/**
 * Semantic Attribute Engine (SAE) — Main Orchestrator.
 *
 * Runs incoming variant axes through a 4-layer classification pipeline:
 *  1. ValuePatternClassifier   — detects GB, mAh, V, shoe sizes, colors, etc.
 *  2. VisualSwatchClassifier   — detects axes with per-value images (visual variants)
 *  3. ContextualSemanticClassifier — linguistic analysis (With/Without, editions, etc.)
 *  4. SemanticAttributeMemory  — persistent cache layer (checked FIRST, stored LAST)
 *
 * After classification, the AxisDecisionEngine separates axes into:
 *  - Purchase axes (shown as interactive buttons)
 *  - Spec axes (shown as static technical specifications)
 *  - Dropped axes (removed entirely — e.g. single-option Ships From)
 *
 * The engine is fully local — no external AI calls, no network latency,
 * no API costs. It runs in <5ms per product without cache, <1ms with cache.
 */
class SemanticAttributeEngine
{
    public function __construct(
        protected ValuePatternClassifier $patternClassifier,
        protected VisualSwatchClassifier $visualClassifier,
        protected ContextualSemanticClassifier $contextualClassifier,
        protected SemanticAttributeMemory $memory,
        protected AxisDecisionEngine $decisionEngine,
    ) {}

    /**
     * Classify and sort variant axes into purchase axes and specifications.
     *
     * @param  NormalizedVariantAxis[]  $axes  Raw axes from the mapper.
     * @param  NormalizedVariant[]  $variants  Product variants (for visual detection).
     * @param  string  $productTitle  Product title (for contextual analysis).
     * @param  ?int  $categoryId  Store category ID (for context-specific rules).
     */
    public function classify(
        array $axes,
        array $variants,
        string $productTitle,
        ?int $categoryId = null,
    ): AxisClassificationResult {
        $classified = [];

        foreach ($axes as $axis) {
            if (! $axis instanceof NormalizedVariantAxis) {
                continue;
            }

            $result = $this->classifySingleAxis($axis, $variants, $productTitle, $categoryId);
            $classified[] = $result;

            Log::channel('aliexpress')->debug('SAE classified axis', [
                'axis_code' => $axis->code,
                'axis_name' => $axis->name,
                'values_count' => count($axis->values),
                'semantic_type' => $result->semanticType,
                'arabic_label' => $result->arabicLabel,
                'confidence' => $result->confidence,
                'classified_by' => $result->classifiedBy,
            ]);
        }

        // Apply decision rules
        $decision = $this->decisionEngine->decide($classified);

        Log::channel('aliexpress')->info('SAE classification complete', [
            'total_axes' => count($axes),
            'purchase_axes' => count($decision->purchaseAxes),
            'spec_axes' => count($decision->specAxes),
            'dropped_axes' => count($decision->droppedAxes),
        ]);

        return $decision;
    }

    /**
     * Run a single axis through the 4-layer pipeline.
     */
    protected function classifySingleAxis(
        NormalizedVariantAxis $axis,
        array $variants,
        string $productTitle,
        ?int $categoryId,
    ): ClassifiedAxis {
        // ── Layer 0: Memory lookup (fastest path) ──────────────────────

        $cached = $this->memory->lookup($axis->code, $axis->values, $categoryId);

        if ($cached !== null) {
            return $cached;
        }

        // ── Layer 1: Pattern signatures ────────────────────────────────

        $result = $this->patternClassifier->classify($axis);

        if ($result !== null) {
            $this->memory->store($axis, $result, $categoryId, 'pattern');

            return $result;
        }

        // ── Layer 2: Visual swatch detection ───────────────────────────

        $result = $this->visualClassifier->classify($axis, $variants);

        if ($result !== null) {
            $this->memory->store($axis, $result, $categoryId, 'visual');

            return $result;
        }

        // ── Layer 3: Contextual semantic analysis (always returns) ─────

        $result = $this->contextualClassifier->classify($axis, $productTitle, $categoryId);
        $this->memory->store($axis, $result, $categoryId, 'contextual');

        return $result;
    }
}
