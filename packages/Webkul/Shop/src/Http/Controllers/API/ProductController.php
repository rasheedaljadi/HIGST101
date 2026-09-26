<?php

namespace Webkul\Shop\Http\Controllers\API;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\DB;
use Webkul\Category\Repositories\CategoryRepository;
use Webkul\Marketing\Jobs\UpdateCreateSearchTerm as UpdateCreateSearchTermJob;
use Webkul\Product\Models\ProductFlat;
use Webkul\Product\Repositories\ProductRepository;
use Webkul\Shop\Http\Resources\ProductResource;

class ProductController extends APIController
{
    /**
     * Create a controller instance.
     *
     * @return void
     */
    public function __construct(
        protected CategoryRepository $categoryRepository,
        protected ProductRepository $productRepository
    ) {}

    /**
     * Product listings.
     */
    public function index(): JsonResource
    {
        $searchEngine = 'database';

        if (core()->getConfigData('catalog.products.search.engine') == 'elastic') {
            $searchEngine = core()->getConfigData('catalog.products.search.storefront_mode');
        }

        $searchData = $this->resolveSearchQueryData($searchEngine);

        $query = $searchData['effective_query'] ?? $searchData['original_query'];

        $products = $this->productRepository
            ->setSearchEngine($searchEngine)
            ->getAll(array_merge(request()->query(), [
                'query' => $query,
                'channel_id' => core()->getCurrentChannel()->id,
                'status' => 1,
                'visible_individually' => 1,
            ]));

        if (! empty($query)) {
            /**
             * Update or create search term only if
             * there is only one filter that is query param
             */
            if (count(request()->except(['mode', 'sort', 'limit'])) == 1) {
                UpdateCreateSearchTermJob::dispatch([
                    'term' => $query,
                    'results' => $products->total(),
                    'channel_id' => core()->getCurrentChannel()->id,
                    'locale' => app()->getLocale(),
                ]);
            }
        }

        return ProductResource::collection($products);
    }

    /**
     * Resolve search query data.
     */
    protected function resolveSearchQueryData($searchEngine): array
    {
        if (request()->query('suggest', '') === '0') {
            return [
                'original_query' => request()->query('query', ''),
                'effective_query' => null,
            ];
        }

        $originalQuery = request()->query('query', '');

        return [
            'original_query' => $originalQuery,
            'effective_query' => $this->getEffectiveQuery($originalQuery, $searchEngine),
        ];
    }

    /**
     * It will return the effective query based on the search engine.
     */
    protected function getEffectiveQuery(string $originalQuery, string $searchEngine): ?string
    {
        $effectiveQuery = $this->productRepository->setSearchEngine($searchEngine)->getSuggestions($originalQuery);

        return $effectiveQuery;
    }

    /**
     * Related product listings.
     *
     * @param  int  $id
     */
    public function relatedProducts($id): JsonResource
    {
        $product = $this->productRepository->findOrFail($id);

        $configuredLimit = (int) core()->getConfigData('catalog.products.product_view_page.no_of_related_products');
        $limit = ($configuredLimit > 0 && $configuredLimit <= 20) ? $configuredLimit : 12;

        $relatedProducts = $product->related_products()
            ->take($limit)
            ->get();

        if ($relatedProducts->count() < $limit) {
            $excludeIds = $relatedProducts->pluck('id')->push($product->id)->all();
            $categoryIds = DB::table('product_categories')
                ->where('product_id', $product->id)
                ->pluck('category_id')
                ->toArray();

            if (! empty($categoryIds)) {
                $channel = core()->getCurrentChannel()->code ?? 'default';
                $locale = core()->getCurrentLocale()->code ?? 'ar';

                $flatIds = ProductFlat::where('product_flat.status', 1)
                    ->where('product_flat.visible_individually', 1)
                    ->where('product_flat.channel', $channel)
                    ->where('product_flat.locale', $locale)
                    ->join('product_categories', 'product_categories.product_id', '=', 'product_flat.product_id')
                    ->whereIn('product_categories.category_id', $categoryIds)
                    ->whereNotIn('product_flat.product_id', $excludeIds)
                    ->select('product_flat.product_id')
                    ->distinct()
                    ->limit($limit - $relatedProducts->count())
                    ->pluck('product_id')
                    ->toArray();

                if (! empty($flatIds)) {
                    $backfill = $this->productRepository->findWhereIn('id', $flatIds);
                    $relatedProducts = $relatedProducts->concat($backfill);
                }
            }
        }

        return ProductResource::collection($relatedProducts);
    }

    /**
     * Up-sell product listings.
     *
     * @param  int  $id
     */
    public function upSellProducts($id): JsonResource
    {
        $product = $this->productRepository->findOrFail($id);

        $configuredLimit = (int) core()->getConfigData('catalog.products.product_view_page.no_of_up_sells_products');
        $limit = ($configuredLimit > 0 && $configuredLimit <= 20) ? $configuredLimit : 12;

        $upSellProducts = $product->up_sells()
            ->take($limit)
            ->get();

        if ($upSellProducts->count() < $limit) {
            $excludeIds = $upSellProducts->pluck('id')->push($product->id)->all();
            $relatedIds = $product->related_products()->pluck('id')->toArray();
            $excludeIds = array_unique(array_merge($excludeIds, $relatedIds));

            $categoryIds = DB::table('product_categories')
                ->where('product_id', $product->id)
                ->pluck('category_id')
                ->toArray();

            if (! empty($categoryIds)) {
                $channel = core()->getCurrentChannel()->code ?? 'default';
                $locale = core()->getCurrentLocale()->code ?? 'ar';

                $flatIds = ProductFlat::where('product_flat.status', 1)
                    ->where('product_flat.visible_individually', 1)
                    ->where('product_flat.channel', $channel)
                    ->where('product_flat.locale', $locale)
                    ->join('product_categories', 'product_categories.product_id', '=', 'product_flat.product_id')
                    ->whereIn('product_categories.category_id', $categoryIds)
                    ->whereNotIn('product_flat.product_id', $excludeIds)
                    ->select('product_flat.product_id')
                    ->distinct()
                    ->limit($limit - $upSellProducts->count())
                    ->pluck('product_id')
                    ->toArray();

                if (empty($flatIds) || count($flatIds) < ($limit - $upSellProducts->count())) {
                    $relaxedExclude = $upSellProducts->pluck('id')->push($product->id)->all();
                    $alreadyFetched = array_merge($excludeIds, $flatIds);
                    $moreFlatIds = ProductFlat::where('product_flat.status', 1)
                        ->where('product_flat.visible_individually', 1)
                        ->where('product_flat.channel', $channel)
                        ->where('product_flat.locale', $locale)
                        ->join('product_categories', 'product_categories.product_id', '=', 'product_flat.product_id')
                        ->whereIn('product_categories.category_id', $categoryIds)
                        ->whereNotIn('product_flat.product_id', $alreadyFetched)
                        ->select('product_flat.product_id')
                        ->distinct()
                        ->limit(($limit - $upSellProducts->count()) - count($flatIds))
                        ->pluck('product_id')
                        ->toArray();

                    $flatIds = array_merge($flatIds, $moreFlatIds);
                }

                if (! empty($flatIds)) {
                    $backfill = $this->productRepository->findWhereIn('id', $flatIds);
                    $upSellProducts = $upSellProducts->concat($backfill);
                }
            }
        }

        return ProductResource::collection($upSellProducts);
    }
}
