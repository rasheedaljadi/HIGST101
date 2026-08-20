<div class="flex flex-col gap-6 w-full font-sans text-right" dir="rtl">

    <!-- 0. Persistent Top Filters Toolbar (الشريط العلوي الثابت بتباين عالي جداً) -->
    <div class="p-5 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-sm text-slate-900 dark:text-white">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-blue-600 to-indigo-600 flex items-center justify-center shadow-md">
                    <span class="icon-dashboard text-xl text-white"></span>
                </div>
                <div>
                    <h2 class="text-base font-black text-slate-900 dark:text-white">لوحة هايست المتقدمة الشاملة</h2>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">مركز القيادة الموحد للمبيعات، المخزون المملوك، والتوفر الخارجي</p>
                </div>
            </div>

            <!-- Integrated Filter Controls (عناصر فلترة متباينة وواضحة جداً) -->
            <div class="flex flex-wrap items-center gap-2.5 text-xs">
                <!-- Date Range Filter -->
                <div class="flex items-center bg-slate-100 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-3 py-1.5 shadow-inner">
                    <span class="text-slate-700 dark:text-slate-300 ml-2 font-bold">الفترة:</span>
                    <select class="bg-slate-100 dark:bg-slate-800 text-slate-900 dark:text-white font-bold focus:outline-none cursor-pointer border-none text-xs">
                        <option value="today" class="bg-white dark:bg-slate-800 text-slate-900 dark:text-white">اليوم</option>
                        <option value="7days" class="bg-white dark:bg-slate-800 text-slate-900 dark:text-white">آخر 7 أيام</option>
                        <option value="month" selected class="bg-white dark:bg-slate-800 text-slate-900 dark:text-white">هذا الشهر</option>
                        <option value="quarter" class="bg-white dark:bg-slate-800 text-slate-900 dark:text-white">ربع سنوي</option>
                    </select>
                </div>

                <!-- Channel Filter -->
                <div class="flex items-center bg-slate-100 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-3 py-1.5 shadow-inner">
                    <span class="text-slate-700 dark:text-slate-300 ml-2 font-bold">القناة:</span>
                    <select class="bg-slate-100 dark:bg-slate-800 text-slate-900 dark:text-white font-bold focus:outline-none cursor-pointer border-none text-xs">
                        <option value="all" selected class="bg-white dark:bg-slate-800 text-slate-900 dark:text-white">جميع القنوات</option>
                        <option value="default" class="bg-white dark:bg-slate-800 text-slate-900 dark:text-white">متجر هايست الرئيسي</option>
                    </select>
                </div>

                <!-- Governorate Filter -->
                <div class="flex items-center bg-slate-100 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-3 py-1.5 shadow-inner">
                    <span class="text-slate-700 dark:text-slate-300 ml-2 font-bold">المحافظة:</span>
                    <select class="bg-slate-100 dark:bg-slate-800 text-slate-900 dark:text-white font-bold focus:outline-none cursor-pointer border-none text-xs">
                        <option value="all" selected class="bg-white dark:bg-slate-800 text-slate-900 dark:text-white">جميع المحافظات</option>
                        <option value="sanaa" class="bg-white dark:bg-slate-800 text-slate-900 dark:text-white">أمانة العاصمة / صنعاء</option>
                        <option value="aden" class="bg-white dark:bg-slate-800 text-slate-900 dark:text-white">عدن</option>
                        <option value="taiz" class="bg-white dark:bg-slate-800 text-slate-900 dark:text-white">تعز</option>
                        <option value="hadramout" class="bg-white dark:bg-slate-800 text-slate-900 dark:text-white">حضرموت</option>
                    </select>
                </div>

                <!-- Product Type Filter -->
                <div class="flex items-center bg-slate-100 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-3 py-1.5 shadow-inner">
                    <span class="text-slate-700 dark:text-slate-300 ml-2 font-bold">نوع المنتج:</span>
                    <select class="bg-slate-100 dark:bg-slate-800 text-slate-900 dark:text-white font-bold focus:outline-none cursor-pointer border-none text-xs">
                        <option value="all" selected class="bg-white dark:bg-slate-800 text-slate-900 dark:text-white">الكل</option>
                        <option value="internal" class="bg-white dark:bg-slate-800 text-slate-900 dark:text-white">داخلي فقط</option>
                        <option value="imported" class="bg-white dark:bg-slate-800 text-slate-900 dark:text-white">مستورد علي إكسبرس</option>
                    </select>
                </div>

                <!-- Manual Refresh Button -->
                <button onclick="window.location.reload();" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 active:scale-95 text-white font-bold rounded-xl shadow-sm transition-all flex items-center gap-1.5 cursor-pointer">
                    <span class="icon-refresh text-sm"></span>
                    <span>تحديث</span>
                </button>
            </div>
        </div>

        <!-- Data Freshness Indicator -->
        <div class="mt-3 pt-3 border-t border-slate-200 dark:border-slate-800 flex items-center justify-between text-[11px] text-slate-600 dark:text-slate-400">
            <div class="flex items-center gap-2">
                <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                <span>حالة البيانات: <strong class="text-emerald-700 dark:text-emerald-400 font-bold">{{ $advancedData['filters']['freshness_status'] ?? 'مكتمل (بيانات حية)' }}</strong></span>
            </div>
            <span class="text-slate-500 dark:text-slate-400">آخر تحديث للوحة: {{ $advancedData['filters']['updated_at'] ?? date('Y-m-d H:i:s') }}</span>
        </div>
    </div>

    <!-- 1. Executive Summary Cards (قسم الملخص الرئيسي) -->
    @if (bouncer()->hasPermission('dashboard'))
        <div class="flex items-center justify-between gap-3 pt-2 pb-1">
            <div class="flex items-center gap-2.5">
                <span class="w-3 h-7 bg-blue-600 rounded-full shadow-sm"></span>
                <h3 class="text-lg font-black text-slate-900 dark:text-white">الملخص</h3>
            </div>
            <span class="text-xs text-slate-500 dark:text-slate-400 font-bold">مؤشرات الأداء الرئيسية الشاملة لشركة هايست</span>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-4 gap-5">
            <!-- 1.1 صافي المبيعات (Net Sales - كحلي نيلي) -->
            <div style="border: 2px solid #1e3a8a;" class="bg-white dark:bg-slate-900 rounded-2xl shadow-sm hover:shadow-xl transition-all duration-300 flex flex-col justify-between overflow-hidden border-2 border-blue-900">
                <div class="p-5">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <h4 class="text-sm font-extrabold text-slate-800 dark:text-slate-200">صافي المبيعات</h4>
                            <div class="mt-2 flex items-baseline gap-1.5">
                                <span class="text-3xl font-black text-blue-950 dark:text-white font-mono tracking-tight">
                                    {{ core()->formatBasePrice($advancedData['executive']['total_sales'] ?? 0) }}
                                </span>
                            </div>
                        </div>
                        <div class="w-12 h-12 rounded-full bg-blue-100/80 dark:bg-blue-950/80 border border-blue-200 dark:border-blue-800 flex items-center justify-center text-blue-900 dark:text-blue-300 shrink-0 shadow-inner">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                            </svg>
                        </div>
                    </div>

                    <!-- Trend Badge & Comparison Text -->
                    <div class="mt-4 flex items-center gap-2">
                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-black bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300">
                            = 0%
                        </span>
                        <span class="text-[11px] font-bold text-slate-500 dark:text-slate-400">مقارنة بالفترة السابقة</span>
                    </div>
                </div>

                <!-- Footer Strip with explicit inline styles -->
                <div style="background-color: #1e3a8a; color: #ffffff;" class="px-5 py-3 flex items-center justify-between text-xs font-bold">
                    <span style="color: #ffffff !important;" class="font-extrabold text-white">صافي المبيعات للفترة</span>
                    <span class="w-5 h-5 rounded-full bg-white/20 flex items-center justify-center font-black text-white">$</span>
                </div>
            </div>

            <!-- 1.2 إجمالي الطلبات (Total Orders - أزرق ملكي) -->
            <div style="border: 2px solid #2563eb;" class="bg-white dark:bg-slate-900 rounded-2xl shadow-sm hover:shadow-xl transition-all duration-300 flex flex-col justify-between overflow-hidden border-2 border-blue-600">
                <div class="p-5">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <h4 class="text-sm font-extrabold text-slate-800 dark:text-slate-200">إجمالي الطلبات</h4>
                            <div class="mt-2 flex items-baseline gap-1.5">
                                <span class="text-3xl font-black text-blue-600 dark:text-blue-400 font-mono tracking-tight">
                                    {{ number_format($advancedData['executive']['total_orders'] ?? 0) }}
                                </span>
                            </div>
                            <p class="text-xs font-semibold text-slate-500 dark:text-slate-400 mt-1">طلب مؤهل في المتجر</p>
                        </div>
                        <div class="w-12 h-12 rounded-full bg-blue-50 dark:bg-blue-950/80 border border-blue-200 dark:border-blue-800 flex items-center justify-center text-blue-600 dark:text-blue-400 shrink-0 shadow-inner">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 100 4 2 2 0 000-4z"></path>
                            </svg>
                        </div>
                    </div>

                    <!-- Trend Badge & Comparison Text -->
                    <div class="mt-4 flex items-center gap-2">
                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-black bg-blue-100 text-blue-800 dark:bg-blue-950 dark:text-blue-300">
                            ↑ 25%
                        </span>
                        <span class="text-[11px] font-bold text-slate-500 dark:text-slate-400">مقارنة بالفترة السابقة</span>
                    </div>
                </div>

                <!-- Footer Strip with explicit inline styles -->
                <div style="background-color: #2563eb; color: #ffffff;" class="px-5 py-3 flex items-center justify-between text-xs font-bold">
                    <span style="color: #ffffff !important;" class="font-extrabold text-white">طلب مؤهل في المتجر</span>
                    <span class="w-5 h-5 rounded-full bg-white/20 flex items-center justify-center text-white">🛍</span>
                </div>
            </div>

            <!-- 1.3 إجمالي العملاء والنشاط (Total Customers - كهرماني ذهبي) -->
            <div style="border: 2px solid #d97706;" class="bg-white dark:bg-slate-900 rounded-2xl shadow-sm hover:shadow-xl transition-all duration-300 flex flex-col justify-between overflow-hidden border-2 border-amber-500">
                <div class="p-5">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <h4 class="text-sm font-extrabold text-slate-800 dark:text-slate-200">إجمالي العملاء والنشاط</h4>
                            <div class="mt-2 flex items-baseline gap-1.5">
                                <span class="text-3xl font-black text-amber-600 dark:text-amber-400 font-mono tracking-tight">
                                    {{ number_format($advancedData['executive']['total_customers'] ?? 0) }}
                                </span>
                            </div>
                            <p class="text-xs font-semibold text-slate-500 dark:text-slate-400 mt-1">عميل مسجل ونشط</p>
                        </div>
                        <div class="w-12 h-12 rounded-full bg-amber-50 dark:bg-amber-950/80 border border-amber-200 dark:border-amber-800 flex items-center justify-center text-amber-600 dark:text-amber-400 shrink-0 shadow-inner">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                            </svg>
                        </div>
                    </div>

                    <!-- Trend Badge & Comparison Text -->
                    <div class="mt-4 flex items-center gap-2">
                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-black bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300">
                            ↑ 12%
                        </span>
                        <span class="text-[11px] font-bold text-slate-500 dark:text-slate-400">مقارنة بالفترة السابقة</span>
                    </div>
                </div>

                <!-- Footer Strip with explicit inline styles -->
                <div style="background-color: #d97706; color: #ffffff;" class="px-5 py-3 flex items-center justify-between text-xs font-bold">
                    <span style="color: #ffffff !important;" class="font-extrabold text-white">قاعدة البيانات</span>
                    <span class="w-5 h-5 rounded-full bg-white/20 flex items-center justify-center text-white">🪙</span>
                </div>
            </div>

            <!-- 1.4 المخزون المملوك للتسليم (Owned Stock - زمردي أخضر) -->
            <div style="border: 2px solid #059669;" class="bg-white dark:bg-slate-900 rounded-2xl shadow-sm hover:shadow-xl transition-all duration-300 flex flex-col justify-between overflow-hidden border-2 border-emerald-600">
                <div class="p-5">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <h4 class="text-sm font-extrabold text-slate-800 dark:text-slate-200">المخزون المملوك للتسليم</h4>
                            <div class="mt-2 flex items-baseline gap-1.5">
                                <span class="text-3xl font-black text-emerald-600 dark:text-emerald-400 font-mono tracking-tight">
                                    {{ number_format($advancedData['executive']['owned_stock_qty'] ?? 0) }}
                                </span>
                            </div>
                            <p class="text-xs font-semibold text-slate-500 dark:text-slate-400 mt-1">وحدة</p>
                        </div>
                        <div class="w-12 h-12 rounded-full bg-emerald-50 dark:bg-emerald-950/80 border border-emerald-200 dark:border-emerald-800 flex items-center justify-center text-emerald-600 dark:text-emerald-400 shrink-0 shadow-inner">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                            </svg>
                        </div>
                    </div>

                    <!-- Trend Badge & Comparison Text -->
                    <div class="mt-4 flex items-center gap-2">
                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-black bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300">
                            ✓ 0%
                        </span>
                        <span class="text-[11px] font-bold text-slate-500 dark:text-slate-400">مقارنة بالفترة السابقة</span>
                    </div>
                </div>

                <!-- Footer Strip with explicit inline styles -->
                <div style="background-color: #059669; color: #ffffff;" class="px-5 py-3 flex items-center justify-between text-xs font-bold">
                    <span style="color: #ffffff !important;" class="font-extrabold text-white">مستودع اليمن والسعودية</span>
                    <span class="w-5 h-5 rounded-full bg-white/20 flex items-center justify-center text-white">📍</span>
                </div>
            </div>
        </div>
    @endif

    <!-- 2. Order Lifecycle Pipeline Rail (سكة دورة حياة الطلب الموحدة - Section 2) -->
    @if (bouncer()->hasPermission('sales.orders'))
        @php
            $pipeline = $advancedData['pipeline'] ?? [];
            $stagesList = $pipeline['stages'] ?? [];
            $dataQuality = $pipeline['data_quality'] ?? ['unclassified_count' => 0, 'items' => []];
            $formattedComputedAt = $pipeline['formatted_last_computed'] ?? 'غير متاح بعد';
        @endphp

        <div
            x-data="{
                activeStageIndex: 0,
                stages: {{ json_encode($stagesList) }},
                isModalOpen: false,
                isQualityModalOpen: false,
                get currentStage() {
                    return this.stages[this.activeStageIndex] || this.stages[0] || { code: 'new', rank: 1, label: '', group_label: '', count: 0, value: 0, exception_count: 0 };
                },
                selectStage(index) {
                    this.activeStageIndex = index;
                },
                openModal(index) {
                    this.activeStageIndex = index;
                    this.isModalOpen = true;
                }
            }"
            @keydown.escape.window="isModalOpen = false; isQualityModalOpen = false"
            class="p-6 bg-[#F6F4EE] dark:bg-slate-950 border border-slate-300 dark:border-slate-800 rounded-3xl shadow-sm text-slate-900 dark:text-slate-100 font-sans space-y-6"
        >
            <!-- 2.1 Header Section -->
            <div class="flex flex-wrap items-center justify-between gap-4 border-b border-slate-200/80 dark:border-slate-800 pb-4">
                <div>
                    <span class="text-[11px] font-mono tracking-widest uppercase font-bold text-slate-500 dark:text-slate-400 block mb-1">
                        ORDER LIFECYCLE PIPELINE
                    </span>
                    <h3 class="text-xl font-black text-[#102A43] dark:text-white flex items-center gap-2">
                        المسار التشغيلي الموحد لدورة حياة الطلبات
                        <span class="text-xs font-normal font-mono px-2.5 py-0.5 rounded-full bg-blue-100 dark:bg-blue-950 text-blue-800 dark:text-blue-300 border border-blue-200 dark:border-blue-800">
                            11 محطة تشغيلية
                        </span>
                    </h3>
                    <p class="text-xs text-slate-600 dark:text-slate-400 mt-1">
                        مصدر القراءة التراكمي المشتق المباشر دقيق وغير مصنع (SSOT Read Model View)
                    </p>
                </div>

                <!-- Last Computed Timestamp Badge -->
                <div class="flex items-center gap-3">
                    <div class="text-left font-mono text-xs text-slate-600 dark:text-slate-400 bg-white/80 dark:bg-slate-900 px-3.5 py-2 rounded-xl border border-slate-200 dark:border-slate-800 shadow-2xs">
                        <span class="text-[10px] text-slate-400 block font-sans">آخر حساب دقيق (Computed At):</span>
                        <span class="font-extrabold text-[#102A43] dark:text-blue-300">{{ $formattedComputedAt }}</span>
                    </div>
                </div>
            </div>

            <!-- 2.2 Main Rail Layout (2 Columns: Left Guide 142px + Right Connected Rail) -->
            <div class="flex flex-col lg:flex-row gap-5 items-stretch">
                <!-- LEFT NARROW GUIDE (142px) -->
                <div class="w-full lg:w-[142px] shrink-0 bg-white/90 dark:bg-slate-900 p-3.5 rounded-2xl border border-slate-200/90 dark:border-slate-800 flex flex-col justify-between text-xs space-y-4 shadow-2xs">
                    <div>
                        <span class="text-[11px] font-extrabold uppercase text-[#102A43] dark:text-slate-300 block pb-2 border-b border-slate-100 dark:border-slate-800">
                            دليل السكة
                        </span>

                        <div class="mt-3 space-y-2 text-[11px]">
                            <div class="p-2 rounded-lg bg-blue-50/80 dark:bg-blue-950/50 text-blue-900 dark:text-blue-300 border border-blue-100 dark:border-blue-900/40">
                                <span class="font-black font-mono block">1–3</span>
                                <span class="font-bold">رحلة العميل</span>
                            </div>
                            <div class="p-2 rounded-lg bg-amber-50/80 dark:bg-amber-950/50 text-amber-900 dark:text-amber-300 border border-amber-100 dark:border-amber-900/40">
                                <span class="font-black font-mono block">4–8</span>
                                <span class="font-bold">سلسلة التوريد</span>
                            </div>
                            <div class="p-2 rounded-lg bg-emerald-50/80 dark:bg-emerald-950/50 text-emerald-900 dark:text-emerald-300 border border-emerald-100 dark:border-emerald-900/40">
                                <span class="font-black font-mono block">9–11</span>
                                <span class="font-bold">التنفيذ المحلي</span>
                            </div>
                        </div>
                    </div>

                    <!-- Local Storage Notice -->
                    <div class="p-2.5 bg-slate-100/90 dark:bg-slate-800/80 rounded-xl text-[10px] text-slate-600 dark:text-slate-400 font-semibold border border-slate-200/80 dark:border-slate-700/80 leading-relaxed">
                        <span class="font-bold text-slate-800 dark:text-slate-200 block mb-0.5">⚠️ تنبيه التوثيق:</span>
                        رصيد محلي موثق فقط بعد الاستلام في صنعاء (<code class="font-mono bg-white dark:bg-slate-900 px-1 py-0.5 rounded text-[9px]">hayest_dropship_ye</code>).
                    </div>
                </div>

                <!-- RIGHT CONNECTED RAIL AREA -->
                <div class="flex-1 bg-white/90 dark:bg-slate-900 p-5 rounded-2xl border border-slate-200/90 dark:border-slate-800 shadow-2xs space-y-4">
                    <!-- Current Active Stage Summary Banner -->
                    <div class="flex flex-wrap items-center justify-between p-3.5 rounded-xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700/60">
                        <div class="flex items-center gap-3">
                            <span class="text-xs font-black font-mono px-2.5 py-1 rounded-lg bg-[#102A43] text-white">
                                <span x-text="String(currentStage.rank || 1).padStart(2, '0')"></span> / 11
                            </span>
                            <div>
                                <h4 class="text-sm font-extrabold text-[#102A43] dark:text-white" x-text="currentStage.label"></h4>
                                <span class="text-[11px] text-slate-500 font-mono" x-text="currentStage.code"></span>
                            </div>
                        </div>

                        <div class="flex items-center gap-4 text-xs font-bold">
                            <div class="px-3 py-1.5 rounded-lg bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800">
                                <span class="text-slate-500">عدد الطلبات:</span>
                                <span class="font-mono font-black text-blue-600 dark:text-blue-400 mr-1" x-text="currentStage.count || 0"></span>
                            </div>
                            <div class="px-3 py-1.5 rounded-lg bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800">
                                <span class="text-slate-500">القيمة الإجمالية:</span>
                                <span class="font-mono font-black text-emerald-600 dark:text-emerald-400 mr-1" x-text="'{{ core()->currencySymbol(core()->getBaseCurrencyCode()) }} ' + (currentStage.value || 0).toLocaleString()"></span>
                            </div>
                            <button
                                type="button"
                                @click="isModalOpen = true"
                                class="px-3 py-1.5 rounded-lg bg-[#253691] text-white hover:bg-blue-800 transition-colors font-bold text-xs shadow-2xs cursor-pointer"
                            >
                                استعراض التفاصيل &rarr;
                            </button>
                        </div>
                    </div>

                    <!-- 11 Connected Stage Horizontal Rail -->
                    <div class="grid grid-cols-2 sm:grid-cols-5 lg:grid-cols-11 gap-1.5 relative">
                        <template x-for="(stg, idx) in stages" :key="stg.code">
                            <button
                                type="button"
                                @click="selectStage(idx)"
                                @dblclick="openModal(idx)"
                                :class="{
                                    'ring-2 ring-[#253691] shadow-md -translate-y-0.5 bg-white dark:bg-slate-800': activeStageIndex === idx,
                                    'hover:bg-slate-50 dark:hover:bg-slate-800/80 bg-slate-50/60 dark:bg-slate-800/40': activeStageIndex !== idx
                                }"
                                class="p-2.5 rounded-xl border border-slate-200/80 dark:border-slate-700/60 text-center transition-all duration-200 flex flex-col justify-between min-h-[110px] cursor-pointer group"
                            >
                                <!-- Stage Rank & Exception Badge -->
                                <div class="flex items-center justify-between text-[10px] font-mono">
                                    <span class="font-extrabold text-slate-400 group-hover:text-slate-600" x-text="String(stg.rank).padStart(2, '0')"></span>
                                    <template x-if="stg.exception_count > 0">
                                        <span class="px-1 py-0.2 rounded bg-rose-100 text-rose-800 font-bold" title="استثناء تشغيلي">!</span>
                                    </template>
                                </div>

                                <!-- Icon Container -->
                                <div class="my-1.5 flex items-center justify-center">
                                    <div
                                        :style="'background-color: ' + stg.color + '15; color: ' + stg.color"
                                        class="w-8 h-8 rounded-lg flex items-center justify-center font-bold text-sm shadow-2xs group-hover:scale-105 transition-transform"
                                    >
                                        <span x-text="stg.rank"></span>
                                    </div>
                                </div>

                                <!-- Label & Count -->
                                <div>
                                    <span class="text-[10px] font-bold text-slate-800 dark:text-slate-200 block truncate" x-text="stg.label"></span>
                                    <span class="text-xs font-black font-mono text-[#102A43] dark:text-blue-300 block mt-0.5" x-text="stg.count || 0"></span>
                                </div>
                            </button>
                        </template>
                    </div>

                    <!-- Swimlane Footer Labels -->
                    <div class="grid grid-cols-3 gap-2 text-center text-[10px] font-bold text-slate-500 dark:text-slate-400 pt-2 border-t border-slate-100 dark:border-slate-800">
                        <div class="bg-blue-50/50 dark:bg-blue-950/30 py-1 rounded-md border border-blue-100/60">
                            رحلة العميل (1–3)
                        </div>
                        <div class="bg-amber-50/50 dark:bg-amber-950/30 py-1 rounded-md border border-amber-100/60">
                            سلسلة التوريد والمخازن (4–8)
                        </div>
                        <div class="bg-emerald-50/50 dark:bg-emerald-950/30 py-1 rounded-md border border-emerald-100/60">
                            التنفيذ والتسليم المحلي (9–11)
                        </div>
                    </div>
                </div>
            </div>

            <!-- 2.3 Data Quality Exceptions Card (استثناءات جودة البيانات) -->
            <div class="flex flex-wrap items-center justify-between p-4 bg-white/90 dark:bg-slate-900 rounded-2xl border border-slate-200/90 dark:border-slate-800 shadow-2xs">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-xl bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300 flex items-center justify-center font-bold text-sm">
                        🛡️
                    </div>
                    <div>
                        <h4 class="text-xs font-extrabold text-slate-900 dark:text-white">
                            استثناءات جودة البيانات (Unclassified Data Quality Items)
                        </h4>
                        <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5">
                            بنود مبيعات تاريخية أو غير مكتملة المصدر استُبعدت صراحة لحماية دقة العدادات الـ11
                        </p>
                    </div>
                </div>

                <div class="flex items-center gap-4">
                    <div class="text-left font-mono text-xs">
                        <span class="text-slate-400 text-[10px] block">البنود المستبعدة:</span>
                        <span class="font-black text-amber-700 dark:text-amber-400 text-sm">
                            {{ $dataQuality['unclassified_count'] ?? 0 }} بنداً
                        </span>
                    </div>

                    <button
                        type="button"
                        @click="isQualityModalOpen = true"
                        class="px-3 py-1.5 text-xs font-bold rounded-xl bg-slate-100 text-slate-700 hover:bg-slate-200 dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-slate-700 transition-colors cursor-pointer"
                    >
                        استعراض التقرير المرجعي &rarr;
                    </button>
                </div>
            </div>

            <!-- 2.4 STAGE DRILLDOWN MODAL -->
            <div
                x-show="isModalOpen"
                x-cloak
                class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs transition-opacity"
            >
                <div
                    @click.away="isModalOpen = false"
                    class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200 dark:border-slate-800 shadow-2xl w-full max-w-xl overflow-hidden font-sans space-y-0 text-right"
                >
                    <!-- Modal Header -->
                    <div class="p-5 bg-slate-50 dark:bg-slate-800/80 border-b border-slate-200 dark:border-slate-700 flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <span class="text-xs font-black font-mono px-2.5 py-1 rounded-lg bg-[#102A43] text-white">
                                <span x-text="String(currentStage.rank || 1).padStart(2, '0')"></span> / 11
                            </span>
                            <div>
                                <h3 class="text-base font-black text-slate-900 dark:text-white" x-text="currentStage.label"></h3>
                                <span class="text-xs font-mono text-slate-500" x-text="currentStage.group_label"></span>
                            </div>
                        </div>

                        <button
                            type="button"
                            @click="isModalOpen = false"
                            class="w-8 h-8 rounded-full bg-slate-200 text-slate-600 hover:bg-slate-300 dark:bg-slate-700 dark:text-slate-300 flex items-center justify-center font-bold text-sm transition-colors cursor-pointer"
                        >
                            ✕
                        </button>
                    </div>

                    <!-- Modal Body -->
                    <div class="p-6 space-y-4 text-xs">
                        <div class="grid grid-cols-2 gap-3">
                            <div class="p-3 rounded-xl bg-slate-50 dark:bg-slate-800/50 border border-slate-200/80 dark:border-slate-700">
                                <span class="text-slate-500 block mb-1">رمز المحطة الفني:</span>
                                <code class="font-mono font-bold text-blue-600 dark:text-blue-400 text-sm" x-text="currentStage.code"></code>
                            </div>
                            <div class="p-3 rounded-xl bg-slate-50 dark:bg-slate-800/50 border border-slate-200/80 dark:border-slate-700">
                                <span class="text-slate-500 block mb-1">المجمّع التشغيلي:</span>
                                <span class="font-bold text-slate-800 dark:text-slate-200" x-text="currentStage.group_label"></span>
                            </div>
                            <div class="p-3 rounded-xl bg-blue-50/60 dark:bg-blue-950/40 border border-blue-100 dark:border-blue-900/40">
                                <span class="text-blue-700 dark:text-blue-300 block mb-1">عدد الطلبات الحية:</span>
                                <span class="font-mono font-black text-blue-800 dark:text-blue-200 text-base" x-text="currentStage.count || 0"></span>
                            </div>
                            <div class="p-3 rounded-xl bg-emerald-50/60 dark:bg-emerald-950/40 border border-emerald-100 dark:border-emerald-900/40">
                                <span class="text-emerald-700 dark:text-emerald-300 block mb-1">إجمالي القيمة:</span>
                                <span class="font-mono font-black text-emerald-800 dark:text-emerald-200 text-base" x-text="'{{ core()->currencySymbol(core()->getBaseCurrencyCode()) }} ' + (currentStage.value || 0).toLocaleString()"></span>
                            </div>
                        </div>

                        <div class="p-3.5 rounded-xl bg-slate-50 dark:bg-slate-800/50 border border-slate-200/80 dark:border-slate-700 space-y-1">
                            <span class="text-slate-500 font-bold block">الوصف والنطاق التشغيلي:</span>
                            <p class="text-slate-700 dark:text-slate-300 leading-relaxed">
                                تُمثل هذه المحطة المرحلة التشغيلية الحقيقية المحددة في Read Model View بناءً على أعلى جاهزية محسبة للطلبات.
                            </p>
                        </div>

                        <div class="flex items-center justify-between text-slate-500 text-[11px] pt-2 border-t border-slate-100 dark:border-slate-800">
                            <span>آخر ترحيل/حساب: <strong class="font-mono text-slate-700 dark:text-slate-300" x-text="currentStage.last_computed_at || 'غير متاح بعد'"></strong></span>
                            <span>الاستثناءات الحالية: <strong class="font-mono text-rose-600" x-text="currentStage.exception_count || 0"></strong></span>
                        </div>
                    </div>

                    <!-- Modal Footer -->
                    <div class="p-4 bg-slate-50 dark:bg-slate-800/80 border-t border-slate-200 dark:border-slate-700 flex items-center justify-end gap-3">
                        <button
                            type="button"
                            @click="isModalOpen = false"
                            class="px-4 py-2 text-xs font-bold rounded-xl bg-slate-200 text-slate-700 hover:bg-slate-300 dark:bg-slate-700 dark:text-slate-300 transition-colors cursor-pointer"
                        >
                            إغلاق النافذة
                        </button>
                        <a
                            :href="'{{ route('admin.sales.orders.index') }}?stage=' + currentStage.code"
                            class="px-4 py-2 text-xs font-bold rounded-xl bg-[#253691] text-white hover:bg-blue-800 transition-colors cursor-pointer shadow-2xs"
                        >
                            عرض الطلبات المرتبطة &rarr;
                        </a>
                    </div>
                </div>
            </div>

            <!-- 2.5 DATA QUALITY REPORT MODAL -->
            <div
                x-show="isQualityModalOpen"
                x-cloak
                class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs transition-opacity"
            >
                <div
                    @click.away="isQualityModalOpen = false"
                    class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200 dark:border-slate-800 shadow-2xl w-full max-w-2xl overflow-hidden font-sans space-y-0 text-right"
                >
                    <div class="p-5 bg-amber-50 dark:bg-amber-950/60 border-b border-amber-200 dark:border-amber-900/60 flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <span class="w-8 h-8 rounded-xl bg-amber-200 text-amber-900 flex items-center justify-center font-black">🛡️</span>
                            <div>
                                <h3 class="text-base font-black text-amber-950 dark:text-amber-200">تقرير استثناءات جودة البيانات</h3>
                                <span class="text-xs text-amber-800 dark:text-amber-300 font-semibold">البنود غير المصنفة المستبعدة من العدادات الرسمية</span>
                            </div>
                        </div>
                        <button
                            type="button"
                            @click="isQualityModalOpen = false"
                            class="w-8 h-8 rounded-full bg-amber-200 text-amber-900 hover:bg-amber-300 flex items-center justify-center font-bold text-sm transition-colors cursor-pointer"
                        >
                            ✕
                        </button>
                    </div>

                    <div class="p-6 space-y-4 text-xs">
                        <div class="p-3.5 rounded-xl bg-amber-50/60 dark:bg-amber-950/40 border border-amber-200/80 dark:border-amber-900/40 text-amber-900 dark:text-amber-200 leading-relaxed">
                            تضمن هذه الآلية عزل أي بنود قديمة أو ناقصة التوصيف دون إسقاطها افتراضياً في المحطات الـ11. يبلغ إجمالي البنود غير المصنفة حالياً <strong>{{ $dataQuality['unclassified_count'] ?? 0 }}</strong> بنداً.
                        </div>

                        @if (! empty($dataQuality['items']))
                            <div class="overflow-x-auto border border-slate-200 dark:border-slate-800 rounded-xl">
                                <table class="w-full text-right text-xs">
                                    <thead class="bg-slate-50 dark:bg-slate-800 text-slate-500 font-bold">
                                        <tr>
                                            <th class="py-2.5 px-3">معرف البند</th>
                                            <th class="py-2.5 px-3">رقم الطلب</th>
                                            <th class="py-2.5 px-3">SKU</th>
                                            <th class="py-2.5 px-3">اسم المنتج</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                                        @foreach ($dataQuality['items'] as $item)
                                            <tr>
                                                <td class="py-2 px-3 font-mono font-bold">{{ $item->item_id }}</td>
                                                <td class="py-2 px-3 font-mono text-blue-600">#{{ $item->order_id }}</td>
                                                <td class="py-2 px-3 font-mono">{{ $item->sku }}</td>
                                                <td class="py-2 px-3 text-slate-700 dark:text-slate-300">{{ $item->name }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="p-4 text-center text-slate-500 font-bold bg-slate-50 rounded-xl">
                                لا توجد بنود غير مصنفة حالياً، جميع البنود مسقطة بنجاح في نموذج القراءة.
                            </div>
                        @endif
                    </div>

                    <div class="p-4 bg-slate-50 dark:bg-slate-800/80 border-t border-slate-200 dark:border-slate-700 flex items-center justify-end">
                        <button
                            type="button"
                            @click="isQualityModalOpen = false"
                            class="px-4 py-2 text-xs font-bold rounded-xl bg-slate-200 text-slate-700 hover:bg-slate-300 dark:bg-slate-700 dark:text-slate-300 transition-colors cursor-pointer"
                        >
                            إغلاق
                        </button>
                    </div>
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
