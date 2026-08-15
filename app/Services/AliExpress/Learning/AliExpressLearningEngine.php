<?php

namespace App\Services\AliExpress\Learning;

use App\Models\AliExpress\AliExpressCategoryMapping;
use App\Models\AliExpress\AliExpressKeywordWeight;
use App\Services\AliExpress\DTO\NormalizedProduct;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class AliExpressLearningEngine
{
    public function __construct(
        protected AliExpressTextTokenizer $tokenizer,
    ) {}

    /**
     * Learn from a product and its assigned store category.
     */
    public function learnFromProduct(NormalizedProduct|array $product, int $categoryId, string $source = 'admin'): void
    {
        if ($categoryId <= 0) {
            return;
        }

        try {
            DB::transaction(function () use ($product, $categoryId, $source) {
                $aeCategoryId = $product instanceof NormalizedProduct
                    ? $product->aliexpressCategoryId
                    : ($product['aliexpress_category_id'] ?? null);

                $title = $product instanceof NormalizedProduct
                    ? $product->title
                    : ($product['title'] ?? $product['name'] ?? '');

                // 1. Learn Category ID Mapping if AliExpress Category ID exists
                if ($aeCategoryId !== null && $aeCategoryId > 0) {
                    $this->learnCategoryMapping((int) $aeCategoryId, $categoryId);
                }

                // 2. Learn Keywords and N-Grams
                if (! empty($title)) {
                    $this->learnKeywords((string) $title, $categoryId);
                }

                Log::channel('aliexpress')->info('Categorization Engine learned from product', [
                    'category_id' => $categoryId,
                    'ae_category_id' => $aeCategoryId,
                    'source' => $source,
                ]);
            });
        } catch (Throwable $e) {
            Log::channel('aliexpress')->warning('LearningEngine failed to learn from product', [
                'message' => $e->getMessage(),
                'category_id' => $categoryId,
            ]);
        }
    }

    /**
     * Update or create learned AliExpress Category ID mapping.
     */
    protected function learnCategoryMapping(int $aeCategoryId, int $targetCategoryId): void
    {
        $mapping = AliExpressCategoryMapping::where('aliexpress_category_id', $aeCategoryId)
            ->where('target_category_id', $targetCategoryId)
            ->first();

        if ($mapping) {
            $mapping->hits_count += 1;
            // Higher hits -> higher confidence, asymptoting towards 1.00
            $mapping->confidence_score = min(1.00, 0.60 + ($mapping->hits_count * 0.08));
            $mapping->last_learned_at = now();
            $mapping->save();
        } else {
            AliExpressCategoryMapping::create([
                'aliexpress_category_id' => $aeCategoryId,
                'target_category_id' => $targetCategoryId,
                'hits_count' => 1,
                'confidence_score' => 0.70,
                'last_learned_at' => now(),
            ]);
        }
    }

    /**
     * Extract tokens/n-grams from title and update their category weights.
     */
    protected function learnKeywords(string $text, int $categoryId): void
    {
        $tokens = $this->tokenizer->extractTokens($text);

        foreach ($tokens as $token) {
            $len = mb_strlen($token, 'UTF-8');
            if ($len < 2 || $len > 100) {
                continue;
            }

            // Word count bonus (bi-grams and tri-grams are more specific and descriptive)
            $wordCount = count(explode(' ', $token));
            $ngramMultiplier = match ($wordCount) {
                2 => 1.5,
                3 => 2.0,
                default => 1.0,
            };

            $kw = AliExpressKeywordWeight::where('keyword', $token)
                ->where('category_id', $categoryId)
                ->first();

            if ($kw) {
                $kw->frequency += 1;
                $kw->weight = ($kw->frequency * $ngramMultiplier);
                $kw->save();
            } else {
                AliExpressKeywordWeight::create([
                    'keyword' => $token,
                    'category_id' => $categoryId,
                    'frequency' => 1,
                    'weight' => (1.0 * $ngramMultiplier),
                ]);
            }
        }
    }
}
