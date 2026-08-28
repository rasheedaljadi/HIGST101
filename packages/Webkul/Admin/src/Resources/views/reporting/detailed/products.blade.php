<x-admin::layouts>
    <x-slot:title>
        تقرير المنتجات التفصيلي — التقارير التفصيلية
    </x-slot>

    <div class="flex flex-col gap-5 p-4 sm:p-6 print:p-0">
        <!-- Header & Action Controls -->
        <div class="flex flex-wrap items-center justify-between gap-4 print:hidden">
            <div class="flex flex-col gap-1">
                <div class="flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400">
                    <span>التقارير التفصيلية</span>
                    <span>/</span>
                    <span class="font-medium text-gray-800 dark:text-white">تقرير المنتجات</span>
                </div>
                <h1 class="text-2xl font-bold text-gray-800 dark:text-white">
                    📊 تقرير المنتجات الشامل (Products Report)
                </h1>
                <p class="text-sm text-gray-600 dark:text-gray-300">
                    كشف مالي وتشغيلي تفصيلي لمنتجات المتجر، التكاليف، هوامش الأرباح، وقيمة المخزون للمنتجات البسيطة والمتغيرات.
                </p>
            </div>

            <div class="flex flex-wrap items-center gap-2.5">
                <!-- Per Page Selector (Placed Prominently at the Top) -->
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
                                <span>📦</span>
                                <span>طباعة شاملة مع تفاصيل المتغيرات</span>
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
                                <span>طباعة ملخص المنتجات فقط</span>
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
                                <span>طباعة كل النتائج (مع المتغيرات)</span>
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
                    href="{{ route('admin.detailed_reports.products.export', array_merge(request()->query(), ['format' => 'pdf'])) }}"
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
                    href="{{ route('admin.detailed_reports.products.export', array_merge(request()->query(), ['format' => 'csv'])) }}"
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
                    href="{{ route('admin.detailed_reports.products.export', array_merge(request()->query(), ['format' => 'xlsx'])) }}"
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
                    <h1 class="text-xl font-bold text-black">تقرير المنتجات التفصيلي — هايست</h1>
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
                if (!empty($filters['product_id'])) $activeFilterLabels[] = 'معرف المنتج: ' . $filters['product_id'];
                if (!empty($filters['sku'])) $activeFilterLabels[] = 'SKU: ' . $filters['sku'];
                if (!empty($filters['name'])) $activeFilterLabels[] = 'اسم المنتج: ' . $filters['name'];
                if (!empty($filters['main_category_id']) && isset($categories['main'][$filters['main_category_id']])) {
                    $activeFilterLabels[] = 'الفئة الرئيسية: ' . $categories['main'][$filters['main_category_id']];
                }
                if (!empty($filters['type'])) $activeFilterLabels[] = 'النوع: ' . ($filters['type'] === 'simple' ? 'بسيط' : 'بمتغيرات');
                if (!empty($filters['source'])) $activeFilterLabels[] = 'المصدر: ' . ($filters['source'] === 'aliexpress' ? 'AliExpress' : 'داخلي');
                if (!empty($filters['supplier'])) $activeFilterLabels[] = 'المورد: ' . $filters['supplier'];
                if (isset($filters['status']) && $filters['status'] !== '') $activeFilterLabels[] = 'الحالة: ' . ($filters['status'] == '1' ? 'نشط' : 'معطل');
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
            <form method="GET" action="{{ route('admin.detailed_reports.products.index') }}" id="filterForm">
                <input type="hidden" name="sort" value="{{ $currentSort }}">
                <input type="hidden" name="order" value="{{ $currentOrder }}">
                <input type="hidden" name="per_page" value="{{ $perPage }}">

                <div class="detailed-filters-container" style="display: flex !important; flex-direction: row !important; flex-wrap: wrap !important; gap: 16px !important; width: 100% !important;">
                    <!-- Global Search -->
                    <div class="detailed-filter-item" style="flex: 0 0 calc(33.3333% - 11px); max-width: calc(33.3333% - 11px); min-width: 220px; box-sizing: border-box; display: flex; flex-direction: column; gap: 4px;">
                        <label class="text-xs font-semibold text-gray-700 dark:text-gray-300">بحث شامل (الاسم، SKU، ID)</label>
                        <div class="relative">
                            <input
                                type="text"
                                name="search"
                                value="{{ request('search') }}"
                                placeholder="ابحث باسم المنتج أو الرمز أو المعرف..."
                                class="w-full text-xs rounded-lg border border-gray-300 bg-white px-3 py-2 text-gray-800 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                            >
                        </div>
                    </div>

                    <!-- Main Category -->
                    <div class="detailed-filter-item" style="flex: 0 0 calc(33.3333% - 11px); max-width: calc(33.3333% - 11px); min-width: 220px; box-sizing: border-box; display: flex; flex-direction: column; gap: 4px;">
                        <label class="text-xs font-semibold text-gray-700 dark:text-gray-300">الفئة الرئيسية</label>
                        <select
                            name="main_category_id"
                            class="w-full text-xs rounded-lg border border-gray-300 bg-white px-2.5 py-2 text-gray-800 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                        >
                            <option value="">جميع الفئات الرئيسية</option>
                            @foreach($categories['main'] as $id => $name)
                                <option value="{{ $id }}" {{ (string) request('main_category_id') === (string) $id ? 'selected' : '' }}>
                                    {{ $name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Product Type -->
                    <div class="detailed-filter-item" style="flex: 0 0 calc(33.3333% - 11px); max-width: calc(33.3333% - 11px); min-width: 220px; box-sizing: border-box; display: flex; flex-direction: column; gap: 4px;">
                        <label class="text-xs font-semibold text-gray-700 dark:text-gray-300">نوع المنتج</label>
                        <select
                            name="type"
                            class="w-full text-xs rounded-lg border border-gray-300 bg-white px-2.5 py-2 text-gray-800 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                        >
                            <option value="">جميع الأنواع</option>
                            <option value="simple" {{ request('type') === 'simple' ? 'selected' : '' }}>منتج بسيط (Simple)</option>
                            <option value="configurable" {{ request('type') === 'configurable' ? 'selected' : '' }}>بمتغيرات (Configurable)</option>
                        </select>
                    </div>

                    <!-- Product Source -->
                    <div class="detailed-filter-item" style="flex: 0 0 calc(33.3333% - 11px); max-width: calc(33.3333% - 11px); min-width: 220px; box-sizing: border-box; display: flex; flex-direction: column; gap: 4px;">
                        <label class="text-xs font-semibold text-gray-700 dark:text-gray-300">مصدر المنتج</label>
                        <select
                            name="source"
                            class="w-full text-xs rounded-lg border border-gray-300 bg-white px-2.5 py-2 text-gray-800 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                        >
                            <option value="">جميع المصادر</option>
                            <option value="internal" {{ request('source') === 'internal' ? 'selected' : '' }}>منتج داخلي للمتجر</option>
                            <option value="aliexpress" {{ request('source') === 'aliexpress' ? 'selected' : '' }}>مستورد من AliExpress</option>
                        </select>
                    </div>

                    <!-- Supplier Name -->
                    <div class="detailed-filter-item" style="flex: 0 0 calc(33.3333% - 11px); max-width: calc(33.3333% - 11px); min-width: 220px; box-sizing: border-box; display: flex; flex-direction: column; gap: 4px;">
                        <label class="text-xs font-semibold text-gray-700 dark:text-gray-300">المورد / المتجر</label>
                        <input
                            type="text"
                            name="supplier"
                            value="{{ request('supplier') }}"
                            placeholder="اسم المورد..."
                            class="w-full text-xs rounded-lg border border-gray-300 bg-white px-3 py-2 text-gray-800 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                        >
                    </div>

                    <!-- Status -->
                    <div class="detailed-filter-item" style="flex: 0 0 calc(33.3333% - 11px); max-width: calc(33.3333% - 11px); min-width: 220px; box-sizing: border-box; display: flex; flex-direction: column; gap: 4px;">
                        <label class="text-xs font-semibold text-gray-700 dark:text-gray-300">حالة المنتج</label>
                        <select
                            name="status"
                            class="w-full text-xs rounded-lg border border-gray-300 bg-white px-2.5 py-2 text-gray-800 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                        >
                            <option value="">الكل</option>
                            <option value="1" {{ request('status') === '1' ? 'selected' : '' }}>نشط (Active)</option>
                            <option value="0" {{ request('status') === '0' ? 'selected' : '' }}>معطل (Inactive)</option>
                        </select>
                    </div>
                </div>

                <!-- Form Action Buttons -->
                <div class="flex items-center justify-between border-t border-gray-100 dark:border-gray-800 mt-3.5 pt-3">
                    <div class="text-xs text-gray-500">
                        عرض <strong class="text-gray-700 dark:text-gray-300">{{ $records->total() }}</strong> منتج وفق شروط الفلترة المحددة
                    </div>
                    <div class="flex items-center gap-2">
                        <a
                            href="{{ route('admin.detailed_reports.products.index') }}"
                            class="px-3.5 py-1.5 text-xs font-semibold rounded-lg border border-gray-300 bg-white text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700 transition"
                        >
                            إعادة ضبط (Reset)
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

        <!-- Main Product Report Data Table -->
        <div class="bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-800 shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table id="productReportTable" class="w-full text-right border-collapse text-xs">
                    <thead>
                        <tr class="bg-gray-50 dark:bg-gray-800 text-gray-700 dark:text-gray-200 font-bold border-b border-gray-200 dark:border-gray-800 select-none">
                            <th class="p-3 w-10 text-center print:hidden">#</th>
                            
                            <!-- Sortable Product ID -->
                            <th class="p-3 whitespace-nowrap">
                                <a href="{{ request()->fullUrlWithQuery(['sort' => 'product_id', 'order' => $currentSort === 'product_id' && $currentOrder === 'asc' ? 'desc' : 'asc']) }}" class="flex items-center gap-1 hover:text-blue-600">
                                    ID
                                    @if($currentSort === 'product_id')
                                        <span>{{ $currentOrder === 'asc' ? '↑' : '↓' }}</span>
                                    @endif
                                </a>
                            </th>

                            <!-- Sortable SKU -->
                            <th class="p-3 whitespace-nowrap">
                                <a href="{{ request()->fullUrlWithQuery(['sort' => 'sku', 'order' => $currentSort === 'sku' && $currentOrder === 'asc' ? 'desc' : 'asc']) }}" class="flex items-center gap-1 hover:text-blue-600">
                                    SKU
                                    @if($currentSort === 'sku')
                                        <span>{{ $currentOrder === 'asc' ? '↑' : '↓' }}</span>
                                    @endif
                                </a>
                            </th>

                            <!-- Sortable Product Name -->
                            <th class="p-3 min-w-[180px]">
                                <a href="{{ request()->fullUrlWithQuery(['sort' => 'name', 'order' => $currentSort === 'name' && $currentOrder === 'asc' ? 'desc' : 'asc']) }}" class="flex items-center gap-1 hover:text-blue-600">
                                    اسم المنتج
                                    @if($currentSort === 'name')
                                        <span>{{ $currentOrder === 'asc' ? '↑' : '↓' }}</span>
                                    @endif
                                </a>
                            </th>

                            <!-- Sortable Main Category -->
                            <th class="p-3 whitespace-nowrap">
                                <a href="{{ request()->fullUrlWithQuery(['sort' => 'main_category', 'order' => $currentSort === 'main_category' && $currentOrder === 'asc' ? 'desc' : 'asc']) }}" class="flex items-center gap-1 hover:text-blue-600">
                                    الفئة الرئيسية
                                    @if($currentSort === 'main_category')
                                        <span>{{ $currentOrder === 'asc' ? '↑' : '↓' }}</span>
                                    @endif
                                </a>
                            </th>

                            <!-- Sortable Type -->
                            <th class="p-3 whitespace-nowrap">
                                <a href="{{ request()->fullUrlWithQuery(['sort' => 'type', 'order' => $currentSort === 'type' && $currentOrder === 'asc' ? 'desc' : 'asc']) }}" class="flex items-center gap-1 hover:text-blue-600">
                                    النوع
                                    @if($currentSort === 'type')
                                        <span>{{ $currentOrder === 'asc' ? '↑' : '↓' }}</span>
                                    @endif
                                </a>
                            </th>

                            <!-- Sortable Source -->
                            <th class="p-3 whitespace-nowrap">
                                <a href="{{ request()->fullUrlWithQuery(['sort' => 'source', 'order' => $currentSort === 'source' && $currentOrder === 'asc' ? 'desc' : 'asc']) }}" class="flex items-center gap-1 hover:text-blue-600">
                                    المصدر
                                    @if($currentSort === 'source')
                                        <span>{{ $currentOrder === 'asc' ? '↑' : '↓' }}</span>
                                    @endif
                                </a>
                            </th>

                            <!-- Sortable Supplier -->
                            <th class="p-3 whitespace-nowrap">
                                <a href="{{ request()->fullUrlWithQuery(['sort' => 'supplier', 'order' => $currentSort === 'supplier' && $currentOrder === 'asc' ? 'desc' : 'asc']) }}" class="flex items-center gap-1 hover:text-blue-600">
                                    المورد
                                    @if($currentSort === 'supplier')
                                        <span>{{ $currentOrder === 'asc' ? '↑' : '↓' }}</span>
                                    @endif
                                </a>
                            </th>

                            <!-- Sortable Variants Count -->
                            <th class="p-3 whitespace-nowrap text-center">
                                <a href="{{ request()->fullUrlWithQuery(['sort' => 'variants_count', 'order' => $currentSort === 'variants_count' && $currentOrder === 'asc' ? 'desc' : 'asc']) }}" class="flex items-center justify-center gap-1 hover:text-blue-600">
                                    المتغيرات
                                    @if($currentSort === 'variants_count')
                                        <span>{{ $currentOrder === 'asc' ? '↑' : '↓' }}</span>
                                    @endif
                                </a>
                            </th>

                            <!-- Sortable Cost -->
                            <th class="p-3 whitespace-nowrap">
                                <a href="{{ request()->fullUrlWithQuery(['sort' => 'cost', 'order' => $currentSort === 'cost' && $currentOrder === 'asc' ? 'desc' : 'asc']) }}" class="flex items-center gap-1 hover:text-blue-600">
                                    التكلفة
                                    @if($currentSort === 'cost')
                                        <span>{{ $currentOrder === 'asc' ? '↑' : '↓' }}</span>
                                    @endif
                                </a>
                            </th>

                            <!-- Sortable Selling Price -->
                            <th class="p-3 whitespace-nowrap">
                                <a href="{{ request()->fullUrlWithQuery(['sort' => 'price', 'order' => $currentSort === 'price' && $currentOrder === 'asc' ? 'desc' : 'asc']) }}" class="flex items-center gap-1 hover:text-blue-600">
                                    سعر البيع
                                    @if($currentSort === 'price')
                                        <span>{{ $currentOrder === 'asc' ? '↑' : '↓' }}</span>
                                    @endif
                                </a>
                            </th>

                            <!-- Sortable Stock -->
                            <th class="p-3 whitespace-nowrap text-center">
                                <a href="{{ request()->fullUrlWithQuery(['sort' => 'stock', 'order' => $currentSort === 'stock' && $currentOrder === 'asc' ? 'desc' : 'asc']) }}" class="flex items-center justify-center gap-1 hover:text-blue-600">
                                    المخزون
                                    @if($currentSort === 'stock')
                                        <span>{{ $currentOrder === 'asc' ? '↑' : '↓' }}</span>
                                    @endif
                                </a>
                            </th>

                            <!-- Sortable Status -->
                            <th class="p-3 whitespace-nowrap text-center">
                                <a href="{{ request()->fullUrlWithQuery(['sort' => 'status', 'order' => $currentSort === 'status' && $currentOrder === 'asc' ? 'desc' : 'asc']) }}" class="flex items-center justify-center gap-1 hover:text-blue-600">
                                    الحالة
                                    @if($currentSort === 'status')
                                        <span>{{ $currentOrder === 'asc' ? '↑' : '↓' }}</span>
                                    @endif
                                </a>
                            </th>
                        </tr>
                    </thead>
                    @forelse($records as $product)
                        @php
                            $hasVariants = !empty($product->variants) && count($product->variants) > 0;
                        @endphp
                        <tbody class="product-item-group divide-y divide-gray-200 dark:divide-gray-800 border-b border-gray-100 dark:border-gray-800">
                            <tr
                                class="hover:bg-gray-50/80 dark:hover:bg-gray-800/40 transition {{ $hasVariants ? 'cursor-pointer parent-with-variants' : 'parent-product-row' }}"
                                @if($hasVariants)
                                    onclick="toggleVariantRow(event, {{ $product->product_id }})"
                                @endif
                            >
                                <!-- Expand Accordion Toggle -->
                                <td class="p-3 text-center print:hidden">
                                    @if($hasVariants)
                                        <button
                                            type="button"
                                            onclick="event.stopPropagation(); toggleVariantRow(null, {{ $product->product_id }})"
                                            class="variant-toggle-btn-{{ $product->product_id }} text-gray-500 hover:text-blue-600 p-1 transition-transform duration-200"
                                            title="عرض تفاصيل المتغيرات"
                                        >
                                            ▶
                                        </button>
                                    @else
                                        <span class="text-gray-300 dark:text-gray-700">•</span>
                                    @endif
                                </td>

                                <!-- Product ID -->
                                <td class="p-3 font-mono font-medium text-gray-500 dark:text-gray-400">
                                    #{{ $product->product_id }}
                                </td>

                                <!-- SKU -->
                                <td class="p-3 font-mono font-semibold text-gray-700 dark:text-gray-300">
                                    {{ $product->sku }}
                                </td>

                                <!-- Product Name -->
                                <td class="p-3">
                                    <div class="font-bold text-gray-900 dark:text-white line-clamp-2">
                                        {{ $product->name }}
                                    </div>
                                    @if($hasVariants)
                                        <div class="text-[10px] text-blue-600 dark:text-blue-400 mt-0.5">
                                            (يحتوي على {{ count($product->variants) }} متغيرات مختلفة الأسعار والمخزون)
                                        </div>
                                    @endif
                                </td>

                                <!-- Main Category -->
                                <td class="p-3 whitespace-nowrap font-medium text-gray-700 dark:text-gray-300">
                                    {{ $product->main_category }}
                                </td>

                                <!-- Product Type -->
                                <td class="p-3 whitespace-nowrap">
                                    @if($product->type === 'configurable')
                                        <span class="px-2 py-0.5 rounded-full text-[11px] font-semibold bg-purple-100 text-purple-700 dark:bg-purple-900/40 dark:text-purple-300">
                                            بمتغيرات
                                        </span>
                                    @else
                                        <span class="px-2 py-0.5 rounded-full text-[11px] font-semibold bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300">
                                            بسيط
                                        </span>
                                    @endif
                                </td>

                                <!-- Product Source -->
                                <td class="p-3 whitespace-nowrap">
                                    @if($product->source === 'aliexpress')
                                        <span class="px-2 py-0.5 rounded-full text-[11px] font-semibold bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300">
                                            AliExpress
                                        </span>
                                    @else
                                        <span class="px-2 py-0.5 rounded-full text-[11px] font-semibold bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-300">
                                            داخلي
                                        </span>
                                    @endif
                                </td>

                                <!-- Supplier -->
                                <td class="p-3 whitespace-nowrap text-gray-600 dark:text-gray-300">
                                    {{ $product->supplier }}
                                </td>

                                <!-- Variants Count -->
                                <td class="p-3 whitespace-nowrap text-center font-bold font-mono">
                                    {{ $product->variants_count > 0 ? $product->variants_count : '—' }}
                                </td>

                                <!-- Cost Price -->
                                <td class="p-3 font-mono font-medium text-gray-700 dark:text-gray-300 whitespace-nowrap">
                                    @if($hasVariants || $product->variants_count > 0 || $product->type === 'configurable')
                                        <span class="text-gray-400 font-bold">—</span>
                                    @else
                                        ${{ number_format($product->cost_price, 2) }}
                                    @endif
                                </td>

                                <!-- Selling Price -->
                                <td class="p-3 font-mono font-bold text-gray-900 dark:text-white whitespace-nowrap">
                                    @if($hasVariants || $product->variants_count > 0 || $product->type === 'configurable')
                                        <span class="text-gray-400 font-bold">—</span>
                                    @else
                                        ${{ number_format($product->selling_price, 2) }}
                                    @endif
                                </td>

                                <!-- Stock -->
                                <td class="p-3 text-center whitespace-nowrap">
                                    @if($hasVariants || $product->variants_count > 0 || $product->type === 'configurable')
                                        <span class="text-gray-400 font-bold">—</span>
                                    @elseif($product->stock_quantity > 0)
                                        <span class="px-2 py-0.5 rounded font-mono font-bold text-xs bg-emerald-50 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-300">
                                            {{ number_format($product->stock_quantity) }}
                                        </span>
                                    @else
                                        <span class="px-2 py-0.5 rounded font-bold text-xs bg-rose-50 text-rose-600 dark:bg-rose-950/40 dark:text-rose-400">
                                            نفذ المخزون
                                        </span>
                                    @endif
                                </td>

                                <!-- Status -->
                                <td class="p-3 text-center whitespace-nowrap">
                                    @if($product->status)
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-300">
                                            نشط
                                        </span>
                                    @else
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400">
                                            معطل
                                        </span>
                                    @endif
                                </td>
                            </tr>

                            <!-- Expandable Sub-table for Configurable Product Variations -->
                            @if($hasVariants)
                                <tr id="variant-row-{{ $product->product_id }}" class="variant-subtable-row hidden bg-blue-50/30 dark:bg-gray-950/60 border-t border-blue-100 dark:border-gray-800 print:table-row">
                                    <td colspan="13" class="p-4 ltr:pl-10 rtl:pr-10">
                                        <div class="rounded-lg border border-blue-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-3 shadow-inner">
                                            <div class="flex items-center justify-between mb-2">
                                                <h4 class="text-xs font-bold text-blue-900 dark:text-blue-300 flex items-center gap-1.5">
                                                    <span>📦 تفاصيل متغيرات المنتج:</span>
                                                    <span class="font-semibold text-gray-700 dark:text-gray-200">{{ $product->name }}</span>
                                                    <span class="text-gray-400">({{ count($product->variants) }} متغيرات)</span>
                                                </h4>
                                            </div>

                                            <div class="overflow-x-auto">
                                                <table class="variant-subtable w-full text-right text-xs">
                                                    <thead>
                                                        <tr class="bg-gray-50 dark:bg-gray-800/80 text-gray-600 dark:text-gray-300 border-b">
                                                            <th class="p-2 font-semibold">اسم المتغير (Attributes)</th>
                                                            <th class="p-2 font-semibold">SKU المتغير</th>
                                                            <th class="p-2 font-semibold">سعر التكلفة</th>
                                                            <th class="p-2 font-semibold">سعر البيع</th>
                                                            <th class="p-2 font-semibold text-center">المخزون</th>
                                                            <th class="p-2 font-semibold text-center">الحالة</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                                                        @foreach($product->variants as $variant)
                                                            <tr class="hover:bg-blue-50/20 dark:hover:bg-gray-800/50">
                                                                <td class="p-2 font-bold text-gray-800 dark:text-gray-200">
                                                                    {{ $variant->name }}
                                                                </td>
                                                                <td class="p-2 font-mono text-gray-600 dark:text-gray-300">
                                                                    {{ $variant->sku }}
                                                                </td>
                                                                <td class="p-2 font-mono text-gray-700 dark:text-gray-300">
                                                                    ${{ number_format($variant->cost_price, 2) }}
                                                                </td>
                                                                <td class="p-2 font-mono font-bold text-gray-900 dark:text-white">
                                                                    ${{ number_format($variant->selling_price, 2) }}
                                                                </td>
                                                                <td class="p-2 text-center font-mono font-bold">
                                                                    @if($variant->stock_quantity > 0)
                                                                        <span class="text-emerald-600">{{ number_format($variant->stock_quantity) }}</span>
                                                                    @else
                                                                        <span class="text-rose-600">0</span>
                                                                    @endif
                                                                </td>
                                                                <td class="p-2 text-center">
                                                                    @if($variant->status)
                                                                        <span class="text-[10px] bg-green-100 text-green-800 px-1.5 py-0.5 rounded">نشط</span>
                                                                    @else
                                                                        <span class="text-[10px] bg-gray-100 text-gray-600 px-1.5 py-0.5 rounded">معطل</span>
                                                                    @endif
                                                                </td>
                                                            </tr>
                                                        @endforeach
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            @endif
                        </tbody>
                    @empty
                        <tbody>
                            <tr>
                                <td colspan="13" class="p-12 text-center text-gray-500 dark:text-gray-400">
                                    <div class="flex flex-col items-center justify-center gap-3">
                                        <span class="icon-product text-5xl text-gray-300 dark:text-gray-700"></span>
                                        <p class="text-sm font-semibold">لا توجد نتائج تطابق خيارات الفلترة المحددة.</p>
                                        <a href="{{ route('admin.detailed_reports.products.index') }}" class="text-xs font-semibold text-blue-600 hover:underline">
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
                    <span>عرض <strong>{{ $records->count() }}</strong> من إجمالي <strong>{{ number_format($records->total()) }}</strong> منتج</span>
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
            function toggleVariantRow(event, productId) {
                if (event) {
                    if (event.target.closest('a') || event.target.closest('button')) {
                        return;
                    }
                }
                const row = document.getElementById('variant-row-' + productId);
                const btn = document.querySelector('.variant-toggle-btn-' + productId);
                if (row) {
                    if (row.classList.contains('hidden')) {
                        row.classList.remove('hidden');
                        if (btn) btn.style.transform = 'rotate(90deg)';
                    } else {
                        row.classList.add('hidden');
                        if (btn) btn.style.transform = 'rotate(0deg)';
                    }
                }
            }

            function changePerPage(value) {
                const url = new URL(window.location.href);
                url.searchParams.set('per_page', value);
                url.searchParams.set('page', 1);
                window.location.href = url.toString();
            }

            function printAllRecords(includeVariants = true) {
                const isShowingAll = @json(request('per_page') == 'all' || $perPage >= 5000);
                if (isShowingAll) {
                    printTableOnly(includeVariants);
                    return;
                }

                const url = new URL(window.location.href);
                url.searchParams.set('per_page', 'all');
                url.searchParams.set('auto_print', includeVariants ? 'with_variants' : 'without_variants');
                window.location.href = url.toString();
            }

            // Auto-trigger print if redirected with auto_print
            window.addEventListener('DOMContentLoaded', () => {
                const urlParams = new URLSearchParams(window.location.search);
                const autoPrint = urlParams.get('auto_print');
                if (autoPrint) {
                    urlParams.delete('auto_print');
                    const cleanUrl = window.location.pathname + (urlParams.toString() ? '?' + urlParams.toString() : '');
                    window.history.replaceState({}, document.title, cleanUrl);

                    setTimeout(() => {
                        printTableOnly(autoPrint === 'with_variants');
                    }, 500);
                }
            });

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

            function togglePrintMenu(event) {
                event.stopPropagation();
                const menu = document.getElementById('printDropdownMenu');
                if (menu) {
                    menu.classList.toggle('hidden');
                }
            }

            function closePrintMenu() {
                const menu = document.getElementById('printDropdownMenu');
                if (menu) {
                    menu.classList.add('hidden');
                }
            }

            document.addEventListener('click', function(e) {
                const wrapper = document.getElementById('printMenuWrapper');
                if (wrapper && !wrapper.contains(e.target)) {
                    closePrintMenu();
                }
            });

            function printTableOnly(includeVariants = true) {
                const table = document.querySelector('#productReportTable');
                if (!table) {
                    window.print();
                    return;
                }

                const clone = table.cloneNode(true);
                
                // Replace sortable header links with plain text
                clone.querySelectorAll('a').forEach(a => {
                    const span = document.createElement('span');
                    span.textContent = a.textContent.replace(/[↑↓]/g, '').trim();
                    a.parentNode.replaceChild(span, a);
                });
                
                // Remove all button elements from table
                clone.querySelectorAll('button').forEach(btn => btn.remove());
                
                // Remove first column (# / toggle arrow) from all table rows
                clone.querySelectorAll('tr').forEach(row => {
                    if (row.children.length > 0 && !row.id.startsWith('variant-row-')) {
                        const first = row.children[0];
                        if (first.classList.contains('print:hidden') || first.textContent.trim() === '#' || first.textContent.trim() === '•' || first.textContent.trim() === '▶') {
                            first.remove();
                        }
                    }
                });

                // Handle variants sub-tables
                if (includeVariants) {
                    clone.querySelectorAll('[id^="variant-row-"]').forEach(r => {
                        r.classList.remove('hidden');
                        r.style.display = 'table-row';
                    });
                } else {
                    clone.querySelectorAll('[id^="variant-row-"]').forEach(r => {
                        r.remove();
                    });
                    clone.querySelectorAll('.parent-with-variants').forEach(r => {
                        r.classList.remove('parent-with-variants');
                        r.style.breakAfter = 'auto';
                        r.style.pageBreakAfter = 'auto';
                    });
                }

                const logoUrl = @json($logoUrl);
                const activeFilters = @json($activeFilterLabels ?? []);
                let filtersHtml = '';
                if (activeFilters.length > 0) {
                    filtersHtml = `<div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:4px;padding:6px 10px;font-size:8pt;margin-bottom:12px;color:#334155;"><strong>الفلاتر المطبقة:</strong> ${activeFilters.join(' | ')}</div>`;
                }

                const printTypeBadge = includeVariants 
                    ? '<span style="color:#2563eb;font-weight:600;font-size:9pt;">(شامل تفاصيل المتغيرات)</span>' 
                    : '<span style="color:#475569;font-weight:600;font-size:9pt;">(المنتجات الرئيسية فقط)</span>';

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
                        <title>تقرير المنتجات التفصيلي</title>
                        <style>
                            @page {
                                size: landscape;
                                margin: 6mm 8mm;
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
                                font-size: 7.5pt;
                            }
                            .header-container {
                                display: flex;
                                justify-content: space-between;
                                align-items: center;
                                border-bottom: 2px solid #0f172a;
                                padding-bottom: 6px;
                                margin-bottom: 8px;
                            }
                            .header-title {
                                font-size: 13pt;
                                font-weight: bold;
                                color: #0f172a;
                                margin: 0 0 3px 0;
                            }
                            .header-subtitle {
                                font-size: 7.5pt;
                                color: #475569;
                                margin: 0;
                            }
                            .header-logo {
                                text-align: left;
                            }
                            .header-logo img {
                                height: 34px;
                                width: auto;
                                object-fit: contain;
                            }
                            table {
                                width: 100%;
                                border-collapse: collapse;
                                font-size: 7.5pt;
                                orphans: 3 !important;
                                widows: 3 !important;
                            }
                            tbody {
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
                                padding: 4px 5px !important;
                                text-align: right !important;
                                white-space: nowrap !important;
                                -webkit-print-color-adjust: exact !important;
                                print-color-adjust: exact !important;
                            }
                            td {
                                border: 1px solid #cbd5e1 !important;
                                padding: 3px 5px !important;
                                text-align: right !important;
                                color: #1e293b !important;
                                line-height: 1.25 !important;
                            }
                            tr {
                                break-inside: avoid !important;
                                page-break-inside: avoid !important;
                            }
                            ${includeVariants ? `
                                tr.parent-with-variants {
                                    break-after: avoid-page !important;
                                    page-break-after: avoid !important;
                                }
                                tr.variant-subtable-row,
                                tr[id^="variant-row-"] {
                                    break-before: avoid-page !important;
                                    page-break-before: avoid !important;
                                }
                                .variant-subtable thead tr {
                                    break-after: avoid-page !important;
                                    page-break-after: avoid !important;
                                }
                                .variant-subtable tbody tr:nth-child(-n+2) {
                                    break-after: avoid !important;
                                    page-break-after: avoid !important;
                                }
                            ` : `
                                tr.parent-with-variants {
                                    break-after: auto !important;
                                    page-break-after: auto !important;
                                }
                            `}
                            tbody tr:nth-child(even) {
                                background-color: #f8fafc !important;
                                -webkit-print-color-adjust: exact !important;
                                print-color-adjust: exact !important;
                            }
                            span {
                                display: inline-block;
                            }
                            /* Zero margin ghost page killer */
                            body > *:last-child,
                            table:last-child,
                            div:last-child {
                                margin-bottom: 0 !important;
                                padding-bottom: 0 !important;
                            }
                        </style>
                    </head>
                    <body>
                        <div class="header-container">
                            <div>
                                <h1 class="header-title">📊 تقرير المنتجات التفصيلي — هايست ${printTypeBadge}</h1>
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
                    margin: 8mm;
                }
                html, body, #app {
                    background: #ffffff !important;
                    color: #000000 !important;
                    font-family: system-ui, -apple-system, sans-serif !important;
                    font-size: 8pt !important;
                    margin: 0 !important;
                    padding: 0 !important;
                    width: 100% !important;
                }
                /* Hide layout chrome */
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
                div.group\/container, div.group\/container > div:last-child {
                    display: block !important;
                    padding: 0 !important;
                    margin: 0 !important;
                    max-width: 100% !important;
                    width: 100% !important;
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
                    font-size: 8pt !important;
                    color: #000000 !important;
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
                    padding: 4px 6px !important;
                    text-align: right !important;
                    color: #000000 !important;
                    background: transparent !important;
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
