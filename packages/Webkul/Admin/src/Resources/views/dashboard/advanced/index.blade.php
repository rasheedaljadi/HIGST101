<div class="flex flex-col gap-6 w-full font-sans text-right" dir="rtl">

    <!-- 0. Persistent Top Filters Toolbar (الشريط العلوي الثابت مع الهوية البصرية) -->
    <div class="p-4 bg-gradient-to-r from-slate-900 via-indigo-950 to-blue-950 rounded-2xl shadow-lg border border-indigo-800/40 text-white">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-blue-600 to-indigo-500 flex items-center justify-center shadow-md">
                    <span class="icon-dashboard text-2xl text-white"></span>
                </div>
                <div>
                    <h2 class="text-lg font-extrabold tracking-wide text-white">لوحة هايست المتقدمة الشاملة</h2>
                    <p class="text-xs text-blue-200/80 mt-0.5">مركز القيادة الموحد للمبيعات، المخزون المملوك، والتوفر الخارجي</p>
                </div>
            </div>

            <!-- Integrated Filter Controls -->
            <div class="flex flex-wrap items-center gap-2 text-xs">
                <!-- Date Range Filter -->
                <div class="flex items-center bg-slate-800/80 border border-slate-700/70 rounded-lg px-3 py-1.5">
                    <span class="text-slate-400 ml-2 font-medium">الفترة:</span>
                    <select class="bg-transparent text-white font-semibold focus:outline-none cursor-pointer">
                        <option value="today" class="bg-slate-900">اليوم</option>
                        <option value="7days" class="bg-slate-900">آخر 7 أيام</option>
                        <option value="month" selected class="bg-slate-900">هذا الشهر</option>
                        <option value="quarter" class="bg-slate-900">ربع سنوي</option>
                    </select>
                </div>

                <!-- Channel Filter -->
                <div class="flex items-center bg-slate-800/80 border border-slate-700/70 rounded-lg px-3 py-1.5">
                    <span class="text-slate-400 ml-2 font-medium">القناة:</span>
                    <select class="bg-transparent text-white font-semibold focus:outline-none cursor-pointer">
                        <option value="all" selected class="bg-slate-900">جميع القنوات</option>
                        <option value="default" class="bg-slate-900">متجر هايست الرئيسي</option>
                    </select>
                </div>

                <!-- Governorate Filter -->
                <div class="flex items-center bg-slate-800/80 border border-slate-700/70 rounded-lg px-3 py-1.5">
                    <span class="text-slate-400 ml-2 font-medium">المحافظة:</span>
                    <select class="bg-transparent text-white font-semibold focus:outline-none cursor-pointer">
                        <option value="all" selected class="bg-slate-900">جميع المحافظات</option>
                        <option value="sanaa" class="bg-slate-900">أمانة العاصمة / صنعاء</option>
                        <option value="aden" class="bg-slate-900">عدن</option>
                        <option value="taiz" class="bg-slate-900">تعز</option>
                        <option value="hadramout" class="bg-slate-900">حضرموت</option>
                    </select>
                </div>

                <!-- Product Type Filter -->
                <div class="flex items-center bg-slate-800/80 border border-slate-700/70 rounded-lg px-3 py-1.5">
                    <span class="text-slate-400 ml-2 font-medium">نوع المنتج:</span>
                    <select class="bg-transparent text-white font-semibold focus:outline-none cursor-pointer">
                        <option value="all" selected class="bg-slate-900">الكل</option>
                        <option value="internal" class="bg-slate-900">داخلي فقط</option>
                        <option value="imported" class="bg-slate-900">مستورد علي إكسبرس</option>
                    </select>
                </div>

                <!-- Manual Refresh Button -->
                <button onclick="window.location.reload();" class="p-2 bg-blue-600 hover:bg-blue-500 text-white rounded-lg transition-all shadow-md active:scale-95 flex items-center gap-1">
                    <span class="icon-refresh text-sm"></span>
                    <span class="font-bold">تحديث</span>
                </button>
            </div>
        </div>

        <!-- Data Freshness Indicator -->
        <div class="mt-3 pt-2.5 border-t border-slate-800/80 flex items-center justify-between text-[11px] text-slate-300">
            <div class="flex items-center gap-2">
                <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                <span>حالة البيانات: <strong class="text-emerald-300">{{ $advancedData['filters']['freshness_status'] ?? 'مكتمل (بيانات حية)' }}</strong></span>
            </div>
            <span class="text-slate-400">آخر تحديث للوحة: {{ $advancedData['filters']['updated_at'] ?? date('Y-m-d H:i:s') }}</span>
        </div>
    </div>

    <!-- 1. Executive Summary Cards (الملخص التنفيذي بألوان هايست الزاهية) -->
    @if (bouncer()->hasPermission('dashboard'))
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <!-- Net Sales Card (الأزرق النيلي الملكي) -->
            <div class="p-5 bg-gradient-to-br from-white to-blue-50/40 dark:from-gray-900 dark:to-blue-950/30 border border-blue-100 dark:border-blue-900/40 rounded-2xl shadow-sm hover:shadow-md transition-all duration-300">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold text-blue-900 dark:text-blue-300 uppercase tracking-wider">صافي المبيعات</span>
                    <span class="w-10 h-10 bg-blue-600/10 text-blue-600 rounded-xl flex items-center justify-center text-xl font-bold">USD</span>
                </div>
                <div class="mt-3">
                    <h3 class="text-2xl font-black text-slate-900 dark:text-white">
                        {{ core()->formatBasePrice($advancedData['executive']['total_sales'] ?? 0) }}
                    </h3>
                    <div class="flex items-center justify-between mt-2 text-xs">
                        <span class="text-emerald-600 dark:text-emerald-400 font-bold">صافي المبيعات للفترة</span>
                        <span class="text-slate-400">محدث قراءة</span>
                    </div>
                </div>
            </div>

            <!-- Total Orders Card (الأزرق الفني) -->
            <div class="p-5 bg-gradient-to-br from-white to-indigo-50/40 dark:from-gray-900 dark:to-indigo-950/30 border border-indigo-100 dark:border-indigo-900/40 rounded-2xl shadow-sm hover:shadow-md transition-all duration-300">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold text-indigo-900 dark:text-indigo-300 uppercase tracking-wider">إجمالي الطلبات</span>
                    <span class="w-10 h-10 bg-indigo-600/10 text-indigo-600 rounded-xl flex items-center justify-center text-xl icon-orders"></span>
                </div>
                <div class="mt-3">
                    <h3 class="text-2xl font-black text-slate-900 dark:text-white">
                        {{ number_format($advancedData['executive']['total_orders'] ?? 0) }}
                    </h3>
                    <div class="flex items-center justify-between mt-2 text-xs">
                        <span class="text-indigo-600 dark:text-indigo-400 font-bold">طلب مؤهل في المتجر</span>
                        <span class="text-slate-400">مكتمل ومستمر</span>
                    </div>
                </div>
            </div>

            <!-- Owned Stock Card (الزمردي الأخضر) -->
            <div class="p-5 bg-gradient-to-br from-white to-emerald-50/40 dark:from-gray-900 dark:to-emerald-950/30 border border-emerald-100 dark:border-emerald-900/40 rounded-2xl shadow-sm hover:shadow-md transition-all duration-300">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold text-emerald-900 dark:text-emerald-300 uppercase tracking-wider">المخزون المملوك القابل للتسليم</span>
                    <span class="w-10 h-10 bg-emerald-600/10 text-emerald-600 rounded-xl flex items-center justify-center text-xl icon-product"></span>
                </div>
                <div class="mt-3">
                    <h3 class="text-2xl font-black text-emerald-700 dark:text-emerald-400">
                        {{ number_format($advancedData['executive']['owned_stock_qty'] ?? 0) }} <span class="text-sm font-semibold">وحدة</span>
                    </h3>
                    <div class="flex items-center justify-between mt-2 text-xs">
                        <span class="text-emerald-700 dark:text-emerald-400 font-bold">مستودع اليمن والسعودية</span>
                        <span class="text-slate-400">مؤكد ومفصول</span>
                    </div>
                </div>
            </div>

            <!-- Customers Card (الكهرماني الذهبي) -->
            <div class="p-5 bg-gradient-to-br from-white to-amber-50/40 dark:from-gray-900 dark:to-amber-950/30 border border-amber-100 dark:border-amber-900/40 rounded-2xl shadow-sm hover:shadow-md transition-all duration-300">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold text-amber-900 dark:text-amber-300 uppercase tracking-wider">إجمالي العملاء والنشاط</span>
                    <span class="w-10 h-10 bg-amber-600/10 text-amber-600 rounded-xl flex items-center justify-center text-xl icon-customer"></span>
                </div>
                <div class="mt-3">
                    <h3 class="text-2xl font-black text-slate-900 dark:text-white">
                        {{ number_format($advancedData['executive']['total_customers'] ?? 0) }}
                    </h3>
                    <div class="flex items-center justify-between mt-2 text-xs">
                        <span class="text-amber-700 dark:text-amber-400 font-bold">عميل مسجل ونشط</span>
                        <span class="text-slate-400">قاعدة البيانات</span>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- 2. Order Lifecycle Pipeline (مسار دورة حياة الطلب الأفقية) -->
    @if (bouncer()->hasPermission('sales.orders'))
        <div class="p-6 bg-white dark:bg-gray-900 border border-slate-200 dark:border-gray-800 rounded-2xl shadow-sm">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h3 class="text-base font-bold text-slate-900 dark:text-white">مسار دورة حياة الطلبات والتوريد (Order Lifecycle Pipeline)</h3>
                    <p class="text-xs text-slate-500 mt-0.5">تتبع مراحل الطلبات من الإنشاء والتأكيد حتى الاستلام والتوصيل النهائي</p>
                </div>
                <span class="text-xs font-mono px-3 py-1 bg-slate-100 dark:bg-slate-800 rounded-full text-slate-600 dark:text-slate-300 font-bold">11 مرحلة</span>
            </div>

            <!-- Horizontal Pipeline Bar -->
            <div class="grid grid-cols-2 sm:grid-cols-5 lg:grid-cols-11 gap-2 text-center">
                <div class="p-3 bg-slate-50 dark:bg-slate-800/60 rounded-xl border border-slate-200/80 dark:border-slate-700/60">
                    <span class="text-[10px] font-bold text-slate-500 block mb-1">1. جديد</span>
                    <span class="text-base font-black text-slate-800 dark:text-white">{{ number_format($advancedData['pipeline']['pending_procurement'] ?? 0) }}</span>
                </div>
                <div class="p-3 bg-blue-50/70 dark:bg-blue-950/40 rounded-xl border border-blue-100 dark:border-blue-900/50">
                    <span class="text-[10px] font-bold text-blue-700 dark:text-blue-300 block mb-1">2. الدفع</span>
                    <span class="text-base font-black text-blue-800 dark:text-blue-200">0</span>
                </div>
                <div class="p-3 bg-indigo-50/70 dark:bg-indigo-950/40 rounded-xl border border-indigo-100 dark:border-indigo-900/50">
                    <span class="text-[10px] font-bold text-indigo-700 dark:text-indigo-300 block mb-1">3. التأكيد</span>
                    <span class="text-base font-black text-indigo-800 dark:text-indigo-200">0</span>
                </div>
                <div class="p-3 bg-amber-50/70 dark:bg-amber-950/40 rounded-xl border border-amber-100 dark:border-amber-900/50">
                    <span class="text-[10px] font-bold text-amber-700 dark:text-amber-300 block mb-1">4. التوريد</span>
                    <span class="text-base font-black text-amber-800 dark:text-amber-200">{{ number_format($advancedData['pipeline']['pending_procurement'] ?? 0) }}</span>
                </div>
                <div class="p-3 bg-amber-100/50 dark:bg-amber-900/30 rounded-xl border border-amber-200 dark:border-amber-800/50">
                    <span class="text-[10px] font-bold text-amber-800 dark:text-amber-300 block mb-1">5. الشراء (PO)</span>
                    <span class="text-base font-black text-amber-900 dark:text-amber-200">{{ number_format($advancedData['pipeline']['purchase_orders'] ?? 0) }}</span>
                </div>
                <div class="p-3 bg-purple-50/70 dark:bg-purple-950/40 rounded-xl border border-purple-100 dark:border-purple-900/50">
                    <span class="text-[10px] font-bold text-purple-700 dark:text-purple-300 block mb-1">6. شحن المصدر</span>
                    <span class="text-base font-black text-purple-800 dark:text-purple-200">0</span>
                </div>
                <div class="p-3 bg-sky-50/70 dark:bg-sky-950/40 rounded-xl border border-sky-100 dark:border-sky-900/50">
                    <span class="text-[10px] font-bold text-sky-700 dark:text-sky-300 block mb-1">7. استلام SA</span>
                    <span class="text-base font-black text-sky-800 dark:text-sky-200">{{ number_format($advancedData['pipeline']['inbound_receipts'] ?? 0) }}</span>
                </div>
                <div class="p-3 bg-cyan-50/70 dark:bg-cyan-950/40 rounded-xl border border-cyan-100 dark:border-cyan-900/50">
                    <span class="text-[10px] font-bold text-cyan-700 dark:text-cyan-300 block mb-1">8. نقل YE</span>
                    <span class="text-base font-black text-cyan-800 dark:text-cyan-200">{{ number_format($advancedData['pipeline']['transfers'] ?? 0) }}</span>
                </div>
                <div class="p-3 bg-teal-50/70 dark:bg-teal-950/40 rounded-xl border border-teal-100 dark:border-teal-900/50">
                    <span class="text-[10px] font-bold text-teal-700 dark:text-teal-300 block mb-1">9. استلام YE</span>
                    <span class="text-base font-black text-teal-800 dark:text-teal-200">0</span>
                </div>
                <div class="p-3 bg-emerald-50/70 dark:bg-emerald-950/40 rounded-xl border border-emerald-100 dark:border-emerald-900/50">
                    <span class="text-[10px] font-bold text-emerald-700 dark:text-emerald-300 block mb-1">10. Handoff</span>
                    <span class="text-base font-black text-emerald-800 dark:text-emerald-200">0</span>
                </div>
                <div class="p-3 bg-emerald-100/70 dark:bg-emerald-900/50 rounded-xl border border-emerald-300 dark:border-emerald-700">
                    <span class="text-[10px] font-bold text-emerald-900 dark:text-emerald-200 block mb-1">11. تم التسليم</span>
                    <span class="text-base font-black text-emerald-950 dark:text-white">{{ number_format($advancedData['pipeline']['delivered'] ?? 0) }}</span>
                </div>
            </div>
        </div>
    @endif

    <!-- 3. Supply Chain & Inventory Breakdown with Source Tagging -->
    @if (bouncer()->hasPermission('settings.inventory_sources') || bouncer()->hasPermission('inventory.reports'))
        <div class="p-6 bg-white dark:bg-gray-900 border border-slate-200 dark:border-gray-800 rounded-2xl shadow-sm">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h3 class="text-base font-bold text-slate-900 dark:text-white">صحة التوريد والأرصدة حسب المصدر</h3>
                    <p class="text-xs text-slate-500 mt-0.5">فصل التوفر الخارجي والكتالوج الافتراضي عن المخزون المملوك لشركة هايست</p>
                </div>
                <a href="{{ Route::has('admin.inventory.reports.index') ? route('admin.inventory.reports.index') : '#' }}" class="text-xs font-bold text-blue-600 dark:text-blue-400 hover:underline">
                    تقرير الأرصدة الكامل &rarr;
                </a>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-right text-xs">
                    <thead>
                        <tr class="border-b border-slate-200 dark:border-gray-800 text-slate-500 font-bold bg-slate-50/50 dark:bg-slate-800/30">
                            <th class="py-3 px-3 text-right">رمز المصدر</th>
                            <th class="py-3 px-3 text-right">اسم المصدر</th>
                            <th class="py-3 px-3 text-center">التصنيف والوسم الفني</th>
                            <th class="py-3 px-3 text-center">عدد الأصناف (SKUs)</th>
                            <th class="py-3 px-3 text-center">الكمية الإجمالية</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-gray-800">
                        @foreach ($advancedData['supply_chain']['sources_report'] as $src)
                            <tr class="hover:bg-slate-50/80 dark:hover:bg-gray-800/40 transition-colors">
                                <td class="py-3 px-3 font-mono font-bold text-slate-800 dark:text-slate-200">{{ $src->code }}</td>
                                <td class="py-3 px-3 font-semibold text-slate-700 dark:text-slate-300">{{ $src->name }}</td>
                                <td class="py-3 px-3 text-center">
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-bold bg-emerald-100 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800/50">
                                        مخزون مملوك (Owned Stock)
                                    </span>
                                </td>
                                <td class="py-3 px-3 text-center font-bold text-slate-800 dark:text-slate-200">{{ number_format($src->total_skus) }}</td>
                                <td class="py-3 px-3 text-center font-black text-emerald-600 dark:text-emerald-400">{{ number_format($src->total_quantity) }}</td>
                            </tr>
                        @endforeach

                        <!-- Legacy Default Source Entry -->
                        <tr class="bg-slate-100/70 dark:bg-slate-800/30">
                            <td class="py-3 px-3 font-mono font-bold text-slate-600">default</td>
                            <td class="py-3 px-3 font-medium text-slate-600">افتراضي (Legacy Default Source)</td>
                            <td class="py-3 px-3 text-center">
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-bold bg-slate-200 text-slate-700 dark:bg-slate-700 dark:text-slate-300 border border-slate-300">
                                    (Legacy / External) - توفر خارجي غير مملوك
                                </span>
                            </td>
                            <td class="py-3 px-3 text-center font-bold text-slate-600">{{ number_format($advancedData['supply_chain']['legacy_external_count']) }}</td>
                            <td class="py-3 px-3 text-center font-black text-slate-600">{{ number_format($advancedData['supply_chain']['legacy_external_qty']) }}</td>
                        </tr>

                        <!-- AliExpress Virtual Catalog Entry -->
                        <tr class="bg-purple-50/50 dark:bg-purple-950/20">
                            <td class="py-3 px-3 font-mono font-bold text-purple-700 dark:text-purple-300">aliexpress_source</td>
                            <td class="py-3 px-3 font-medium text-purple-800 dark:text-purple-300">AliExpress Virtual Catalog Source</td>
                            <td class="py-3 px-3 text-center">
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-bold bg-purple-100 text-purple-800 dark:bg-purple-900/60 dark:text-purple-300 border border-purple-200">
                                    (Virtual Projection) - كتالوج افتراضي
                                </span>
                            </td>
                            <td class="py-3 px-3 text-center font-bold text-purple-800 dark:text-purple-300">{{ number_format($advancedData['supply_chain']['virtual_projection_count']) }}</td>
                            <td class="py-3 px-3 text-center font-black text-purple-800 dark:text-purple-300">{{ number_format($advancedData['supply_chain']['virtual_projection_qty']) }}</td>
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
            <div class="p-6 bg-white dark:bg-gray-900 border border-slate-200 dark:border-gray-800 rounded-2xl shadow-sm">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-base font-bold text-slate-900 dark:text-white">التسليم الفعلي للميل الأخير (Delivery Metrics)</h3>
                    <a href="{{ Route::has('admin.delivery.index') ? route('admin.delivery.index') : '#' }}" class="text-xs font-bold text-blue-600 dark:text-blue-400 hover:underline">لوحة التوصيل &rarr;</a>
                </div>
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 text-center">
                    <div class="p-3 bg-slate-50 dark:bg-slate-800/60 rounded-xl border border-slate-200/80">
                        <span class="text-xs text-slate-500 font-semibold block mb-1">إجمالي المهام</span>
                        <span class="text-lg font-black text-slate-900 dark:text-white">{{ number_format($advancedData['delivery']['total_assignments']) }}</span>
                    </div>
                    <div class="p-3 bg-blue-50 dark:bg-blue-950/40 rounded-xl border border-blue-100 dark:border-blue-900/50">
                        <span class="text-xs text-blue-700 dark:text-blue-300 font-semibold block mb-1">قيد التنسيق</span>
                        <span class="text-lg font-black text-blue-800 dark:text-blue-200">{{ number_format($advancedData['delivery']['pending']) }}</span>
                    </div>
                    <div class="p-3 bg-amber-50 dark:bg-amber-950/40 rounded-xl border border-amber-100 dark:border-amber-900/50">
                        <span class="text-xs text-amber-700 dark:text-amber-300 font-semibold block mb-1">جاري التوصيل</span>
                        <span class="text-lg font-black text-amber-800 dark:text-amber-200">{{ number_format($advancedData['delivery']['in_transit']) }}</span>
                    </div>
                    <div class="p-3 bg-emerald-50 dark:bg-emerald-950/40 rounded-xl border border-emerald-100 dark:border-emerald-900/50">
                        <span class="text-xs text-emerald-700 dark:text-emerald-300 font-semibold block mb-1">مكتمل</span>
                        <span class="text-lg font-black text-emerald-800 dark:text-emerald-200">{{ number_format($advancedData['delivery']['completed']) }}</span>
                    </div>
                </div>
            </div>
        @endif

        <!-- Financial Section -->
        @if (bouncer()->hasPermission('sales.transactions') || bouncer()->hasPermission('wallet'))
            <div class="p-6 bg-white dark:bg-gray-900 border border-slate-200 dark:border-gray-800 rounded-2xl shadow-sm">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-base font-bold text-slate-900 dark:text-white">المالية والتحصيل (Financial Ledger)</h3>
                    <a href="{{ Route::has('admin.sales.transactions.index') ? route('admin.sales.transactions.index') : '#' }}" class="text-xs font-bold text-blue-600 dark:text-blue-400 hover:underline">سجل المعاملات &rarr;</a>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div class="p-4 bg-gradient-to-br from-emerald-50 to-teal-50/50 dark:from-emerald-950/40 dark:to-teal-950/20 rounded-xl border border-emerald-100 dark:border-emerald-900/40">
                        <span class="text-xs font-bold text-emerald-800 dark:text-emerald-300 block mb-1">إجمالي التحصيل النقدي (COD)</span>
                        <span class="text-xl font-black text-emerald-900 dark:text-emerald-200">{{ core()->formatBasePrice($advancedData['financial']['cash_collected_sum']) }}</span>
                    </div>
                    <div class="p-4 bg-gradient-to-br from-blue-50 to-indigo-50/50 dark:from-blue-950/40 dark:to-indigo-950/20 rounded-xl border border-blue-100 dark:border-blue-900/40">
                        <span class="text-xs font-bold text-blue-800 dark:text-blue-300 block mb-1">إجمالي الإيرادات المسجلة</span>
                        <span class="text-xl font-black text-blue-900 dark:text-blue-200">{{ core()->formatBasePrice($advancedData['financial']['total_sales']) }}</span>
                    </div>
                </div>
            </div>
        @endif
    </div>

    <!-- 5. Exceptions & System Alerts (التنبيهات والاستثناءات) -->
    @if (bouncer()->hasPermission('dashboard'))
        <div class="p-6 bg-white dark:bg-gray-900 border border-slate-200 dark:border-gray-800 rounded-2xl shadow-sm">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-base font-bold text-slate-900 dark:text-white">مركز التنبيهات والاستثناءات (System Alerts & Exceptions)</h3>
                <span class="text-xs font-bold px-2.5 py-0.5 bg-rose-100 text-rose-800 dark:bg-rose-950 dark:text-rose-300 rounded-full">استثناءات حية</span>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div class="p-4 bg-slate-50 dark:bg-slate-800/70 rounded-xl border border-slate-200 dark:border-slate-700 flex items-center justify-between">
                    <div>
                        <span class="text-xs text-slate-500 font-bold block mb-0.5">لقطات التوفر الخارجي (Snapshots)</span>
                        <span class="text-lg font-black text-slate-900 dark:text-white">{{ number_format($advancedData['supply_chain']['external_snapshots_count']) }}</span>
                    </div>
                    <span class="px-2.5 py-1 bg-slate-200 text-slate-700 rounded-lg text-xs font-mono font-bold">Active</span>
                </div>

                <div class="p-4 bg-amber-50/80 dark:bg-amber-950/30 rounded-xl border border-amber-200 dark:border-amber-900/50 flex items-center justify-between">
                    <div>
                        <span class="text-xs text-amber-800 dark:text-amber-400 font-bold block mb-0.5">لقطات منتهية الصلاحية (Stale)</span>
                        <span class="text-lg font-black text-amber-950 dark:text-amber-200">{{ number_format($advancedData['exceptions']['stale_snapshots']) }}</span>
                    </div>
                    <span class="px-2.5 py-1 bg-amber-200 text-amber-900 rounded-lg text-xs font-mono font-bold">Alert</span>
                </div>

                <div class="p-4 bg-rose-50/80 dark:bg-rose-950/30 rounded-xl border border-rose-200 dark:border-rose-900/50 flex items-center justify-between">
                    <div>
                        <span class="text-xs text-rose-800 dark:text-rose-400 font-bold block mb-0.5">كميات الحجر الصحي (Quarantine)</span>
                        <span class="text-lg font-black text-rose-950 dark:text-rose-200">{{ number_format($advancedData['exceptions']['quarantine_qty_ye'] + $advancedData['exceptions']['quarantine_qty_sa']) }}</span>
                    </div>
                    <span class="px-2.5 py-1 bg-rose-200 text-rose-900 rounded-lg text-xs font-mono font-bold">Held</span>
                </div>
            </div>
        </div>
    @endif

</div>
