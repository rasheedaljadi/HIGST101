<x-admin::layouts>
    <x-slot:title>
        محفظة هايست - الرقابة المالية | نظرة عامة لحظية
    </x-slot:title>

    <div class="flex flex-col gap-6 p-6 bg-gray-50/50 dark:bg-gray-950 min-h-screen">
        @include('wallet::admin.layouts.tabs')

        {{-- 1. Header & Toolbar --}}
        <div class="flex flex-wrap items-center justify-between gap-4 rounded-2xl bg-white p-6 shadow-sm border border-gray-100 dark:border-gray-800 dark:bg-gray-900">
            <div>
                <div class="flex items-center gap-2">
                    <span class="inline-flex h-3 w-3 rounded-full bg-emerald-500 animate-pulse"></span>
                    <h1 class="text-2xl font-black text-gray-900 dark:text-white">
                        محفظة هايست - الرقابة المالية
                    </h1>
                </div>
                <p class="mt-1 text-sm font-medium text-gray-500 dark:text-gray-400">
                    نظرة عامة لحظية ومراقبة فورية للالتزامات والسيولة والتنبيهات التشغيلية
                </p>
            </div>

            {{-- Controls & Actions --}}
            <div class="flex flex-wrap items-center gap-3">
                {{-- Date Filter Dropdown --}}
                <div class="relative">
                    <select class="appearance-none rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 pl-8 text-sm font-semibold text-gray-700 transition hover:bg-gray-100 dark:border-gray-800 dark:bg-gray-800 dark:text-gray-200">
                        <option>هذا الأسبوع</option>
                        <option selected>هذا الشهر</option>
                        <option>الربع الحالي</option>
                        <option>هذا العام</option>
                    </select>
                </div>

                {{-- Refresh Button --}}
                <button 
                    onclick="window.location.reload()" 
                    class="inline-flex items-center gap-2 rounded-xl border border-gray-200 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 shadow-sm transition hover:bg-gray-50 hover:text-blue-600 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300 dark:hover:bg-gray-800"
                    title="تحديث البيانات"
                >
                    <span class="icon-repeat text-lg"></span>
                    <span>تحديث البيانات</span>
                </button>

                {{-- Download Report Button --}}
                <a 
                    href="{{ route('admin.wallet.accounts.index') }}" 
                    class="inline-flex items-center gap-2 rounded-xl border border-gray-200 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 shadow-sm transition hover:bg-gray-50 hover:text-blue-600 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300 dark:hover:bg-gray-800"
                >
                    <span class="icon-admin-export text-lg"></span>
                    <span>تنزيل التقرير</span>
                </a>

                {{-- Primary Action: Request Withdrawal --}}
                <a 
                    href="{{ route('admin.wallet.withdrawals.index') }}" 
                    class="inline-flex items-center gap-2 rounded-xl bg-blue-900 px-5 py-2.5 text-sm font-bold text-white shadow-md transition hover:bg-blue-950 focus:ring-4 focus:ring-blue-900/20"
                >
                    <span class="icon-arrow-left text-lg"></span>
                    <span>طلب السحب</span>
                </a>
            </div>
        </div>

        {{-- 2. KPI Cards Grid (4 Cards) --}}
        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
            {{-- KPI 1: Total System Liability --}}
            <div class="group relative overflow-hidden rounded-2xl border border-gray-100 bg-white p-5 shadow-sm transition duration-300 hover:shadow-md dark:border-gray-800 dark:bg-gray-900">
                <div class="flex items-center justify-between">
                    <p class="text-sm font-semibold text-gray-500 dark:text-gray-400">
                        إجمالي التزامات النظام
                    </p>
                    <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-blue-50 text-blue-600 dark:bg-blue-900/40 dark:text-blue-400">
                        <span class="icon-sales text-xl"></span>
                    </div>
                </div>
                <div class="mt-4 flex items-baseline justify-between">
                    <h3 class="text-2xl font-black tracking-tight text-gray-900 dark:text-white">
                        {{ core()->formatBasePrice($statistics['totalLiability']) }}
                    </h3>
                    <span class="inline-flex items-center text-xs font-bold text-emerald-600 dark:text-emerald-400">
                        ↑ 2.4%
                    </span>
                </div>
                {{-- Sparkline Visual --}}
                <div class="mt-4 h-1.5 w-full overflow-hidden rounded-full bg-blue-100 dark:bg-blue-950">
                    <div class="h-full rounded-full bg-blue-600 transition-all duration-500" style="width: 70%"></div>
                </div>
            </div>

            {{-- KPI 2: Available Liquid Balance --}}
            <div class="group relative overflow-hidden rounded-2xl border border-gray-100 bg-white p-5 shadow-sm transition duration-300 hover:shadow-md dark:border-gray-800 dark:bg-gray-900">
                <div class="flex items-center justify-between">
                    <p class="text-sm font-semibold text-gray-500 dark:text-gray-400">
                        السيولة المتاحة
                    </p>
                    <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600 dark:bg-emerald-900/40 dark:text-emerald-400">
                        <span class="icon-refund text-xl"></span>
                    </div>
                </div>
                <div class="mt-4 flex items-baseline justify-between">
                    <h3 class="text-2xl font-black tracking-tight text-emerald-600 dark:text-emerald-400">
                        {{ core()->formatBasePrice($statistics['availableBalance']) }}
                    </h3>
                    <span class="inline-flex items-center text-xs font-bold text-emerald-600 dark:text-emerald-400">
                        نشط 100%
                    </span>
                </div>
                {{-- Progress Bar Visual --}}
                <div class="mt-4 h-1.5 w-full overflow-hidden rounded-full bg-emerald-100 dark:bg-emerald-950">
                    <div class="h-full rounded-full bg-emerald-500 transition-all duration-500" style="width: 85%"></div>
                </div>
            </div>

            {{-- KPI 3: Held Pending Balance --}}
            <div class="group relative overflow-hidden rounded-2xl border border-gray-100 bg-white p-5 shadow-sm transition duration-300 hover:shadow-md dark:border-gray-800 dark:bg-gray-900">
                <div class="flex items-center justify-between">
                    <p class="text-sm font-semibold text-gray-500 dark:text-gray-400">
                        الرصيد المحجوز والمعلق
                    </p>
                    <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-amber-50 text-amber-600 dark:bg-amber-900/40 dark:text-amber-400">
                        <span class="icon-attribute text-xl"></span>
                    </div>
                </div>
                <div class="mt-4 flex items-baseline justify-between">
                    <h3 class="text-2xl font-black tracking-tight text-amber-600 dark:text-amber-400">
                        {{ core()->formatBasePrice($statistics['heldBalance']) }}
                    </h3>
                    <span class="inline-flex items-center text-xs font-bold text-amber-600 dark:text-amber-400">
                        قيد المعالجة
                    </span>
                </div>
                {{-- Progress Bar Visual --}}
                <div class="mt-4 h-1.5 w-full overflow-hidden rounded-full bg-amber-100 dark:bg-amber-950">
                    <div class="h-full rounded-full bg-amber-500 transition-all duration-500" style="width: 35%"></div>
                </div>
            </div>

            {{-- KPI 4: Pending Withdrawals --}}
            <div class="group relative overflow-hidden rounded-2xl border border-gray-100 bg-white p-5 shadow-sm transition duration-300 hover:shadow-md dark:border-gray-800 dark:bg-gray-900">
                <div class="flex items-center justify-between">
                    <p class="text-sm font-semibold text-gray-500 dark:text-gray-400">
                        طلبات السحب المعلقة
                    </p>
                    <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-purple-50 text-purple-600 dark:bg-purple-900/40 dark:text-purple-400">
                        <span class="icon-arrow-left text-xl"></span>
                    </div>
                </div>
                <div class="mt-4 flex items-baseline justify-between">
                    <div>
                        <h3 class="text-2xl font-black tracking-tight text-purple-600 dark:text-purple-400">
                            {{ $statistics['pendingWithdrawals'] }} <span class="text-sm font-semibold text-gray-400">طلب</span>
                        </h3>
                        <p class="text-xs font-semibold text-gray-400">
                            {{ core()->formatBasePrice($statistics['pendingWithdrawalsAmount'] ?? 0) }}
                        </p>
                    </div>
                </div>
                {{-- Mini Bar Visual --}}
                <div class="mt-4 flex items-end gap-1.5 h-3">
                    <div class="h-full w-full rounded-sm bg-purple-200 dark:bg-purple-950"></div>
                    <div class="h-3/4 w-full rounded-sm bg-purple-300 dark:bg-purple-900"></div>
                    <div class="h-1/2 w-full rounded-sm bg-purple-400 dark:bg-purple-800"></div>
                    <div class="h-5/6 w-full rounded-sm bg-purple-600"></div>
                </div>
            </div>
        </div>

        {{-- 3. Charts & Main Data Section --}}
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
            {{-- Right Column: Liquidity Trend Line Chart (2/3 width) --}}
            <div class="rounded-2xl border border-gray-100 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900 lg:col-span-2">
                <div class="flex items-center justify-between mb-6">
                    <div>
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white">
                            تدفق السيولة مقابل الالتزامات (7 أيام)
                        </h3>
                        <p class="text-xs text-gray-500 dark:text-gray-400">مقارنة حركة الأرصدة المتاحة والالتزامات الكلية على مدار الأسبوع</p>
                    </div>
                    <div class="flex items-center gap-4 text-xs font-bold">
                        <span class="flex items-center gap-1.5 text-blue-600">
                            <span class="h-3 w-3 rounded-full bg-blue-600"></span> التزامات النظام
                        </span>
                        <span class="flex items-center gap-1.5 text-emerald-600">
                            <span class="h-3 w-3 rounded-full bg-emerald-500"></span> السيولة المتاحة
                        </span>
                    </div>
                </div>

                {{-- Chart Canvas --}}
                <div class="relative h-72 w-full">
                    <canvas id="liquidityChart"></canvas>
                </div>
            </div>

            {{-- Left Column: Recent Pending Withdrawals Table (1/3 width) --}}
            <div class="flex flex-col justify-between rounded-2xl border border-gray-100 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                <div>
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white">
                            آخر طلبات السحب المعلقة
                        </h3>
                        <a href="{{ route('admin.wallet.withdrawals.index') }}" class="text-xs font-bold text-blue-600 hover:underline">
                            عرض الكل ←
                        </a>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-right text-xs">
                            <thead>
                                <tr class="border-b border-gray-100 text-gray-400 dark:border-gray-800">
                                    <th class="pb-3 font-semibold">المستخدم</th>
                                    <th class="pb-3 font-semibold">المبلغ</th>
                                    <th class="pb-3 font-semibold">التاريخ</th>
                                    <th class="pb-3 font-semibold">الحالة</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-50 dark:divide-gray-800">
                                @forelse ($recentPendingWithdrawals as $withdrawal)
                                    <tr>
                                        <td class="py-3 font-bold text-gray-800 dark:text-gray-200">
                                            {{ $withdrawal->wallet->customer->name ?? '—' }}
                                        </td>
                                        <td class="py-3 font-black text-purple-600 dark:text-purple-400">
                                            {{ core()->formatBasePrice($withdrawal->amount) }}
                                        </td>
                                        <td class="py-3 text-gray-500">
                                            {{ $withdrawal->created_at->format('d/m H:i') }}
                                        </td>
                                        <td class="py-3">
                                            <span class="inline-flex rounded-full bg-amber-50 px-2 py-0.5 text-[10px] font-bold text-amber-700 dark:bg-amber-900/40 dark:text-amber-300">
                                                قيد المراجعة
                                            </span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="py-8 text-center text-gray-400">
                                             لا توجد طلبات سحب معلقة حالياً
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="mt-4 pt-4 border-t border-gray-100 dark:border-gray-800 text-center">
                    <p class="text-xs text-gray-400">معالجة السحوبات تتم خلال 24 ساعة عمل رسمية</p>
                </div>
            </div>
        </div>

        {{-- 4. Critical Operational Alerts Panel (تنبيهات مالية هامة) --}}
        <div class="rounded-2xl border border-red-200/80 bg-red-50/60 p-6 shadow-sm backdrop-blur dark:border-red-900/40 dark:bg-red-950/20">
            <div class="flex items-center gap-3">
                <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-red-600 text-white shadow-sm">
                    <span class="text-lg">⚠️</span>
                </div>
                <div>
                    <h2 class="text-lg font-black text-red-900 dark:text-red-300">
                        تنبيهات تشغيلية حرجة - تتطلب الانتباه
                    </h2>
                    <p class="text-xs text-red-700/80 dark:text-red-400/80">مراقبة الثغرات وإخفاقات الاسترداد والربط البرمجي لضمان سلامة الأموال</p>
                </div>
            </div>

            <div class="mt-5 grid grid-cols-1 gap-4 md:grid-cols-3">
                {{-- Alert 1: Failed Refunds --}}
                <div class="rounded-xl border border-red-200 bg-white p-4 shadow-sm transition hover:border-red-300 dark:border-red-900/50 dark:bg-gray-900">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-extrabold uppercase tracking-wider text-red-600 dark:text-red-400">
                            عمليات الاسترداد الفاشلة
                        </span>
                        <span class="flex h-2 w-2 rounded-full bg-red-600"></span>
                    </div>
                    <p class="mt-2 text-3xl font-black text-red-600 dark:text-red-400">
                        {{ $failedOperations['refunds'] }}
                    </p>
                    <p class="mt-1 text-xs font-semibold text-gray-500 dark:text-gray-400">
                        عملية تحتاج لتدخل يدوي
                    </p>
                </div>

                {{-- Alert 2: Pending TopUps --}}
                <div class="rounded-xl border border-amber-200 bg-white p-4 shadow-sm transition hover:border-amber-300 dark:border-amber-900/50 dark:bg-gray-900">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-extrabold uppercase tracking-wider text-amber-600 dark:text-amber-400">
                            طلبات الشحن المعلقة
                        </span>
                        <span class="flex h-2 w-2 rounded-full bg-amber-500"></span>
                    </div>
                    <p class="mt-2 text-3xl font-black text-amber-600 dark:text-amber-400">
                        {{ $failedOperations['topups'] }}
                    </p>
                    <p class="mt-1 text-xs font-semibold text-gray-500 dark:text-gray-400">
                        إيداع بانتظار تحقق الإدارة
                    </p>
                </div>

                {{-- Alert 3: Failed Webhooks --}}
                <div class="rounded-xl border border-slate-300 bg-white p-4 shadow-sm transition hover:border-slate-400 dark:border-slate-700 dark:bg-gray-900">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-extrabold uppercase tracking-wider text-slate-700 dark:text-slate-300">
                            إخفاقات الإشعارات (WEBHOOKS)
                        </span>
                        <span class="flex h-2 w-2 rounded-full bg-slate-500"></span>
                    </div>
                    <p class="mt-2 text-3xl font-black text-slate-800 dark:text-slate-200">
                        {{ $failedOperations['webhooks'] }}
                    </p>
                    <p class="mt-1 text-xs font-semibold text-gray-500 dark:text-gray-400">
                        إشعار بوابة الدفع بانتظار إعادة المحاولة
                    </p>
                </div>
            </div>
        </div>
    </div>

    {{-- Chart.js Script --}}
    @pushOnce('scripts')
        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const ctx = document.getElementById('liquidityChart');
                if (!ctx) return;

                new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: @json($chartData['labels']),
                        datasets: [
                            {
                                label: 'تزامات النظام الكلية',
                                data: @json($chartData['liabilities']),
                                borderColor: '#2563eb',
                                backgroundColor: 'rgba(37, 99, 235, 0.05)',
                                fill: true,
                                tension: 0.4,
                                borderWidth: 3,
                                pointRadius: 4,
                                pointBackgroundColor: '#2563eb'
                            },
                            {
                                label: 'السيولة المتاحة',
                                data: @json($chartData['liquidity']),
                                borderColor: '#10b981',
                                backgroundColor: 'rgba(16, 185, 129, 0.05)',
                                fill: true,
                                tension: 0.4,
                                borderWidth: 3,
                                pointRadius: 4,
                                pointBackgroundColor: '#10b981'
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { display: false }
                        },
                        scales: {
                            x: {
                                grid: { display: false }
                            },
                            y: {
                                grid: { color: 'rgba(229, 231, 235, 0.5)' },
                                ticks: {
                                    callback: function(value) {
                                        return '$' + value;
                                    }
                                }
                            }
                        }
                    }
                });
            });
        </script>
    @endpushOnce
</x-admin::layouts>
