<?php

namespace Webkul\FlashDeal\Http\Controllers\Admin;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Webkul\FlashDeal\DataGrids\FlashDealDataGrid;
use Webkul\FlashDeal\Repositories\FlashDealProductRepository;
use Webkul\FlashDeal\Repositories\FlashDealRepository;
use Webkul\Product\Repositories\ProductRepository;

class FlashDealController extends Controller
{
    public function __construct(
        protected FlashDealRepository $flashDealRepository,
        protected FlashDealProductRepository $flashDealProductRepository,
        protected ProductRepository $productRepository
    ) {}

    /**
     * Display a listing of flash deals.
     */
    public function index()
    {
        if (request()->ajax()) {
            return app(FlashDealDataGrid::class)->toJson();
        }

        return view('flash_deal::admin.index');
    }

    /**
     * Show the form for creating a new flash deal.
     */
    public function create(): View
    {
        return view('flash_deal::admin.create');
    }

    /**
     * Store a newly created flash deal metadata.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'status' => 'required|boolean',
            'starts_at' => 'required|date',
            'ends_at' => 'required|date|after:starts_at',
        ]);

        $deal = $this->flashDealRepository->create([
            'title' => $validated['title'],
            'status' => $validated['status'],
            'starts_at' => $validated['starts_at'],
            'ends_at' => $validated['ends_at'],
        ]);

        session()->flash('success', 'تم إنشاء العرض السريع بنجاح. يمكنك الآن إضافة المنتجات عبر زر إدارة المنتجات.');

        return redirect()->route('admin.marketing.promotions.flash_deals.index');
    }

    /**
     * Show the form for editing the specified flash deal metadata.
     */
    public function edit(int $id): View
    {
        $deal = $this->flashDealRepository->findOrFail($id);

        return view('flash_deal::admin.edit', compact('deal'));
    }

