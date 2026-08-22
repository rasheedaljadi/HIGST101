<?php

namespace App\Services\AliExpress\Semantic;

use App\Models\AliExpress\SemanticAttributeMemory as MemoryModel;
use App\Services\AliExpress\DTO\NormalizedVariantAxis;
use App\Services\AliExpress\Semantic\DTO\ClassifiedAxis;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Layer 4: Semantic Attribute Memory.
 *
 * A persistent cache that stores previously classified axis patterns.
 * When the same set of values (same SHA-256 signature) is encountered
 * again, the memory returns the cached classification instantly — no
 * need to re-run the classifier pipeline.
 *
 * The memory uses a two-tier lookup:
 *  1. Exact match: same signature + axis code + category context
 *  2. Global match: same signature with category_context = 'global'
 *     (for universally recognizable patterns like "128GB", "5000mAh")
 */
class SemanticAttributeMemory
{
    /** Minimum confidence to trust an exact-context cache hit. */
    protected const EXACT_CONFIDENCE_THRESHOLD = 0.80;

    /** Minimum confidence to trust a global (context-free) cache hit. */
    protected const GLOBAL_CONFIDENCE_THRESHOLD = 0.90;

    /** Semantic types that are globally recognizable (context-independent). */
    protected const GLOBAL_SEMANTIC_TYPES = [
        'storage_capacity',
        'ram_storage_combo',
        'battery_capacity',
        'power_wattage',
        'voltage',
        'screen_size',
        'liquid_volume',
        'quantity_pack',
        'plug_type',
        'dimension',
        'shoe_size',
        'apparel_size',
        'origin_country',
    ];

    /**
     * Look up a previously classified axis in the memory.
     *
     * @param  string  $axisCode  The normalized axis code (e.g. ae_size).
     * @param  string[]  $values  The axis option values.
     * @param  ?int  $categoryId  The store category ID (null = unknown).
     */
    public function lookup(string $axisCode, array $values, ?int $categoryId): ?ClassifiedAxis
    {
        if ($values === []) {
            return null;
        }

        try {
            $signature = MemoryModel::generateSignature($values);
            $categoryContext = $categoryId !== null ? (string) $categoryId : null;

            // 1. Exact match: same signature + axis + category
            if ($categoryContext !== null) {
                $exact = MemoryModel::where('value_signature', $signature)
                    ->where('original_axis_code', $axisCode)
                    ->where('category_context', $categoryContext)
                    ->first();

                if ($exact && $exact->confidence >= self::EXACT_CONFIDENCE_THRESHOLD) {
                    $this->recordHit($exact);

                    return $this->toClassifiedAxis($exact, $values, $axisCode);
                }
            }

            // 2. Global match: same signature regardless of category
            $global = MemoryModel::where('value_signature', $signature)
                ->where('original_axis_code', $axisCode)
                ->where('category_context', 'global')
                ->first();

            if ($global && $global->confidence >= self::GLOBAL_CONFIDENCE_THRESHOLD) {
                $this->recordHit($global);

                return $this->toClassifiedAxis($global, $values, $axisCode);
            }

            return null;
        } catch (Throwable $e) {
            Log::channel('aliexpress')->warning('SemanticAttributeMemory lookup failed', [
                'message' => $e->getMessage(),
                'axis_code' => $axisCode,
            ]);

            return null;
        }
    }

    /**
     * Store a new classification result in the memory.
     *
     * @param  NormalizedVariantAxis  $axis  The original axis.
     * @param  ClassifiedAxis  $result  The classification result.
     * @param  ?int  $categoryId  The store category ID.
     * @param  string  $classifiedBy  Which classifier produced this.
     */
    public function store(
        NormalizedVariantAxis $axis,
        ClassifiedAxis $result,
        ?int $categoryId,
        string $classifiedBy,
    ): void {
        try {
            $signature = MemoryModel::generateSignature($axis->values);

            // Determine if this should be stored as global or category-specific
            $isGlobal = in_array($result->semanticType, self::GLOBAL_SEMANTIC_TYPES, true);
            $categoryContext = $isGlobal ? 'global' : ($categoryId !== null ? (string) $categoryId : 'global');

            MemoryModel::updateOrCreate(
                [
                    'value_signature' => $signature,
                    'original_axis_code' => $axis->code,
                    'category_context' => $categoryContext,
                ],
                [
                    'semantic_type' => $result->semanticType,
                    'arabic_label' => $result->arabicLabel,
                    'english_label' => $result->englishLabel,
                    'classified_by' => $classifiedBy,
                    'confidence' => $result->confidence,
                    'last_hit_at' => now(),
                ],
            );
        } catch (Throwable $e) {
            // Memory storage is non-critical — log and move on
            Log::channel('aliexpress')->warning('SemanticAttributeMemory store failed', [
                'message' => $e->getMessage(),
                'axis_code' => $axis->code,
                'semantic_type' => $result->semanticType,
            ]);
        }
    }

    /**
     * Increment the hit counter for a memory record.
     */
    protected function recordHit(MemoryModel $record): void
    {
        try {
            $record->hits_count += 1;
            $record->last_hit_at = now();
            $record->save();
        } catch (Throwable) {
            // Non-critical
        }
    }

    /**
     * Convert a memory record back into a ClassifiedAxis DTO.
     */
    protected function toClassifiedAxis(MemoryModel $record, array $values, string $axisCode): ClassifiedAxis
    {
        return new ClassifiedAxis(
            originalAxis: new NormalizedVariantAxis(
                name: $record->english_label,
                code: $axisCode,
                values: $values,
            ),
            semanticType: $record->semantic_type,
            arabicLabel: $record->arabic_label,
            englishLabel: $record->english_label,
            confidence: $record->confidence,
            classifiedBy: 'memory',
        );
    }
}
