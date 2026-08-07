<?php

namespace Webkul\FlashDeal\Http\Controllers\Admin;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
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
        $products = $this->getAvailableProducts();

        return view('flash_deal::admin.create', compact('products'));
    }

    /**
     * Store a newly created flash deal.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title'                 => 'required|string|max:255',
            'status'                => 'required|boolean',
            'starts_at'             => 'required|date',
            'ends_at'               => 'required|date|after:starts_at',
            'products'              => 'required|array|min:1',
            'products.*.product_id' => 'required|integer|exists:products,id',
            'products.*.flash_price'=> 'required|numeric|min:0',
            'products.*.allocation_qty' => 'required|integer|min:1',
        ]);

        $deal = $this->flashDealRepository->create([
            'title'     => $validated['title'],
            'status'    => $validated['status'],
            'starts_at' => $validated['starts_at'],
            'ends_at'   => $validated['ends_at'],
        ]);

        foreach ($validated['products'] as $productData) {
            $this->flashDealProductRepository->create([
                'flash_deal_id'  => $deal->id,
                'product_id'     => $productData['product_id'],
                'flash_price'    => $productData['flash_price'],
                'allocation_qty' => $productData['allocation_qty'],
                'sold_qty'       => 0,
            ]);
        }

        session()->flash('success', 'تم إنشاء العرض السريع بنجاح.');

        return redirect()->route('admin.marketing.promotions.flash_deals.index');
    }

    /**
     * Show the form for editing the specified flash deal.
     */
    public function edit(int $id): View
    {
        $deal = $this->flashDealRepository->with(['products.product'])->findOrFail($id);
        $products = $this->getAvailableProducts();

        return view('flash_deal::admin.edit', compact('deal', 'products'));
    }

    /**
     * Update the specified flash deal.
     */
    public function update(Request $request, int $id): RedirectResponse
    {
        $validated = $request->validate([
            'title'                 => 'required|string|max:255',
            'status'                => 'required|boolean',
            'starts_at'             => 'required|date',
            'ends_at'               => 'required|date|after:starts_at',
            'products'              => 'required|array|min:1',
            'products.*.product_id' => 'required|integer|exists:products,id',
            'products.*.flash_price'=> 'required|numeric|min:0',
            'products.*.allocation_qty' => 'required|integer|min:1',
        ]);

        $deal = $this->flashDealRepository->findOrFail($id);

        $this->flashDealRepository->update([
            'title'     => $validated['title'],
            'status'    => $validated['status'],
            'starts_at' => $validated['starts_at'],
            'ends_at'   => $validated['ends_at'],
        ], $id);

        // Sync deal products
        $deal->products()->delete();

        foreach ($validated['products'] as $productData) {
            $this->flashDealProductRepository->create([
                'flash_deal_id'  => $deal->id,
                'product_id'     => $productData['product_id'],
                'flash_price'    => $productData['flash_price'],
                'allocation_qty' => $productData['allocation_qty'],
                'sold_qty'       => $productData['sold_qty'] ?? 0,
            ]);
        }

        session()->flash('success', 'تم تحديث العرض السريع بنجاح.');

        return redirect()->route('admin.marketing.promotions.flash_deals.index');
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
     * Helper to retrieve available products safely with locale names & prices.
     */
    protected function getAvailableProducts()
    {
        try {
            $locale = core()->getRequestedLocaleCode();
        } catch (\Throwable $e) {
            $locale = app()->getLocale();
        }

        return DB::table('products')
            ->leftJoin('product_flat', function ($join) use ($locale) {
                $join->on('products.id', '=', 'product_flat.product_id')
                    ->where('product_flat.locale', '=', $locale);
            })
            ->select('products.id', 'products.sku', 'product_flat.name', 'product_flat.price')
            ->distinct()
            ->get()
            ->map(function ($product) {
                return [
                    'id'    => $product->id,
                    'sku'   => $product->sku,
                    'name'  => ! empty($product->name) ? $product->name : $product->sku,
                    'price' => (float) ($product->price ?? 0),
                ];
            });
    }
}
