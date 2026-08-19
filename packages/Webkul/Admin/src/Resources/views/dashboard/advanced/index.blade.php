<div class="flex flex-col gap-6 w-full">

    <!-- 1. Executive Summary Bar -->
    @if (bouncer()->hasPermission('dashboard'))
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <!-- Total Sales Card -->
            <div class="p-5 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl shadow-sm">
                <div class="flex items-center justify-between">
                    <span class="text-sm font-medium text-gray-500 dark:text-gray-400">إجمالي المبيعات</span>
                    <span class="p-2 bg-blue-50 dark:bg-gray-800 text-blue-600 rounded-lg text-lg icon-sales"></span>
                </div>
                <div class="mt-3">
                    <h3 class="text-2xl font-bold text-gray-900 dark:text-white">
                        {{ core()->formatBasePrice($advancedData['executive']['total_sales'] ?? 0) }}
                    </h3>
                    <p class="text-xs text-gray-500 mt-1">المجمعة كقراءة فقط</p>
                </div>
            </div>

            <!-- Total Orders Card -->
            <div class="p-5 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl shadow-sm">
                <div class="flex items-center justify-between">
                    <span class="text-sm font-medium text-gray-500 dark:text-gray-400">إجمالي الطلبات</span>
                    <span class="p-2 bg-emerald-50 dark:bg-gray-800 text-emerald-600 rounded-lg text-lg icon-orders"></span>
                </div>
                <div class="mt-3">
                    <h3 class="text-2xl font-bold text-gray-900 dark:text-white">
                        {{ number_format($advancedData['executive']['total_orders'] ?? 0) }}
                    </h3>
                    <p class="text-xs text-emerald-600 mt-1">طلبات المتجر المسجلة</p>
                </div>
            </div>

            <!-- Owned Physical Stock Card -->
            <div class="p-5 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl shadow-sm">
                <div class="flex items-center justify-between">
                    <span class="text-sm font-medium text-gray-500 dark:text-gray-400">المخزون المملوك الفعلي</span>
                    <span class="p-2 bg-indigo-50 dark:bg-gray-800 text-indigo-600 rounded-lg text-lg icon-product"></span>
                </div>
                <div class="mt-3">
                    <h3 class="text-2xl font-bold text-gray-900 dark:text-white">
                        {{ number_format($advancedData['executive']['owned_stock_qty'] ?? 0) }}
                    </h3>
                    <p class="text-xs text-indigo-600 mt-1">مستودعات اليمن والسعودية الفعالة</p>
                </div>
            </div>

            <!-- Total Customers Card -->
            <div class="p-5 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl shadow-sm">
                <div class="flex items-center justify-between">
                    <span class="text-sm font-medium text-gray-500 dark:text-gray-400">إجمالي العملاء المسجلين</span>
                    <span class="p-2 bg-amber-50 dark:bg-gray-800 text-amber-600 rounded-lg text-lg icon-customer"></span>
                </div>
                <div class="mt-3">
                    <h3 class="text-2xl font-bold text-gray-900 dark:text-white">
                        {{ number_format($advancedData['executive']['total_customers'] ?? 0) }}
                    </h3>
                    <p class="text-xs text-gray-500 mt-1">قاعدة العملاء النشطة</p>
                </div>
            </div>
        </div>
    @endif

    <!-- 2. Order Lifecycle Pipeline -->
    @if (bouncer()->hasPermission('sales.orders'))
        <div class="p-6 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl shadow-sm">
            <h3 class="text-lg font-bold text-gray-800 dark:text-white mb-4">مسار الطلبات والتوريد (Order Lifecycle Pipeline)</h3>
            <div class="grid grid-cols-1 sm:grid-cols-5 gap-3">
                <div class="p-4 bg-gray-50 dark:bg-gray-800/50 rounded-lg border border-gray-100 dark:border-gray-800 text-center">
                    <span class="text-xs font-semibold text-gray-500 block mb-1">معالجة التوريد</span>
                    <span class="text-xl font-bold text-gray-900 dark:text-white">{{ number_format($advancedData['pipeline']['pending_procurement'] ?? 0) }}</span>
                </div>
                <div class="p-4 bg-blue-50/50 dark:bg-gray-800/50 rounded-lg border border-blue-100 dark:border-gray-800 text-center">
                    <span class="text-xs font-semibold text-blue-600 dark:text-blue-400 block mb-1">أوامر الشراء (POs)</span>
                    <span class="text-xl font-bold text-blue-700 dark:text-blue-300">{{ number_format($advancedData['pipeline']['purchase_orders'] ?? 0) }}</span>
                </div>
                <div class="p-4 bg-amber-50/50 dark:bg-gray-800/50 rounded-lg border border-amber-100 dark:border-gray-800 text-center">
                    <span class="text-xs font-semibold text-amber-600 dark:text-amber-400 block mb-1">استلامات المانيفست</span>
                    <span class="text-xl font-bold text-amber-700 dark:text-amber-300">{{ number_format($advancedData['pipeline']['inbound_receipts'] ?? 0) }}</span>
                </div>
                <div class="p-4 bg-indigo-50/50 dark:bg-gray-800/50 rounded-lg border border-indigo-100 dark:border-gray-800 text-center">
                    <span class="text-xs font-semibold text-indigo-600 dark:text-indigo-400 block mb-1">مناقلات الترانزيت</span>
                    <span class="text-xl font-bold text-indigo-700 dark:text-indigo-300">{{ number_format($advancedData['pipeline']['transfers'] ?? 0) }}</span>
                </div>
                <div class="p-4 bg-emerald-50/50 dark:bg-gray-800/50 rounded-lg border border-emerald-100 dark:border-gray-800 text-center">
                    <span class="text-xs font-semibold text-emerald-600 dark:text-emerald-400 block mb-1">تم التسليم بنجاح</span>
                    <span class="text-xl font-bold text-emerald-700 dark:text-emerald-300">{{ number_format($advancedData['pipeline']['delivered'] ?? 0) }}</span>
                </div>
            </div>
        </div>
    @endif

    <!-- 3. Supply Chain & Owned Inventory Health -->
    @if (bouncer()->hasPermission('settings.inventory_sources') || bouncer()->hasPermission('inventory.reports'))
        <div class="p-6 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl shadow-sm">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h3 class="text-lg font-bold text-gray-800 dark:text-white">صحة التوريد والأرصدة حسب المصدر</h3>
                    <p class="text-xs text-gray-500 mt-0.5">فصل التوفر الخارجي والكتالوج الافتراضي عن المخزون المملوك لشركة هايست</p>
                </div>
                <a href="{{ Route::has('admin.inventory.reports.index') ? route('admin.inventory.reports.index') : '#' }}" class="text-xs font-semibold text-blue-600 dark:text-blue-400 hover:underline">
                    تقرير الأرصدة الكامل &rarr;
                </a>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-right text-sm">
                    <thead>
                        <tr class="border-b border-gray-200 dark:border-gray-800 text-gray-500 font-semibold">
                            <th class="pb-3 text-right">رمز المصدر</th>
                            <th class="pb-3 text-right">اسم المصدر</th>
                            <th class="pb-3 text-center">التصنيف والوسم الفني</th>
                            <th class="pb-3 text-center">عدد الأصناف (SKUs)</th>
                            <th class="pb-3 text-center">الكمية الإجمالية</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                        @foreach ($advancedData['supply_chain']['sources_report'] as $src)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/40">
                                <td class="py-3 font-mono font-bold text-gray-800 dark:text-gray-200">{{ $src->code }}</td>
                                <td class="py-3 font-medium text-gray-700 dark:text-gray-300">{{ $src->name }}</td>
                                <td class="py-3 text-center">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300">
                                        مخزون مملوك (Owned Stock)
                                    </span>
                                </td>
                                <td class="py-3 text-center font-bold">{{ number_format($src->total_skus) }}</td>
                                <td class="py-3 text-center font-bold text-emerald-600 dark:text-emerald-400">{{ number_format($src->total_quantity) }}</td>
                            </tr>
                        @endforeach

                        <!-- Legacy Default Source Entry -->
                        <tr class="bg-gray-50/60 dark:bg-gray-800/20">
                            <td class="py-3 font-mono font-bold text-gray-500">default</td>
                            <td class="py-3 text-gray-500">افتراضي (Legacy Default Source)</td>
                            <td class="py-3 text-center">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-slate-200 text-slate-700 dark:bg-slate-800 dark:text-slate-400">
                                    (Legacy / External) - توفر خارجي
                                </span>
                            </td>
                            <td class="py-3 text-center text-gray-500">{{ number_format($advancedData['supply_chain']['legacy_external_count']) }}</td>
                            <td class="py-3 text-center font-bold text-gray-500">{{ number_format($advancedData['supply_chain']['legacy_external_qty']) }}</td>
                        </tr>

                        <!-- AliExpress Virtual Catalog Entry -->
                        <tr class="bg-amber-50/30 dark:bg-amber-900/10">
                            <td class="py-3 font-mono font-bold text-amber-600 dark:text-amber-400">aliexpress_source</td>
                            <td class="py-3 text-gray-600 dark:text-gray-400">AliExpress Virtual Catalog Source</td>
                            <td class="py-3 text-center">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300">
                                    (Virtual Projection) - كتالوج افتراضي
                                </span>
                            </td>
                            <td class="py-3 text-center text-amber-700 dark:text-amber-300">{{ number_format($advancedData['supply_chain']['virtual_projection_count']) }}</td>
                            <td class="py-3 text-center font-bold text-amber-700 dark:text-amber-300">{{ number_format($advancedData['supply_chain']['virtual_projection_qty']) }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    <!-- 4. Last-Mile Delivery & Financial Ledger Split -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Delivery Section -->
        @if (bouncer()->hasPermission('delivery') || bouncer()->hasPermission('delivery.index'))
            <div class="p-6 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl shadow-sm">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-base font-bold text-gray-800 dark:text-white">التسليم الفعلي للميل الأخير (Delivery Metrics)</h3>
                    <a href="{{ Route::has('admin.delivery.index') ? route('admin.delivery.index') : '#' }}" class="text-xs font-semibold text-blue-600 dark:text-blue-400 hover:underline">لوحة التوصيل &rarr;</a>
                </div>
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                    <div class="p-3 bg-gray-50 dark:bg-gray-800 rounded-lg text-center">
                        <span class="text-xs text-gray-500 block mb-1">إجمالي المهام</span>
                        <span class="text-lg font-bold text-gray-900 dark:text-white">{{ number_format($advancedData['delivery']['total_assignments']) }}</span>
                    </div>
                    <div class="p-3 bg-blue-50 dark:bg-gray-800 rounded-lg text-center">
                        <span class="text-xs text-blue-600 dark:text-blue-400 block mb-1">قيد التنسيق</span>
                        <span class="text-lg font-bold text-blue-700 dark:text-blue-300">{{ number_format($advancedData['delivery']['pending']) }}</span>
                    </div>
                    <div class="p-3 bg-amber-50 dark:bg-gray-800 rounded-lg text-center">
                        <span class="text-xs text-amber-600 dark:text-amber-400 block mb-1">جاري التوصيل</span>
                        <span class="text-lg font-bold text-amber-700 dark:text-amber-300">{{ number_format($advancedData['delivery']['in_transit']) }}</span>
                    </div>
                    <div class="p-3 bg-emerald-50 dark:bg-gray-800 rounded-lg text-center">
                        <span class="text-xs text-emerald-600 dark:text-emerald-400 block mb-1">مكتمل</span>
                        <span class="text-lg font-bold text-emerald-700 dark:text-emerald-300">{{ number_format($advancedData['delivery']['completed']) }}</span>
                    </div>
                </div>
            </div>
        @endif

        <!-- Financial Section -->
        @if (bouncer()->hasPermission('sales.transactions') || bouncer()->hasPermission('wallet'))
            <div class="p-6 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl shadow-sm">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-base font-bold text-gray-800 dark:text-white">المالية والتحصيل (Financial Ledger)</h3>
                    <a href="{{ Route::has('admin.sales.transactions.index') ? route('admin.sales.transactions.index') : '#' }}" class="text-xs font-semibold text-blue-600 dark:text-blue-400 hover:underline">سجل المعاملات &rarr;</a>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div class="p-4 bg-emerald-50/60 dark:bg-gray-800/60 rounded-lg border border-emerald-100 dark:border-gray-800">
                        <span class="text-xs text-emerald-700 dark:text-emerald-400 block mb-1">إجمالي التحصيل النقدي</span>
                        <span class="text-xl font-bold text-emerald-800 dark:text-emerald-300">{{ core()->formatBasePrice($advancedData['financial']['cash_collected_sum']) }}</span>
                    </div>
                    <div class="p-4 bg-blue-50/60 dark:bg-gray-800/60 rounded-lg border border-blue-100 dark:border-gray-800">
                        <span class="text-xs text-blue-700 dark:text-blue-400 block mb-1">إجمالي الإيرادات المسجلة</span>
                        <span class="text-xl font-bold text-blue-800 dark:text-blue-300">{{ core()->formatBasePrice($advancedData['financial']['total_sales']) }}</span>
                    </div>
                </div>
            </div>
        @endif
    </div>

    <!-- 5. Exceptions & Alerts Section -->
    @if (bouncer()->hasPermission('dashboard'))
        <div class="p-6 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl shadow-sm">
            <h3 class="text-base font-bold text-gray-800 dark:text-white mb-3">التنبيهات والاستثناءات (Alerts & Exceptions)</h3>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div class="p-4 bg-slate-50 dark:bg-gray-800 rounded-lg border border-slate-200 dark:border-gray-700 flex items-center justify-between">
                    <div>
                        <span class="text-xs text-slate-600 dark:text-slate-400 block">لقطات توفر خارجي (Snapshots)</span>
                        <span class="text-lg font-bold text-slate-900 dark:text-white">{{ number_format($advancedData['supply_chain']['external_snapshots_count']) }}</span>
                    </div>
                    <span class="p-2 bg-slate-200 text-slate-700 rounded-full text-xs font-mono">Active</span>
                </div>

                <div class="p-4 bg-amber-50 dark:bg-amber-950/20 rounded-lg border border-amber-200 dark:border-amber-900/40 flex items-center justify-between">
                    <div>
                        <span class="text-xs text-amber-700 dark:text-amber-400 block">لقطات منتهية الصلاحية (Stale)</span>
                        <span class="text-lg font-bold text-amber-900 dark:text-amber-200">{{ number_format($advancedData['exceptions']['stale_snapshots']) }}</span>
                    </div>
                    <span class="p-2 bg-amber-200 text-amber-800 rounded-full text-xs font-mono">Alert</span>
                </div>

                <div class="p-4 bg-rose-50 dark:bg-rose-950/20 rounded-lg border border-rose-200 dark:border-rose-900/40 flex items-center justify-between">
                    <div>
                        <span class="text-xs text-rose-700 dark:text-rose-400 block">كميات الحجر الصحي (Quarantine)</span>
                        <span class="text-lg font-bold text-rose-900 dark:text-rose-200">{{ number_format($advancedData['exceptions']['quarantine_qty_ye'] + $advancedData['exceptions']['quarantine_qty_sa']) }}</span>
                    </div>
                    <span class="p-2 bg-rose-200 text-rose-800 rounded-full text-xs font-mono">Held</span>
                </div>
            </div>
        </div>
    @endif

</div>
