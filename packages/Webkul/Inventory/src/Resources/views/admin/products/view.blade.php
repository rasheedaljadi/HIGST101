<x-admin::layouts>
    <x-slot:title>
        {{ trans('inventory::app.admin.products.title') }} - {{ $product->sku }}
    </x-slot>

    <div class="flex flex-col gap-6">
        {{-- Header Section --}}
        <div class="flex items-center justify-between flex-wrap gap-4">
            <div class="flex flex-col">
                <div class="flex items-center gap-2 text-sm text-gray-500">
                    <a href="{{ route('admin.inventory.products.index') }}" class="hover:underline">
                        {{ trans('inventory::app.admin.products.title') }}
                    </a>
                    <span>/</span>
                    <span class="text-gray-800 dark:text-white font-medium">{{ $product->sku }}</span>
                </div>
                <h1 class="text-2xl font-bold text-gray-800 dark:text-white mt-1">
                    {{ $product->sku }}
                </h1>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">
                    النوع: {{ $product->type }} | المعرف: #{{ $product->id }}
                </p>
            </div>

            <div class="flex items-center gap-3">
                <a href="{{ route('admin.inventory.products.index') }}" class="secondary-button">
                    ← العودة لقائمة المنتجات
                </a>
            </div>
        </div>

        {{-- External Projection Isolated Section --}}
        @if($virtualProjection)
            <div class="p-5 bg-amber-50/70 dark:bg-amber-950/30 rounded-lg border border-amber-300 dark:border-amber-900/60 shadow-sm flex flex-col gap-3">
                <div class="flex items-center justify-between flex-wrap gap-2">
                    <div class="flex items-center gap-2">
                        <span class="icon-attribute text-xl text-amber-700 dark:text-amber-300"></span>
                        <h2 class="text-base font-bold text-amber-900 dark:text-amber-200">
                            {{ trans('inventory::app.admin.products.external-projection-title') }}
                        </h2>
                    </div>
                    <span class="px-2.5 py-1 rounded text-xs font-bold bg-amber-200/60 dark:bg-amber-900/60 text-amber-900 dark:text-amber-200">
                        {{ number_format($virtualProjection->current_qty) }} وحدة استرشادية
                    </span>
                </div>

                <p class="text-xs text-amber-800 dark:text-amber-300 leading-relaxed">
                    {{ trans('inventory::app.admin.products.external-projection-notice') }}
                </p>
            </div>
        @endif

        {{-- Physical Inventory Sources Breakdown --}}
        <div class="p-5 bg-white dark:bg-gray-900 rounded-lg border border-gray-200 dark:border-gray-800 shadow-sm flex flex-col gap-4">
            <div class="flex items-center justify-between">
                <h2 class="text-base font-bold text-gray-800 dark:text-white flex items-center gap-2">
                    <span class="icon-product text-lg text-emerald-600"></span>
                    {{ trans('inventory::app.admin.products.local-salable-title') }}
                </h2>
                <span class="text-sm font-bold text-emerald-600 dark:text-emerald-400">
                    إجمالي الجاهز للتسليم في اليمن: {{ number_format($totalSalableLocal) }} وحدة
                </span>
            </div>

            <div class="grid grid-cols-3 gap-4 max-lg:grid-cols-1">
                @foreach($localSources as $src)
                    <div class="p-4 rounded-lg border {{ $src->is_salable ? 'border-emerald-200 bg-emerald-50/30 dark:border-emerald-900/50 dark:bg-emerald-950/20' : 'border-gray-200 bg-gray-50/50 dark:border-gray-800 dark:bg-gray-800/40' }} flex flex-col justify-between">
                        <div>
                            <div class="flex items-center justify-between mb-1">
                                <span class="text-xs font-bold text-gray-800 dark:text-white">{{ $src->name }}</span>
                                <span class="text-[10px] px-2 py-0.5 rounded {{ $src->is_salable ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900 dark:text-emerald-200' : 'bg-gray-200 text-gray-800 dark:bg-gray-700 dark:text-gray-300' }}">
                                    {{ $src->is_salable ? 'قابل للبيع' : 'غير قابل للبيع' }}
                                </span>
                            </div>
                            <span class="text-[11px] text-gray-500">{{ $src->code }} ({{ $src->country }})</span>
                        </div>

                        <div class="mt-3 flex items-baseline justify-between">
                            <span class="text-xs text-gray-500">الرصيد المادي:</span>
                            <span class="text-xl font-bold {{ $src->current_qty > 0 ? ($src->is_salable ? 'text-emerald-600' : 'text-gray-800 dark:text-white') : 'text-gray-400' }}">
                                {{ number_format($src->current_qty) }}
                            </span>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Active Allocations --}}
        @if($allocations->isNotEmpty())
            <div class="p-5 bg-white dark:bg-gray-900 rounded-lg border border-gray-200 dark:border-gray-800 shadow-sm flex flex-col gap-3">
                <h2 class="text-base font-bold text-gray-800 dark:text-white flex items-center gap-2">
                    <span class="icon-cart text-lg text-amber-600"></span>
                    الحجوزات النشطة للطلبات (Allocations)
                </h2>

                <div class="overflow-x-auto">
                    <table class="w-full text-xs text-right">
                        <thead class="text-gray-500 bg-gray-50 dark:bg-gray-800 border-b">
                            <tr>
                                <th class="p-2.5">رقم الحجز</th>
                                <th class="p-2.5">الطلب</th>
                                <th class="p-2.5">المصدر المحجوز منه</th>
                                <th class="p-2.5">الكمية المحجوزة</th>
                                <th class="p-2.5">تاريخ الحجز</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                            @foreach($allocations as $alloc)
                                <tr>
                                    <td class="p-2.5 font-bold">#{{ $alloc->id }}</td>
                                    <td class="p-2.5 font-bold text-blue-600">#{{ $alloc->order_increment_id ?: $alloc->order_id }}</td>
                                    <td class="p-2.5">{{ $alloc->source_code }}</td>
                                    <td class="p-2.5 font-bold text-amber-600">{{ $alloc->reserved_qty }}</td>
                                    <td class="p-2.5 text-gray-400">{{ core()->formatDate($alloc->created_at, 'Y-m-d H:i') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        {{-- Product Movement Ledger History --}}
        <div class="p-5 bg-white dark:bg-gray-900 rounded-lg border border-gray-200 dark:border-gray-800 shadow-sm flex flex-col gap-3">
            <h2 class="text-base font-bold text-gray-800 dark:text-white flex items-center gap-2">
                <span class="icon-history text-lg text-blue-600"></span>
                {{ trans('inventory::app.admin.products.movements-history') }}
            </h2>

            <div class="overflow-x-auto">
                <table class="w-full text-xs text-right">
                    <thead class="text-gray-500 bg-gray-50 dark:bg-gray-800 border-b">
                        <tr>
                            <th class="p-2.5">المعرف</th>
                            <th class="p-2.5">النوع</th>
                            <th class="p-2.5">الكمية</th>
                            <th class="p-2.5">المسار</th>
                            <th class="p-2.5">المرجع</th>
                            <th class="p-2.5">المنفذ</th>
                            <th class="p-2.5">الوقت</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                        @forelse($movements as $m)
                            <tr>
                                <td class="p-2.5 text-gray-400">#{{ $m->id }}</td>
                                <td class="p-2.5">
                                    <span class="px-2 py-0.5 rounded text-[10px] font-semibold bg-gray-100 dark:bg-gray-800">
                                        {{ trans("inventory::app.admin.movements.{$m->movement_type}") ?: $m->movement_type }}
                                    </span>
                                </td>
                                <td class="p-2.5 font-bold {{ $m->quantity >= 0 ? 'text-emerald-600' : 'text-rose-600' }}">
                                    {{ $m->quantity >= 0 ? '+'.$m->quantity : $m->quantity }}
                                </td>
                                <td class="p-2.5 text-gray-500">
                                    {{ $m->targetInventorySource?->name ?: $m->sourceInventorySource?->name ?: '-' }}
                                </td>
                                <td class="p-2.5 text-gray-500">
                                    {{ $m->order_id ? 'طلب #'.$m->order_id : ($m->reference_event ?: '-') }}
                                </td>
                                <td class="p-2.5 text-gray-500">{{ $m->actor?->name ?: $m->actor_type }}</td>
                                <td class="p-2.5 text-gray-400">{{ core()->formatDate($m->created_at, 'Y-m-d H:i') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="p-4 text-center text-gray-400">لا توجد حركات مسجلة لهذا المنتج.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-admin::layouts>
