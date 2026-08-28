<x-admin::layouts>
    <x-slot:title>
        تقرير العملاء التفصيلي — التقارير التفصيلية
    </x-slot>

    <div class="flex flex-col gap-5 p-4 sm:p-6 print:p-0">
        <!-- Header & Action Controls -->
        <div class="flex flex-wrap items-center justify-between gap-4 print:hidden">
            <div class="flex flex-col gap-1">
                <div class="flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400">
                    <span>التقارير التفصيلية</span>
                    <span>/</span>
                    <span class="font-medium text-gray-800 dark:text-white">تقرير العملاء</span>
                </div>
                <h1 class="text-2xl font-bold text-gray-800 dark:text-white">
                    👥 تقرير العملاء الشامل (Customers Report)
                </h1>
                <p class="text-sm text-gray-600 dark:text-gray-300">
                    تحليل مالي وتشغيلي دقيق لأداء العملاء، المبيعات الصافية، تكاليف المنتجات، الأرباح، هوامش الربحية، وسجل الطلبات.
                </p>
            </div>

            <div class="flex flex-wrap items-center gap-2.5">
                <!-- Per Page Selector -->
                <div class="flex items-center gap-1.5 px-3 py-1.5 text-xs rounded-lg border border-gray-300 bg-white text-gray-700 shadow-sm dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200">
                    <span class="font-medium text-gray-500 dark:text-gray-400">عرض:</span>
                    <select
                        onchange="changePerPage(this.value)"
                        class="text-xs font-semibold rounded bg-transparent border-0 py-0.5 px-1 text-gray-800 focus:ring-0 dark:text-white cursor-pointer"
                    >
                        <option value="25" {{ $perPage == 25 ? 'selected' : '' }}>25 صف</option>
                        <option value="50" {{ $perPage == 50 ? 'selected' : '' }}>50 صف</option>
                        <option value="100" {{ $perPage == 100 ? 'selected' : '' }}>100 صف</option>
                    </select>
                </div>

                <!-- Print Dropdown Menu Button -->
                <div class="relative inline-block text-right" id="printMenuWrapper" style="position: relative; z-index: 50;">
                    <button
                        type="button"
                        onclick="togglePrintMenu(event)"
                        class="inline-flex items-center gap-2 px-3.5 py-2 text-sm font-semibold rounded-lg border border-gray-300 bg-white text-gray-700 shadow-sm hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700 transition"
                    >
                        <span class="icon-printer text-lg"></span>
                        <span>طباعة التقرير</span>
                        <span class="text-[10px] text-gray-500">▼</span>
                    </button>
                    
                    <div id="printDropdownMenu" 
                         class="hidden absolute left-0 rtl:right-0 mt-1.5 w-72 rounded-xl shadow-2xl border py-1.5 z-50"
                         style="position: absolute; z-index: 999999 !important; background: #ffffff !important; color: #1f2937 !important; border: 1px solid #e2e8f0 !important; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.25), 0 10px 10px -5px rgba(0, 0, 0, 0.1) !important;">
                        <div class="px-3 py-1.5 text-[11px] font-bold text-gray-500 border-b border-gray-100" style="background: #f8fafc !important;">
                            طباعة الصفحة الحالية
                        </div>
                        <button
                            type="button"
                            onclick="printTableOnly(true); closePrintMenu();"
                            class="w-full text-right px-4 py-2.5 text-xs font-semibold text-gray-700 hover:bg-blue-50 hover:text-blue-600 flex items-center justify-between transition"
                            style="background: transparent; border: none; cursor: pointer;"
                        >
                            <span class="flex items-center gap-2">
                                <span>📋</span>
                                <span>طباعة شاملة مع تفاصيل الطلبات</span>
                            </span>
                            <span class="text-[10px] px-1.5 py-0.5 rounded bg-blue-100 text-blue-700 font-bold">شامل</span>
                        </button>
                        <button
                            type="button"
                            onclick="printTableOnly(false); closePrintMenu();"
                            class="w-full text-right px-4 py-2.5 text-xs font-semibold text-gray-700 hover:bg-blue-50 hover:text-blue-600 flex items-center justify-between border-t border-gray-100 transition"
                            style="background: transparent; cursor: pointer;"
                        >
                            <span class="flex items-center gap-2">
                                <span>📄</span>
                                <span>طباعة ملخص العملاء فقط</span>
                            </span>
                            <span class="text-[10px] px-1.5 py-0.5 rounded bg-gray-100 text-gray-600 font-bold">ملخص</span>
                        </button>

                        <div class="px-3 py-1.5 text-[11px] font-bold text-blue-600 border-t border-b border-gray-100" style="background: #eff6ff !important;">
                            طباعة التقرير كاملاً (جميع النتائج)
                        </div>
                        <button
                            type="button"
                            onclick="printAllRecords(true); closePrintMenu();"
                            class="w-full text-right px-4 py-2.5 text-xs font-semibold text-blue-700 hover:bg-blue-50 flex items-center justify-between transition"
                            style="background: transparent; border: none; cursor: pointer;"
                        >
                            <span class="flex items-center gap-2">
                                <span>🌐</span>
                                <span>طباعة كل النتائج (مع الطلبات)</span>
                            </span>
                            <span class="text-[10px] px-1.5 py-0.5 rounded bg-blue-600 text-white font-bold">الكل</span>
                        </button>
                        <button
                            type="button"
                            onclick="printAllRecords(false); closePrintMenu();"
                            class="w-full text-right px-4 py-2.5 text-xs font-semibold text-gray-700 hover:bg-blue-50 flex items-center justify-between border-t border-gray-100 transition"
                            style="background: transparent; cursor: pointer;"
                        >
                            <span class="flex items-center gap-2">
                                <span>🌐</span>
                                <span>طباعة كل النتائج (ملخص فقط)</span>
                            </span>
                            <span class="text-[10px] px-1.5 py-0.5 rounded bg-gray-600 text-white font-bold">الكل</span>
                        </button>
                    </div>
                </div>

                <!-- Export PDF Button -->
                <a
                    href="{{ route('admin.detailed_reports.customers.export', array_merge(request()->query(), ['format' => 'pdf'])) }}"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="inline-flex items-center gap-2 px-3.5 py-2 text-sm font-semibold rounded-lg border border-rose-300 bg-rose-50 text-rose-700 shadow-sm hover:bg-rose-100 dark:border-rose-800 dark:bg-rose-950/40 dark:text-rose-300 dark:hover:bg-rose-900/50 transition"
                    title="تصدير وحفظ التقرير كملف PDF"
                >
                    <span class="icon-export text-lg"></span>
                    <span>تصدير PDF</span>
                </a>

                <!-- Export CSV Button -->
                <a
                    href="{{ route('admin.detailed_reports.customers.export', array_merge(request()->query(), ['format' => 'csv'])) }}"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="inline-flex items-center gap-2 px-3.5 py-2 text-sm font-semibold rounded-lg border border-gray-300 bg-white text-gray-700 shadow-sm hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200 transition"
                    title="تصدير ملف CSV"
                >
                    <span class="icon-export text-lg"></span>
                    <span>تصدير CSV</span>
                </a>

                <!-- Export Excel XLSX Button -->
                <a
                    href="{{ route('admin.detailed_reports.customers.export', array_merge(request()->query(), ['format' => 'xlsx'])) }}"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="inline-flex items-center gap-2 px-4 py-2 text-sm font-semibold rounded-lg bg-emerald-600 text-white shadow-sm hover:bg-emerald-700 transition"
                >
                    <span class="icon-export text-lg"></span>
                    <span>تصدير Excel</span>
                </a>
            </div>
        </div>

        @php
            $logoUrl = ($logo = core()->getConfigData('general.design.admin_logo.logo_image')) ? Storage::url($logo) : bagisto_asset('images/logo.svg');
        @endphp

        <!-- Print-Only Official Report Header -->
        <div class="hidden print:block mb-4 border-b-2 border-gray-800 pb-3">
            <div class="flex justify-between items-center">
                <div>
                    <h1 class="text-xl font-bold text-black">تقرير العملاء التفصيلي — هايست</h1>
                    <p class="text-xs text-gray-700 mt-0.5">تاريخ ووقت استخراج التقرير: {{ $generatedAt }}</p>
                </div>
                <div class="text-left">
                    <img src="{{ $logoUrl }}" alt="هايست" class="h-9 w-auto object-contain">
                </div>
            </div>

            <!-- Print Applied Filters Summary -->
            @php
                $activeFilterLabels = [];
                if (!empty($filters['search'])) $activeFilterLabels[] = 'بحث: ' . $filters['search'];
                if (!empty($filters['customer_id'])) $activeFilterLabels[] = 'معرف العميل: ' . $filters['customer_id'];
                if (!empty($filters['name'])) $activeFilterLabels[] = 'اسم العميل: ' . $filters['name'];
                if (!empty($filters['email'])) $activeFilterLabels[] = 'البريد: ' . $filters['email'];
                if (!empty($filters['phone'])) $activeFilterLabels[] = 'الهاتف: ' . $filters['phone'];
                if (!empty($filters['segment'])) {
                    $segNames = [
                        'high_value' => 'عميل عالي القيمة (VIP)',
                        'repeat' => 'عميل متكرر',
                        'new' => 'عميل جديد',
                        'inactive' => 'عميل غير نشط',
                        'no_orders' => 'مسجل بدون طلبات'
                    ];
                    $activeFilterLabels[] = 'التصنيف: ' . ($segNames[$filters['segment']] ?? $filters['segment']);
                }
                if (isset($filters['status']) && $filters['status'] !== '') $activeFilterLabels[] = 'الحالة: ' . ($filters['status'] == '1' ? 'نشط' : 'معطل');
                if (!empty($filters['customer_group_id']) && isset($groups[$filters['customer_group_id']])) {
                    $activeFilterLabels[] = 'المجموعة: ' . $groups[$filters['customer_group_id']];
                }
            @endphp

            @if(count($activeFilterLabels) > 0)
                <div class="mt-2 text-xs bg-gray-100 p-2 rounded">
                    <strong>الفلاتر المطبقة:</strong> {{ implode(' | ', $activeFilterLabels) }}
                </div>
            @endif
        </div>

        <style>
            .detailed-filters-container {
                display: flex !important;
                flex-direction: row !important;
                flex-wrap: wrap !important;
                gap: 16px !important;
                width: 100% !important;
            }
            .detailed-filter-item {
                flex: 0 0 calc(33.3333% - 11px) !important;
                width: calc(33.3333% - 11px) !important;
                max-width: calc(33.3333% - 11px) !important;
                min-width: 220px !important;
                box-sizing: border-box !important;
                display: flex !important;
                flex-direction: column !important;
                gap: 4px !important;
            }
            @media (max-width: 1024px) {
                .detailed-filter-item {
                    flex: 0 0 calc(50% - 8px) !important;
                    width: calc(50% - 8px) !important;
                    max-width: calc(50% - 8px) !important;
                }
            }
            @media (max-width: 640px) {
                .detailed-filter-item {
                    flex: 0 0 100% !important;
                    width: 100% !important;
                    max-width: 100% !important;
                }
            }
        </style>

        <!-- Advanced Filter & Search Box -->
        <div class="bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-800 p-4 shadow-sm print:hidden">
            <form method="GET" action="{{ route('admin.detailed_reports.customers.index') }}" id="filterForm">
                <input type="hidden" name="sort" value="{{ $currentSort }}">
                <input type="hidden" name="order" value="{{ $currentOrder }}">
                <input type="hidden" name="per_page" value="{{ $perPage }}">

                <div class="detailed-filters-container" style="display: flex !important; flex-direction: row !important; flex-wrap: wrap !important; gap: 16px !important; width: 100% !important;">
                    <!-- Global Search -->
                    <div class="detailed-filter-item" style="flex: 0 0 calc(33.3333% - 11px); max-width: calc(33.3333% - 11px); min-width: 220px; box-sizing: border-box; display: flex; flex-direction: column; gap: 4px;">
                        <label class="text-xs font-semibold text-gray-700 dark:text-gray-300">بحث شامل (الاسم، البريد، الهاتف، ID)</label>
                        <input
                            type="text"
                            name="search"
                            value="{{ request('search') }}"
                            placeholder="ابحث بأي بيان للعميل..."
                            class="w-full text-xs rounded-lg border border-gray-300 bg-white px-3 py-2 text-gray-800 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                        >
                    </div>

                    <!-- Customer Status -->
                    <div class="detailed-filter-item" style="flex: 0 0 calc(33.3333% - 11px); max-width: calc(33.3333% - 11px); min-width: 220px; box-sizing: border-box; display: flex; flex-direction: column; gap: 4px;">
                        <label class="text-xs font-semibold text-gray-700 dark:text-gray-300">حالة الحساب</label>
                        <select
                            name="status"
                            class="w-full text-xs rounded-lg border border-gray-300 bg-white px-2.5 py-2 text-gray-800 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                        >
                            <option value="">جميع الحالات</option>
                            <option value="1" {{ request('status') === '1' ? 'selected' : '' }}>نشط (Active)</option>
                            <option value="0" {{ request('status') === '0' ? 'selected' : '' }}>معطل / موقوف</option>
                        </select>
                    </div>

                    <!-- Customer Classification (Segment) -->
                    <div class="detailed-filter-item" style="flex: 0 0 calc(33.3333% - 11px); max-width: calc(33.3333% - 11px); min-width: 220px; box-sizing: border-box; display: flex; flex-direction: column; gap: 4px;">
                        <label class="text-xs font-semibold text-gray-700 dark:text-gray-300">تصنيف العميل</label>
                        <select
                            name="segment"
                            class="w-full text-xs rounded-lg border border-gray-300 bg-white px-2.5 py-2 text-gray-800 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                        >
                            <option value="">جميع التصنيفات</option>
                            <option value="high_value" {{ request('segment') === 'high_value' ? 'selected' : '' }}>💎 عميل عالي القيمة (VIP)</option>
                            <option value="repeat" {{ request('segment') === 'repeat' ? 'selected' : '' }}>🔁 عميل متكرر (Repeat)</option>
                            <option value="new" {{ request('segment') === 'new' ? 'selected' : '' }}>⭐ عميل جديد (New)</option>
                            <option value="inactive" {{ request('segment') === 'inactive' ? 'selected' : '' }}>💤 عميل غير نشط (Inactive)</option>
                            <option value="no_orders" {{ request('segment') === 'no_orders' ? 'selected' : '' }}>📝 مسجل بدون طلبات</option>
                        </select>
                    </div>

                </div>

                <!-- Form Action Buttons -->
                <div class="flex items-center justify-between border-t border-gray-100 dark:border-gray-800 mt-3.5 pt-3">
                    <div class="text-xs text-gray-500">
                        عرض <strong class="text-gray-700 dark:text-gray-300">{{ $records->total() }}</strong> عميل وفق شروط الفلترة
                    </div>
                    <div class="flex items-center gap-2">
                        <a
                            href="{{ route('admin.detailed_reports.customers.index') }}"
                            class="px-3.5 py-1.5 text-xs font-semibold rounded-lg border border-gray-300 bg-white text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700 transition"
                        >
                            إعادة ضبط الفلاتر
                        </a>
                        <button
                            type="submit"
                            class="px-4 py-1.5 text-xs font-semibold rounded-lg bg-blue-600 text-white hover:bg-blue-700 shadow-sm transition"
                        >
                            تطبيق الفلاتر (Filter)
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <!-- Main Customer Report Data Table -->
        <div class="bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-800 shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table id="customerReportTable" class="w-full text-right border-collapse text-xs">
                    <thead>
                        <tr class="bg-gray-50 dark:bg-gray-800 text-gray-700 dark:text-gray-200 font-bold border-b border-gray-200 dark:border-gray-800 select-none">
                            <th class="p-3 w-10 text-center print:hidden">#</th>
                            
                            <!-- Customer ID -->
                            <th class="p-3 whitespace-nowrap">
                                <a href="{{ request()->fullUrlWithQuery(['sort' => 'customer_id', 'order' => $currentSort === 'customer_id' && $currentOrder === 'asc' ? 'desc' : 'asc']) }}" class="flex items-center gap-1 hover:text-blue-600">
                                    ID
                                    @if($currentSort === 'customer_id')
                                        <span>{{ $currentOrder === 'asc' ? '↑' : '↓' }}</span>
                                    @endif
                                </a>
                            </th>

                            <!-- Customer Name -->
                            <th class="p-3 min-w-[170px]">
                                <a href="{{ request()->fullUrlWithQuery(['sort' => 'name', 'order' => $currentSort === 'name' && $currentOrder === 'asc' ? 'desc' : 'asc']) }}" class="flex items-center gap-1 hover:text-blue-600">
                                    العميل
                                    @if($currentSort === 'name')
                                        <span>{{ $currentOrder === 'asc' ? '↑' : '↓' }}</span>
                                    @endif
                                </a>
                            </th>

                            <!-- Email & Phone -->
                            <th class="p-3 min-w-[160px]">
                                <a href="{{ request()->fullUrlWithQuery(['sort' => 'email', 'order' => $currentSort === 'email' && $currentOrder === 'asc' ? 'desc' : 'asc']) }}" class="flex items-center gap-1 hover:text-blue-600">
                                    البريد والهاتف
                                    @if($currentSort === 'email')
                                        <span>{{ $currentOrder === 'asc' ? '↑' : '↓' }}</span>
                                    @endif
                                </a>
                            </th>

                            <!-- Classification & Status -->
                            <th class="p-3 whitespace-nowrap text-center">
                                <a href="{{ request()->fullUrlWithQuery(['sort' => 'segment', 'order' => $currentSort === 'segment' && $currentOrder === 'asc' ? 'desc' : 'asc']) }}" class="flex items-center justify-center gap-1 hover:text-blue-600">
                                    التصنيف والحالة
                                    @if($currentSort === 'segment')
                                        <span>{{ $currentOrder === 'asc' ? '↑' : '↓' }}</span>
                                    @endif
                                </a>
                            </th>

                            <!-- Orders Count Breakdown -->
                            <th class="p-3 whitespace-nowrap text-center">
                                <a href="{{ request()->fullUrlWithQuery(['sort' => 'orders_count', 'order' => $currentSort === 'orders_count' && $currentOrder === 'asc' ? 'desc' : 'asc']) }}" class="flex items-center justify-center gap-1 hover:text-blue-600">
                                    الطلبات
                                    @if($currentSort === 'orders_count')
                                        <span>{{ $currentOrder === 'asc' ? '↑' : '↓' }}</span>
                                    @endif
                                </a>
                            </th>

                            <!-- Gross Sales -->
                            <th class="p-3 whitespace-nowrap">
                                <a href="{{ request()->fullUrlWithQuery(['sort' => 'gross_sales', 'order' => $currentSort === 'gross_sales' && $currentOrder === 'asc' ? 'desc' : 'asc']) }}" class="flex items-center gap-1 hover:text-blue-600">
                                    إجمالي المبيعات
                                    @if($currentSort === 'gross_sales')
                                        <span>{{ $currentOrder === 'asc' ? '↑' : '↓' }}</span>
                                    @endif
                                </a>
                            </th>

                            <!-- Net Sales -->
                            <th class="p-3 whitespace-nowrap">
                                <a href="{{ request()->fullUrlWithQuery(['sort' => 'net_sales', 'order' => $currentSort === 'net_sales' && $currentOrder === 'asc' ? 'desc' : 'asc']) }}" class="flex items-center gap-1 hover:text-blue-600">
                                    صافي المبيعات
                                    @if($currentSort === 'net_sales')
                                        <span>{{ $currentOrder === 'asc' ? '↑' : '↓' }}</span>
                                    @endif
                                </a>
                            </th>

                            <!-- Total Cost -->
                            <th class="p-3 whitespace-nowrap">
                                <a href="{{ request()->fullUrlWithQuery(['sort' => 'total_cost', 'order' => $currentSort === 'total_cost' && $currentOrder === 'asc' ? 'desc' : 'asc']) }}" class="flex items-center gap-1 hover:text-blue-600">
                                    التكلفة
                                    @if($currentSort === 'total_cost')
                                        <span>{{ $currentOrder === 'asc' ? '↑' : '↓' }}</span>
                                    @endif
                                </a>
                            </th>

                            <!-- Total Profit -->
                            <th class="p-3 whitespace-nowrap">
                                <a href="{{ request()->fullUrlWithQuery(['sort' => 'total_profit', 'order' => $currentSort === 'total_profit' && $currentOrder === 'asc' ? 'desc' : 'asc']) }}" class="flex items-center gap-1 hover:text-blue-600">
                                    الربح
                                    @if($currentSort === 'total_profit')
                                        <span>{{ $currentOrder === 'asc' ? '↑' : '↓' }}</span>
                                    @endif
                                </a>
                            </th>

                            <!-- Profit Margin % -->
                            <th class="p-3 whitespace-nowrap text-center">
                                <a href="{{ request()->fullUrlWithQuery(['sort' => 'profit_margin', 'order' => $currentSort === 'profit_margin' && $currentOrder === 'asc' ? 'desc' : 'asc']) }}" class="flex items-center justify-center gap-1 hover:text-blue-600">
                                    هامش الربح
                                    @if($currentSort === 'profit_margin')
                                        <span>{{ $currentOrder === 'asc' ? '↑' : '↓' }}</span>
                                    @endif
                                </a>
                            </th>

                            <!-- Average Order Value (AOV) -->
                            <th class="p-3 whitespace-nowrap">
                                <a href="{{ request()->fullUrlWithQuery(['sort' => 'avg_order_value', 'order' => $currentSort === 'avg_order_value' && $currentOrder === 'asc' ? 'desc' : 'asc']) }}" class="flex items-center gap-1 hover:text-blue-600">
                                    متوسط الطلب
                                    @if($currentSort === 'avg_order_value')
                                        <span>{{ $currentOrder === 'asc' ? '↑' : '↓' }}</span>
                                    @endif
                                </a>
                            </th>

                            <!-- Registration Date -->
                            <th class="p-3 whitespace-nowrap">
                                <a href="{{ request()->fullUrlWithQuery(['sort' => 'created_at', 'order' => $currentSort === 'created_at' && $currentOrder === 'asc' ? 'desc' : 'asc']) }}" class="flex items-center gap-1 hover:text-blue-600">
                                    تاريخ التسجيل
                                    @if($currentSort === 'created_at')
                                        <span>{{ $currentOrder === 'asc' ? '↑' : '↓' }}</span>
                                    @endif
                                </a>
                            </th>

                            <!-- Last Order Date -->
                            <th class="p-3 whitespace-nowrap">
                                <a href="{{ request()->fullUrlWithQuery(['sort' => 'last_order_date', 'order' => $currentSort === 'last_order_date' && $currentOrder === 'asc' ? 'desc' : 'asc']) }}" class="flex items-center gap-1 hover:text-blue-600">
                                    آخر طلب
                                    @if($currentSort === 'last_order_date')
                                        <span>{{ $currentOrder === 'asc' ? '↑' : '↓' }}</span>
                                    @endif
                                </a>
                            </th>

                            <!-- Actions -->
                            <th class="p-3 whitespace-nowrap text-center print:hidden">إجراءات</th>
                        </tr>
                    </thead>
                    @forelse($records as $customer)
                        @php
                            $hasOrders = $customer->total_orders > 0;
                        @endphp
                        <tbody class="customer-item-group divide-y divide-gray-200 dark:divide-gray-800 border-b border-gray-100 dark:border-gray-800">
                            <tr
                                class="hover:bg-gray-50/80 dark:hover:bg-gray-800/40 transition {{ $hasOrders ? 'cursor-pointer parent-with-orders' : 'parent-customer-row' }}"
                                @if($hasOrders)
                                    onclick="toggleCustomerOrders({{ $customer->customer_id }})"
                                @endif
                            >
                                <!-- Expand Accordion Toggle -->
                                <td class="p-3 text-center print:hidden">
                                    @if($hasOrders)
                                        <button
                                            type="button"
                                            class="customer-toggle-btn-{{ $customer->customer_id }} text-gray-500 hover:text-blue-600 p-1 transition-transform duration-200"
                                            title="عرض سجل الطلبات"
                                        >
                                            ▶
                                        </button>
                                    @else
                                        <span class="text-gray-300 dark:text-gray-700">•</span>
                                    @endif
                                </td>

                                <!-- Customer ID -->
                                <td class="p-3 font-mono font-medium text-gray-500 dark:text-gray-400">
                                    #{{ $customer->customer_id }}
                                </td>

                                <!-- Customer Name -->
                                <td class="p-3">
                                    <div class="flex items-center gap-2">
                                        <div class="w-7 h-7 rounded-full bg-blue-100 dark:bg-blue-900/50 text-blue-700 dark:text-blue-300 font-bold flex items-center justify-center text-[11px] shrink-0">
                                            {{ mb_substr($customer->first_name ?? $customer->name, 0, 1) }}
                                        </div>
                                        <div class="flex flex-col">
                                            <span class="font-bold text-gray-900 dark:text-white">
                                                {{ $customer->name }}
                                            </span>
                                            <span class="text-[10px] text-gray-400">
                                                المجموعة: {{ $customer->group_name }}
                                            </span>
                                        </div>
                                    </div>
                                </td>

                                <!-- Email & Phone -->
                                <td class="p-3">
                                    <div class="flex flex-col text-xs">
                                        <span class="font-medium text-gray-800 dark:text-gray-200 select-all font-mono">
                                            {{ $customer->email }}
                                        </span>
                                        <span class="text-[11px] text-gray-500 font-mono select-all mt-0.5">
                                            {{ $customer->phone }}
                                        </span>
                                    </div>
                                </td>

                                <!-- Segment & Status -->
                                <td class="p-3 text-center whitespace-nowrap">
                                    <div class="flex flex-col items-center gap-1">
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold {{ $customer->segment_badge_class }}">
                                            {{ $customer->segment_label }}
                                        </span>
                                        @if($customer->is_active)
                                            <span class="text-[9px] text-emerald-600 font-semibold">● حساب نشط</span>
                                        @elseif($customer->is_suspended)
                                            <span class="text-[9px] text-rose-600 font-semibold">● موقوف</span>
                                        @else
                                            <span class="text-[9px] text-gray-400 font-semibold">● معطل</span>
                                        @endif
                                    </div>
                                </td>

                                <!-- Orders Breakdown -->
                                <td class="p-3 text-center whitespace-nowrap">
                                    <div class="flex flex-col items-center">
                                        <span class="font-bold font-mono text-sm text-gray-800 dark:text-white">
                                            {{ $customer->total_orders }}
                                        </span>
                                        <div class="flex items-center gap-1 text-[9px] text-gray-400 mt-0.5">
                                            <span class="text-emerald-600" title="مكتمل">{{ $customer->completed_orders }} مكتمل</span>
                                            @if($customer->canceled_orders > 0)
                                                <span>|</span>
                                                <span class="text-rose-600" title="ملغي">{{ $customer->canceled_orders }} ملغي</span>
                                            @endif
                                            @if($customer->refunded_orders > 0)
                                                <span>|</span>
                                                <span class="text-amber-600" title="مرتجع">{{ $customer->refunded_orders }} مرتجع</span>
                                            @endif
                                        </div>
                                    </div>
                                </td>

                                <!-- Gross Sales -->
                                <td class="p-3 font-mono font-medium text-gray-600 dark:text-gray-300 whitespace-nowrap">
                                    ${{ number_format($customer->gross_sales, 2) }}
                                </td>

                                <!-- Net Sales -->
                                <td class="p-3 font-mono font-bold text-emerald-600 dark:text-emerald-400 whitespace-nowrap">
                                    ${{ number_format($customer->net_sales, 2) }}
                                </td>

                                <!-- Total Cost -->
                                <td class="p-3 font-mono font-medium text-amber-700 dark:text-amber-400 whitespace-nowrap">
                                    ${{ number_format($customer->total_cost, 2) }}
                                </td>

                                <!-- Total Profit -->
                                <td class="p-3 font-mono font-bold text-gray-900 dark:text-white whitespace-nowrap">
                                    ${{ number_format($customer->total_profit, 2) }}
                                </td>

                                <!-- Profit Margin % -->
                                <td class="p-3 text-center font-mono font-bold whitespace-nowrap">
                                    @if($customer->profit_margin > 0)
                                        <span class="px-2 py-0.5 rounded text-xs bg-emerald-50 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-300">
                                            {{ $customer->profit_margin }}%
                                        </span>
                                    @else
                                        <span class="text-gray-400">0%</span>
                                    @endif
                                </td>

                                <!-- AOV -->
                                <td class="p-3 font-mono font-medium text-indigo-600 dark:text-indigo-400 whitespace-nowrap">
                                    ${{ number_format($customer->avg_order_value, 2) }}
                                </td>

                                <!-- Registration Date -->
                                <td class="p-3 text-gray-500 dark:text-gray-400 whitespace-nowrap font-mono text-[11px]">
                                    {{ $customer->created_at }}
                                </td>

                                <!-- Last Order Date -->
                                <td class="p-3 text-gray-600 dark:text-gray-300 whitespace-nowrap font-mono text-[11px]">
                                    {{ $customer->last_order_date }}
                                </td>

                                <!-- Actions -->
                                <td class="p-3 text-center whitespace-nowrap print:hidden" onclick="event.stopPropagation()">
                                    <div class="flex items-center justify-center gap-1.5">
                                        <a
                                            href="{{ route('admin.customers.customers.view', $customer->customer_id) }}"
                                            target="_blank"
                                            class="p-1.5 rounded-lg border border-gray-300 bg-white text-gray-700 hover:bg-gray-100 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 transition"
                                            title="فتح ملف العميل الكامل"
                                        >
                                            <span class="icon-eye text-base"></span>
                                        </a>
                                        @if($hasOrders)
                                            <button
                                                type="button"
                                                onclick="toggleCustomerOrders({{ $customer->customer_id }})"
                                                class="px-2 py-1 text-[11px] font-semibold rounded-lg bg-blue-50 text-blue-700 hover:bg-blue-100 dark:bg-blue-950/40 dark:text-blue-300 transition"
                                            >
                                                الطلبات
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>

                            <!-- Expandable Sub-Table for Customer Order History & Metrics -->
                            @if($hasOrders)
                                <tr id="orders-row-{{ $customer->customer_id }}" class="orders-subtable-row hidden bg-blue-50/25 dark:bg-gray-950/70 border-t border-blue-100 dark:border-gray-800 print:table-row">
                                    <td colspan="15" class="p-4 ltr:pl-8 rtl:pr-8">
                                        <div class="rounded-xl border border-blue-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-4 shadow-inner">
                                            <!-- Sub-Header / Summary Bar -->
                                            <div class="flex flex-wrap items-center justify-between gap-3 pb-3 border-b border-gray-100 dark:border-gray-800 mb-3">
                                                <div class="flex items-center gap-2">
                                                    <span class="text-base">📋</span>
                                                    <h4 class="text-xs font-bold text-gray-900 dark:text-white">
                                                        سجل طلبات العميل: <span class="text-blue-600">{{ $customer->name }}</span>
                                                    </h4>
                                                    <span class="text-[11px] text-gray-500">({{ $customer->total_orders }} طلبات | إجمالي القطع: {{ $customer->total_items_bought }})</span>
                                                </div>

                                                <div class="flex items-center gap-3">
                                                    <div class="flex items-center gap-2 text-[11px]">
                                                        <span class="text-gray-500">المدفوع: <strong class="text-emerald-600 font-mono">${{ number_format($customer->total_invoiced, 2) }}</strong></span>
                                                        <span class="text-gray-300">|</span>
                                                        <span class="text-gray-500">المسترد: <strong class="text-rose-600 font-mono">${{ number_format($customer->total_refunded, 2) }}</strong></span>
                                                    </div>

                                                    <a
                                                        href="{{ route('admin.customers.customers.view', $customer->customer_id) }}"
                                                        target="_blank"
                                                        class="inline-flex items-center gap-1 px-3 py-1 text-xs font-bold rounded-lg bg-blue-600 text-white hover:bg-blue-700 transition print:hidden"
                                                    >
                                                        <span>فتح ملف العميل الكامل</span>
                                                        <span>↗</span>
                                                    </a>
                                                </div>
                                            </div>

                                            <!-- Orders Mini Table Container -->
                                            <div id="orders-container-{{ $customer->customer_id }}" class="overflow-x-auto">
                                                <div class="text-center py-4 text-gray-400 text-xs">
                                                    جارٍ تحميل تفاصيل الطلبات...
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            @endif
                        </tbody>
                    @empty
                        <tbody>
                            <tr>
                                <td colspan="15" class="p-12 text-center text-gray-500 dark:text-gray-400">
                                    <div class="flex flex-col items-center justify-center gap-3">
                                        <span class="icon-customer text-5xl text-gray-300 dark:text-gray-700"></span>
                                        <p class="text-sm font-semibold">لا توجد نتائج تطابق خيارات الفلترة المحددة.</p>
                                        <a href="{{ route('admin.detailed_reports.customers.index') }}" class="text-xs font-semibold text-blue-600 hover:underline">
                                            إعادة ضبط جميع الفلاتر والبحث
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    @endforelse
                </table>
            </div>

            <!-- Pagination Controls -->
            <div class="flex flex-wrap items-center justify-between gap-4 p-4 border-t border-gray-200 dark:border-gray-800 bg-gray-50/50 dark:bg-gray-900/50 print:hidden">
                <div class="text-xs text-gray-600 dark:text-gray-300">
                    <span>عرض <strong>{{ $records->count() }}</strong> من إجمالي <strong>{{ number_format($records->total()) }}</strong> عميل</span>
                </div>

                <!-- Pagination Links -->
                <div>
                    {{ $records->links() }}
                </div>
            </div>
        </div>
    </div>

    @pushOnce('scripts')
        <script>
            const loadedCustomerOrders = {};

            function toggleCustomerOrders(customerId) {
                const row = document.getElementById('orders-row-' + customerId);
                const btn = document.querySelector('.customer-toggle-btn-' + customerId);
                if (!row) return;

                if (row.classList.contains('hidden')) {
                    row.classList.remove('hidden');
                    if (btn) btn.style.transform = 'rotate(90deg)';

                    if (!loadedCustomerOrders[customerId]) {
                        loadCustomerOrdersAjax(customerId);
                    }
                } else {
                    row.classList.add('hidden');
                    if (btn) btn.style.transform = 'rotate(0deg)';
                }
            }

            function loadCustomerOrdersAjax(customerId) {
                const container = document.getElementById('orders-container-' + customerId);
                if (!container) return;

                const url = "{{ route('admin.detailed_reports.customers.orders', ':id') }}".replace(':id', customerId);

                fetch(url)
                    .then(res => res.json())
                    .then(data => {
                        loadedCustomerOrders[customerId] = true;
                        if (!data.success || !data.orders || data.orders.length === 0) {
                            container.innerHTML = '<div class="text-center py-3 text-gray-400 text-xs">لا توجد طلبات مسجلة لهذا العميل.</div>';
                            return;
                        }

                        let html = `
                            <table class="w-full text-right text-xs border-collapse">
                                <thead>
                                    <tr class="bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 border-b border-gray-200 dark:border-gray-700">
                                        <th class="p-2 font-semibold">رقم الطلب</th>
                                        <th class="p-2 font-semibold">التاريخ</th>
                                        <th class="p-2 font-semibold text-center">الحالة</th>
                                        <th class="p-2 font-semibold text-center">القطع</th>
                                        <th class="p-2 font-semibold">إجمالي الطلب</th>
                                        <th class="p-2 font-semibold">المدفوع</th>
                                        <th class="p-2 font-semibold">المسترد</th>
                                        <th class="p-2 font-semibold">التكلفة</th>
                                        <th class="p-2 font-semibold">الربح</th>
                                        <th class="p-2 font-semibold text-center">الهامش %</th>
                                        <th class="p-2 font-semibold text-center print:hidden">عرض</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                        `;

                        data.orders.forEach(ord => {
                            let statusBadgeClass = 'bg-gray-100 text-gray-700';
                            if (ord.status === 'completed') statusBadgeClass = 'bg-green-100 text-green-800';
                            else if (ord.status === 'processing') statusBadgeClass = 'bg-blue-100 text-blue-800';
                            else if (ord.status === 'canceled') statusBadgeClass = 'bg-red-100 text-red-800';
                            else if (ord.status === 'closed') statusBadgeClass = 'bg-amber-100 text-amber-800';

                            html += `
                                <tr class="hover:bg-blue-50/30 dark:hover:bg-gray-800/40">
                                    <td class="p-2 font-mono font-bold text-gray-900 dark:text-white">
                                        #${ord.increment_id}
                                    </td>
                                    <td class="p-2 font-mono text-gray-600 dark:text-gray-400 text-[11px]">
                                        ${ord.created_at}
                                    </td>
                                    <td class="p-2 text-center">
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold ${statusBadgeClass}">
                                            ${ord.status_label}
                                        </span>
                                    </td>
                                    <td class="p-2 text-center font-mono">
                                        ${ord.total_qty}
                                    </td>
                                    <td class="p-2 font-mono font-medium text-gray-700 dark:text-gray-300">
                                        $${ord.gross_total.toFixed(2)}
                                    </td>
                                    <td class="p-2 font-mono text-emerald-600">
                                        $${ord.invoiced_total.toFixed(2)}
                                    </td>
                                    <td class="p-2 font-mono text-rose-600">
                                        $${ord.refunded_total.toFixed(2)}
                                    </td>
                                    <td class="p-2 font-mono text-amber-700 dark:text-amber-400">
                                        $${ord.cost_total.toFixed(2)}
                                    </td>
                                    <td class="p-2 font-mono font-bold text-gray-900 dark:text-white">
                                        $${ord.profit_total.toFixed(2)}
                                    </td>
                                    <td class="p-2 text-center font-mono font-bold">
                                        ${ord.profit_margin > 0 ? `<span class="text-emerald-600">${ord.profit_margin}%</span>` : `<span class="text-gray-400">0%</span>`}
                                    </td>
                                    <td class="p-2 text-center print:hidden">
                                        <a href="${ord.view_url}" target="_blank" class="text-blue-600 hover:underline font-bold text-[11px]">
                                            فتح ↗
                                        </a>
                                    </td>
                                </tr>
                            `;
                        });

                        html += `</tbody></table>`;
                        container.innerHTML = html;
                    })
                    .catch(err => {
                        container.innerHTML = '<div class="text-center py-3 text-rose-500 text-xs">تعذر جلب تفاصيل الطلبات.</div>';
                    });
            }

            function changePerPage(value) {
                const url = new URL(window.location.href);
                url.searchParams.set('per_page', value);
                url.searchParams.set('page', 1);
                window.location.href = url.toString();
            }

            function printAllRecords(includeOrders = true) {
                const isShowingAll = @json(request('per_page') == 'all' || $perPage >= 5000);
                if (isShowingAll) {
                    printTableOnly(includeOrders);
                    return;
                }

                const url = new URL(window.location.href);
                url.searchParams.set('per_page', 'all');
                url.searchParams.set('auto_print', includeOrders ? 'with_orders' : 'without_orders');
                window.location.href = url.toString();
            }

            function enforceFiltersLayout() {
                const container = document.querySelector('.detailed-filters-container');
                if (container) {
                    container.style.display = 'flex';
                    container.style.flexWrap = 'wrap';
                    container.style.gap = '16px';
                    container.style.width = '100%';
                    const items = container.querySelectorAll('.detailed-filter-item');
                    items.forEach(el => {
                        if (window.innerWidth > 1024) {
                            el.style.flex = '0 0 calc(33.3333% - 11px)';
                            el.style.maxWidth = 'calc(33.3333% - 11px)';
                            el.style.width = 'calc(33.3333% - 11px)';
                        } else if (window.innerWidth > 640) {
                            el.style.flex = '0 0 calc(50% - 8px)';
                            el.style.maxWidth = 'calc(50% - 8px)';
                            el.style.width = 'calc(50% - 8px)';
                        } else {
                            el.style.flex = '0 0 100%';
                            el.style.maxWidth = '100%';
                            el.style.width = '100%';
                        }
                    });
                }
            }
            window.addEventListener('resize', enforceFiltersLayout);
            document.addEventListener('DOMContentLoaded', enforceFiltersLayout);
            setTimeout(enforceFiltersLayout, 50);
            setTimeout(enforceFiltersLayout, 300);
            setTimeout(enforceFiltersLayout, 800);
            setTimeout(enforceFiltersLayout, 1500);

            // Auto-trigger print if redirected with auto_print
            window.addEventListener('DOMContentLoaded', () => {
                const urlParams = new URLSearchParams(window.location.search);
                const autoPrint = urlParams.get('auto_print');
                if (autoPrint) {
                    urlParams.delete('auto_print');
                    const cleanUrl = window.location.pathname + (urlParams.toString() ? '?' + urlParams.toString() : '');
                    window.history.replaceState({}, document.title, cleanUrl);

                    setTimeout(() => {
                        printTableOnly(autoPrint === 'with_orders');
                    }, 600);
                }
            });

            function togglePrintMenu(event) {
                event.stopPropagation();
                const menu = document.getElementById('printDropdownMenu');
                if (menu) menu.classList.toggle('hidden');
            }

            function closePrintMenu() {
                const menu = document.getElementById('printDropdownMenu');
                if (menu) menu.classList.add('hidden');
            }

            document.addEventListener('click', function(e) {
                const wrapper = document.getElementById('printMenuWrapper');
                if (wrapper && !wrapper.contains(e.target)) {
                    closePrintMenu();
                }
            });

            async function printTableOnly(includeOrders = true) {
                const table = document.querySelector('#customerReportTable');
                if (!table) {
                    window.print();
                    return;
                }

                // If includeOrders is true, preload all customer orders that haven't been loaded yet
                if (includeOrders) {
                    const orderRows = document.querySelectorAll('[id^="orders-row-"]');
                    const loadPromises = [];
                    orderRows.forEach(r => {
                        const customerId = r.id.replace('orders-row-', '');
                        if (!loadedCustomerOrders[customerId]) {
                            const p = new Promise((resolve) => {
                                const container = document.getElementById('orders-container-' + customerId);
                                const url = "{{ route('admin.detailed_reports.customers.orders', ':id') }}".replace(':id', customerId);
                                fetch(url)
                                    .then(res => res.json())
                                    .then(data => {
                                        loadedCustomerOrders[customerId] = true;
                                        if (data.success && data.orders && data.orders.length > 0) {
                                            let html = `
                                                <table class="w-full text-right text-xs border-collapse">
                                                    <thead>
                                                        <tr class="bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 border-b">
                                                            <th class="p-2 font-semibold">رقم الطلب</th>
                                                            <th class="p-2 font-semibold">التاريخ</th>
                                                            <th class="p-2 font-semibold text-center">الحالة</th>
                                                            <th class="p-2 font-semibold text-center">القطع</th>
                                                            <th class="p-2 font-semibold">إجمالي الطلب</th>
                                                            <th class="p-2 font-semibold">المدفوع</th>
                                                            <th class="p-2 font-semibold">المسترد</th>
                                                            <th class="p-2 font-semibold">التكلفة</th>
                                                            <th class="p-2 font-semibold">الربح</th>
                                                            <th class="p-2 font-semibold text-center">الهامش %</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                                            `;
                                            data.orders.forEach(ord => {
                                                html += `
                                                    <tr class="hover:bg-blue-50/30">
                                                        <td class="p-2 font-mono font-bold">#${ord.increment_id}</td>
                                                        <td class="p-2 font-mono text-gray-600 text-[11px]">${ord.created_at}</td>
                                                        <td class="p-2 text-center font-bold text-blue-700">${ord.status_label}</td>
                                                        <td class="p-2 text-center font-mono">${ord.total_qty}</td>
                                                        <td class="p-2 font-mono">$${ord.gross_total.toFixed(2)}</td>
                                                        <td class="p-2 font-mono text-emerald-700 font-bold">$${ord.invoiced_total.toFixed(2)}</td>
                                                        <td class="p-2 font-mono text-rose-700">$${ord.refunded_total.toFixed(2)}</td>
                                                        <td class="p-2 font-mono text-amber-700">$${ord.cost_total.toFixed(2)}</td>
                                                        <td class="p-2 font-mono font-bold">$${ord.profit_total.toFixed(2)}</td>
                                                        <td class="p-2 text-center font-mono font-bold text-emerald-700">${ord.profit_margin}%</td>
                                                    </tr>
                                                `;
                                            });
                                            html += `</tbody></table>`;
                                            if (container) container.innerHTML = html;
                                        }
                                        resolve();
                                    })
                                    .catch(() => resolve());
                            });
                            loadPromises.push(p);
                        }
                    });
                    if (loadPromises.length > 0) {
                        await Promise.all(loadPromises);
                    }
                }

                const clone = table.cloneNode(true);
                
                // Replace sortable links with text and unset min-widths
                clone.querySelectorAll('a').forEach(a => {
                    const span = document.createElement('span');
                    span.textContent = a.textContent.replace(/[↑↓]/g, '').trim();
                    a.parentNode.replaceChild(span, a);
                });
                
                clone.querySelectorAll('button').forEach(btn => btn.remove());
                clone.querySelectorAll('*').forEach(el => {
                    el.style.minWidth = 'unset';
                    el.classList.remove('min-w-[170px]', 'min-w-[160px]', 'min-w-[200px]');
                });
                
                // Remove first column (# toggle) and last column (actions)
                clone.querySelectorAll('tr').forEach(row => {
                    if (row.children.length > 0 && !row.id.startsWith('orders-row-')) {
                        const first = row.children[0];
                        if (first.classList.contains('print:hidden') || first.textContent.trim() === '#' || first.textContent.trim() === '•' || first.textContent.trim() === '▶') {
                            first.remove();
                        }
                        const last = row.children[row.children.length - 1];
                        if (last && last.classList.contains('print:hidden')) {
                            last.remove();
                        }
                    }
                });

                if (includeOrders) {
                    clone.querySelectorAll('[id^="orders-row-"]').forEach(r => {
                        r.classList.remove('hidden');
                        r.style.display = 'table-row';
                    });
                } else {
                    clone.querySelectorAll('[id^="orders-row-"]').forEach(r => {
                        r.remove();
                    });
                }

                const logoUrl = @json($logoUrl);
                const activeFilters = @json($activeFilterLabels ?? []);
                let filtersHtml = '';
                if (activeFilters.length > 0) {
                    filtersHtml = `<div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:4px;padding:4px 8px;font-size:7pt;margin-bottom:8px;color:#334155;"><strong>الفلاتر المطبقة:</strong> ${activeFilters.join(' | ')}</div>`;
                }

                const printTypeBadge = includeOrders 
                    ? '<span style="color:#2563eb;font-weight:600;font-size:8.5pt;">(شامل تفاصيل الطلبات)</span>' 
                    : '<span style="color:#475569;font-weight:600;font-size:8.5pt;">(ملخص العملاء فقط)</span>';

                const iframe = document.createElement('iframe');
                iframe.style.position = 'fixed';
                iframe.style.right = '0';
                iframe.style.bottom = '0';
                iframe.style.width = '0';
                iframe.style.height = '0';
                iframe.style.border = '0';
                document.body.appendChild(iframe);

                const doc = iframe.contentWindow.document;
                doc.open();
                doc.write(`
                    <!DOCTYPE html>
                    <html dir="rtl" lang="ar">
                    <head>
                        <meta charset="utf-8">
                        <title>تقرير العملاء التفصيلي</title>
                        <style>
                            @page {
                                size: landscape;
                                margin: 4mm 5mm;
                            }
                            * {
                                box-sizing: border-box;
                                font-family: "Segoe UI", -apple-system, BlinkMacSystemFont, Tahoma, Arial, sans-serif;
                            }
                            html, body {
                                margin: 0 !important;
                                padding: 0 !important;
                                background: #ffffff;
                                color: #000000;
                                direction: rtl;
                                font-size: 6.5pt;
                            }
                            .header-container {
                                display: flex;
                                justify-content: space-between;
                                align-items: center;
                                border-bottom: 2px solid #0f172a;
                                padding-bottom: 4px;
                                margin-bottom: 6px;
                            }
                            .header-title {
                                font-size: 11pt;
                                font-weight: bold;
                                color: #0f172a;
                                margin: 0 0 2px 0;
                            }
                            .header-subtitle {
                                font-size: 6.5pt;
                                color: #475569;
                                margin: 0;
                            }
                            .header-logo {
                                text-align: left;
                            }
                            .header-logo img {
                                height: 28px;
                                width: auto;
                                object-fit: contain;
                            }
                            table {
                                width: 100%;
                                border-collapse: collapse;
                                font-size: 6.5pt;
                                table-layout: auto;
                                orphans: 3 !important;
                                widows: 3 !important;
                            }
                            thead {
                                display: table-header-group;
                            }
                            th {
                                background-color: #f1f5f9 !important;
                                color: #0f172a !important;
                                font-weight: bold !important;
                                border: 1px solid #94a3b8 !important;
                                padding: 3px 2px !important;
                                text-align: right !important;
                                word-break: break-word !important;
                                -webkit-print-color-adjust: exact !important;
                                print-color-adjust: exact !important;
                            }
                            td {
                                border: 1px solid #cbd5e1 !important;
                                padding: 2.5px 2px !important;
                                text-align: right !important;
                                color: #1e293b !important;
                                line-height: 1.2 !important;
                                word-break: break-word !important;
                            }
                            tr {
                                break-inside: avoid !important;
                                page-break-inside: avoid !important;
                            }
                            tbody tr:nth-child(even) {
                                background-color: #f8fafc !important;
                                -webkit-print-color-adjust: exact !important;
                                print-color-adjust: exact !important;
                            }
                            span {
                                display: inline-block;
                            }
                        </style>
                    </head>
                    <body>
                        <div class="header-container">
                            <div>
                                <h1 class="header-title">👥 تقرير العملاء التفصيلي — هايست ${printTypeBadge}</h1>
                                <p class="header-subtitle">تاريخ ووقت استخراج التقرير: {{ $generatedAt }}</p>
                            </div>
                            <div class="header-logo">
                                <img src="${logoUrl}" alt="هايست">
                            </div>
                        </div>
                        ${filtersHtml}
                        ${clone.outerHTML}
                    </body>
                    </html>
                `);
                doc.close();

                setTimeout(() => {
                    iframe.contentWindow.focus();
                    iframe.contentWindow.print();
                    setTimeout(() => {
                        document.body.removeChild(iframe);
                    }, 2000);
                }, 300);
            }
        </script>
    @endPushOnce

    @pushOnce('styles')
        <style>
            @media print {
                @page {
                    size: landscape;
                    margin: 4mm 5mm;
                }
                html, body, #app {
                    background: #ffffff !important;
                    color: #000000 !important;
                    font-family: system-ui, -apple-system, sans-serif !important;
                    font-size: 6.5pt !important;
                    margin: 0 !important;
                    padding: 0 !important;
                    width: 100% !important;
                }
                header, aside, nav, footer,
                .sidebar, .navbar, .left-sidebar,
                [class*="sidebar"], [class*="navbar"], [class*="header"], [class*="top-nav"],
                .print\:hidden, #filterForm, .detailed-filters-container,
                button, a, select, input,
                div[class*="border-t"] {
                    display: none !important;
                    visibility: hidden !important;
                    height: 0 !important;
                    margin: 0 !important;
                    padding: 0 !important;
                }
                .hidden.print\:block, div[class*="print:block"] {
                    display: block !important;
                    visibility: visible !important;
                }
                div[class*="rounded-xl"], div[class*="overflow-x-auto"] {
                    border: none !important;
                    box-shadow: none !important;
                    background: transparent !important;
                    padding: 0 !important;
                    margin: 0 !important;
                    overflow: visible !important;
                }
                table {
                    width: 100% !important;
                    border-collapse: collapse !important;
                    font-size: 6.5pt !important;
                    color: #000000 !important;
                    table-layout: auto !important;
                }
                thead {
                    display: table-header-group !important;
                }
                thead tr {
                    background-color: #f2f2f2 !important;
                    color: #000000 !important;
                    -webkit-print-color-adjust: exact !important;
                    print-color-adjust: exact !important;
                }
                th, td {
                    border: 1px solid #777777 !important;
                    padding: 2.5px 2px !important;
                    text-align: right !important;
                    color: #000000 !important;
                    background: transparent !important;
                    word-break: break-word !important;
                }
                th {
                    font-weight: bold !important;
                }
                tr {
                    page-break-inside: avoid !important;
                }
            }
        </style>
    @endPushOnce
</x-admin::layouts>
