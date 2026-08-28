<?php

namespace Webkul\Admin\Http\Controllers\Reporting;

use ArPHP\I18N\Arabic;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use Webkul\Admin\Exports\DetailedCustomerReportExport;
use Webkul\Admin\Exports\DetailedProductReportExport;
use Webkul\Admin\Http\Controllers\Controller;
use Webkul\Category\Repositories\CategoryRepository;
use Webkul\Core\Traits\PDFHandler;
use Webkul\Product\Repositories\ProductRepository;

class DetailedReportController extends Controller
{
    use PDFHandler;

    /**
     * Create a new controller instance.
     */
    public function __construct(
        protected ProductRepository $productRepository,
        protected CategoryRepository $categoryRepository,
    ) {}

    /**
     * Display the Detailed Product Report page.
     */
    public function products(Request $request)
    {
        @ini_set('memory_limit', '-1');
        @set_time_limit(300);

        $currentLocale = app()->getLocale();

        $perPageInput = $request->input('per_page', 25);
        if ($perPageInput === 'all' || (int) $perPageInput >= 5000) {
            $perPage = 10000;
        } elseif (in_array((int) $perPageInput, [25, 50, 100, 250], true)) {
            $perPage = (int) $perPageInput;
        } else {
            $perPage = 25;
        }

        // 1. Fetch category tree metadata for filters and mapping
        $categoryTree = $this->getCategoryHierarchy($currentLocale);

        // 2. Fetch and process products with applied filters and sorting
        $processedRecords = $this->getProcessedProducts($request, $currentLocale, $categoryTree);

        // 3. Paginate the resulting collection
        $currentPage = LengthAwarePaginator::resolveCurrentPage();
        $total = $processedRecords->count();
        $currentPageItems = $processedRecords->slice(($currentPage - 1) * $perPage, $perPage)->values();

        $paginatedRecords = new LengthAwarePaginator(
            $currentPageItems,
            $total,
            $perPage,
            $currentPage,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        // 4. Compute overall report summary totals
        $totals = [
            'total_products' => $total,
            'total_simple' => $processedRecords->where('type', 'simple')->count(),
            'total_configurable' => $processedRecords->where('type', 'configurable')->count(),
            'total_imported' => $processedRecords->where('source', 'aliexpress')->count(),
            'total_internal' => $processedRecords->where('source', 'internal')->count(),
            'total_stock' => $processedRecords->sum('stock_quantity'),
            'total_stock_value' => $processedRecords->sum('stock_value'),
            'total_avg_margin' => $total > 0 ? round($processedRecords->avg('profit_margin'), 2) : 0,
        ];

        return view('admin::reporting.detailed.products', [
            'records' => $paginatedRecords,
            'totals' => $totals,
            'categories' => $categoryTree,
            'filters' => $request->all(),
            'perPage' => $perPage,
            'currentSort' => $request->input('sort', 'product_id'),
            'currentOrder' => strtolower($request->input('order', 'desc')),
            'generatedAt' => now()->translatedFormat('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Export the filtered products report to Excel (XLSX) or CSV.
     */
    public function exportProducts(Request $request)
    {
        @ini_set('memory_limit', '-1');
        @set_time_limit(300);

        $currentLocale = app()->getLocale();
        $format = strtolower($request->input('format', 'xlsx'));
        $categoryTree = $this->getCategoryHierarchy($currentLocale);

        $processedRecords = $this->getProcessedProducts($request, $currentLocale, $categoryTree);

        $includeVariants = $request->boolean('include_variants', false);

        if ($format === 'pdf') {
            $logoUrl = $this->getReportLogoBase64();

            $activeFilterLabels = [];
            $filters = $request->all();
            if (! empty($filters['search'])) {
                $activeFilterLabels[] = 'بحث: '.$filters['search'];
            }
            if (! empty($filters['product_id'])) {
                $activeFilterLabels[] = 'معرف المنتج: '.$filters['product_id'];
            }
            if (! empty($filters['sku'])) {
                $activeFilterLabels[] = 'SKU: '.$filters['sku'];
            }
            if (! empty($filters['name'])) {
                $activeFilterLabels[] = 'اسم المنتج: '.$filters['name'];
            }
            if (! empty($filters['main_category_id']) && isset($categoryTree['main'][$filters['main_category_id']])) {
                $activeFilterLabels[] = 'الفئة الرئيسية: '.$categoryTree['main'][$filters['main_category_id']];
            }
            if (! empty($filters['type'])) {
                $activeFilterLabels[] = 'النوع: '.($filters['type'] === 'simple' ? 'بسيط' : 'بمتغيرات');
            }
            if (! empty($filters['source'])) {
                $activeFilterLabels[] = 'المصدر: '.($filters['source'] === 'aliexpress' ? 'AliExpress' : 'داخلي');
            }
            if (! empty($filters['supplier'])) {
                $activeFilterLabels[] = 'المورد: '.$filters['supplier'];
            }
            if (isset($filters['status']) && $filters['status'] !== '') {
                $activeFilterLabels[] = 'الحالة: '.($filters['status'] == '1' ? 'نشط' : 'معطل');
            }

            $html = view('admin::reporting.detailed.pdf', [
                'records' => $processedRecords,
                'includeVariants' => $includeVariants,
                'logoUrl' => $logoUrl,
                'activeFilterLabels' => $activeFilterLabels,
                'generatedAt' => now()->translatedFormat('Y-m-d H:i:s'),
            ])->render();

            $filename = 'products_detailed_report_'.now()->format('Y-m-d_His');

            return $this->downloadDetailedPDF($html, $filename, 'landscape');
        }

        $filename = 'products_detailed_report_'.now()->format('Y-m-d_His').'.'.($format === 'csv' ? 'csv' : 'xlsx');
        $export = new DetailedProductReportExport($processedRecords, $includeVariants);

        if ($format === 'csv') {
            return Excel::download($export, $filename, \Maatwebsite\Excel\Excel::CSV);
        }

        return Excel::download($export, $filename, \Maatwebsite\Excel\Excel::XLSX);
    }

    /**
     * Return variant details for a configurable product via JSON.
     */
    public function productVariants(int $id)
    {
        $currentLocale = app()->getLocale();
        $variants = $this->fetchChildVariants([$id], $currentLocale);

        return response()->json([
            'success' => true,
            'variants' => $variants[$id] ?? [],
        ]);
    }

    /**
     * Query, enrich, filter, and sort products dataset.
     */
    protected function getProcessedProducts(Request $request, string $locale, array $categoryTree): Collection
    {
        $tablePrefix = DB::getTablePrefix();

        // Query only parent/main products without Cartesian duplicate joins
        $query = DB::table('products')
            ->whereNull('products.parent_id')
            ->leftJoin('product_flat as pf', function ($join) use ($locale) {
                $join->on('products.id', '=', 'pf.product_id')
                    ->where('pf.locale', '=', $locale);
            })
            ->select(
                'products.id as product_id',
                'products.sku',
                'products.type',
                'products.attribute_family_id',
                'products.created_at',
                'pf.name',
                'pf.price',
                'pf.status',
                'pf.url_key'
            );

        // Filter: Direct Product ID
        if ($productId = $request->input('product_id')) {
            $query->where('products.id', '=', (int) $productId);
        }

        // Filter: SKU
        if ($sku = $request->input('sku')) {
            $query->where('products.sku', 'LIKE', "%{$sku}%");
        }

        // Filter: Name
        if ($name = $request->input('name')) {
            $query->where('pf.name', 'LIKE', "%{$name}%");
        }

        // Filter: Search Keyword across ID, SKU, Name
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('products.id', 'LIKE', "%{$search}%")
                    ->orWhere('products.sku', 'LIKE', "%{$search}%")
                    ->orWhere('pf.name', 'LIKE', "%{$search}%");
            });
        }

        // Filter: Product Type
        if ($type = $request->input('type')) {
            $query->where('products.type', '=', $type);
        }

        // Filter: Status
        if ($request->filled('status')) {
            $query->where('pf.status', '=', (int) $request->input('status'));
        }

        // Filter: Product Source
        if ($source = $request->input('source')) {
            if ($source === 'aliexpress' || $source === 'imported') {
                $query->where(function ($q) {
                    $q->whereExists(function ($sub) {
                        $sub->select(DB::raw(1))
                            ->from('aliexpress_product_imports')
                            ->whereColumn('aliexpress_product_imports.product_id', 'products.id');
                    })->orWhereExists(function ($sub) {
                        $sub->select(DB::raw(1))
                            ->from('higest_source_offers')
                            ->whereColumn('higest_source_offers.product_id', 'products.id')
                            ->where('source_provider', 'aliexpress');
                    });
                });
            } elseif ($source === 'internal') {
                $query->whereNotExists(function ($sub) {
                    $sub->select(DB::raw(1))
                        ->from('aliexpress_product_imports')
                        ->whereColumn('aliexpress_product_imports.product_id', 'products.id');
                })->whereNotExists(function ($sub) {
                    $sub->select(DB::raw(1))
                        ->from('higest_source_offers')
                        ->whereColumn('higest_source_offers.product_id', 'products.id')
                        ->where('source_provider', 'aliexpress');
                });
            }
        }

        $rawProducts = $query->get();

        if ($rawProducts->isEmpty()) {
            return collect();
        }

        $productIds = $rawProducts->pluck('product_id')->toArray();

        // Fetch AliExpress imports map (keyed by product_id)
        $aeImportsMap = DB::table('aliexpress_product_imports')
            ->whereIn('product_id', $productIds)
            ->get()
            ->keyBy('product_id');

        // Fetch AliExpress source offers map (grouped by product_id)
        $aeOffersMap = DB::table('higest_source_offers')
            ->whereIn('product_id', $productIds)
            ->where('source_provider', 'aliexpress')
            ->get()
            ->groupBy('product_id');

        // 1. Fetch inventories for all queried products and variants
        $inventoriesMap = $this->fetchInventoriesMap($productIds);

        // 2. Fetch category mappings for products
        $productCategoriesMap = $this->fetchProductCategoriesMap($productIds, $categoryTree);

        // 3. Fetch attribute costs map (for internal/custom products)
        $attributeCostsMap = $this->fetchAttributeCostsMap($productIds);

        // 4. Fetch variants for configurable products
        $configurableIds = $rawProducts->where('type', 'configurable')->pluck('product_id')->toArray();
        $variantsMap = $this->fetchChildVariants($configurableIds, $locale);

        // 5. Enrich each product record with financial & taxonomy calculations
        $enriched = $rawProducts->map(function ($row) use ($inventoriesMap, $productCategoriesMap, $attributeCostsMap, $variantsMap, $aeImportsMap, $aeOffersMap) {
            $pId = (int) $row->product_id;
            $isConfigurable = ($row->type === 'configurable');
            $variants = $variantsMap[$pId] ?? [];
            $variantsCount = count($variants);

            $aeImport = $aeImportsMap[$pId] ?? null;
            $aeOffers = $aeOffersMap[$pId] ?? collect();
            $hsoCost = $aeOffers->isNotEmpty() ? (float) ($aeOffers->first()->acquisition_cost ?? 0) : null;

            // Determine Source & Supplier
            $isImported = (! empty($aeImport) || ! empty($hsoCost));
            $source = $isImported ? 'aliexpress' : 'internal';

            $supplier = 'المتجر الداخلي';
            if ($isImported && ! empty($aeImport->payload_snapshot)) {
                $snapshot = is_array($aeImport->payload_snapshot)
                    ? $aeImport->payload_snapshot
                    : json_decode((string) $aeImport->payload_snapshot, true);

                $supplier = data_get($snapshot, 'store_name')
                    ?? data_get($snapshot, 'supplier_name')
                    ?? data_get($snapshot, 'seller_name')
                    ?? 'AliExpress Store';
            }

            // Determine Categories
            $catInfo = $productCategoriesMap[$pId] ?? [
                'main_id' => null,
                'main_name' => 'غير مصنف',
                'sub_id' => null,
                'sub_name' => '-',
            ];

            // Determine Cost & Selling Price
            $sellingPrice = (float) ($row->price ?? 0);
            $costPrice = 0.0;

            if ($isConfigurable && $variantsCount > 0) {
                // Stock is sum of variants
                $stockQuantity = collect($variants)->sum('stock_quantity');
                $stockValue = collect($variants)->sum('stock_value');

                // Average / representative cost and price
                $costPrice = (float) (collect($variants)->avg('cost_price') ?? 0);
                if ($sellingPrice <= 0) {
                    $sellingPrice = (float) (collect($variants)->min('selling_price') ?? 0);
                }
            } else {
                $stockQuantity = (int) ($inventoriesMap[$pId] ?? 0);
                $costPrice = (float) ($row->hso_cost ?? $attributeCostsMap[$pId] ?? 0);
                $stockValue = $stockQuantity * $costPrice;
            }

            // Financial Calculations
            $unitProfit = $sellingPrice - $costPrice;
            $profitMargin = $sellingPrice > 0
                ? round(($unitProfit / $sellingPrice) * 100, 2)
                : 0.0;

            return (object) [
                'product_id' => $pId,
                'sku' => $row->sku,
                'name' => $row->name ?? '—',
                'type' => $row->type,
                'status' => (bool) ($row->status ?? true),
                'source' => $source,
                'source_label' => $source === 'aliexpress' ? 'مستورد من AliExpress' : 'منتج داخلي',
                'supplier' => $supplier,
                'main_category_id' => $catInfo['main_id'],
                'main_category' => $catInfo['main_name'],
                'sub_category_id' => $catInfo['sub_id'],
                'sub_category' => $catInfo['sub_name'],
                'variants_count' => $variantsCount,
                'variants' => $variants,
                'cost_price' => round($costPrice, 2),
                'selling_price' => round($sellingPrice, 2),
                'unit_profit' => round($unitProfit, 2),
                'profit_margin' => round($profitMargin, 2),
                'stock_quantity' => $stockQuantity,
                'stock_value' => round($stockValue, 2),
                'url_key' => $row->url_key,
                'created_at' => $row->created_at,
            ];
        });

        // 5. Apply Post-Enrichment Filters (Categories, Supplier, Financial Ranges)
        $filtered = $enriched->filter(function ($item) use ($request) {
            // Main Category Filter
            if ($mainCat = $request->input('main_category_id')) {
                if ((string) $item->main_category_id !== (string) $mainCat) {
                    return false;
                }
            }

            // Sub Category Filter
            if ($subCat = $request->input('sub_category_id')) {
                if ((string) $item->sub_category_id !== (string) $subCat) {
                    return false;
                }
            }

            // Supplier Filter
            if ($sup = $request->input('supplier')) {
                if (stripos($item->supplier, $sup) === false) {
                    return false;
                }
            }

            // Cost Price Range
            if ($request->filled('cost_from') && $item->cost_price < (float) $request->input('cost_from')) {
                return false;
            }
            if ($request->filled('cost_to') && $item->cost_price > (float) $request->input('cost_to')) {
                return false;
            }

            // Selling Price Range
            if ($request->filled('price_from') && $item->selling_price < (float) $request->input('price_from')) {
                return false;
            }
            if ($request->filled('price_to') && $item->selling_price > (float) $request->input('price_to')) {
                return false;
            }

            // Profit Margin Range
            if ($request->filled('margin_from') && $item->profit_margin < (float) $request->input('margin_from')) {
                return false;
            }
            if ($request->filled('margin_to') && $item->profit_margin > (float) $request->input('margin_to')) {
                return false;
            }

            // Stock Range
            if ($request->filled('stock_from') && $item->stock_quantity < (int) $request->input('stock_from')) {
                return false;
            }
            if ($request->filled('stock_to') && $item->stock_quantity > (int) $request->input('stock_to')) {
                return false;
            }

            return true;
        });

        // 6. Sorting
        $sortColumn = $request->input('sort', 'product_id');
        $sortOrder = strtolower($request->input('order', 'desc'));
        $isDesc = ($sortOrder === 'desc');

        $sorted = $filtered->sortBy(function ($item) use ($sortColumn) {
            return match ($sortColumn) {
                'sku' => strtolower($item->sku ?? ''),
                'name' => strtolower($item->name ?? ''),
                'type' => $item->type,
                'source' => $item->source,
                'supplier' => strtolower($item->supplier ?? ''),
                'main_category' => strtolower($item->main_category ?? ''),
                'sub_category' => strtolower($item->sub_category ?? ''),
                'cost' => $item->cost_price,
                'price' => $item->selling_price,
                'profit' => $item->unit_profit,
                'margin' => $item->profit_margin,
                'quantity', 'stock' => $item->stock_quantity,
                'stock_value' => $item->stock_value,
                'status' => (int) $item->status,
                'variants_count' => $item->variants_count,
                default => $item->product_id,
            };
        }, SORT_REGULAR, $isDesc);

        return $sorted->values();
    }

    /**
     * Build full category hierarchy tree for category breakdown and dropdowns.
     */
    protected function getCategoryHierarchy(string $locale): array
    {
        $categories = DB::table('categories')
            ->leftJoin('category_translations as ct', function ($join) use ($locale) {
                $join->on('categories.id', '=', 'ct.category_id')
                    ->where('ct.locale', '=', $locale);
            })
            ->select(
                'categories.id',
                'categories.parent_id',
                'categories._lft',
                'categories._rgt',
                'ct.name'
            )
            ->orderBy('categories._lft')
            ->get();

        $byId = [];
        foreach ($categories as $cat) {
            $byId[$cat->id] = [
                'id' => $cat->id,
                'parent_id' => $cat->parent_id,
                'name' => $cat->name ?? "Category #{$cat->id}",
            ];
        }

        // Group into Main (Level 1 under root) and Sub (Level 2+) categories
        $rootId = 1;
        $mainCategories = [];
        $subCategories = [];

        foreach ($byId as $id => $cat) {
            if ($id === $rootId || $cat['parent_id'] === null) {
                continue;
            }

            if ($cat['parent_id'] === $rootId || $cat['parent_id'] === null) {
                $mainCategories[$id] = $cat['name'];
            } else {
                $parentName = $byId[$cat['parent_id']]['name'] ?? '—';
                $subCategories[$id] = "{$parentName} › {$cat['name']}";
            }
        }

        return [
            'by_id' => $byId,
            'main' => $mainCategories,
            'sub' => $subCategories,
        ];
    }

    /**
     * Map each product ID to its Main and Sub category names.
     */
    protected function fetchProductCategoriesMap(array $productIds, array $categoryTree): array
    {
        if (empty($productIds)) {
            return [];
        }

        $records = DB::table('product_categories')
            ->whereIn('product_id', $productIds)
            ->select('product_id', 'category_id')
            ->get();

        $byId = $categoryTree['by_id'] ?? [];
        $map = [];

        foreach ($records as $rec) {
            $pId = (int) $rec->product_id;
            $catId = (int) $rec->category_id;

            if (! isset($byId[$catId])) {
                continue;
            }

            $current = $byId[$catId];
            $mainCatId = null;
            $mainCatName = '-';
            $subCatId = null;
            $subCatName = '-';

            if ($current['parent_id'] === 1 || $current['parent_id'] === null) {
                // Category is directly Main
                $mainCatId = $current['id'];
                $mainCatName = $current['name'];
            } else {
                // Category is Sub; find its top-level parent under root
                $subCatId = $current['id'];
                $subCatName = $current['name'];

                $ancestor = $current;
                while ($ancestor && $ancestor['parent_id'] !== 1 && $ancestor['parent_id'] !== null) {
                    $ancestor = $byId[$ancestor['parent_id']] ?? null;
                }

                if ($ancestor) {
                    $mainCatId = $ancestor['id'];
                    $mainCatName = $ancestor['name'];
                }
            }

            $map[$pId] = [
                'main_id' => $mainCatId,
                'main_name' => $mainCatName,
                'sub_id' => $subCatId,
                'sub_name' => $subCatName,
            ];
        }

        return $map;
    }

    /**
     * Fetch aggregated stock quantities for a list of product IDs.
     */
    protected function fetchInventoriesMap(array $productIds): array
    {
        if (empty($productIds)) {
            return [];
        }

        return DB::table('product_inventories')
            ->whereIn('product_id', $productIds)
            ->groupBy('product_id')
            ->select('product_id', DB::raw('SUM(qty) as total_qty'))
            ->pluck('total_qty', 'product_id')
            ->toArray();
    }

    /**
     * Fetch product cost attribute values from product_attribute_values table.
     */
    protected function fetchAttributeCostsMap(array $productIds): array
    {
        if (empty($productIds)) {
            return [];
        }

        $costAttrId = DB::table('attributes')->where('code', 'cost')->value('id');
        if (! $costAttrId) {
            return [];
        }

        return DB::table('product_attribute_values')
            ->where('attribute_id', $costAttrId)
            ->whereIn('product_id', $productIds)
            ->pluck('float_value', 'product_id')
            ->toArray();
    }

    /**
     * Fetch child variants for configurable products.
     */
    protected function fetchChildVariants(array $parentIds, string $locale): array
    {
        if (empty($parentIds)) {
            return [];
        }

        $rawVariants = DB::table('products')
            ->whereIn('products.parent_id', $parentIds)
            ->leftJoin('product_flat as pf', function ($join) use ($locale) {
                $join->on('products.id', '=', 'pf.product_id')
                    ->where('pf.locale', '=', $locale);
            })
            ->leftJoin('higest_source_offers as hso', function ($join) {
                $join->on('products.id', '=', 'hso.variant_id')
                    ->where('hso.source_provider', '=', 'aliexpress');
            })
            ->select(
                'products.id',
                'products.parent_id',
                'products.sku',
                'products.type',
                'pf.name',
                'pf.price',
                'pf.status',
                'hso.acquisition_cost as hso_cost'
            )
            ->get();

        if ($rawVariants->isEmpty()) {
            return [];
        }

        $variantIds = $rawVariants->pluck('id')->toArray();
        $inventories = $this->fetchInventoriesMap($variantIds);
        $variantCosts = $this->fetchAttributeCostsMap($variantIds);

        // Fetch attribute options (color, size, etc.) for variant titles
        $attributeValues = DB::table('product_attribute_values as pav')
            ->join('attributes as a', 'pav.attribute_id', '=', 'a.id')
            ->leftJoin('attribute_options as ao', 'pav.integer_value', '=', 'ao.id')
            ->leftJoin('attribute_option_translations as aot', function ($join) use ($locale) {
                $join->on('ao.id', '=', 'aot.attribute_option_id')
                    ->where('aot.locale', '=', $locale);
            })
            ->whereIn('pav.product_id', $variantIds)
            ->whereIn('a.code', ['color', 'size', 'ae_color', 'ae_sku_title', 'ae_size'])
            ->select(
                'pav.product_id',
                'a.admin_name as attr_name',
                'pav.text_value',
                'aot.label as option_label',
                'ao.admin_name as option_admin'
            )
            ->get()
            ->groupBy('product_id');

        $grouped = [];

        foreach ($rawVariants as $v) {
            $parentId = (int) $v->parent_id;
            $vId = (int) $v->id;
            $qty = (int) ($inventories[$vId] ?? 0);
            $cost = (float) ($v->hso_cost ?? $variantCosts[$vId] ?? 0);
            $price = (float) ($v->price ?? 0);
            $profit = $price - $cost;
            $margin = $price > 0 ? round(($profit / $price) * 100, 2) : 0.0;
            $stockVal = $qty * $cost;

            // Generate descriptive attribute summary (e.g. "Color: Red | Size: L")
            $attrSummary = [];
            if (isset($attributeValues[$vId])) {
                foreach ($attributeValues[$vId] as $attrRow) {
                    $val = $attrRow->option_label ?? $attrRow->option_admin ?? $attrRow->text_value;
                    if ($val) {
                        $attrSummary[] = "{$attrRow->attr_name}: {$val}";
                    }
                }
            }
            $summaryStr = ! empty($attrSummary) ? implode(' | ', $attrSummary) : ($v->name ?? "Variant #{$vId}");

            $variantObj = (object) [
                'id' => $vId,
                'product_id' => $vId,
                'parent_id' => $parentId,
                'sku' => $v->sku,
                'name' => $summaryStr,
                'attribute_summary' => $summaryStr,
                'cost_price' => round($cost, 2),
                'selling_price' => round($price, 2),
                'unit_profit' => round($profit, 2),
                'profit_margin' => round($margin, 2),
                'stock_quantity' => $qty,
                'stock_value' => round($stockVal, 2),
                'status' => (bool) ($v->status ?? true),
            ];

            $grouped[$parentId][] = $variantObj;
        }

        return $grouped;
    }

    /**
     * Display the Detailed Customer Report page.
     */
    public function customers(Request $request)
    {
        @ini_set('memory_limit', '-1');
        @set_time_limit(300);

        $perPageInput = $request->input('per_page', 25);
        if ($perPageInput === 'all' || (int) $perPageInput >= 5000) {
            $perPage = 10000;
        } elseif (in_array((int) $perPageInput, [25, 50, 100, 250], true)) {
            $perPage = (int) $perPageInput;
        } else {
            $perPage = 25;
        }

        // Fetch customer groups for filter dropdown
        $customerGroups = DB::table('customer_groups')->pluck('name', 'id')->toArray();

        // Process customer dataset
        $processedRecords = $this->getProcessedCustomers($request);

        // Paginate the resulting collection
        $currentPage = LengthAwarePaginator::resolveCurrentPage();
        $total = $processedRecords->count();
        $currentPageItems = $processedRecords->slice(($currentPage - 1) * $perPage, $perPage)->values();

        $paginatedRecords = new LengthAwarePaginator(
            $currentPageItems,
            $total,
            $perPage,
            $currentPage,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        // Compute overall report summary totals
        $totalOrdersCount = $processedRecords->sum('total_orders');
        $totalCompletedOrders = $processedRecords->sum('completed_orders');
        $totalNetSales = $processedRecords->sum('net_sales');
        $totalCost = $processedRecords->sum('total_cost');
        $totalProfit = $processedRecords->sum('total_profit');
        $validOrdersTotal = $processedRecords->sum('valid_orders_count');

        $totals = [
            'total_customers' => $total,
            'total_orders' => $totalOrdersCount,
            'total_completed_orders' => $totalCompletedOrders,
            'total_gross_sales' => round($processedRecords->sum('gross_sales'), 2),
            'total_invoiced' => round($processedRecords->sum('total_invoiced'), 2),
            'total_refunded' => round($processedRecords->sum('total_refunded'), 2),
            'total_net_sales' => round($totalNetSales, 2),
            'total_cost' => round($totalCost, 2),
            'total_profit' => round($totalProfit, 2),
            'avg_profit_margin' => $totalNetSales > 0 ? round(($totalProfit / $totalNetSales) * 100, 2) : 0.0,
            'overall_aov' => $validOrdersTotal > 0 ? round($totalNetSales / $validOrdersTotal, 2) : 0.0,
            'total_items_bought' => $processedRecords->sum('total_items_bought'),
            'total_active' => $processedRecords->where('is_active', true)->count(),
            'total_high_value' => $processedRecords->where('segment', 'high_value')->count(),
            'total_repeat' => $processedRecords->where('segment', 'repeat')->count(),
            'total_new' => $processedRecords->where('segment', 'new')->count(),
            'total_inactive' => $processedRecords->where('segment', 'inactive')->count(),
            'total_no_orders' => $processedRecords->where('segment', 'no_orders')->count(),
        ];

        return view('admin::reporting.detailed.customers', [
            'records' => $paginatedRecords,
            'totals' => $totals,
            'groups' => $customerGroups,
            'filters' => $request->all(),
            'perPage' => $perPage,
            'currentSort' => $request->input('sort', 'customer_id'),
            'currentOrder' => strtolower($request->input('order', 'desc')),
            'generatedAt' => now()->translatedFormat('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Export the filtered customer report to Excel (XLSX), CSV, or PDF.
     */
    public function exportCustomers(Request $request)
    {
        @ini_set('memory_limit', '-1');
        @set_time_limit(300);

        $format = strtolower($request->input('format', 'xlsx'));
        $processedRecords = $this->getProcessedCustomers($request);
        $includeOrders = $request->has('include_orders') ? $request->boolean('include_orders') : false;

        if ($format === 'pdf') {
            $logoUrl = $this->getReportLogoBase64();

            $activeFilterLabels = [];
            $filters = $request->all();
            if (! empty($filters['search'])) {
                $activeFilterLabels[] = 'بحث: '.$filters['search'];
            }
            if (! empty($filters['customer_id'])) {
                $activeFilterLabels[] = 'معرف العميل: '.$filters['customer_id'];
            }
            if (! empty($filters['name'])) {
                $activeFilterLabels[] = 'اسم العميل: '.$filters['name'];
            }
            if (! empty($filters['email'])) {
                $activeFilterLabels[] = 'البريد: '.$filters['email'];
            }
            if (! empty($filters['phone'])) {
                $activeFilterLabels[] = 'الهاتف: '.$filters['phone'];
            }
            if (! empty($filters['segment'])) {
                $segLabels = [
                    'high_value' => 'عميل عالي القيمة (VIP)',
                    'repeat' => 'عميل متكرر',
                    'new' => 'عميل جديد',
                    'inactive' => 'عميل غير نشط',
                    'no_orders' => 'مسجل بدون طلبات',
                ];
                $activeFilterLabels[] = 'التصنيف: '.($segLabels[$filters['segment']] ?? $filters['segment']);
            }
            if (isset($filters['status']) && $filters['status'] !== '') {
                $activeFilterLabels[] = 'الحالة: '.($filters['status'] == '1' ? 'نشط' : 'معطل');
            }

            $html = view('admin::reporting.detailed.customers-pdf', [
                'records' => $processedRecords,
                'includeOrders' => $includeOrders,
                'logoUrl' => $logoUrl,
                'activeFilterLabels' => $activeFilterLabels,
                'generatedAt' => now()->translatedFormat('Y-m-d H:i:s'),
            ])->render();

            $filename = 'customers_detailed_report_'.now()->format('Y-m-d_His');

            return $this->downloadDetailedPDF($html, $filename, 'landscape');
        }

        $filename = 'customers_detailed_report_'.now()->format('Y-m-d_His').'.'.($format === 'csv' ? 'csv' : 'xlsx');
        $export = new DetailedCustomerReportExport($processedRecords, $includeOrders);

        if ($format === 'csv') {
            return Excel::download($export, $filename, \Maatwebsite\Excel\Excel::CSV);
        }

        return Excel::download($export, $filename, \Maatwebsite\Excel\Excel::XLSX);
    }

    /**
     * Return JSON orders list with financial details for a customer.
     */
    public function customerOrders(int $id)
    {
        $customer = DB::table('customers')->where('id', $id)->first();
        if (! $customer) {
            return response()->json(['success' => false, 'message' => 'العميل غير موجود'], 404);
        }

        $orders = DB::table('orders')
            ->where(function ($q) use ($id, $customer) {
                $q->where('customer_id', $id);
                if (! empty($customer->email)) {
                    $q->orWhere('customer_email', $customer->email);
                }
            })
            ->select(
                'id',
                'increment_id',
                'status',
                'total_qty_ordered',
                'base_grand_total',
                'base_grand_total_invoiced',
                'base_grand_total_refunded',
                'created_at'
            )
            ->orderByDesc('created_at')
            ->get();

        if ($orders->isEmpty()) {
            return response()->json(['success' => true, 'orders' => []]);
        }

        $orderIds = $orders->pluck('id')->toArray();

        // Fetch cost map
        $orderItems = DB::table('order_items')
            ->whereIn('order_id', $orderIds)
            ->select('id', 'order_id', 'product_id', 'sku', 'name', 'qty_ordered', 'qty_canceled', 'qty_refunded', 'base_price', 'base_total')
            ->get();

        $productIds = $orderItems->pluck('product_id')->filter()->unique()->toArray();
        $attributeCostsMap = $this->fetchAttributeCostsMap($productIds);
        $aeOffersCostMap = DB::table('higest_source_offers')
            ->whereIn('product_id', $productIds)
            ->where('source_provider', 'aliexpress')
            ->pluck('acquisition_cost', 'product_id')
            ->toArray();

        $itemsByOrder = $orderItems->groupBy('order_id');

        $statusLabels = [
            'pending' => 'قيد الانتظار',
            'pending_payment' => 'بانتظار الدفع',
            'processing' => 'قيد المعالجة',
            'completed' => 'مكتمل',
            'canceled' => 'ملغي',
            'closed' => 'مغلق / مسترد',
            'fraud' => 'احتيال',
        ];

        $enrichedOrders = $orders->map(function ($ord) use ($itemsByOrder, $attributeCostsMap, $aeOffersCostMap, $statusLabels) {
            $items = $itemsByOrder[$ord->id] ?? collect();
            $orderCost = 0.0;
            $orderGross = (float) ($ord->base_grand_total ?? 0);
            $orderRefunded = (float) ($ord->base_grand_total_refunded ?? 0);
            $orderNet = max(0, $orderGross - $orderRefunded);
            $isCanceled = ($ord->status === 'canceled');

            $itemsDetailed = [];
            foreach ($items as $item) {
                $pId = (int) $item->product_id;
                $unitCost = (float) ($aeOffersCostMap[$pId] ?? $attributeCostsMap[$pId] ?? 0);
                $effectiveQty = max(0, (int) $item->qty_ordered - (int) $item->qty_canceled - (int) $item->qty_refunded);
                $itemTotalCost = $effectiveQty * $unitCost;

                if (! $isCanceled) {
                    $orderCost += $itemTotalCost;
                }

                $itemsDetailed[] = [
                    'id' => $item->id,
                    'sku' => $item->sku,
                    'name' => $item->name,
                    'qty' => (int) $item->qty_ordered,
                    'effective_qty' => $effectiveQty,
                    'unit_price' => round((float) $item->base_price, 2),
                    'unit_cost' => round($unitCost, 2),
                    'total_price' => round((float) $item->base_total, 2),
                    'total_cost' => round($itemTotalCost, 2),
                ];
            }

            $orderProfit = ! $isCanceled ? ($orderNet - $orderCost) : 0.0;
            $orderMargin = (! $isCanceled && $orderNet > 0) ? round(($orderProfit / $orderNet) * 100, 2) : 0.0;

            return [
                'id' => $ord->id,
                'increment_id' => $ord->increment_id ?? (string) $ord->id,
                'status' => $ord->status,
                'status_label' => $statusLabels[$ord->status] ?? $ord->status,
                'items_count' => count($itemsDetailed),
                'total_qty' => (int) ($ord->total_qty_ordered ?? collect($itemsDetailed)->sum('qty')),
                'gross_total' => round($orderGross, 2),
                'invoiced_total' => round((float) ($ord->base_grand_total_invoiced ?? 0), 2),
                'refunded_total' => round($orderRefunded, 2),
                'net_total' => round($orderNet, 2),
                'cost_total' => round($orderCost, 2),
                'profit_total' => round($orderProfit, 2),
                'profit_margin' => $orderMargin,
                'created_at' => $ord->created_at,
                'items' => $itemsDetailed,
                'view_url' => route('admin.sales.orders.view', $ord->id),
            ];
        });

        return response()->json([
            'success' => true,
            'customer_id' => $id,
            'customer_name' => trim(($customer->first_name ?? '').' '.($customer->last_name ?? '')),
            'orders' => $enrichedOrders,
        ]);
    }

    /**
     * Query, enrich, filter, and sort customers dataset.
     */
    protected function getProcessedCustomers(Request $request): Collection
    {
        // 1. Fetch all customers with group info
        $custQuery = DB::table('customers')
            ->leftJoin('customer_groups', 'customers.customer_group_id', '=', 'customer_groups.id')
            ->select(
                'customers.id as customer_id',
                'customers.first_name',
                'customers.last_name',
                'customers.email',
                'customers.phone',
                'customers.status',
                'customers.is_suspended',
                'customers.is_verified',
                'customers.created_at',
                'customer_groups.id as group_id',
                'customer_groups.name as group_name'
            );

        // Pre-query filtering on Customer table fields
        if ($customerId = $request->input('customer_id')) {
            $custQuery->where('customers.id', '=', (int) $customerId);
        }

        if ($name = $request->input('name')) {
            $custQuery->where(function ($q) use ($name) {
                $q->where('customers.first_name', 'LIKE', "%{$name}%")
                    ->orWhere('customers.last_name', 'LIKE', "%{$name}%");
            });
        }

        if ($email = $request->input('email')) {
            $custQuery->where('customers.email', 'LIKE', "%{$email}%");
        }

        if ($phone = $request->input('phone')) {
            $custQuery->where('customers.phone', 'LIKE', "%{$phone}%");
        }

        if ($search = $request->input('search')) {
            $custQuery->where(function ($q) use ($search) {
                $q->where('customers.id', 'LIKE', "%{$search}%")
                    ->orWhere('customers.first_name', 'LIKE', "%{$search}%")
                    ->orWhere('customers.last_name', 'LIKE', "%{$search}%")
                    ->orWhere('customers.email', 'LIKE', "%{$search}%")
                    ->orWhere('customers.phone', 'LIKE', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $custQuery->where('customers.status', '=', (int) $request->input('status'));
        }

        if ($groupId = $request->input('customer_group_id')) {
            $custQuery->where('customers.customer_group_id', '=', (int) $groupId);
        }

        if ($regFrom = $request->input('reg_from')) {
            $custQuery->whereDate('customers.created_at', '>=', $regFrom);
        }
        if ($regTo = $request->input('reg_to')) {
            $custQuery->whereDate('customers.created_at', '<=', $regTo);
        }

        $rawCustomers = $custQuery->get();

        if ($rawCustomers->isEmpty()) {
            return collect();
        }

        $customerIds = $rawCustomers->pluck('customer_id')->toArray();
        $customerEmails = $rawCustomers->pluck('email')->filter()->unique()->toArray();

        // 2. Fetch all related orders
        $orders = DB::table('orders')
            ->where(function ($q) use ($customerIds, $customerEmails) {
                $q->whereIn('customer_id', $customerIds);
                if (! empty($customerEmails)) {
                    $q->orWhereIn('customer_email', $customerEmails);
                }
            })
            ->select(
                'id',
                'increment_id',
                'customer_id',
                'customer_email',
                'status',
                'base_grand_total',
                'base_grand_total_invoiced',
                'base_grand_total_refunded',
                'total_qty_ordered',
                'created_at'
            )
            ->get();

        $orderIds = $orders->pluck('id')->toArray();

        // 3. Fetch order items and calculate unit costs
        $orderItems = DB::table('order_items')
            ->whereIn('order_id', $orderIds)
            ->select(
                'id',
                'order_id',
                'product_id',
                'sku',
                'name',
                'qty_ordered',
                'qty_canceled',
                'qty_refunded',
                'base_price',
                'base_total'
            )
            ->get();

        $productIds = $orderItems->pluck('product_id')->filter()->unique()->toArray();
        $attributeCostsMap = $this->fetchAttributeCostsMap($productIds);
        $aeOffersCostMap = DB::table('higest_source_offers')
            ->whereIn('product_id', $productIds)
            ->where('source_provider', 'aliexpress')
            ->pluck('acquisition_cost', 'product_id')
            ->toArray();

        // Map items cost and effective qty per order
        $orderCostMap = [];
        $orderItemsCountMap = [];
        foreach ($orderItems as $item) {
            $oId = (int) $item->order_id;
            $pId = (int) $item->product_id;
            $unitCost = (float) ($aeOffersCostMap[$pId] ?? $attributeCostsMap[$pId] ?? 0);
            $effectiveQty = max(0, (int) $item->qty_ordered - (int) $item->qty_canceled - (int) $item->qty_refunded);

            if (! isset($orderCostMap[$oId])) {
                $orderCostMap[$oId] = 0.0;
                $orderItemsCountMap[$oId] = 0;
            }
            $orderCostMap[$oId] += ($effectiveQty * $unitCost);
            $orderItemsCountMap[$oId] += $effectiveQty;
        }

        // Group orders by customer ID / email
        $ordersByCustId = $orders->groupBy('customer_id');
        $ordersByCustEmail = $orders->groupBy('customer_email');

        // 4. Enrich each customer record
        $enriched = $rawCustomers->map(function ($cust) use ($ordersByCustId, $ordersByCustEmail, $orderCostMap, $orderItemsCountMap) {
            $cId = (int) $cust->customer_id;
            $cEmail = $cust->email;

            // Combine orders by customer_id and customer_email avoiding duplicates
            $custOrdersById = $ordersByCustId[$cId] ?? collect();
            $custOrdersByEmail = (! empty($cEmail) && isset($ordersByCustEmail[$cEmail])) ? $ordersByCustEmail[$cEmail] : collect();
            $custOrders = $custOrdersById->merge($custOrdersByEmail)->unique('id');

            $totalOrders = $custOrders->count();
            $completedOrders = $custOrders->where('status', 'completed')->count();
            $canceledOrders = $custOrders->where('status', 'canceled')->count();
            $refundedOrders = $custOrders->filter(function ($o) {
                return $o->status === 'closed' || (float) ($o->base_grand_total_refunded ?? 0) > 0;
            })->count();

            $validOrders = $custOrders->where('status', '!=', 'canceled');
            $validOrdersCount = $validOrders->count();

            // Financial sums (excluding canceled orders)
            $grossSales = (float) $validOrders->sum('base_grand_total');
            $completedSales = (float) $custOrders->where('status', 'completed')->sum('base_grand_total');
            $totalInvoiced = (float) $custOrders->sum('base_grand_total_invoiced');
            $totalRefunded = (float) $custOrders->sum('base_grand_total_refunded');

            $netSales = max(0, $grossSales - $totalRefunded);

            // Total product costs for valid orders
            $totalCost = 0.0;
            $totalItemsBought = 0;
            foreach ($validOrders as $vOrd) {
                $totalCost += ($orderCostMap[$vOrd->id] ?? 0.0);
                $totalItemsBought += ($orderItemsCountMap[$vOrd->id] ?? 0);
            }

            $totalProfit = max(0, $netSales - $totalCost);
            $profitMargin = $netSales > 0 ? round(($totalProfit / $netSales) * 100, 2) : 0.0;
            $avgOrderValue = $validOrdersCount > 0 ? round($netSales / $validOrdersCount, 2) : 0.0;

            $firstOrderDate = $custOrders->min('created_at');
            $lastOrderDate = $custOrders->max('created_at');

            // Customer Segmentation
            $segment = $this->classifyCustomer(
                $cust,
                $totalOrders,
                $completedOrders,
                $validOrdersCount,
                $netSales,
                $firstOrderDate,
                $lastOrderDate
            );

            $fullName = trim(($cust->first_name ?? '').' '.($cust->last_name ?? ''));
            if (empty($fullName)) {
                $fullName = 'عميل #'.$cId;
            }

            $isActive = ((int) $cust->status === 1 && ! $cust->is_suspended);
            $statusLabel = $isActive ? 'نشط' : ($cust->is_suspended ? 'موقوف' : 'معطل');

            return (object) [
                'customer_id' => $cId,
                'name' => $fullName,
                'first_name' => $cust->first_name,
                'last_name' => $cust->last_name,
                'email' => $cust->email ?? '—',
                'phone' => $cust->phone ?? '—',
                'status' => (int) $cust->status,
                'is_suspended' => (bool) $cust->is_suspended,
                'is_verified' => (bool) $cust->is_verified,
                'is_active' => $isActive,
                'status_label' => $statusLabel,
                'group_id' => $cust->group_id,
                'group_name' => $cust->group_name ?? 'عام',
                'segment' => $segment['code'],
                'segment_label' => $segment['label'],
                'segment_badge_class' => $segment['badge_class'],
                'total_orders' => $totalOrders,
                'completed_orders' => $completedOrders,
                'canceled_orders' => $canceledOrders,
                'refunded_orders' => $refundedOrders,
                'valid_orders_count' => $validOrdersCount,
                'gross_sales' => round($grossSales, 2),
                'completed_sales' => round($completedSales, 2),
                'total_invoiced' => round($totalInvoiced, 2),
                'total_refunded' => round($totalRefunded, 2),
                'net_sales' => round($netSales, 2),
                'total_cost' => round($totalCost, 2),
                'total_profit' => round($totalProfit, 2),
                'profit_margin' => $profitMargin,
                'avg_order_value' => $avgOrderValue,
                'total_items_bought' => $totalItemsBought,
                'first_order_date' => $firstOrderDate ? date('Y-m-d H:i', strtotime($firstOrderDate)) : '—',
                'last_order_date' => $lastOrderDate ? date('Y-m-d H:i', strtotime($lastOrderDate)) : '—',
                'created_at' => $cust->created_at ? date('Y-m-d H:i', strtotime($cust->created_at)) : '—',
                'raw_created_at' => $cust->created_at,
                'orders_list' => $custOrders,
            ];
        });

        // 5. Apply Post-Enrichment Filters
        $filtered = $enriched->filter(function ($item) use ($request) {
            // Segment filter
            if ($segment = $request->input('segment')) {
                if ($item->segment !== $segment) {
                    return false;
                }
            }

            // Orders count range
            if ($request->filled('orders_from') && $item->total_orders < (int) $request->input('orders_from')) {
                return false;
            }
            if ($request->filled('orders_to') && $item->total_orders > (int) $request->input('orders_to')) {
                return false;
            }

            // Net Sales range
            if ($request->filled('sales_from') && $item->net_sales < (float) $request->input('sales_from')) {
                return false;
            }
            if ($request->filled('sales_to') && $item->net_sales > (float) $request->input('sales_to')) {
                return false;
            }

            // Profit range
            if ($request->filled('profit_from') && $item->total_profit < (float) $request->input('profit_from')) {
                return false;
            }
            if ($request->filled('profit_to') && $item->total_profit > (float) $request->input('profit_to')) {
                return false;
            }

            // Margin range
            if ($request->filled('margin_from') && $item->profit_margin < (float) $request->input('margin_from')) {
                return false;
            }
            if ($request->filled('margin_to') && $item->profit_margin > (float) $request->input('margin_to')) {
                return false;
            }

            // First Order Date Range
            if ($firstFrom = $request->input('first_order_from')) {
                if ($item->first_order_date === '—' || $item->first_order_date < $firstFrom) {
                    return false;
                }
            }
            if ($firstTo = $request->input('first_order_to')) {
                if ($item->first_order_date === '—' || $item->first_order_date > $firstTo.' 23:59:59') {
                    return false;
                }
            }

            // Last Order Date Range
            if ($lastFrom = $request->input('last_order_from')) {
                if ($item->last_order_date === '—' || $item->last_order_date < $lastFrom) {
                    return false;
                }
            }
            if ($lastTo = $request->input('last_order_to')) {
                if ($item->last_order_date === '—' || $item->last_order_date > $lastTo.' 23:59:59') {
                    return false;
                }
            }

            return true;
        });

        // 6. Sorting
        $sortColumn = $request->input('sort', 'customer_id');
        $sortOrder = strtolower($request->input('order', 'desc'));
        $isDesc = ($sortOrder === 'desc');

        $sorted = $filtered->sortBy(function ($item) use ($sortColumn) {
            return match ($sortColumn) {
                'name' => strtolower($item->name ?? ''),
                'email' => strtolower($item->email ?? ''),
                'phone' => $item->phone ?? '',
                'group' => strtolower($item->group_name ?? ''),
                'segment' => $item->segment,
                'status' => $item->status,
                'orders', 'orders_count', 'total_orders' => $item->total_orders,
                'completed_orders' => $item->completed_orders,
                'canceled_orders' => $item->canceled_orders,
                'refunded_orders' => $item->refunded_orders,
                'gross_sales' => $item->gross_sales,
                'completed_sales' => $item->completed_sales,
                'invoiced', 'total_invoiced' => $item->total_invoiced,
                'refunded', 'total_refunded' => $item->total_refunded,
                'sales', 'net_sales' => $item->net_sales,
                'cost', 'total_cost' => $item->total_cost,
                'profit', 'total_profit' => $item->total_profit,
                'margin', 'profit_margin' => $item->profit_margin,
                'aov', 'avg_order_value' => $item->avg_order_value,
                'items_count', 'total_items_bought' => $item->total_items_bought,
                'first_order_date' => $item->first_order_date,
                'last_order_date' => $item->last_order_date,
                'created_at', 'registration_date' => $item->raw_created_at ?? '',
                default => $item->customer_id,
            };
        }, SORT_REGULAR, $isDesc);

        return $sorted->values();
    }

    /**
     * Determine customer classification / segment.
     */
    protected function classifyCustomer(
        object $customer,
        int $totalOrders,
        int $completedOrders,
        int $validOrders,
        float $netSales,
        ?string $firstOrderDate,
        ?string $lastOrderDate
    ): array {
        if ($totalOrders === 0) {
            return [
                'code' => 'no_orders',
                'label' => 'مسجل بدون طلبات',
                'badge_class' => 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300',
            ];
        }

        // Suspended or inactive customer
        if ((int) $customer->status === 0 || $customer->is_suspended) {
            return [
                'code' => 'inactive',
                'label' => 'عميل غير نشط',
                'badge_class' => 'bg-rose-100 text-rose-700 dark:bg-rose-900/40 dark:text-rose-300',
            ];
        }

        // High-Value VIP: Net Sales >= $500 OR Completed Orders >= 5
        if ($netSales >= 500 || $completedOrders >= 5) {
            return [
                'code' => 'high_value',
                'label' => 'عميل عالي القيمة (VIP)',
                'badge_class' => 'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300',
            ];
        }

        // Repeat / Loyal: 2 or more valid orders
        if ($validOrders >= 2) {
            return [
                'code' => 'repeat',
                'label' => 'عميل متكرر',
                'badge_class' => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300',
            ];
        }

        // Dormant/Inactive purchaser: orders in the past but none in the last 90 days
        if ($lastOrderDate && strtotime($lastOrderDate) < strtotime('-90 days')) {
            return [
                'code' => 'inactive',
                'label' => 'عميل غير نشط',
                'badge_class' => 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300',
            ];
        }

        // New customer: 1 order within the last 60 days
        if ($validOrders === 1) {
            return [
                'code' => 'new',
                'label' => 'عميل جديد',
                'badge_class' => 'bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-300',
            ];
        }

        return [
            'code' => 'regular',
            'label' => 'عميل عادي',
            'badge_class' => 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300',
        ];
    }

    /**
     * Download Landscape PDF for detailed reports with high performance, tiny file size, and crystal clear Arabic text.
     */
    protected function downloadDetailedPDF(string $html, ?string $fileName = null, string $orientation = 'landscape')
    {
        $fileName = $this->resolvePdfFileName($fileName);

        @ini_set('memory_limit', '-1');
        @set_time_limit(300);

        if (class_exists(Arabic::class)) {
            $arabic = new Arabic;

            $html = preg_replace_callback(
                '/[\x{0600}-\x{06FF}\x{0750}-\x{077F}\x{08A0}-\x{08FF}\x{FB50}-\x{FDFF}\x{FE70}-\x{FEFF}][\x{0600}-\x{06FF}\x{0750}-\x{077F}\x{08A0}-\x{08FF}\x{FB50}-\x{FDFF}\x{FE70}-\x{FEFF}\s\d\p{P}]*/u',
                function ($match) use ($arabic) {
                    $str = $match[0];
                    try {
                        return $arabic->utf8Glyphs($str);
                    } catch (\Throwable $e) {
                        return $str;
                    }
                },
                $html
            );
        }

        $pdf = Pdf::loadHTML($html)
            ->setPaper('A4', strtolower($orientation) === 'landscape' ? 'landscape' : 'portrait')
            ->set_option('defaultFont', 'DejaVu Sans')
            ->set_option('isFontSubsettingEnabled', true)
            ->set_option('isRemoteEnabled', true);

        return $pdf->download($fileName.'.pdf');
    }

    /**
     * Get Base64 encoded logo image for PDF rendering.
     */
    protected function getReportLogoBase64(): ?string
    {
        $logoPath = null;
        if ($logo = core()->getConfigData('general.design.admin_logo.logo_image')) {
            if (Storage::exists($logo)) {
                $logoPath = Storage::path($logo);
            }
        }

        if (! $logoPath || ! file_exists($logoPath)) {
            $files = glob(public_path('themes/*/default/build/assets/logo-*.png'));
            if (! empty($files)) {
                $logoPath = $files[0];
            }
        }

        if ($logoPath && file_exists($logoPath)) {
            $ext = strtolower(pathinfo($logoPath, PATHINFO_EXTENSION));
            $mime = $ext === 'svg' ? 'image/svg+xml' : 'image/png';

            return 'data:'.$mime.';base64,'.base64_encode(file_get_contents($logoPath));
        }

        return null;
    }
}
