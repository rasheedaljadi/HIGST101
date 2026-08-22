<?php

namespace App\Models\AliExpress;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * Persistent semantic memory for classified variant axes.
 *
 * Each row records a previously seen (value_signature, axis_code, category)
 * combination and the semantic classification that was determined for it.
 * On subsequent imports, the engine looks up this table first to avoid
 * re-running the classifier pipeline — providing instant, consistent results.
 *
 * @property int $id
 * @property string $value_signature SHA-256 of sorted cleaned values
 * @property string $original_axis_code Original axis code (e.g. ae_size)
 * @property ?string $category_context Store category slug or 'global'
 * @property string $semantic_type e.g. storage_capacity, color, shoe_size
 * @property string $arabic_label Proper Arabic label
 * @property string $english_label Proper English label
 * @property string $classified_by pattern | visual | contextual | admin_override
 * @property float $confidence 0.00 — 1.00
 * @property int $hits_count Number of times this classification was used
 * @property ?Carbon $last_hit_at
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class SemanticAttributeMemory extends Model
{
    protected $table = 'semantic_attribute_memory';

    protected $fillable = [
        'value_signature',
        'original_axis_code',
        'category_context',
        'semantic_type',
        'arabic_label',
        'english_label',
        'classified_by',
        'confidence',
        'hits_count',
        'last_hit_at',
    ];

    protected function casts(): array
    {
        return [
            'confidence' => 'float',
            'hits_count' => 'integer',
            'last_hit_at' => 'datetime',
        ];
    }

    /**
     * Generate a deterministic SHA-256 signature from an array of values.
     *
     * Values are lowercased, trimmed, sorted alphabetically, then hashed.
     * This ensures the same set of values always produces the same signature
     * regardless of ordering or whitespace variations.
     *
     * @param  string[]  $values
     */
    public static function generateSignature(array $values): string
    {
        $cleaned = array_map(
            fn (string $v) => mb_strtolower(trim($v), 'UTF-8'),
            $values,
        );

        sort($cleaned, SORT_STRING);

        return hash('sha256', implode('|', $cleaned));
    }
}
