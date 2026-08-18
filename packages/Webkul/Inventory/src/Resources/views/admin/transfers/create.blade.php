<x-admin::layouts>
    <x-slot:title>
        {{ trans('inventory::app.admin.transfers.create-title') }}
    </x-slot>

    <div class="flex flex-col gap-6 max-w-4xl">
        {{-- Header Section --}}
        <div class="flex items-center justify-between flex-wrap gap-4">
            <div class="flex flex-col">
                <div class="flex items-center gap-2 text-sm text-gray-500">
                    <a href="{{ route('admin.inventory.transfers.index') }}" class="hover:underline">
                        {{ trans('inventory::app.admin.transfers.title') }}
                    </a>
                    <span>/</span>
                    <span class="text-gray-800 dark:text-white font-medium">{{ trans('inventory::app.admin.transfers.create-title') }}</span>
                </div>
                <h1 class="text-2xl font-bold text-gray-800 dark:text-white mt-1">
                    {{ trans('inventory::app.admin.transfers.create-title') }}
                </h1>
            </div>

            <a href="{{ route('admin.inventory.transfers.index') }}" class="secondary-button">
                ← إلغاء وعودة
            </a>
        </div>

        {{-- Form --}}
        <form method="POST" action="{{ route('admin.inventory.transfers.store') }}" class="p-6 bg-white dark:bg-gray-900 rounded-lg border border-gray-200 dark:border-gray-800 shadow-sm flex flex-col gap-6">
            @csrf

            <div class="grid grid-cols-2 gap-4 max-sm:grid-cols-1">
                {{-- Origin Source --}}
                <div class="flex flex-col gap-1.5">
                    <label class="text-xs font-bold text-gray-700 dark:text-gray-300 required">
                        {{ trans('inventory::app.admin.transfers.source') }}
                    </label>
                    <select name="source_inventory_source_id" required class="px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-sm">
                        <option value="">-- اختر المستودع المصدر --</option>
                        @foreach($sources as $source)
                            <option value="{{ $source->id }}" {{ old('source_inventory_source_id') == $source->id ? 'selected' : '' }}>
                                {{ $source->name }} ({{ $source->code }}) - {{ $source->country }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Destination Source --}}
                <div class="flex flex-col gap-1.5">
                    <label class="text-xs font-bold text-gray-700 dark:text-gray-300 required">
                        {{ trans('inventory::app.admin.transfers.destination') }}
                    </label>
                    <select name="destination_inventory_source_id" required class="px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-sm">
                        <option value="">-- اختر المستودع الهدف --</option>
                        @foreach($sources as $source)
                            <option value="{{ $source->id }}" {{ old('destination_inventory_source_id') == $source->id ? 'selected' : '' }}>
                                {{ $source->name }} ({{ $source->code }}) - {{ $source->country }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-3 gap-4 max-sm:grid-cols-1">
                {{-- Carrier --}}
                <div class="flex flex-col gap-1.5">
                    <label class="text-xs font-semibold text-gray-700 dark:text-gray-300">
                        {{ trans('inventory::app.admin.transfers.carrier') }}
                    </label>
                    <input type="text" name="carrier_name" value="{{ old('carrier_name') }}" placeholder="مثال: Hayest Express / DHL" class="px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-sm">
                </div>

                {{-- Tracking Number --}}
                <div class="flex flex-col gap-1.5">
                    <label class="text-xs font-semibold text-gray-700 dark:text-gray-300">
                        {{ trans('inventory::app.admin.transfers.tracking-number') }}
                    </label>
                    <input type="text" name="tracking_number" value="{{ old('tracking_number') }}" placeholder="رقم بوليصة الشحن" class="px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-sm">
                </div>

                {{-- Total Packages --}}
                <div class="flex flex-col gap-1.5">
                    <label class="text-xs font-semibold text-gray-700 dark:text-gray-300">
                        {{ trans('inventory::app.admin.transfers.packages-count') }}
                    </label>
                    <input type="number" name="total_packages" value="{{ old('total_packages', 1) }}" min="1" class="px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-sm">
                </div>
            </div>

            {{-- Items Container --}}
            <div class="flex flex-col gap-3">
                <h3 class="text-sm font-bold text-gray-800 dark:text-white flex items-center justify-between">
                    <span>{{ trans('inventory::app.admin.transfers.items') }}</span>
                    <span class="text-xs font-normal text-gray-500">أدخل تفاصيل البنود المشحونة</span>
                </h3>

                <div class="p-4 rounded-lg bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 flex flex-col gap-3" id="items-wrapper">
                    <div class="grid grid-cols-12 gap-3 items-center item-row">
                        <div class="col-span-3">
                            <input type="number" name="items[0][product_id]" placeholder="معرف المنتج Product ID" required class="w-full px-3 py-2 rounded border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-xs">
                        </div>
                        <div class="col-span-5">
                            <input type="text" name="items[0][sku]" placeholder="رمز SKU" required class="w-full px-3 py-2 rounded border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-xs">
                        </div>
                        <div class="col-span-4">
                            <input type="number" name="items[0][qty_shipped]" min="1" placeholder="الكمية المشحونة" required class="w-full px-3 py-2 rounded border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-xs">
                        </div>
                    </div>
                </div>
            </div>

            {{-- Notes --}}
            <div class="flex flex-col gap-1.5">
                <label class="text-xs font-semibold text-gray-700 dark:text-gray-300">
                    {{ trans('inventory::app.admin.transfers.notes') }}
                </label>
                <textarea name="notes" rows="3" placeholder="أي ملاحظات خاصة بنقل الشحنة..." class="px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-sm"></textarea>
            </div>

            <div class="flex items-center justify-end gap-3 pt-4 border-t border-gray-200 dark:border-gray-800">
                <button type="submit" class="primary-button">
                    حفظ مسودة المانيفست
                </button>
            </div>
        </form>
    </div>
</x-admin::layouts>
