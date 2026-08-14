@props([
    'totalQty' => 0,
    'inStock'  => true,
    'product'  => null,
])

@php
    $isChoice = false;
    if ($product) {
        $aeImport = \App\Models\AliExpressProductImport::where('product_id', $product->id)
            ->orWhere(function ($q) use ($product) {
                if (! empty($product->parent_id)) {
                    $q->where('product_id', $product->parent_id);
                }
            })
            ->first();
        $isChoice = $aeImport ? $aeImport->isChoice() : false;
    }
@endphp

<div class="mt-3 flex items-center gap-2 flex-wrap">
    @if (! $inStock || $totalQty <= 0)
        <span class="inline-flex items-center gap-1.5 rounded-full border border-red-200 bg-red-50 px-3 py-1 text-xs font-semibold text-red-600">
            <span class="h-2 w-2 rounded-full bg-red-500"></span>
            {{ trans('shop::app.products.view.out-of-stock') !== 'shop::app.products.view.out-of-stock' ? trans('shop::app.products.view.out-of-stock') : 'Out of Stock' }}
        </span>
    @elseif ($totalQty <= 5)
        <span class="inline-flex items-center gap-1.5 rounded-full border border-amber-200 bg-amber-50 px-3 py-1 text-xs font-semibold text-amber-600 animate-pulse">
            <span class="h-2 w-2 rounded-full bg-amber-500"></span>
            {{ trans('shop::app.products.view.limited-stock', ['qty' => $totalQty]) !== 'shop::app.products.view.limited-stock' ? trans('shop::app.products.view.limited-stock', ['qty' => $totalQty]) : "Only {$totalQty} left in stock - order soon" }}
        </span>
    @else
        <span class="inline-flex items-center gap-1.5 rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-600">
            <span class="h-2 w-2 rounded-full bg-emerald-500"></span>
            {{ trans('shop::app.products.view.in-stock') !== 'shop::app.products.view.in-stock' ? trans('shop::app.products.view.in-stock') : 'In Stock & Ready to Dispatch' }}
        </span>
    @endif

    @if ($isChoice)
        <span class="inline-flex items-center gap-1.5 rounded-full border border-amber-300 bg-amber-50 dark:bg-amber-950/40 px-2.5 py-1 text-xs font-bold text-amber-800 dark:text-amber-300 shadow-xs">
            <span class="rounded bg-amber-500 text-white px-1.5 py-0.5 text-[9px] font-black leading-none">Choice</span>
            <span>فريد</span>
        </span>
    @endif
</div>
