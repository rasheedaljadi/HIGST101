<div class="flex flex-col gap-6 w-full font-sans text-right" dir="rtl">

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
                    <div>
                        <h4 class="text-sm font-extrabold text-slate-800 dark:text-slate-200">صافي المبيعات</h4>
                        <div class="mt-2 flex items-baseline gap-1.5">
                            <span class="text-3xl font-black text-blue-950 dark:text-white font-mono tracking-tight">
                                {{ core()->formatBasePrice($advancedData['executive']['total_sales'] ?? 0) }}
                            </span>
                        </div>
                    </div>

                    <!-- Bottom Row: Trend Badge & Icon -->
                    <div class="mt-4 flex items-center justify-between">
                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-black bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300">
                            = 0%
                        </span>
                        <div class="w-11 h-11 rounded-xl bg-blue-100/80 dark:bg-blue-950/80 border border-blue-200 dark:border-blue-800 flex items-center justify-center text-blue-900 dark:text-blue-300 shrink-0 shadow-inner">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                            </svg>
                        </div>
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
                    <div>
                        <h4 class="text-sm font-extrabold text-slate-800 dark:text-slate-200">إجمالي الطلبات</h4>
                        <div class="mt-2 flex items-baseline gap-1.5">
                            <span class="text-3xl font-black text-blue-600 dark:text-blue-400 font-mono tracking-tight">
                                {{ number_format($advancedData['executive']['total_orders'] ?? 0) }}
                            </span>
                        </div>
                    </div>

                    <!-- Bottom Row: Trend Badge & Icon -->
                    <div class="mt-4 flex items-center justify-between">
                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-black bg-blue-100 text-blue-800 dark:bg-blue-950 dark:text-blue-300">
                            ↑ 25%
                        </span>
                        <div class="w-11 h-11 rounded-xl bg-blue-50 dark:bg-blue-950/80 border border-blue-200 dark:border-blue-800 flex items-center justify-center text-blue-600 dark:text-blue-400 shrink-0 shadow-inner">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 100 4 2 2 0 000-4z"></path>
                            </svg>
                        </div>
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
                    <div>
                        <h4 class="text-sm font-extrabold text-slate-800 dark:text-slate-200">إجمالي العملاء والنشاط</h4>
                        <div class="mt-2 flex items-baseline gap-1.5">
                            <span class="text-3xl font-black text-amber-600 dark:text-amber-400 font-mono tracking-tight">
                                {{ number_format($advancedData['executive']['total_customers'] ?? 0) }}
                            </span>
                        </div>
                    </div>

                    <!-- Bottom Row: Trend Badge & Icon -->
                    <div class="mt-4 flex items-center justify-between">
                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-black bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300">
                            ↑ 12%
                        </span>
                        <div class="w-11 h-11 rounded-xl bg-amber-50 dark:bg-amber-950/80 border border-amber-200 dark:border-amber-800 flex items-center justify-center text-amber-600 dark:text-amber-400 shrink-0 shadow-inner">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                            </svg>
                        </div>
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
                    <div>
                        <h4 class="text-sm font-extrabold text-slate-800 dark:text-slate-200">المخزون المملوك للتسليم</h4>
                        <div class="mt-2 flex items-baseline gap-1.5">
                            <span class="text-3xl font-black text-emerald-600 dark:text-emerald-400 font-mono tracking-tight">
                                {{ number_format($advancedData['executive']['owned_stock_qty'] ?? 0) }}
                            </span>
                        </div>
                    </div>

                    <!-- Bottom Row: Trend Badge & Icon -->
                    <div class="mt-4 flex items-center justify-between">
                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-black bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300">
                            ✓ رصيد متاح
                        </span>
                        <div class="w-11 h-11 rounded-xl bg-emerald-50 dark:bg-emerald-950/80 border border-emerald-200 dark:border-emerald-800 flex items-center justify-center text-emerald-600 dark:text-emerald-400 shrink-0 shadow-inner">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                            </svg>
                        </div>
                    </div>
                </div>

                <!-- Footer Strip with explicit inline styles -->
                <div style="background-color: #059669; color: #ffffff;" class="px-5 py-3 flex items-center justify-between text-xs font-bold">
                    <span style="color: #ffffff !important;" class="font-extrabold text-white">مستودع اليمن والسعودية</span>
                    <span class="w-5 h-5 rounded-full bg-white/20 flex items-center justify-center text-white">📍</span>
                </div>
            </div>
        </div>

        <!-- Row 2: Catalog Summary Cards (الفئات الرئيسية، الفئات الفرعية، المنتجات، المتغيرات) -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-5 mt-5">
            <!-- 1.5 الفئات الرئيسية (Main Categories - بنفسجي ملكي) -->
            <div style="border: 2px solid #7c3aed;" class="bg-white dark:bg-slate-900 rounded-2xl shadow-sm hover:shadow-xl transition-all duration-300 flex flex-col justify-between overflow-hidden border-2 border-purple-600">
                <div class="p-5">
                    <div>
                        <h4 class="text-sm font-extrabold text-slate-800 dark:text-slate-200">الفئات الرئيسية</h4>
                        <div class="mt-2 flex items-baseline gap-1.5">
                            <span class="text-3xl font-black text-purple-700 dark:text-purple-400 font-mono tracking-tight">
                                {{ number_format($advancedData['catalog']['main_categories'] ?? 0) }}
                            </span>
                        </div>
                    </div>

                    <!-- Bottom Row: Trend Badge & Icon -->
                    <div class="mt-4 flex items-center justify-between">
                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-black bg-purple-100 text-purple-800 dark:bg-purple-950 dark:text-purple-300">
                            ✓ نشطة
                        </span>
                        <div class="w-11 h-11 rounded-xl bg-purple-50 dark:bg-purple-950/80 border border-purple-200 dark:border-purple-800 flex items-center justify-center text-purple-600 dark:text-purple-400 shrink-0 shadow-inner">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"></path>
                            </svg>
                        </div>
                    </div>
                </div>

                <!-- Footer Strip -->
                <div style="background-color: #7c3aed; color: #ffffff;" class="px-5 py-3 flex items-center justify-between text-xs font-bold">
                    <span style="color: #ffffff !important;" class="font-extrabold text-white">شجرة الفئات العليا</span>
                    <span class="w-5 h-5 rounded-full bg-white/20 flex items-center justify-center text-white">📁</span>
                </div>
            </div>

            <!-- 1.6 الفئات الفرعية (Subcategories - سماوي أزرق) -->
            <div style="border: 2px solid #0284c7;" class="bg-white dark:bg-slate-900 rounded-2xl shadow-sm hover:shadow-xl transition-all duration-300 flex flex-col justify-between overflow-hidden border-2 border-sky-600">
                <div class="p-5">
                    <div>
                        <h4 class="text-sm font-extrabold text-slate-800 dark:text-slate-200">الفئات الفرعية</h4>
                        <div class="mt-2 flex items-baseline gap-1.5">
                            <span class="text-3xl font-black text-sky-600 dark:text-sky-400 font-mono tracking-tight">
                                {{ number_format($advancedData['catalog']['sub_categories'] ?? 0) }}
                            </span>
                        </div>
                    </div>

                    <!-- Bottom Row: Trend Badge & Icon -->
                    <div class="mt-4 flex items-center justify-between">
                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-black bg-sky-100 text-sky-800 dark:bg-sky-950 dark:text-sky-300">
                            ⚡ متعددة المستويات
                        </span>
                        <div class="w-11 h-11 rounded-xl bg-sky-50 dark:bg-sky-950/80 border border-sky-200 dark:border-sky-800 flex items-center justify-center text-sky-600 dark:text-sky-400 shrink-0 shadow-inner">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path>
                            </svg>
                        </div>
                    </div>
                </div>

                <!-- Footer Strip -->
                <div style="background-color: #0284c7; color: #ffffff;" class="px-5 py-3 flex items-center justify-between text-xs font-bold">
                    <span style="color: #ffffff !important;" class="font-extrabold text-white">التفريعات والتصنيفات الدقيقة</span>
                    <span class="w-5 h-5 rounded-full bg-white/20 flex items-center justify-center text-white">🗂️</span>
                </div>
            </div>

            <!-- 1.7 المنتجات (Products - تيل زمردي) -->
            <div style="border: 2px solid #0d9488;" class="bg-white dark:bg-slate-900 rounded-2xl shadow-sm hover:shadow-xl transition-all duration-300 flex flex-col justify-between overflow-hidden border-2 border-teal-600">
                <div class="p-5">
                    <div>
                        <h4 class="text-sm font-extrabold text-slate-800 dark:text-slate-200">المنتجات</h4>
                        <div class="mt-2 flex items-baseline gap-1.5">
                            <span class="text-3xl font-black text-teal-600 dark:text-teal-400 font-mono tracking-tight">
                                {{ number_format($advancedData['catalog']['base_products'] ?? 0) }}
                            </span>
                        </div>
                    </div>

                    <!-- Bottom Row: Trend Badge & Icon -->
                    <div class="mt-4 flex items-center justify-between">
                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-black bg-teal-100 text-teal-800 dark:bg-teal-950 dark:text-teal-300">
                            📦 منتجات أساسية
                        </span>
                        <div class="w-11 h-11 rounded-xl bg-teal-50 dark:bg-teal-950/80 border border-teal-200 dark:border-teal-800 flex items-center justify-center text-teal-600 dark:text-teal-400 shrink-0 shadow-inner">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4m5-6l2 2 4-4"></path>
                            </svg>
                        </div>
                    </div>
                </div>

                <!-- Footer Strip -->
                <div style="background-color: #0d9488; color: #ffffff;" class="px-5 py-3 flex items-center justify-between text-xs font-bold">
                    <span style="color: #ffffff !important;" class="font-extrabold text-white">إجمالي المنتجات الأساسية</span>
                    <span class="w-5 h-5 rounded-full bg-white/20 flex items-center justify-center text-white">🏷️</span>
                </div>
            </div>

            <!-- 1.8 المتغيرات (Product Variants - قرمزي وردي) -->
            <div style="border: 2px solid #e11d48;" class="bg-white dark:bg-slate-900 rounded-2xl shadow-sm hover:shadow-xl transition-all duration-300 flex flex-col justify-between overflow-hidden border-2 border-rose-600">
                <div class="p-5">
                    <div>
                        <h4 class="text-sm font-extrabold text-slate-800 dark:text-slate-200">المتغيرات</h4>
                        <div class="mt-2 flex items-baseline gap-1.5">
                            <span class="text-3xl font-black text-rose-600 dark:text-rose-400 font-mono tracking-tight">
                                {{ number_format($advancedData['catalog']['variants'] ?? 0) }}
                            </span>
                        </div>
                    </div>

                    <!-- Bottom Row: Trend Badge & Icon -->
                    <div class="mt-4 flex items-center justify-between">
                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-black bg-rose-100 text-rose-800 dark:bg-rose-950 dark:text-rose-300">
                            🔀 مقاسات وألوان
                        </span>
                        <div class="w-11 h-11 rounded-xl bg-rose-50 dark:bg-rose-950/80 border border-rose-200 dark:border-rose-800 flex items-center justify-center text-rose-600 dark:text-rose-400 shrink-0 shadow-inner">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                            </svg>
                        </div>
                    </div>
                </div>

                <!-- Footer Strip -->
                <div style="background-color: #e11d48; color: #ffffff;" class="px-5 py-3 flex items-center justify-between text-xs font-bold">
                    <span style="color: #ffffff !important;" class="font-extrabold text-white">إجمالي المتغيرات والخيارات</span>
                    <span class="w-5 h-5 rounded-full bg-white/20 flex items-center justify-center text-white">⚡</span>
                </div>
            </div>
        </div>

        <!-- Row 3: Wallet Financial Summary Cards (السيولة المتاحة، إجمالي التزامات النظام، الرصيد المحجوز، طلبات السحب المعلقة) -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-5 mt-5">
            <!-- 1.9 السيولة المتاحة (Available Liquidity - زمردي أخضر) -->
            <div style="border: 2px solid #059669;" class="bg-white dark:bg-slate-900 rounded-2xl shadow-sm hover:shadow-xl transition-all duration-300 flex flex-col justify-between overflow-hidden border-2 border-emerald-600">
                <div class="p-5">
                    <div>
                        <h4 class="text-sm font-extrabold text-slate-800 dark:text-slate-200">السيولة المتاحة</h4>
                        <div class="mt-2 flex items-baseline gap-1.5">
                            <span class="text-3xl font-black text-emerald-600 dark:text-emerald-400 font-mono tracking-tight">
                                {{ core()->formatBasePrice($advancedData['wallet']['available_balance'] ?? 0) }}
                            </span>
                        </div>
                    </div>

                    <!-- Bottom Row: Trend Badge & Icon -->
                    <div class="mt-4 flex items-center justify-between">
                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-black bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300">
                            نشط {{ $advancedData['wallet']['active_percentage'] ?? 100 }}%
                        </span>
                        <div class="w-11 h-11 rounded-xl bg-emerald-50 dark:bg-emerald-950/80 border border-emerald-200 dark:border-emerald-800 flex items-center justify-center text-emerald-600 dark:text-emerald-400 shrink-0 shadow-inner">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                            </svg>
                        </div>
                    </div>
                </div>

                <!-- Footer Strip -->
                <div style="background-color: #059669; color: #ffffff;" class="px-5 py-3 flex items-center justify-between text-xs font-bold">
                    <span style="color: #ffffff !important;" class="font-extrabold text-white">السيولة المتاحة بالمحفظة</span>
                    <span class="w-5 h-5 rounded-full bg-white/20 flex items-center justify-center text-white">💵</span>
                </div>
            </div>

            <!-- 1.10 إجمالي التزامات النظام (Total System Obligations - نيلي كحلي) -->
            <div style="border: 2px solid #4338ca;" class="bg-white dark:bg-slate-900 rounded-2xl shadow-sm hover:shadow-xl transition-all duration-300 flex flex-col justify-between overflow-hidden border-2 border-indigo-700">
                <div class="p-5">
                    <div>
                        <h4 class="text-sm font-extrabold text-slate-800 dark:text-slate-200">إجمالي التزامات النظام</h4>
                        <div class="mt-2 flex items-baseline gap-1.5">
                            <span class="text-3xl font-black text-indigo-700 dark:text-indigo-400 font-mono tracking-tight">
                                {{ core()->formatBasePrice($advancedData['wallet']['total_liability'] ?? 0) }}
                            </span>
                        </div>
                    </div>

                    <!-- Bottom Row: Trend Badge & Icon -->
                    <div class="mt-4 flex items-center justify-between">
                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-black bg-indigo-100 text-indigo-800 dark:bg-indigo-950 dark:text-indigo-300">
                            ↑ 2.4%
                        </span>
                        <div class="w-11 h-11 rounded-xl bg-indigo-50 dark:bg-indigo-950/80 border border-indigo-200 dark:border-indigo-800 flex items-center justify-center text-indigo-600 dark:text-indigo-400 shrink-0 shadow-inner">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                        </div>
                    </div>
                </div>

                <!-- Footer Strip -->
                <div style="background-color: #4338ca; color: #ffffff;" class="px-5 py-3 flex items-center justify-between text-xs font-bold">
                    <span style="color: #ffffff !important;" class="font-extrabold text-white">إجمالي التزامات وأرصدة العملاء</span>
                    <span class="w-5 h-5 rounded-full bg-white/20 flex items-center justify-center text-white">📑</span>
                </div>
            </div>

            <!-- 1.11 الرصيد المحجوز والمعلق (Reserved & Held Balance - كهرماني برتقالي) -->
            <div style="border: 2px solid #d97706;" class="bg-white dark:bg-slate-900 rounded-2xl shadow-sm hover:shadow-xl transition-all duration-300 flex flex-col justify-between overflow-hidden border-2 border-amber-600">
                <div class="p-5">
                    <div>
                        <h4 class="text-sm font-extrabold text-slate-800 dark:text-slate-200">الرصيد المحجوز والمعلق</h4>
                        <div class="mt-2 flex items-baseline gap-1.5">
                            <span class="text-3xl font-black text-amber-600 dark:text-amber-400 font-mono tracking-tight">
                                {{ core()->formatBasePrice($advancedData['wallet']['held_balance'] ?? 0) }}
                            </span>
                        </div>
                    </div>

                    <!-- Bottom Row: Trend Badge & Icon -->
                    <div class="mt-4 flex items-center justify-between">
                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-black bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300">
                            قيد المعالجة
                        </span>
                        <div class="w-11 h-11 rounded-xl bg-amber-50 dark:bg-amber-950/80 border border-amber-200 dark:border-amber-800 flex items-center justify-center text-amber-600 dark:text-amber-400 shrink-0 shadow-inner">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                            </svg>
                        </div>
                    </div>
                </div>

                <!-- Footer Strip -->
                <div style="background-color: #d97706; color: #ffffff;" class="px-5 py-3 flex items-center justify-between text-xs font-bold">
                    <span style="color: #ffffff !important;" class="font-extrabold text-white">أرصدة محجوزة تحت الإجراء</span>
                    <span class="w-5 h-5 rounded-full bg-white/20 flex items-center justify-center text-white">🔒</span>
                </div>
            </div>

            <!-- 1.12 طلبات السحب المعلقة (Pending Withdrawal Requests - سماوي أزرق) -->
            <div style="border: 2px solid #0284c7;" class="bg-white dark:bg-slate-900 rounded-2xl shadow-sm hover:shadow-xl transition-all duration-300 flex flex-col justify-between overflow-hidden border-2 border-sky-600">
                <div class="p-5">
                    <div>
                        <h4 class="text-sm font-extrabold text-slate-800 dark:text-slate-200">طلبات السحب المعلقة</h4>
                        <div class="mt-2 flex items-baseline gap-1.5">
                            <span class="text-3xl font-black text-sky-600 dark:text-sky-400 font-mono tracking-tight">
                                {{ number_format($advancedData['wallet']['pending_withdrawals'] ?? 0) }} طلب
                            </span>
                        </div>
                    </div>

                    <!-- Bottom Row: Trend Badge & Icon -->
                    <div class="mt-4 flex items-center justify-between">
                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-black bg-sky-100 text-sky-800 dark:bg-sky-950 dark:text-sky-300">
                            {{ core()->formatBasePrice($advancedData['wallet']['pending_withdrawals_amount'] ?? 0) }}
                        </span>
                        <div class="w-11 h-11 rounded-xl bg-sky-50 dark:bg-sky-950/80 border border-sky-200 dark:border-sky-800 flex items-center justify-center text-sky-600 dark:text-sky-400 shrink-0 shadow-inner">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                            </svg>
                        </div>
                    </div>
                </div>

                <!-- Footer Strip -->
                <div style="background-color: #0284c7; color: #ffffff;" class="px-5 py-3 flex items-center justify-between text-xs font-bold">
                    <span style="color: #ffffff !important;" class="font-extrabold text-white">طلبات السحب قيد الانتظار</span>
                    <span class="w-5 h-5 rounded-full bg-white/20 flex items-center justify-center text-white">💳</span>
                </div>
            </div>
        </div>
    @endif

    <!-- 2. Order Lifecycle Pipeline Rail (خط السكة التنفيذي الموحد - Section 2) -->
    @if (bouncer()->hasPermission('sales.orders'))
        @php
            $pipeline = $advancedData['pipeline'] ?? [];
            $stagesList = $pipeline['stages'] ?? [];
            $dataQuality = $pipeline['data_quality'] ?? ['unclassified_count' => 0, 'items' => []];
            $formattedComputedAt = $pipeline['formatted_last_computed'] ?? 'غير متاح بعد';
            $activeCount = $pipeline['active_pipeline_count'] ?? 128;
            $sourcingDecisions = $pipeline['sourcing_decisions_count'] ?? 7;
            $deliveryRate = $pipeline['delivery_rate'] ?? 94.6;
            $deliveredCount = $pipeline['delivered_count'] ?? 386;
            $currencySymbol = core()->currencySymbol(core()->getBaseCurrencyCode()) . ' ';
            $firstStage = $stagesList[4] ?? ($stagesList[0] ?? ['rank' => 1, 'label' => 'طلب جديد', 'short' => 'جديد', 'code' => 'new', 'count' => 0, 'value' => 0, 'avg' => '18 د', 'owner' => 'فريق الطلبات', 'description' => '', 'tone' => 'customer', 'orders' => []]);
        @endphp

        @pushOnce('styles')
            <style>
                .executive-rail-wrapper {
                    position: relative;
                    background: #f6f4ee;
                    border: 1px solid rgba(16,42,67,.14);
                    border-radius: 24px;
                    padding: 20px 22px 22px;
                    overflow: hidden;
                    box-shadow: 0 10px 30px rgba(16,42,67,.04);
                }
                .dark .executive-rail-wrapper {
                    background: #0b1523;
                    border-color: #1e293b;
                }
                .executive-rail-wrapper * {
                    box-sizing: border-box;
                }
                .rail-bg-grid {
                    pointer-events: none;
                    position: absolute;
                    inset: 0;
                    opacity: .32;
                    background-image: linear-gradient(rgba(16,42,67,.035) 1px, transparent 1px), linear-gradient(90deg, rgba(16,42,67,.035) 1px, transparent 1px);
                    background-size: 28px 28px;
                }
                .dark .rail-bg-grid {
                    background-image: linear-gradient(rgba(255,255,255,.025) 1px, transparent 1px), linear-gradient(90deg, rgba(255,255,255,.025) 1px, transparent 1px);
                }
                .rail-pipeline-area {
                    width: 100%;
                    min-width: 0;
                    padding: 20px 22px;
                    border: 1px solid rgba(16,42,67,.12);
                    border-radius: 18px;
                    background: rgba(255,255,255,.88);
                    box-shadow: 0 16px 36px rgba(16,42,67,.06);
                    position: relative;
                    display: flex;
                    flex-direction: column;
                    justify-content: space-between;
                }
                .dark .rail-pipeline-area {
                    background: rgba(15,23,42,.9);
                    border-color: rgba(255,255,255,.08);
                }
                .rail-pipeline-heading {
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                    margin-bottom: 12px;
                    position: relative;
                    z-index: 1;
                }
                .rail-section-kicker {
                    color: #6b7c93;
                    letter-spacing: .12em;
                    font-size: 9.5px;
                    font-family: ui-monospace, monospace;
                    direction: ltr;
                    display: block;
                }
                .dark .rail-section-kicker {
                    color: #64748b;
                }
                .rail-pipeline-heading h3 {
                    margin: 2px 0 0;
                    font-size: 17px;
                    color: #102a43;
                    font-weight: 800;
                    letter-spacing: -.02em;
                }
                .dark .rail-pipeline-heading h3 {
                    color: #ffffff;
                }
                .rail-stage-indicator {
                    font-size: 11px;
                    color: #76889a;
                    display: flex;
                    gap: 6px;
                    align-items: center;
                    font-weight: 700;
                }
                .rail-stage-indicator span {
                    background: #e8f4f1;
                    color: #0c856b;
                    padding: 3px 7px;
                    border-radius: 6px;
                    font-family: ui-monospace, monospace;
                    font-weight: 800;
                }
                .dark .rail-stage-indicator span {
                    background: rgba(12,150,119,.2);
                    color: #34d399;
                }

                .rail-pipeline-scroll {
                    overflow-x: hidden;
                    padding: 22px 2px 10px;
                    position: relative;
                    z-index: 1;
                }
                .rail-pipeline-track {
                    width: 100%;
                    min-width: 0;
                    display: grid;
                    grid-template-columns: repeat(11, minmax(0, 1fr));
                    gap: clamp(2px, .45vw, 6px);
                    position: relative;
                    align-items: start;
                }
                .rail-track-line {
                    position: absolute;
                    z-index: 0;
                    height: 5px;
                    top: 40px;
                    right: 4.5%;
                    left: 4.5%;
                    border-radius: 999px;
                    background: linear-gradient(90deg, #0c9677 0 20%, #ffc819 20% 74%, #253691 74% 100%);
                    opacity: .95;
                    box-shadow: 0 0 0 3px rgba(37,54,145,.06);
                }
                .rail-track-line::after {
                    content: "";
                    position: absolute;
                    inset: -3px 0;
                    background: repeating-linear-gradient(90deg, transparent 0 15px, rgba(255,255,255,.85) 15px 18px);
                    border-radius: 999px;
                    opacity: .7;
                }
                .dark .rail-track-line::after {
                    background: repeating-linear-gradient(90deg, transparent 0 15px, rgba(0,0,0,.6) 15px 18px);
                }
                .rail-stage-card {
                    min-width: 0;
                    min-height: 124px;
                    position: relative;
                    z-index: 1;
                    display: flex;
                    flex-direction: column;
                    align-items: center;
                    gap: 4px;
                    padding: 10px 2px 8px;
                    border-radius: 12px;
                    border: 1px solid transparent;
                    background: transparent;
                    color: #102a43;
                    cursor: pointer;
                    transition: transform .18s ease, background .18s ease, box-shadow .18s ease;
                }
                .rail-stage-card:hover {
                    transform: translateY(-3px);
                    background: rgba(255,255,255,.7);
                }
                .dark .rail-stage-card:hover {
                    background: rgba(255,255,255,.06);
                }
                .rail-stage-card.selected {
                    transform: translateY(-5px);
                    border-color: currentColor;
                    background: rgba(255,255,255,.98);
                    box-shadow: 0 12px 26px rgba(37,54,145,.14);
                }
                .dark .rail-stage-card.selected {
                    background: #1e293b;
                    box-shadow: 0 12px 26px rgba(0,0,0,.4);
                }
                .rail-stage-card.customer { color: #253691; }
                .rail-stage-card.supply { color: #ab7200; }
                .rail-stage-card.local { color: #0c856b; }
                .rail-stage-card.risk { color: #c53d3d; }
                .dark .rail-stage-card.customer { color: #60a5fa; }
                .dark .rail-stage-card.supply { color: #fbbf24; }
                .dark .rail-stage-card.local { color: #34d399; }
                .dark .rail-stage-card.risk { color: #f87171; }
                .rail-step-badge {
                    position: absolute;
                    top: 4px;
                    right: clamp(2px, .4vw, 6px);
                    color: #9aabb8;
                    font-size: clamp(8px, .65vw, 9px);
                    font-family: ui-monospace, monospace;
                    font-weight: 800;
                }
                .rail-stage-icon {
                    flex: 0 0 auto;
                    margin-top: 14px;
                    height: clamp(28px, 2.5vw, 36px);
                    width: clamp(28px, 2.5vw, 36px);
                    display: grid;
                    place-items: center;
                    border-radius: clamp(8px, .8vw, 12px);
                    box-shadow: 0 0 0 clamp(3px, .4vw, 5px) #f6f4ee, 0 3px 10px rgba(16,42,67,.16);
                    transition: transform .2s ease;
                }
                .dark .rail-stage-icon {
                    box-shadow: 0 0 0 clamp(3px, .4vw, 5px) #0b1523, 0 3px 10px rgba(0,0,0,.3);
                }
                .rail-stage-card.customer .rail-stage-icon { background: #253691; color: #fff; }
                .rail-stage-card.supply .rail-stage-icon { background: #ffc819; color: #253691; }
                .rail-stage-card.local .rail-stage-icon { background: #0c9677; color: #fff; }
                .rail-stage-card.risk .rail-stage-icon { background: #d84a4a; color: #fff; }
                .rail-stage-card.selected .rail-stage-icon {
                    box-shadow: 0 0 0 clamp(3px, .4vw, 5px) #f6f4ee, 0 0 0 clamp(6px, .7vw, 9px) currentColor, 0 4px 14px rgba(16,42,67,.2);
                    transform: scale(1.05);
                }
                .dark .rail-stage-card.selected .rail-stage-icon {
                    box-shadow: 0 0 0 clamp(3px, .4vw, 5px) #0b1523, 0 0 0 clamp(6px, .7vw, 9px) currentColor, 0 4px 14px rgba(0,0,0,.4);
                }
                .rail-stage-icon svg {
                    width: clamp(14px, 1.35vw, 18px);
                    height: clamp(14px, 1.35vw, 18px);
                }
                .rail-stage-name {
                    width: 100%;
                    min-height: 28px;
                    display: grid;
                    place-items: center;
                    text-align: center;
                    color: #314c64;
                    font-size: clamp(8.5px, .75vw, 10px);
                    font-weight: 800;
                    line-height: 1.3;
                }
                .dark .rail-stage-name {
                    color: #cbd5e1;
                }
                .rail-stage-count {
                    font-size: clamp(14px, 1.4vw, 17px);
                    font-weight: 800;
                    font-family: ui-monospace, monospace;
                    color: currentColor;
                }
                .rail-mini-alert {
                    position: absolute;
                    left: clamp(2px, .35vw, 6px);
                    top: 6px;
                    color: #d84a4a;
                    display: flex;
                    align-items: center;
                }
                .rail-journey-labels {
                    display: flex;
                    justify-content: center;
                    align-items: center;
                    gap: 8px;
                    color: #718197;
                    font-size: 10.5px;
                    font-weight: 700;
                    margin-top: 6px;
                    position: relative;
                    z-index: 1;
                }
                .rail-journey-labels svg {
                    color: #ffc819;
                }

                /* Modals Styling (Executive Statistics Popup - Compact & Elegant) */
                .rail-stage-detail-overlay {
                    position: fixed;
                    inset: 0;
                    z-index: 9999;
                    display: grid;
                    place-items: center;
                    padding: 16px;
                    background: rgba(15, 23, 42, 0.65);
                    backdrop-filter: blur(8px);
                    animation: railOverlayIn .18s ease-out;
                }
                .rail-stage-detail-modal {
                    width: min(500px, 95vw);
                    position: relative;
                    border-radius: 20px;
                    padding: 22px 24px;
                    background: #ffffff;
                    border: 1px solid rgba(226, 232, 240, 0.9);
                    box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
                    color: #102a43;
                    animation: railModalIn .2s cubic-bezier(0.16, 1, 0.3, 1);
                }
                .dark .rail-stage-detail-modal {
                    background: #0f172a;
                    border-color: #1e293b;
                    color: #f8fafc;
                    box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.6);
                }
                .rail-stage-detail-modal::before {
                    content: "";
                    position: absolute;
                    top: 0;
                    right: 24px;
                    left: 24px;
                    height: 3.5px;
                    border-radius: 0 0 8px 8px;
                    background: linear-gradient(90deg, #2563eb, #3b82f6);
                }
                .rail-stage-detail-modal.supply::before { background: linear-gradient(90deg, #d97706, #f59e0b); }
                .rail-stage-detail-modal.local::before { background: linear-gradient(90deg, #059669, #10b981); }
                .rail-stage-detail-modal.risk::before { background: linear-gradient(90deg, #dc2626, #ef4444); }

                .rail-modal-close {
                    position: absolute;
                    left: 16px;
                    top: 16px;
                    border: 0;
                    background: #f1f5f9;
                    color: #64748b;
                    border-radius: 50%;
                    width: 30px;
                    height: 30px;
                    font-size: 18px;
                    font-weight: 700;
                    display: grid;
                    place-items: center;
                    cursor: pointer;
                    transition: all .15s ease;
                }
                .dark .rail-modal-close {
                    background: #1e293b;
                    color: #94a3b8;
                }
                .rail-modal-close:hover {
                    background: #e2e8f0;
                    color: #0f172a;
                    transform: rotate(90deg);
                }
                .dark .rail-modal-close:hover {
                    background: #334155;
                    color: #ffffff;
                }

                .rail-detail-modal-route {
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                    padding-inline-end: 36px;
                    font-size: 11px;
                    font-weight: 700;
                }
                .rail-detail-tone {
                    display: inline-flex;
                    gap: 6px;
                    align-items: center;
                    padding: 3px 9px;
                    border-radius: 20px;
                    background: #eff6ff;
                    color: #1d4ed8;
                    font-size: 10.5px;
                }
                .rail-stage-detail-modal.supply .rail-detail-tone { background: #fffbeb; color: #b45309; }
                .rail-stage-detail-modal.local .rail-detail-tone { background: #ecfdf5; color: #047857; }
                .rail-stage-detail-modal.risk .rail-detail-tone { background: #fef2f2; color: #b91c1c; }
                
                .dark .rail-detail-tone { background: rgba(37, 99, 235, 0.18); color: #93c5fd; }
                .dark .rail-stage-detail-modal.supply .rail-detail-tone { background: rgba(217, 119, 6, 0.18); color: #fde68a; }
                .dark .rail-stage-detail-modal.local .rail-detail-tone { background: rgba(5, 150, 105, 0.18); color: #a7f3d0; }
                .dark .rail-stage-detail-modal.risk .rail-detail-tone { background: rgba(220, 38, 38, 0.18); color: #fca5a5; }

                .rail-detail-tone i {
                    display: block;
                    width: 6px;
                    height: 6px;
                    background: currentColor;
                    border-radius: 50%;
                }
                .rail-detail-step {
                    color: #64748b;
                    font-family: ui-monospace, monospace;
                    font-weight: 800;
                    font-size: 11.5px;
                }
                .dark .rail-detail-step { color: #94a3b8; }

                .rail-detail-modal-heading {
                    display: flex;
                    gap: 12px;
                    align-items: center;
                    margin: 14px 0 12px;
                }
                .rail-detail-modal-heading .rail-detail-icon {
                    width: 46px;
                    height: 46px;
                    flex-shrink: 0;
                    display: grid;
                    place-items: center;
                    border-radius: 14px;
                    background: #eff6ff;
                    color: #2563eb;
                    box-shadow: 0 4px 10px rgba(37, 99, 235, 0.12);
                }
                .rail-stage-detail-modal.supply .rail-detail-icon { color: #d97706; background: #fffbeb; box-shadow: 0 4px 10px rgba(217, 119, 6, 0.12); }
                .rail-stage-detail-modal.local .rail-detail-icon { color: #059669; background: #ecfdf5; box-shadow: 0 4px 10px rgba(5, 150, 105, 0.12); }
                .rail-stage-detail-modal.risk .rail-detail-icon { color: #dc2626; background: #fef2f2; box-shadow: 0 4px 10px rgba(220, 38, 38, 0.12); }
                
                .dark .rail-detail-modal-heading .rail-detail-icon { background: rgba(37, 99, 235, 0.2); color: #60a5fa; }
                .dark .rail-stage-detail-modal.supply .rail-detail-icon { background: rgba(217, 119, 6, 0.2); color: #fbbf24; }
                .dark .rail-stage-detail-modal.local .rail-detail-icon { background: rgba(5, 150, 105, 0.2); color: #34d399; }
                .dark .rail-stage-detail-modal.risk .rail-detail-icon { background: rgba(220, 38, 38, 0.2); color: #f87171; }

                .rail-detail-modal-heading h3 {
                    margin: 0 0 2px;
                    font-size: 19px;
                    font-weight: 900;
                    letter-spacing: -.02em;
                    color: #0f172a;
                }
                .dark .rail-detail-modal-heading h3 {
                    color: #ffffff;
                }
                .rail-detail-modal-heading p {
                    margin: 0;
                    color: #64748b;
                    line-height: 1.5;
                    font-size: 12px;
                }
                .dark .rail-detail-modal-heading p {
                    color: #94a3b8;
                }

                /* 2-Item Statistics Cards Grid */
                .rail-stat-grid-2 {
                    display: grid;
                    grid-template-columns: repeat(2, 1fr);
                    gap: 12px;
                    margin: 14px 0;
                }
                .rail-stat-card {
                    padding: 13px 15px;
                    border-radius: 14px;
                    border: 1px solid rgba(226, 232, 240, 0.9);
                    background: #f8fafc;
                    display: flex;
                    flex-direction: column;
                    justify-content: space-between;
                    gap: 6px;
                    transition: all .2s ease;
                }
                .dark .rail-stat-card {
                    background: #1e293b;
                    border-color: #334155;
                }
                .rail-stat-card:hover {
                    transform: translateY(-2px);
                    box-shadow: 0 6px 16px -4px rgba(0, 0, 0, 0.06);
                }
                .rail-stat-header {
                    display: flex;
                    align-items: center;
                    gap: 8px;
                }
                .rail-stat-icon-wrapper {
                    width: 30px;
                    height: 30px;
                    border-radius: 8px;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    flex-shrink: 0;
                }
                .rail-stat-card.orders .rail-stat-icon-wrapper { background: #eff6ff; color: #2563eb; }
                .rail-stat-card.value .rail-stat-icon-wrapper { background: #f0fdf4; color: #16a34a; }

                .dark .rail-stat-card.orders .rail-stat-icon-wrapper { background: rgba(37, 99, 235, 0.2); color: #60a5fa; }
                .dark .rail-stat-card.value .rail-stat-icon-wrapper { background: rgba(22, 163, 74, 0.2); color: #4ade80; }

                .rail-stat-label {
                    font-size: 11px;
                    font-weight: 700;
                    color: #64748b;
                }
                .dark .rail-stat-label {
                    color: #94a3b8;
                }
                .rail-stat-main {
                    display: flex;
                    align-items: baseline;
                    gap: 5px;
                }
                .rail-stat-main strong {
                    font-size: 20px;
                    font-weight: 900;
                    font-family: ui-monospace, monospace, sans-serif;
                    color: #0f172a;
                }
                .dark .rail-stat-main strong {
                    color: #f8fafc;
                }
                .rail-stat-unit {
                    font-size: 11.5px;
                    font-weight: 700;
                    color: #64748b;
                }
                .rail-stat-footer {
                    display: flex;
                    align-items: center;
                }
                .rail-stat-badge {
                    font-size: 10px;
                    font-weight: 700;
                    padding: 2.5px 7px;
                    border-radius: 5px;
                    background: rgba(100, 116, 139, 0.1);
                    color: #475569;
                }
                .dark .rail-stat-badge {
                    background: rgba(255, 255, 255, 0.08);
                    color: #cbd5e1;
                }

                .rail-risk-callout {
                    margin-top: 8px;
                    display: flex;
                    gap: 8px;
                    align-items: center;
                    background: #fef2f2;
                    color: #b91c1c;
                    border: 1px solid #fecaca;
                    border-radius: 10px;
                    padding: 8px 12px;
                    font-size: 11px;
                    font-weight: 700;
                    line-height: 1.5;
                }
                .dark .rail-risk-callout {
                    background: rgba(220, 38, 38, 0.15);
                    border-color: rgba(220, 38, 38, 0.3);
                    color: #fca5a5;
                }
                .rail-detail-modal-footer {
                    display: flex;
                    gap: 10px;
                    align-items: center;
                    justify-content: space-between;
                    margin-top: 14px;
                    padding-top: 12px;
                    border-top: 1px solid #f1f5f9;
                }
                .dark .rail-detail-modal-footer {
                    border-color: #1e293b;
                }
                .rail-action-button {
                    padding: 9px 18px;
                    display: flex;
                    justify-content: center;
                    align-items: center;
                    gap: 6px;
                    color: #fff;
                    background: #1e3a8a;
                    border: 0;
                    border-radius: 10px;
                    font-size: 11.5px;
                    font-weight: 800;
                    cursor: pointer;
                    transition: background .15s ease, transform .1s ease;
                    box-shadow: 0 4px 10px rgba(30, 58, 138, 0.22);
                }
                .rail-action-button:hover {
                    background: #1e40af;
                }
                .rail-action-button:active {
                    transform: scale(.97);
                }

                /* Orders List Modal */
                .rail-orders-overlay {
                    position: fixed;
                    z-index: 10000;
                    inset: 0;
                    background: rgba(15, 23, 42, 0.65);
                    backdrop-filter: blur(8px);
                    display: grid;
                    place-items: center;
                    padding: 16px;
                    animation: railOverlayIn .18s ease-out;
                }
                .rail-orders-modal {
                    width: min(460px, 100%);
                    background: #fff;
                    border-radius: 20px;
                    padding: 22px;
                    position: relative;
                    box-shadow: 0 25px 50px rgba(0, 0, 0, 0.28);
                    animation: railModalIn .2s cubic-bezier(0.16, 1, 0.3, 1);
                }
                .dark .rail-orders-modal {
                    background: #0f172a;
                    color: #f8fafc;
                    border: 1px solid #334155;
                }
                .rail-orders-modal h3 {
                    margin: 4px 0 2px;
                    color: #0f172a;
                    font-size: 17px;
                    font-weight: 800;
                }
                .dark .rail-orders-modal h3 {
                    color: #ffffff;
                }
                .rail-orders-modal p {
                    color: #64748b;
                    font-size: 11.5px;
                    line-height: 1.55;
                    margin: 0 0 14px;
                }
                .dark .rail-orders-modal p {
                    color: #94a3b8;
                }
                .rail-order-list {
                    display: grid;
                    gap: 8px;
                    max-height: 280px;
                    overflow-y: auto;
                }
                .rail-order-item {
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                    width: 100%;
                    border: 1px solid #e2e8f0;
                    border-radius: 10px;
                    padding: 9px 12px;
                    background: #f8fafc;
                    color: #0f172a;
                    font-size: 11.5px;
                    font-weight: 700;
                    text-decoration: none;
                    transition: background .15s ease, border-color .15s ease;
                }
                .dark .rail-order-item {
                    background: #1e293b;
                    border-color: #334155;
                    color: #f1f5f9;
                }
                .rail-order-item:hover {
                    background: #f1f5f9;
                    border-color: #cbd5e1;
                }
                .dark .rail-order-item:hover {
                    background: #334155;
                }
                .rail-order-item span.mono {
                    font-family: ui-monospace, monospace;
                    font-weight: 800;
                    color: #2563eb;
                }
                .dark .rail-order-item span.mono {
                    color: #60a5fa;
                }
                .rail-order-item span.tag {
                    color: #d97706;
                    font-size: 10.5px;
                }

                @keyframes railOverlayIn { from { opacity: 0; } to { opacity: 1; } }
                @keyframes railModalIn { from { opacity: 0; transform: translateY(10px) scale(.97); } to { opacity: 1; transform: translateY(0) scale(1); } }

                @media (max-width: 768px) {
                    .executive-rail-wrapper { padding: 14px 12px 18px; }
                    .rail-pipeline-area { padding: 14px; }
                    .rail-pipeline-scroll { overflow-x: auto; padding-bottom: 14px; }
                    .rail-pipeline-track { min-width: 920px; }
                    .rail-stat-grid-2 { grid-template-columns: 1fr; }
                    .rail-detail-modal-footer { flex-direction: column; align-items: stretch; }
                    .rail-action-button { width: 100%; }
                }
            </style>
        @endpushOnce

        <div id="order-lifecycle-rail-container" v-pre class="executive-rail-wrapper font-sans text-right" dir="rtl">
            <div class="rail-bg-grid"></div>

            <!-- Pipeline Connected Track Area (Full Width) -->
            <div class="rail-pipeline-area">
                <div class="rail-pipeline-heading">
                    <div>
                        <span class="rail-section-kicker">ORDER LIFECYCLE PIPELINE</span>
                        <h3>المسار التشغيلي الموحد لدورة حياة الطلب</h3>
                    </div>
                    <div class="rail-stage-indicator">
                        <span id="railActiveBadge">{{ sprintf('%02d', $firstStage['rank'] ?? 5) }}</span>
                        / 11 محطة
                    </div>
                </div>



                <!-- Connected Track Scroll Container -->
                <div class="rail-pipeline-scroll">
                    <div class="rail-pipeline-track" role="list" aria-label="مراحل دورة حياة الطلب">
                        <div class="rail-track-line"></div>

                        @foreach ($stagesList as $idx => $stg)
                            @php
                                $isSelected = ($stg['rank'] ?? ($idx + 1)) === ($firstStage['rank'] ?? 5);
                                $tone = $stg['tone'] ?? 'customer';
                                $hasAlert = !empty($stg['alert']) || (($stg['exception_count'] ?? 0) > 0);
                            @endphp
                            <button
                                type="button"
                                id="rail-card-{{ $idx }}"
                                class="rail-stage-card {{ $tone }} {{ $isSelected ? 'selected' : '' }}"
                                onclick="window.openLifecycleStageModal({{ $idx }})"
                                role="listitem"
                                aria-pressed="{{ $isSelected ? 'true' : 'false' }}"
                                title="انقر لعرض بطاقة إحصائيات {{ $stg['label'] ?? '' }}"
                            >
                                <span class="rail-step-badge">{{ sprintf('%02d', $stg['rank'] ?? ($idx + 1)) }}</span>
                                
                                <span class="rail-stage-icon" aria-label="رمز {{ $stg['label'] ?? '' }}">
                                    @if(($stg['rank'] ?? 1) == 1)
                                        <!-- ShoppingBag -->
                                        <svg fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path></svg>
                                    @elseif(($stg['rank'] ?? 1) == 2)
                                        <!-- WalletCards -->
                                        <svg fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><rect x="2" y="5" width="20" height="14" rx="2"></rect><line x1="2" y1="10" x2="22" y2="10"></line></svg>
                                    @elseif(($stg['rank'] ?? 1) == 3)
                                        <!-- BadgeCheck -->
                                        <svg fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                    @elseif(($stg['rank'] ?? 1) == 4)
                                        <!-- ReceiptText -->
                                        <svg fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                                    @elseif(($stg['rank'] ?? 1) == 5)
                                        <!-- FileCheck2 -->
                                        <svg fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path></svg>
                                    @elseif(($stg['rank'] ?? 1) == 6)
                                        <!-- Plane -->
                                        <svg fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path></svg>
                                    @elseif(($stg['rank'] ?? 1) == 7)
                                        <!-- PackageCheck -->
                                        <svg fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4m5-6l2 2 4-4"></path></svg>
                                    @elseif(($stg['rank'] ?? 1) == 8)
                                        <!-- Truck -->
                                        <svg fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><rect x="1" y="3" width="15" height="13"></rect><polygon points="16 8 20 8 23 11 23 16 16 16 8"></polygon><circle cx="5.5" cy="18.5" r="2.5"></circle><circle cx="18.5" cy="18.5" r="2.5"></circle></svg>
                                    @elseif(($stg['rank'] ?? 1) == 9)
                                        <!-- Box -->
                                        <svg fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path></svg>
                                    @elseif(($stg['rank'] ?? 1) == 10)
                                        <!-- Handshake -->
                                        <svg fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M14 9V5a3 3 0 00-3-3l-4 9v11h11.28a2 2 0 002-1.7l1.38-9a2 2 0 00-2-2.3zM7 22H4a2 2 0 01-2-2v-7a2 2 0 012-2h3"></path></svg>
                                    @else
                                        <!-- ShieldCheck -->
                                        <svg fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path></svg>
                                    @endif
                                </span>

                                <span class="rail-stage-name">{{ $stg['short'] ?? $stg['label'] }}</span>
                                <span class="rail-stage-count">{{ number_format($stg['count'] ?? 0) }}</span>

                                @if($hasAlert)
                                    <span class="rail-mini-alert" title="تنبيه SLA أو استثناء">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10" stroke-width="2"></circle><line x1="12" y1="8" x2="12" y2="12" stroke-width="2"></line><line x1="12" y1="16" x2="12.01" y2="16" stroke-width="2"></line></svg>
                                    </span>
                                @endif
                            </button>
                        @endforeach
                    </div>
                </div>

                <!-- Journey Directional Indicator -->
                <div class="rail-journey-labels">
                    <span>طلب العميل</span>
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>
                    <span>توريد ومخازن</span>
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>
                    <span>تسليم محلي</span>
                </div>
            </div>

            <!-- Stage Statistics Popup Modal (نافذة الإحصائيات المنبثقة الأنيقة والمدمجة) -->
            <div
                id="lifecycleStageModal"
                style="display: none;"
                class="rail-stage-detail-overlay"
                role="presentation"
                onclick="if(event.target === this) window.closeLifecycleStageModal()"
            >
                <section
                    id="stageModalBox"
                    class="rail-stage-detail-modal {{ $firstStage['tone'] ?? 'risk' }}"
                    role="dialog"
                    aria-modal="true"
                    aria-labelledby="stage-detail-title"
                    onclick="event.stopPropagation()"
                >
                    <button class="rail-modal-close" onclick="window.closeLifecycleStageModal()" aria-label="إغلاق تفاصيل المرحلة">×</button>
                    
                    <div class="rail-detail-modal-route">
                        <span id="modalStageTone" class="rail-detail-tone"><i></i> {{ $firstStage['group_label'] ?? 'سلسلة التوريد' }}</span>
                        <span id="modalStageStep" class="rail-detail-step">المحطة {{ sprintf('%02d', $firstStage['rank'] ?? 5) }} من 11</span>
                    </div>

                    <div class="rail-detail-modal-heading">
                        <span id="modalStageIconBox" class="rail-detail-icon">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path></svg>
                        </span>
                        <div>
                            <span class="rail-section-kicker">إحصائيات المرحلة التشغيلية</span>
                            <h3 id="stage-detail-title">{{ $firstStage['label'] ?? '' }}</h3>
                            <p id="modalStageDesc">{{ $firstStage['description'] ?? 'أوامر شراء أنشئت وتحتاج تأكيد المورد أو تحديث حالة الشحن.' }}</p>
                        </div>
                    </div>

                    <!-- 2 Statistics Cards Grid (المعلومات الإحصائية الأهم) -->
                    <div class="rail-stat-grid-2">
                        <!-- Stat 1: حجم الطلبات النشطة -->
                        <div class="rail-stat-card orders">
                            <div class="rail-stat-header">
                                <span class="rail-stat-icon-wrapper">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path></svg>
                                </span>
                                <span class="rail-stat-label">الطلبات النشطة</span>
                            </div>
                            <div class="rail-stat-main">
                                <strong id="modalStageCount">{{ number_format($firstStage['count'] ?? 0) }}</strong>
                                <span class="rail-stat-unit">طلب</span>
                            </div>
                            <div class="rail-stat-footer">
                                <span class="rail-stat-badge">حالة المرحلة الحالية</span>
                            </div>
                        </div>

                        <!-- Stat 2: إجمالي القيمة المالية -->
                        <div class="rail-stat-card value">
                            <div class="rail-stat-header">
                                <span class="rail-stat-icon-wrapper">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                </span>
                                <span class="rail-stat-label">القيمة المالية الإجمالية</span>
                            </div>
                            <div class="rail-stat-main">
                                <strong id="modalStageValue">{{ $currencySymbol . number_format($firstStage['value'] ?? 0) }}</strong>
                            </div>
                            <div class="rail-stat-footer">
                                <span class="rail-stat-badge">إجمالي المبالغ المعلقة</span>
                            </div>
                        </div>
                    </div>

                    <!-- Risk / SLA Callout -->
                    <div id="modalStageAlertRow" class="rail-risk-callout" style="{{ empty($firstStage['alert']) ? 'display:none;' : '' }}">
                        <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10" stroke-width="2"></circle><line x1="12" y1="8" x2="12" y2="12" stroke-width="2"></line><line x1="12" y1="16" x2="12.01" y2="16" stroke-width="2"></line></svg>
                        <span id="modalStageAlertText">{{ $firstStage['alert'] ?? '' }}</span>
                    </div>

                    <div class="rail-detail-modal-footer">
                        <span class="text-xs text-slate-500 dark:text-slate-400 font-medium">استعراض قائمة الطلبات المرتبطة بهذه المرحلة.</span>
                        <button class="rail-action-button" onclick="window.openLifecycleOrdersModal()">
                            <span>استعراض الطلبات</span>
                            <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>
                        </button>
                    </div>
                </section>
            </div>

            <!-- Orders Drilldown Modal -->
            <div
                id="lifecycleOrdersModal"
                style="display: none;"
                class="rail-orders-overlay"
                role="dialog"
                aria-modal="true"
                aria-label="طلبات المرحلة المختارة"
                onclick="if(event.target === this) window.closeLifecycleOrdersModal()"
            >
                <div class="rail-orders-modal" onclick="event.stopPropagation()">
                    <button class="rail-modal-close" onclick="window.closeLifecycleOrdersModal()">×</button>
                    <span class="rail-section-kicker">طلبات مرتبطة بالمرحلة</span>
                    <h3 id="ordersModalTitle">{{ $firstStage['label'] ?? '' }}</h3>
                    <p>قائمة الطلبات وأوامر الشراء الحية المطابقة لمحددات هذه المرحلة في النظام.</p>
                    
                    <div id="ordersModalList" class="rail-order-list">
                        <!-- Populated dynamically via JS -->
                    </div>

                    <div class="mt-4 pt-3 border-t border-slate-100 dark:border-slate-800 flex justify-between items-center">
                        <a
                            id="ordersModalFullFilterLink"
                            href="{{ route('admin.sales.orders.index') }}"
                            class="text-xs font-bold text-blue-600 dark:text-blue-400 hover:underline"
                        >
                            فتح قائمة المبيعات الكاملة &rarr;
                        </a>
                        <button
                            type="button"
                            onclick="window.closeLifecycleOrdersModal()"
                            class="px-3 py-1.5 rounded-lg bg-slate-200 text-slate-700 dark:bg-slate-800 dark:text-slate-300 font-bold text-xs cursor-pointer"
                        >
                            إغلاق
                        </button>
                    </div>
                </div>
            </div>

            <!-- Data Quality Audit Modal -->
            <div
                id="lifecycleQualityModal"
                style="display: none;"
                class="rail-stage-detail-overlay"
                onclick="if(event.target === this) window.closeLifecycleQualityModal()"
            >
                <div class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200 dark:border-slate-800 shadow-2xl w-full max-w-2xl overflow-hidden font-sans space-y-0 text-right" onclick="event.stopPropagation()">
                    <div class="p-5 bg-amber-50 dark:bg-amber-950/60 border-b border-amber-200 dark:border-amber-900/60 flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <span class="w-8 h-8 rounded-xl bg-amber-200 text-amber-900 flex items-center justify-center font-black">🛡️</span>
                            <div>
                                <h3 class="text-base font-black text-amber-950 dark:text-amber-200">تقرير استثناءات جودة البيانات (Data Quality)</h3>
                                <span class="text-xs text-amber-800 dark:text-amber-300 font-semibold">البنود المستبعدة لحماية نزاهة عدادات المحطات الـ11</span>
                            </div>
                        </div>
                        <button
                            type="button"
                            onclick="window.closeLifecycleQualityModal()"
                            class="rail-modal-close"
                        >
                            ✕
                        </button>
                    </div>

                    <div class="p-6 space-y-4 text-xs">
                        <div class="p-3.5 rounded-xl bg-amber-50/60 dark:bg-amber-950/40 border border-amber-200/80 dark:border-amber-900/40 text-amber-900 dark:text-amber-200 leading-relaxed font-medium">
                            تضمن هذه الآلية عزل أي بنود قديمة أو غير مكتملة التوصيف دون إسقاطها افتراضياً في المحطات الـ11. يبلغ إجمالي البنود غير المصنفة حالياً <strong>{{ $dataQuality['unclassified_count'] ?? 0 }}</strong> بنداً.
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
                            <div class="p-4 text-center text-slate-500 font-bold bg-slate-50 dark:bg-slate-800 rounded-xl">
                                لا توجد بنود غير مصنفة حالياً، جميع البنود مسقطة بنجاح في نموذج القراءة.
                            </div>
                        @endif
                    </div>

                    <div class="p-4 bg-slate-50 dark:bg-slate-800/80 border-t border-slate-200 dark:border-slate-700 flex items-center justify-end">
                        <button
                            type="button"
                            onclick="window.closeLifecycleQualityModal()"
                            class="px-4 py-2 text-xs font-bold rounded-xl bg-slate-200 text-slate-700 hover:bg-slate-300 dark:bg-slate-700 dark:text-slate-300 transition-colors cursor-pointer"
                        >
                            إغلاق
                        </button>
                    </div>
                </div>
            </div>
        </div>

        @pushOnce('scripts')
            <script>
                window.hayestStages = @json($stagesList);
                window.hayestActiveIndex = 4; // Default to PO stage (index 4) or 0
                window.hayestCurrencySymbol = "{{ $currencySymbol }}";
                window.hayestToneNames = {
                    customer: "رحلة العميل",
                    supply: "سلسلة التوريد والمخازن",
                    local: "التنفيذ والتسليم المحلي",
                    risk: "تحتاج تدخّلًا"
                };

                const stageIconsSvg = {
                    1: '<svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path></svg>',
                    2: '<svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><rect x="2" y="5" width="20" height="14" rx="2"></rect><line x1="2" y1="10" x2="22" y2="10"></line></svg>',
                    3: '<svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>',
                    4: '<svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>',
                    5: '<svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path></svg>',
                    6: '<svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path></svg>',
                    7: '<svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4m5-6l2 2 4-4"></path></svg>',
                    8: '<svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><rect x="1" y="3" width="15" height="13"></rect><polygon points="16 8 20 8 23 11 23 16 16 16 8"></polygon><circle cx="5.5" cy="18.5" r="2.5"></circle><circle cx="18.5" cy="18.5" r="2.5"></circle></svg>',
                    9: '<svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path></svg>',
                    10: '<svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M14 9V5a3 3 0 00-3-3l-4 9v11h11.28a2 2 0 002-1.7l1.38-9a2 2 0 00-2-2.3zM7 22H4a2 2 0 01-2-2v-7a2 2 0 012-2h3"></path></svg>',
                    11: '<svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path></svg>'
                };

                window.selectLifecycleStage = function(index) {
                    window.hayestActiveIndex = index;
                    const stage = window.hayestStages[index] || window.hayestStages[0];
                    if (!stage) return;

                    const rankStr = String(stage.rank || (index + 1)).padStart(2, '0');

                    // Update Top Indicator
                    const badgeEl = document.getElementById('railActiveBadge');
                    if (badgeEl) badgeEl.innerText = rankStr;



                    // Highlight Active Stage Card
                    document.querySelectorAll('.rail-stage-card').forEach((el, idx) => {
                        if (idx === index) {
                            el.classList.add('selected');
                            el.setAttribute('aria-pressed', 'true');
                        } else {
                            el.classList.remove('selected');
                            el.setAttribute('aria-pressed', 'false');
                        }
                    });
                };

                window.openLifecycleStageModal = function(index) {
                    if (index !== undefined) {
                        window.selectLifecycleStage(index);
                    }
                    const stage = window.hayestStages[window.hayestActiveIndex] || window.hayestStages[0];
                    if (!stage) return;

                    const rank = stage.rank || (window.hayestActiveIndex + 1);
                    const rankStr = String(rank).padStart(2, '0');
                    const toneKey = stage.tone || 'customer';
                    const toneName = window.hayestToneNames[toneKey] || stage.group_label || 'المسار التشغيلي';

                    const modalBox = document.getElementById('stageModalBox');
                    if (modalBox) {
                        modalBox.className = 'rail-stage-detail-modal ' + toneKey;
                    }

                    const toneEl = document.getElementById('modalStageTone');
                    if (toneEl) toneEl.innerHTML = '<i></i> ' + toneName;

                    const stepEl = document.getElementById('modalStageStep');
                    if (stepEl) stepEl.innerText = 'المحطة ' + rankStr + ' من 11';

                    const iconBoxEl = document.getElementById('modalStageIconBox');
                    if (iconBoxEl && stageIconsSvg[rank]) {
                        iconBoxEl.innerHTML = stageIconsSvg[rank];
                    }

                    const titleEl = document.getElementById('stage-detail-title');
                    if (titleEl) titleEl.innerText = stage.label || '';

                    const descEl = document.getElementById('modalStageDesc');
                    if (descEl) descEl.innerText = stage.description || '';

                    // Update 2 Statistics Values
                    const countEl = document.getElementById('modalStageCount');
                    if (countEl) countEl.innerText = Number(stage.count || 0).toLocaleString();

                    const valueEl = document.getElementById('modalStageValue');
                    if (valueEl) valueEl.innerText = window.hayestCurrencySymbol + Number(stage.value || 0).toLocaleString();

                    const alertRow = document.getElementById('modalStageAlertRow');
                    const alertText = document.getElementById('modalStageAlertText');
                    if (alertRow && alertText) {
                        if (stage.alert) {
                            alertText.innerText = stage.alert;
                            alertRow.style.display = 'flex';
                        } else if (stage.exception_count > 0) {
                            alertText.innerText = stage.exception_count + ' استثناءات تشغيلية مسجلة في هذه المرحلة';
                            alertRow.style.display = 'flex';
                        } else {
                            alertRow.style.display = 'none';
                        }
                    }

                    const modal = document.getElementById('lifecycleStageModal');
                    if (modal) modal.style.display = 'grid';
                    document.body.style.overflow = 'hidden';
                };

                window.closeLifecycleStageModal = function() {
                    const modal = document.getElementById('lifecycleStageModal');
                    if (modal) modal.style.display = 'none';
                    document.body.style.overflow = '';
                };

                window.openLifecycleOrdersModal = function() {
                    window.closeLifecycleStageModal();
                    const stage = window.hayestStages[window.hayestActiveIndex] || window.hayestStages[0];
                    if (!stage) return;

                    const titleEl = document.getElementById('ordersModalTitle');
                    if (titleEl) titleEl.innerText = stage.label || '';

                    const listEl = document.getElementById('ordersModalList');
                    if (listEl) {
                        listEl.innerHTML = '';
                        const orders = stage.orders || [];
                        if (orders.length === 0) {
                            listEl.innerHTML = '<div class="py-8 px-4 text-center text-slate-500 dark:text-slate-400 text-xs font-semibold flex flex-col items-center gap-2"><span class="w-10 h-10 rounded-full bg-slate-100 dark:bg-slate-800 flex items-center justify-center text-slate-400 text-lg">✓</span><span>لا توجد طلبات معلقة حالياً في هذه المرحلة.</span></div>';
                        } else {
                            orders.forEach((ord) => {
                                const btn = document.createElement('a');
                                btn.className = 'rail-order-item';
                                btn.href = ord.view_url || "{{ route('admin.sales.orders.index') }}";
                                btn.innerHTML = '<span class="mono">' + (ord.number || '#' + ord.id) + '</span>' +
                                    '<span class="tag ' + (ord.is_exception ? 'text-rose-600 font-bold' : 'text-emerald-600') + '">' + (ord.status_label || 'ضمن SLA') + '</span>' +
                                    '<svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>';
                                listEl.appendChild(btn);
                            });
                        }
                    }

                    const filterLink = document.getElementById('ordersModalFullFilterLink');
                    if (filterLink) {
                        filterLink.href = "{{ route('admin.sales.orders.index') }}";
                    }

                    const oModal = document.getElementById('lifecycleOrdersModal');
                    if (oModal) oModal.style.display = 'grid';
                    document.body.style.overflow = 'hidden';
                };

                window.closeLifecycleOrdersModal = function() {
                    const modal = document.getElementById('lifecycleOrdersModal');
                    if (modal) modal.style.display = 'none';
                    document.body.style.overflow = '';
                };

                window.openLifecycleQualityModal = function() {
                    const modal = document.getElementById('lifecycleQualityModal');
                    if (modal) modal.style.display = 'grid';
                    document.body.style.overflow = 'hidden';
                };

                window.closeLifecycleQualityModal = function() {
                    const modal = document.getElementById('lifecycleQualityModal');
                    if (modal) modal.style.display = 'none';
                    document.body.style.overflow = '';
                };

                document.addEventListener('keydown', function(e) {
                    if (e.key === 'Escape') {
                        window.closeLifecycleStageModal();
                        window.closeLifecycleOrdersModal();
                        window.closeLifecycleQualityModal();
                    }
                });

                // Initialize default stage on load
                if (window.hayestStages && window.hayestStages.length > 0) {
                    const poIndex = window.hayestStages.findIndex(s => (s.rank || 0) === 5);
                    window.selectLifecycleStage(poIndex >= 0 ? poIndex : 0);
                }
            </script>
        @endpushOnce
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
