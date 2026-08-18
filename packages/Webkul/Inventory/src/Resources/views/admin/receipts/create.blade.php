<x-admin::layouts>
    <x-slot:title>
        {{ trans('inventory::app.admin.receipts.create-title') }}
    </x-slot>

    <div class="flex flex-col gap-6 max-w-4xl">
        {{-- Header Section --}}
        <div class="flex items-center justify-between flex-wrap gap-4">
            <div class="flex flex-col">
                <div class="flex items-center gap-2 text-sm text-gray-500">
                    <a href="{{ route('admin.inventory.receipts.index') }}" class="hover:underline">
                        {{ trans('inventory::app.admin.receipts.title') }}
                    </a>
                    <span>/</span>
                    <span class="text-gray-800 dark:text-white font-medium">{{ trans('inventory::app.admin.receipts.create-title') }}</span>
                </div>
                <h1 class="text-2xl font-bold text-gray-800 dark:text-white mt-1">
                    {{ trans('inventory::app.admin.receipts.create-title') }}
                </h1>
            </div>

            <a href="{{ route('admin.inventory.receipts.index') }}" class="secondary-button">
                ← إلغاء وعودة
            </a>
        </div>

        {{-- Form --}}
        <form method="POST" action="{{ route('admin.inventory.receipts.store') }}" class="p-6 bg-white dark:bg-gray-900 rounded-lg border border-gray-200 dark:border-gray-800 shadow-sm flex flex-col gap-6" id="receipt-form">
            @csrf

            {{-- Link to Transfer Manifest --}}
            <div class="grid grid-cols-2 gap-4 max-sm:grid-cols-1">
                <div class="flex flex-col gap-1.5">
                    <label class="text-xs font-bold text-gray-700 dark:text-gray-300">
                        مانيفست النقل المرتبط (اختياري)
                    </label>
                    <select name="inventory_transfer_manifest_id" class="px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-sm">
                        <option value="">-- استلام مباشر بدون مانيفست --</option>
                        @foreach($activeTransfers as $trf)
                            <option value="{{ $trf->id }}" {{ ($transferManifest && $transferManifest->id == $trf->id) ? 'selected' : '' }}>
                                {{ $trf->manifest_number }} ({{ $trf->status }}) - {{ $trf->carrier_name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="flex flex-col gap-1.5">
                    <label class="text-xs font-semibold text-gray-700 dark:text-gray-300">
                        مرجع خارجي / رقم الشحنة
                    </label>
                    <input type="text" name="external_reference" value="{{ old('external_reference') }}" placeholder="رقم الشحنة أو بوليصة الناقل" class="px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-sm">
                </div>
            </div>

            {{-- Destination and Quarantine Selectors --}}
            <div class="grid grid-cols-2 gap-4 max-sm:grid-cols-1">
                <div class="flex flex-col gap-1.5">
                    <label class="text-xs font-bold text-gray-700 dark:text-gray-300 required">
                        {{ trans('inventory::app.admin.receipts.destination') }}
                    </label>
                    <select name="destination_inventory_source_id" required class="px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-sm">
                        @foreach($destinationSources as $src)
                            <option value="{{ $src->id }}">
                                {{ $src->name }} ({{ $src->code }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="flex flex-col gap-1.5">
                    <label class="text-xs font-semibold text-gray-700 dark:text-gray-300">
                        {{ trans('inventory::app.admin.receipts.quarantine-destination') }}
                    </label>
                    <select name="quarantine_inventory_source_id" class="px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-sm">
                        @foreach($quarantineSources as $qsrc)
                            <option value="{{ $qsrc->id }}" {{ $qsrc->code === 'hayest_quarantine_ye' ? 'selected' : '' }}>
                                {{ $qsrc->name }} ({{ $qsrc->code }})
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            {{-- Items Grid --}}
            <div class="flex flex-col gap-3">
                <h3 class="text-sm font-bold text-gray-800 dark:text-white">
                    نتائج فحص وتصنيف البنود المستلمة (سليم / تالف / عجز ونقص)
                </h3>

                <div class="p-4 rounded-lg bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 flex flex-col gap-3">
                    @if($transferManifest && $transferManifest->items->isNotEmpty())
                        @foreach($transferManifest->items as $index => $item)
                            <div class="grid grid-cols-12 gap-3 items-center border-b border-gray-200 dark:border-gray-700 pb-3">
                                <input type="hidden" name="items[{{ $index }}][product_id]" value="{{ $item->product_id }}">
                                <input type="hidden" name="items[{{ $index }}][sku]" value="{{ $item->sku }}">
                                <input type="hidden" name="items[{{ $index }}][inventory_transfer_manifest_item_id]" value="{{ $item->id }}">

                                <div class="col-span-3 font-bold text-xs">
                                    {{ $item->sku }} (المشحون: {{ $item->qty_shipped }})
                                </div>
                                <div class="col-span-3">
                                    <input type="number" name="items[{{ $index }}][qty_good]" value="{{ $item->qty_shipped }}" min="0" placeholder="سليم (Good)" class="w-full px-2 py-1.5 rounded border border-emerald-300 bg-white dark:bg-gray-900 text-xs">
                                </div>
                                <div class="col-span-3">
                                    <input type="number" name="items[{{ $index }}][qty_damaged]" value="0" min="0" placeholder="تالف (Damaged)" class="w-full px-2 py-1.5 rounded border border-rose-300 bg-white dark:bg-gray-900 text-xs">
                                </div>
                                <div class="col-span-3">
                                    <input type="number" name="items[{{ $index }}][qty_missing]" value="0" min="0" placeholder="ناقص / عجز" class="w-full px-2 py-1.5 rounded border border-amber-300 bg-white dark:bg-gray-900 text-xs">
                                </div>
                            </div>
                        @endforeach
                    @else
                        <div class="grid grid-cols-12 gap-3 items-center">
                            <div class="col-span-3">
                                <input type="number" name="items[0][product_id]" placeholder="معرف المنتج Product ID" required class="w-full px-2 py-1.5 rounded border bg-white dark:bg-gray-900 text-xs">
                            </div>
                            <div class="col-span-3">
                                <input type="text" name="items[0][sku]" placeholder="رمز SKU" required class="w-full px-2 py-1.5 rounded border bg-white dark:bg-gray-900 text-xs">
                            </div>
                            <div class="col-span-2">
                                <input type="number" name="items[0][qty_good]" value="1" min="0" placeholder="سليم" class="w-full px-2 py-1.5 rounded border border-emerald-300 bg-white dark:bg-gray-900 text-xs">
                            </div>
                            <div class="col-span-2">
                                <input type="number" name="items[0][qty_damaged]" value="0" min="0" placeholder="تالف" class="w-full px-2 py-1.5 rounded border border-rose-300 bg-white dark:bg-gray-900 text-xs">
                            </div>
                            <div class="col-span-2">
                                <input type="number" name="items[0][qty_missing]" value="0" min="0" placeholder="ناقص" class="w-full px-2 py-1.5 rounded border border-amber-300 bg-white dark:bg-gray-900 text-xs">
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Notes --}}
            <div class="flex flex-col gap-1.5">
                <label class="text-xs font-semibold text-gray-700 dark:text-gray-300">
                    {{ trans('inventory::app.admin.receipts.inspection-notes') }}
                </label>
                <textarea name="notes" rows="3" placeholder="ملاحظات فحص وتدقيق الجودة والاستلام..." class="px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-sm"></textarea>
            </div>

            <div class="flex items-center justify-end gap-3 pt-4 border-t border-gray-200 dark:border-gray-800">
                <button type="submit" class="primary-button bg-emerald-600 hover:bg-emerald-700">
                    {{ trans('inventory::app.admin.receipts.confirm-receipt') }}
                </button>
            </div>
        </form>
    </div>
</x-admin::layouts>
