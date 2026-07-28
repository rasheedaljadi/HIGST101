@props([
    'dropshipping' => [],
])

@php
    $origin       = $dropshipping['origin_country'] ?? 'International Overseas Warehouse (Express Freight)';
    $delivery     = $dropshipping['estimated_delivery_window'] ?? '5 - 8 Business Days';
    $tracking     = $dropshipping['tracking_available'] ?? true;
    $rmaDays      = $dropshipping['local_rma_days'] ?? 14;
    $returnCenter = $dropshipping['return_center_location'] ?? 'Local HIGEST Return Hub Processing';
@endphp

<div class="mt-6 rounded-2xl border border-zinc-200 bg-zinc-50/70 p-4 font-sans shadow-sm transition-all hover:border-zinc-300">
    <div class="mb-3 flex items-center justify-between border-b border-zinc-200/80 pb-2.5">
        <h3 class="flex items-center gap-2 text-xs font-bold uppercase tracking-wider text-navyBlue">
            <span class="text-base">🌐</span>
            Supplier Fulfillment & Dispatch Transparency
        </h3>
        <span class="rounded-full bg-emerald-100 px-2 py-0.5 text-[10px] font-bold text-emerald-800">
            HIGEST Verified
        </span>
    </div>

    <div class="grid gap-2.5 text-xs text-zinc-700">
        <!-- Item Origin -->
        <div class="flex items-start gap-2.5">
            <span class="mt-0.5 text-sm">📍</span>
            <div>
                <span class="font-semibold text-zinc-900">Item Origin:</span>
                <span class="text-zinc-600">{{ $origin }}</span>
            </div>
        </div>

        <!-- Estimated Delivery -->
        <div class="flex items-start gap-2.5">
            <span class="mt-0.5 text-sm">🚚</span>
            <div>
                <span class="font-semibold text-zinc-900">Estimated Delivery:</span>
                <span class="font-bold text-emerald-700">{{ $delivery }}</span>
            </div>
        </div>

        <!-- Parcel Tracking -->
        <div class="flex items-start gap-2.5">
            <span class="mt-0.5 text-sm">📦</span>
            <div>
                <span class="font-semibold text-zinc-900">Parcel Tracking:</span>
                <span class="text-zinc-600">
                    @if ($tracking)
                        End-to-End Tracking Number Provided Upon Dispatch
                    @else
                        Standard Tracking Available
                    @endif
                </span>
            </div>
        </div>

        <!-- Local Return Policy -->
        <div class="flex items-start gap-2.5">
            <span class="mt-0.5 text-sm">↩️</span>
            <div>
                <span class="font-semibold text-zinc-900">Return Policy:</span>
                <span class="text-zinc-600">{{ $returnCenter }} ({{ $rmaDays }} Days Protection)</span>
            </div>
        </div>
    </div>
</div>
