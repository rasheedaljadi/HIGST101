<?php

namespace App\Services\AliExpress\Learning;

use App\Models\AliExpress\AliExpressCategoryMapping;
use App\Models\AliExpress\AliExpressKeywordWeight;
use App\Services\AliExpress\DTO\NormalizedProduct;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class AliExpressInferenceEngine
{
    /**
     * Minimum cumulative score required for keyword-based category prediction.
     */
    public const KEYWORD_CONFIDENCE_THRESHOLD = 1.50;

    public function __construct(
        protected AliExpressTextTokenizer $tokenizer,
    ) {}

    /**
     * Predict the target store category for an AliExpress product based on
     * learned category bridges and statistical keyword/n-gram weights.
     */
    public function predictCategory(NormalizedProduct|array $product): ?int
    {
        try {
            $aeCategoryId = $product instanceof NormalizedProduct
                ? $product->aliexpressCategoryId
                : ($product['aliexpress_category_id'] ?? null);

            $title = $product instanceof NormalizedProduct
                ? $product->title
                : ($product['title'] ?? $product['name'] ?? '');

            // 1. Check Learned AliExpress Category ID Bridge (Highest precision)
            if ($aeCategoryId !== null && $aeCategoryId > 0) {
                $predictedId = $this->predictFromCategoryMapping((int) $aeCategoryId);
                if ($predictedId !== null) {
                    Log::channel('aliexpress')->info('InferenceEngine predicted category from learned AE category bridge', [
                        'ae_category_id' => $aeCategoryId,
                        'predicted_category_id' => $predictedId,
                    ]);

                    return $predictedId;
                }
            }

            // 2. Predict from Learned Keywords and N-Gram Weights
            if (! empty($title)) {
                $predictedId = $this->predictFromKeywords((string) $title);
                if ($predictedId !== null) {
                    Log::channel('aliexpress')->info('InferenceEngine predicted category from learned keyword weights', [
                        'title_sample' => mb_substr($title, 0, 50),
                        'predicted_category_id' => $predictedId,
                    ]);

                    return $predictedId;
                }
            }
        } catch (Throwable $e) {
            Log::channel('aliexpress')->warning('InferenceEngine prediction failed', [
                'message' => $e->getMessage(),
            ]);
        }

        return null;
    }

    /**
     * Look up learned category mappings for an AliExpress category ID.
     */
    protected function predictFromCategoryMapping(int $aeCategoryId): ?int
    {
        $mapping = AliExpressCategoryMapping::where('aliexpress_category_id', $aeCategoryId)
            ->where('confidence_score', '>=', 0.60)
            ->orderByDesc('hits_count')
            ->orderByDesc('confidence_score')
            ->first();

        if ($mapping && $this->categoryExists((int) $mapping->target_category_id)) {
            return (int) $mapping->target_category_id;
        }

        return null;
    }

    /**
     * Calculate probabilistic match scores for all store categories based on title tokens.
     */
    protected function predictFromKeywords(string $title): ?int
    {
        $tokens = $this->tokenizer->extractTokens($title);

        if (empty($tokens)) {
            return null;
        }

        // Query all matching keywords in batches
        $matches = AliExpressKeywordWeight::whereIn('keyword', $tokens)
            ->select('category_id', DB::raw('SUM(weight) as total_score'), DB::raw('COUNT(*) as matched_tokens_count'))
            ->groupBy('category_id')
            ->orderByDesc('total_score')
            ->get();

        if ($matches->isEmpty()) {
            return null;
        }

        $topMatch = $matches->first();

        // Check if top match meets the minimum confidence threshold
        if ($topMatch->total_score >= self::KEYWORD_CONFIDENCE_THRESHOLD) {
            $catId = (int) $topMatch->category_id;
            if ($this->categoryExists($catId)) {
                return $catId;
            }
        }

        return null;
    }

    /**
     * Ensure the target category still exists in the database.
     */
    protected function categoryExists(int $categoryId): bool
    {
        return DB::table('categories')->where('id', $categoryId)->exists();
    }
}