    /**
     * Update the specified flash deal metadata.
     */
    public function update(Request $request, int $id): RedirectResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'status' => 'required|boolean',
            'starts_at' => 'required|date',
            'ends_at' => 'required|date|after:starts_at',
        ]);

        $this->flashDealRepository->update([
            'title' => $validated['title'],
            'status' => $validated['status'],
            'starts_at' => $validated['starts_at'],
            'ends_at' => $validated['ends_at'],
        ], $id);

        session()->flash('success', 'تم تحديث العرض السريع بنجاح.');

        return redirect()->route('admin.marketing.promotions.flash_deals.index');
    }

    /**
     * Get allocated products for a specific flash deal in JSON for modal popup.
     */
    public function getDealProducts(int $id): JsonResponse
    {
        $deal = $this->flashDealRepository->with(['products.product'])->findOrFail($id);

        $dealProducts = $deal->products->map(function ($dp) {
            $product = $dp->product;
            if (! $product) {
                return null;
            }

            $image = product_image()->getProductBaseImage($product)['medium_image_url'] ?? bagisto_asset('images/small-product-placeholder.webp', 'shop');
            $cleanName = preg_replace('/[^\p{L}\p{N}\s\-\_]/u', '', $product->name ?? '');
            $cleanName = trim($cleanName);
            if (empty($cleanName)) {
                $cleanName = urldecode($product->url_key ?? '');
                $cleanName = trim(str_replace(['-', '_'], ' ', $cleanName));
            }
            if (empty($cleanName)) {
                $cleanName = 'منتج #'.$product->id;
            }

            $price = $product->type === 'configurable' ? $product->getTypeInstance()->getMinimalPrice() : $product->price;

            return [
                'id' => $dp->id,
                'product_id' => $dp->product_id,
                'name' => $cleanName,
                'sku' => $product->sku,
                'price' => (float) $price,
                'image' => $image,
                'flash_price' => (float) $dp->flash_price,
                'allocation_qty' => (int) $dp->allocation_qty,
                'sold_qty' => (int) $dp->sold_qty,
            ];
        })->filter()->values();

        return response()->json([
            'deal' => [
                'id' => $deal->id,
                'title' => $deal->title,
            ],
            'products' => $dealProducts,
        ]);
    }

    /**
     * Save/sync allocated products for a specific flash deal from modal popup.
     */
    public function saveDealProducts(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'products' => 'present|array',
            'products.*.product_id' => 'required|integer|exists:products,id',
            'products.*.flash_price' => 'required|numeric|min:0',
            'products.*.allocation_qty' => 'required|integer|min:1',
        ]);

        $deal = $this->flashDealRepository->findOrFail($id);

        $existingItems = $deal->products->keyBy('product_id');

        $deal->products()->delete();

        if (isset($validated['products']) && is_array($validated['products'])) {
            foreach ($validated['products'] as $productData) {
                $existing = $existingItems->get($productData['product_id']);

                $this->flashDealProductRepository->create([
                    'flash_deal_id' => $deal->id,
                    'product_id' => $productData['product_id'],
                    'flash_price' => $productData['flash_price'],
                    'allocation_qty' => $productData['allocation_qty'],
                    'sold_qty' => $existing ? $existing->sold_qty : 0,
                ]);
            }
        }

        try {
            \Illuminate\Support\Facades\Artisan::call('cache:clear');
            \Illuminate\Support\Facades\Artisan::call('view:clear');
        } catch (\Throwable $e) {}

        return response()->json(['message' => 'تم حفظ منتجات العرض السريع بنجاح.']);
    }

    /**
     * Search products for flash deal form autocomplete with image & name fallback.
     */
    public function searchProducts(Request $request): JsonResponse
    {
        return response()->json($this->fetchProducts($request->get('query', '')));
    }

    /**
     * Remove the specified flash deal from storage.
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $this->flashDealRepository->delete($id);

            return response()->json(['message' => 'تم حذف العرض السريع بنجاح.']);
        } catch (\Exception $e) {
            return response()->json(['message' => 'تعذر حذف العرض السريع.'], 500);
        }
    }

    /**
     * Helper to retrieve products safely with fallback for name, price & image.
     */
    protected function fetchProducts(?string $term = null, array $includeIds = [])
    {
        $term = trim($term ?? '');
        $nameAttrId = DB::table('attributes')->where('code', 'name')->value('id');
        $locale = app()->getLocale();

        $query = DB::table('products')
            ->whereNull('products.parent_id')
            ->leftJoin('product_flat', function ($join) use ($locale) {
                $join->on('products.id', '=', 'product_flat.product_id')
                    ->where(function ($q) use ($locale) {
                        $q->where('product_flat.locale', $locale)
                            ->orWhereNull('product_flat.locale');
                    });
            })
            ->leftJoin('products as parent_products', 'products.parent_id', '=', 'parent_products.id')
            ->leftJoin('product_flat as parent_flat', function ($join) use ($locale) {
                $join->on('parent_products.id', '=', 'parent_flat.product_id')
                    ->where(function ($q) use ($locale) {
                        $q->where('parent_flat.locale', $locale)
                            ->orWhereNull('parent_flat.locale');
                    });
            });

        if ($nameAttrId) {
            $query->leftJoin('product_attribute_values as name_vals', function ($join) use ($nameAttrId) {
                $join->on('products.id', '=', 'name_vals.product_id')
                    ->where('name_vals.attribute_id', $nameAttrId);
            })
                ->leftJoin('product_attribute_values as parent_name_vals', function ($join) use ($nameAttrId) {
                    $join->on('parent_products.id', '=', 'parent_name_vals.product_id')
                        ->where('parent_name_vals.attribute_id', $nameAttrId);
                });
        }

        $query->leftJoin('product_images', 'products.id', '=', 'product_images.product_id')
            ->leftJoin('product_images as parent_images', 'products.parent_id', '=', 'parent_images.product_id');

        $selects = [
            'products.id',
            'products.sku',
            'products.parent_id',
            'product_flat.name as flat_name',
            'parent_flat.name as parent_flat_name',
            DB::raw('COALESCE(MAX(product_flat.price), MAX(parent_flat.price), 0) as price'),
            DB::raw('COALESCE(MIN(product_images.path), MIN(parent_images.path)) as image_path'),
        ];

        if ($nameAttrId) {
            $selects[] = 'name_vals.text_value as attr_name';
            $selects[] = 'parent_name_vals.text_value as parent_attr_name';
        } else {
            $selects[] = DB::raw('NULL as attr_name');
            $selects[] = DB::raw('NULL as parent_attr_name');
        }

        $query->select($selects);

        if (! empty($includeIds)) {
            $query->whereIn('products.id', $includeIds);
        } elseif (! empty($term)) {
            $query->where(function ($q) use ($term, $nameAttrId) {
                $q->where('products.id', 'like', "%{$term}%")
                    ->orWhere('products.sku', 'like', "%{$term}%")
                    ->orWhere('product_flat.name', 'like', "%{$term}%")
                    ->orWhere('parent_flat.name', 'like', "%{$term}%");

                if ($nameAttrId) {
                    $q->orWhere('name_vals.text_value', 'like', "%{$term}%")
                        ->orWhere('parent_name_vals.text_value', 'like', "%{$term}%");
                }
            });
        } else {
            $query->limit(50);
        }

        $groupBy = [
            'products.id',
            'products.sku',
            'products.parent_id',
            'product_flat.name',
            'parent_flat.name',
        ];
        if ($nameAttrId) {
            $groupBy[] = 'name_vals.text_value';
            $groupBy[] = 'parent_name_vals.text_value';
        }

        return $query->groupBy($groupBy)->get()->map(function ($product) {
            $imageUrl = ! empty($product->image_path)
                ? Storage::url($product->image_path)
                : bagisto_asset('images/small-product-placeholder.webp', 'shop');

            $childName = ! empty($product->flat_name) && $product->flat_name !== $product->sku
                ? $product->flat_name
                : (! empty($product->attr_name) && $product->attr_name !== $product->sku ? $product->attr_name : null);

            $parentName = ! empty($product->parent_flat_name)
                ? $product->parent_flat_name
                : (! empty($product->parent_attr_name) ? $product->parent_attr_name : null);

            if (! empty($childName) && ! empty($parentName) && $childName !== $parentName) {
                if (str_contains(mb_strtolower($childName), mb_strtolower($parentName))) {
                    $displayName = $childName;
                } else {
                    $displayName = $parentName.' - '.$childName;
                }
            } elseif (! empty($childName)) {
                $displayName = $childName;
            } elseif (! empty($parentName)) {
                $displayName = $parentName;
            } else {
                $displayName = 'منتج #'.$product->id;
            }

            $displayName = preg_replace('/[^\p{L}\p{N}\s\-\_]/u', '', $displayName);
            $displayName = trim($displayName);
            if (empty($displayName)) {
                $displayName = 'منتج #'.$product->id;
            }

            return [
                'id' => $product->id,
                'sku' => $product->sku,
                'name' => $displayName,
                'price' => (float) $product->price,
                'image' => $imageUrl,
            ];
        });
    }
}
