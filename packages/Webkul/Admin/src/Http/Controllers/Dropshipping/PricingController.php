<?php

namespace Webkul\Admin\Http\Controllers\Dropshipping;

use App\Enums\PricingTrigger;
use App\Enums\SourceDiscountPolicy;
use App\Models\AliExpressProductImport;
use App\Models\HigestCalculatedPriceHistory;
use App\Models\HigestPricingRule;
use App\Models\HigestProductPriceOverride;
use App\Models\HigestSourceOffer;
use App\Services\AliExpress\AliExpressFreightService;
use App\Services\Pricing\PriceRecalculationService;
use Illuminate\Http\Request;
use Webkul\Admin\Http\Controllers\Controller;

class PricingController extends Controller
{
    public function __construct(
        protected PriceRecalculationService $recalculationService,
    ) {}

    /**
     * Redirect standalone pricing index route to the Key Management Pricing tab.
     */
    public function index()
    {
        return redirect()->to(route('admin.dropshipping.keys.index').'#pricing');
    }

    /**
     * Store a new pricing rule.
     */
    public function storeRule(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'scope' => 'required|in:global,category,product',
            'scope_id' => 'nullable|integer',
            'type' => 'required|in:percentage,fixed',
            'value' => 'required|numeric|min:0',
            'source_discount_policy' => 'nullable|string|in:PASS_TO_CUSTOMER,ABSORB_BY_HIGEST',
            'priority' => 'nullable|integer|min:0',
        ]);

        $rule = HigestPricingRule::create([
            'name' => $validated['name'],
            'scope' => $validated['scope'],
            'scope_id' => $validated['scope'] === 'global' ? null : ($validated['scope_id'] ?? null),
            'type' => $validated['type'],
            'value' => $validated['value'],
            'source_discount_policy' => $validated['source_discount_policy'] ?? SourceDiscountPolicy::PASS_TO_CUSTOMER->value,
            'priority' => $validated['priority'] ?? 0,
            'version' => 1,
            'status' => true,
        ]);

        // Recalculate prices for products affected by this new rule
        $affectedCount = $this->recalculationService->recalculateForRule($rule);

        session()->flash('success', "تم إضافة قاعدة التسعير بنجاح وإعادة حساب {$affectedCount} منتج.");

        return redirect()->back();
    }

    /**
     * Update an existing pricing rule.
     */
    public function updateRule(Request $request, int $id)
    {
        $rule = HigestPricingRule::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'scope' => 'required|in:global,category,product',
            'scope_id' => 'nullable|integer',
            'type' => 'required|in:percentage,fixed',
            'value' => 'required|numeric|min:0',
            'source_discount_policy' => 'nullable|string|in:PASS_TO_CUSTOMER,ABSORB_BY_HIGEST',
            'priority' => 'nullable|integer|min:0',
            'status' => 'required|boolean',
        ]);

        $rule->update([
            'name' => $validated['name'],
            'scope' => $validated['scope'],
            'scope_id' => $validated['scope'] === 'global' ? null : ($validated['scope_id'] ?? null),
            'type' => $validated['type'],
            'value' => $validated['value'],
            'source_discount_policy' => $validated['source_discount_policy'] ?? $rule->source_discount_policy?->value ?? SourceDiscountPolicy::PASS_TO_CUSTOMER->value,
            'priority' => $validated['priority'] ?? 0,
            'status' => $validated['status'],
        ]);

        // Recalculate affected products (rule version was auto-incremented by model boot)
        $affectedCount = $this->recalculationService->recalculateForRule($rule);

        $message = "تم تحديث قاعدة التسعير (النسخة {$rule->version}) وإعادة حساب {$affectedCount} منتج.";

        if (request()->wantsJson() || request()->ajax()) {
            return response()->json([
                'message' => $message,
                'status' => true,
            ]);
        }

        session()->flash('success', $message);

        return redirect()->back();
    }

    /**
     * Delete a pricing rule.
     */
    public function destroyRule(int $id)
    {
        $rule = HigestPricingRule::findOrFail($id);
        $rule->delete();

        // Recalculate all prices (fallback rules will be resolved)
        $affectedCount = $this->recalculationService->recalculateAll(PricingTrigger::RULE_CHANGE);

        $message = "تم حذف قاعدة التسعير وإعادة حساب {$affectedCount} منتج.";

        if (request()->wantsJson() || request()->ajax()) {
            return response()->json([
                'message' => $message,
                'status' => true,
            ]);
        }

        session()->flash('success', $message);

        return redirect()->back();
    }

    /**
     * Display calculated price history log.
     */
    public function history()
    {
        $histories = HigestCalculatedPriceHistory::with(['variant', 'parentProduct', 'pricingRule'])
            ->orderByDesc('id')
            ->paginate(50);

        return view('admin::dropshipping.pricing.history', compact('histories'));
    }

    /**
     * Trigger manual batch recalculation for all products.
     */
    public function recalculate()
    {
        $count = $this->recalculationService->recalculateAll(PricingTrigger::MANUAL);

        session()->flash('success', "تمت إعادة حساب أسعار {$count} منتج بنجاح عبر محرك التسعير.");

        return redirect()->back();
    }

    /**
     * Save or update manual catalog price override for a variant.
     */
    public function toggleOverride(Request $request)
    {
        $validated = $request->validate([
            'variant_id' => 'required|integer|exists:products,id',
            'product_id' => 'required|integer|exists:products,id',
            'pricing_mode' => 'required|in:AUTO,MANUAL',
            'manual_price' => 'nullable|numeric|min:0',
            'manual_special_price' => 'nullable|numeric|min:0',
            'override_reason' => 'nullable|string|max:255',
        ]);

        $override = HigestProductPriceOverride::updateOrCreate(
            ['variant_id' => $validated['variant_id']],
            [
                'product_id' => $validated['product_id'],
                'pricing_mode' => $validated['pricing_mode'],
                'manual_price' => $validated['pricing_mode'] === 'MANUAL' ? $validated['manual_price'] : null,
                'manual_special_price' => $validated['pricing_mode'] === 'MANUAL' ? $validated['manual_special_price'] : null,
                'override_reason' => $validated['override_reason'] ?? null,
                'updated_by' => auth()->id(),
            ]
        );

        // Recalculate and write price for this single variant
        $offer = HigestSourceOffer::where('variant_id', $validated['variant_id'])->first();

        if ($offer !== null) {
            $this->recalculationService->recalculateOffer($offer, PricingTrigger::MANUAL);
        }

        $modeLabel = $override->isManual() ? 'اليدوي (Manual Override)' : 'الآلي (Automated Pricing)';
        session()->flash('success', "تم تحديث وضع التسعير للمنتج إلى {$modeLabel} بنجاح.");

        return redirect()->back();
    }

    /**
     * Display product imports audit log.
     */
    public function productImportHistory(Request $request)
    {
        $currentLocale = app()->getLocale();
        $defaultChannelLocale = core()->getDefaultLocaleCodeFromDefaultChannel();

        $query = AliExpressProductImport::with(['product'])
            ->leftJoin('products as p', 'aliexpress_product_imports.product_id', '=', 'p.id')
            ->leftJoin('product_flat as pf', function ($join) use ($currentLocale, $defaultChannelLocale) {
                $join->on('aliexpress_product_imports.product_id', '=', 'pf.product_id')
                    ->where(function ($q) use ($currentLocale, $defaultChannelLocale) {
                        $q->where('pf.locale', '=', $currentLocale)
                            ->orWhere('pf.locale', '=', strtolower($currentLocale))
                            ->orWhere('pf.locale', '=', strtoupper($currentLocale))
                            ->orWhere('pf.locale', '=', $defaultChannelLocale);
                    });
            })
            ->select(
                'aliexpress_product_imports.*',
                'p.sku as catalog_sku',
                'p.type as catalog_type',
                'pf.name as product_name',
                'pf.url_key',
                'pf.meta_title',
                'pf.meta_keywords',
                'pf.meta_description'
            );

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('aliexpress_product_imports.aliexpress_product_id', 'like', "%{$search}%")
                    ->orWhere('aliexpress_product_imports.sku', 'like', "%{$search}%")
                    ->orWhere('p.sku', 'like', "%{$search}%")
                    ->orWhere('pf.name', 'like', "%{$search}%");
            });
        }

        if ($status = $request->input('status')) {
            $query->where('aliexpress_product_imports.status', $status);
        }

        $imports = $query->orderByDesc('aliexpress_product_imports.updated_at')
            ->orderByDesc('aliexpress_product_imports.id')
            ->paginate(25)
            ->withQueryString();

        // High-level statistics
        $stats = [
            'total_imports' => AliExpressProductImport::count(),
            'active_imports' => AliExpressProductImport::whereNotNull('product_id')->whereHas('product')->count(),
            'deleted_imports' => AliExpressProductImport::where(function ($q) {
                $q->whereNull('product_id')
                    ->orWhereDoesntHave('product')
                    ->orWhere('error', 'like', '%no longer exists%');
            })->count(),
            'with_shipping' => AliExpressProductImport::whereNotNull('base_shipping_cost')->count(),
        ];

        return view('admin::dropshipping.audit-logs.products-import', compact('imports', 'stats'));
    }

    /**
     * Synchronize and fetch shipping information for a specific imported product.
     */
    public function syncProductShipping(int $id, AliExpressFreightService $freightService)
    {
        $import = AliExpressProductImport::find($id);

        if (! $import) {
            return response()->json([
                'success' => false,
                'message' => 'سجل الاستيراد غير موجود.',
            ], 404);
        }

        if (str_starts_with((string) $import->aliexpress_product_id, 'CSV-')) {
            return response()->json([
                'success' => false,
                'message' => 'هذا المنتج تم استيراده عبر ملف CSV ولا يتبع لمنتجات علي إكسبرس.',
            ], 422);
        }

        $snapshot = is_array($import->payload_snapshot)
            ? $import->payload_snapshot
            : (json_decode((string) $import->payload_snapshot, true) ?? []);

        $skuId = data_get($snapshot, 'variants.0.sku_id');
        $shipping = $freightService->quote($import->aliexpress_product_id, $skuId ? (string) $skuId : null);

        if ($shipping === null) {
            return response()->json([
                'success' => false,
                'message' => 'تعذر العثور على خيارات شحن متاحة لهذا المنتج حالياً من AliExpress إلى وجهة الشحن المحددة.',
            ], 422);
        }

        $snapshot['shipping'] = $shipping;
        $snapshot['is_choice'] = (bool) ($shipping['is_choice'] ?? false);

        $import->forceFill([
            'base_shipping_cost' => $shipping['cost'],
            'shipping_currency' => $shipping['currency'],
            'shipping_min_days' => $shipping['min_days'],
            'shipping_max_days' => $shipping['max_days'],
            'shipping_company' => $shipping['company'],
            'shipping_tracking' => $shipping['tracking'],
            'shipping_synced_at' => now(),
            'payload_snapshot' => $snapshot,
            'updated_at' => now(),
        ])->save();

        return response()->json([
            'success' => true,
            'message' => 'تمت مزامنة وجلب بيانات الشحن بنجاح.',
            'shipping' => [
                'cost' => $shipping['cost'],
                'currency' => $shipping['currency'],
                'min_days' => $shipping['min_days'],
                'max_days' => $shipping['max_days'],
                'company' => $shipping['company'],
                'tracking' => $shipping['tracking'],
                'is_choice' => $shipping['is_choice'] ?? false,
            ],
        ]);
    }
}
