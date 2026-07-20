<x-admin::layouts>
    <x-slot:title>
        الإدارة المالية والحسابات
    </x-slot>

    <div class="flex flex-col gap-6 pt-3 px-2 sm:px-4 lg:pt-3 lg:px-4">
        {{-- Header Section --}}
        <div class="flex items-center justify-between flex-wrap gap-4">
            <div class="flex flex-col">
                <h1 class="text-2xl font-bold text-gray-800 dark:text-white font-sans">
                    الإدارة المالية والحسابات (Finance Center)
                </h1>
                <p class="text-sm text-gray-550 dark:text-gray-400 mt-1 font-sans">
                    مراقبة وتدقيق قيود اليومية المزدوجة، هوامش الأرباح وتكاليف الشراء للموردين.
                </p>
            </div>
        </div>

        {{-- KPI Cards --}}
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
            {{-- Total Revenue Card --}}
            <div class="p-6 bg-white dark:bg-gray-900 rounded-lg border border-gray-200 dark:border-gray-850 shadow-sm hover:shadow-md hover:-translate-y-0.5 transition-all duration-300 flex items-center justify-between border-t-4 border-t-emerald-500">
                <div class="flex flex-col">
                    <span class="text-sm font-semibold text-gray-500 dark:text-gray-400 whitespace-nowrap font-sans">إجمالي المقبوضات (Revenue)</span>
                    <span class="text-2xl font-bold text-emerald-600 dark:text-emerald-400 mt-2 font-mono">{{ core()->formatPrice($totalRevenue, 'USD') }}</span>
                    <span class="text-[10px] text-gray-400 mt-1 font-sans">إجمالي المقبوضات النقدية (حساب 1010)</span>
                </div>
                <div class="w-12 h-12 bg-emerald-50 dark:bg-emerald-950/30 rounded-full flex items-center justify-center text-emerald-600 dark:text-emerald-400 font-bold text-lg font-sans">
                    $
                </div>
            </div>

            {{-- Total Supplier Cost Card --}}
            <div class="p-6 bg-white dark:bg-gray-900 rounded-lg border border-gray-200 dark:border-gray-850 shadow-sm hover:shadow-md hover:-translate-y-0.5 transition-all duration-300 flex items-center justify-between border-t-4 border-t-rose-500">
                <div class="flex flex-col">
                    <span class="text-sm font-semibold text-gray-500 dark:text-gray-400 whitespace-nowrap font-sans">نفقات الموردين (Expenses)</span>
                    <span class="text-2xl font-bold text-rose-600 dark:text-rose-400 mt-2 font-mono">{{ core()->formatPrice($totalSupplierCost, 'USD') }}</span>
                    <span class="text-[10px] text-gray-400 mt-1 font-sans">إجمالي تكاليف الشراء (حساب 2010)</span>
                </div>
                <div class="w-12 h-12 bg-rose-50 dark:bg-rose-950/20 rounded-full flex items-center justify-center text-rose-600 dark:text-rose-450 font-bold text-lg font-sans">
                    $
                </div>
            </div>

            {{-- Net Profit Card --}}
            <div class="p-6 bg-white dark:bg-gray-900 rounded-lg border border-gray-200 dark:border-gray-850 shadow-sm hover:shadow-md hover:-translate-y-0.5 transition-all duration-300 flex items-center justify-between border-t-4 border-t-blue-500">
                <div class="flex flex-col">
                    <span class="text-sm font-semibold text-gray-500 dark:text-gray-400 whitespace-nowrap font-sans">صافي الأرباح المحققة (Net Profit)</span>
                    <span class="text-2xl font-bold text-blue-600 dark:text-blue-400 mt-2 font-mono">{{ core()->formatPrice($totalProfit, 'USD') }}</span>
                    <span class="text-[10px] text-gray-400 mt-1 font-sans">هامش الربح التشغيلي المحسوب</span>
                </div>
                <div class="w-12 h-12 bg-blue-50 dark:bg-blue-950/30 rounded-full flex items-center justify-center text-blue-600 dark:text-blue-400 font-bold text-lg font-sans">
                    $
                </div>
            </div>

            {{-- Pending COGS Card --}}
            <div class="p-6 bg-white dark:bg-gray-900 rounded-lg border border-gray-200 dark:border-gray-850 shadow-sm hover:shadow-md hover:-translate-y-0.5 transition-all duration-300 flex items-center justify-between border-t-4 border-t-amber-500">
                <div class="flex flex-col">
                    <span class="text-sm font-semibold text-gray-500 dark:text-gray-400 whitespace-nowrap font-sans">تكاليف توريد معلقة (COGS Pending)</span>
                    <span class="text-2xl font-bold text-amber-600 dark:text-amber-500 mt-2 font-mono">{{ core()->formatPrice($cogsPending, 'USD') }}</span>
                    <span class="text-[10px] text-gray-400 mt-1 font-sans">نفقات توريد بانتظار الشحن (حساب 5010)</span>
                </div>
                <div class="w-12 h-12 bg-amber-50 dark:bg-amber-950/30 rounded-full flex items-center justify-center text-amber-600 dark:text-amber-500 font-bold text-lg font-sans">
                    $
                </div>
            </div>
        </div>

        {{-- Tabs Navigation --}}
        <div class="flex border-b border-gray-200 dark:border-gray-850 gap-6">
            <button
                type="button"
                class="fin-tab-btn py-3 text-sm font-bold border-b-2 focus:outline-none transition-all font-sans"
                data-tab="ledger-entries"
            >
                دفتر الأستاذ والقيود المزدوجة (Ledger Journal)
            </button>
            <button
                type="button"
                class="fin-tab-btn py-3 text-sm font-bold border-b-2 focus:outline-none transition-all font-sans"
                data-tab="financial-timeline"
            >
                شريط العمليات المالي للطلبات (Financial Timeline)
            </button>
        </div>

        {{-- Tab Content: Ledger Entries --}}
        <div id="tab-content-ledger-entries" class="fin-tab-content flex flex-col gap-4">
            <h2 class="text-lg font-bold text-gray-800 dark:text-white font-sans">قيود اليومية المزدوجة (Double-Entry Ledger)</h2>
            <div class="bg-white dark:bg-gray-900 rounded-lg border border-gray-200 dark:border-gray-850 shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full border-collapse text-right text-sm">
                        <thead>
                            <tr class="bg-gray-50 dark:bg-gray-800 border-b border-gray-200 dark:border-gray-800 text-gray-650 dark:text-gray-400 font-bold font-sans">
                                <th class="p-4 w-16">المعرف</th>
                                <th class="p-4 text-center">الطلب المحلي</th>
                                <th class="p-4 text-center">أمر الشراء</th>
                                <th class="p-4">رقم الحساب</th>
                                <th class="p-4">حساب تفصيلي</th>
                                <th class="p-4 text-center">مدين (Debit)</th>
                                <th class="p-4 text-center">دائن (Credit)</th>
                                <th class="p-4">المرجع</th>
                                <th class="p-4">التاريخ</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-850">
                            @forelse($ledgerEntries as $entry)
                                <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-850/50 transition-all duration-200 font-sans">
                                    <td class="p-4 font-mono text-xs text-gray-500">{{ $entry->id }}</td>
                                    <td class="p-4 text-center">
                                        @if($entry->order_id)
                                            <a href="{{ route('admin.sales.orders.view', $entry->order_id) }}" class="text-blue-600 dark:text-blue-400 font-semibold hover:underline">
                                                #{{ $entry->order_id }}
                                            </a>
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td class="p-4 text-center">
                                        @if($entry->purchase_order_id)
                                            <a href="{{ route('admin.dropshipping.fulfillment.view', $entry->purchase_order_id) }}" class="text-amber-700 dark:text-amber-500 font-semibold hover:underline">
                                                #{{ $entry->purchase_order_id }}
                                            </a>
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td class="p-4 font-mono text-xs">{{ $entry->account_code }}</td>
                                    <td class="p-4 text-xs font-semibold">
                                        @switch($entry->account_code)
                                            @case('1010') <span class="text-emerald-600 dark:text-emerald-450">النقدية والمقبوضات (Cash/Receivables)</span> @break
                                            @case('2010') <span class="text-rose-600 dark:text-rose-450">مستحقات الموردين (Payables)</span> @break
                                            @case('4010') <span class="text-blue-600 dark:text-blue-400">إيرادات المبيعات (Revenue)</span> @break
                                            @case('5010') <span class="text-amber-600 dark:text-amber-500">تكلفة البضاعة المعلقة (COGS Pending)</span> @break
                                            @default <span class="text-gray-500">حساب عام</span>
                                        @endswitch
                                    </td>
                                    <td class="p-4 text-center font-mono text-xs font-bold text-gray-700 dark:text-gray-300">
                                        {{ $entry->debit > 0 ? core()->formatPrice($entry->debit, 'USD') : '-' }}
                                    </td>
                                    <td class="p-4 text-center font-mono text-xs font-bold text-gray-700 dark:text-gray-300">
                                        {{ $entry->credit > 0 ? core()->formatPrice($entry->credit, 'USD') : '-' }}
                                    </td>
                                    <td class="p-4 text-xs text-gray-650 dark:text-gray-400 truncate max-w-xs" title="{{ $entry->reference }}">{{ $entry->reference }}</td>
                                    <td class="p-4 text-xs text-gray-500 font-mono">{{ $entry->created_at->diffForHumans() }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="p-12 text-center text-gray-500 dark:text-gray-400 font-sans">
                                        لا توجد قيود مالية مسجلة حالياً.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if($ledgerEntries->hasPages())
                    <div class="p-4 border-t border-gray-200 dark:border-gray-850">
                        {!! $ledgerEntries->appends(['active_tab' => 'ledger-entries'])->links() !!}
                    </div>
                @endif
            </div>
        </div>

        {{-- Tab Content: Financial Timeline --}}
        <div id="tab-content-financial-timeline" class="fin-tab-content hidden flex flex-col gap-4">
            <h2 class="text-lg font-bold text-gray-800 dark:text-white font-sans">شريط العمليات المالي للطلب (Financial Timeline Logs)</h2>
            <div class="bg-white dark:bg-gray-900 rounded-lg border border-gray-200 dark:border-gray-850 shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full border-collapse text-right text-sm">
                        <thead>
                            <tr class="bg-gray-50 dark:bg-gray-800 border-b border-gray-200 dark:border-gray-800 text-gray-650 dark:text-gray-400 font-bold font-sans">
                                <th class="p-4 w-16">المعرف</th>
                                <th class="p-4 text-center">الطلب المحلي</th>
                                <th class="p-4">نوع العملية</th>
                                <th class="p-4 text-center">المبلغ</th>
                                <th class="p-4 text-center">العملة</th>
                                <th class="p-4">الوصف</th>
                                <th class="p-4">التاريخ</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-850">
                            @forelse($financialTimeline as $event)
                                <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-850/50 transition-all duration-200 font-sans">
                                    <td class="p-4 font-mono text-xs text-gray-500">{{ $event->id }}</td>
                                    <td class="p-4 text-center">
                                        @if($event->order_id)
                                            <a href="{{ route('admin.sales.orders.view', $event->order_id) }}" class="text-blue-600 dark:text-blue-400 font-semibold hover:underline">
                                                #{{ $event->order_id }}
                                            </a>
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td class="p-4 font-semibold text-xs">
                                        @switch($event->event_type)
                                            @case('customer_paid') <span class="inline-flex items-center rounded bg-emerald-50 px-2.5 py-0.5 text-emerald-800 border border-emerald-250">مدفوعات عميل</span> @break
                                            @case('supplier_charged') <span class="inline-flex items-center rounded bg-rose-50 px-2.5 py-0.5 text-rose-800 border border-rose-250">مشتريات مورد</span> @break
                                            @case('currency_conversion') <span class="inline-flex items-center rounded bg-blue-50 px-2.5 py-0.5 text-blue-800 border border-blue-250">تحويل عملة</span> @break
                                            @case('profit_calculated') <span class="inline-flex items-center rounded bg-violet-50 px-2.5 py-0.5 text-violet-800 border border-violet-250">احتساب أرباح</span> @break
                                            @default <span class="inline-flex items-center rounded bg-gray-50 px-2.5 py-0.5 text-gray-800 border border-gray-250">{{ $event->event_type }}</span>
                                        @endswitch
                                    </td>
                                    <td class="p-4 text-center font-mono text-xs font-bold text-gray-800 dark:text-white">
                                        {{ core()->formatPrice($event->amount, $event->currency) }}
                                    </td>
                                    <td class="p-4 text-center font-mono text-xs font-semibold">{{ $event->currency }}</td>
                                    <td class="p-4 text-xs text-gray-650 dark:text-gray-400">
                                        @if($event->metadata)
                                            <div class="flex flex-col gap-0.5">
                                                <span>{{ $event->metadata['message'] ?? '-' }}</span>
                                            </div>
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td class="p-4 text-xs text-gray-500 font-mono">{{ $event->recorded_at }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="p-12 text-center text-gray-500 dark:text-gray-400 font-sans">
                                        لا توجد سجلات مالية للخط الزمني حالياً.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if($financialTimeline->hasPages())
                    <div class="p-4 border-t border-gray-200 dark:border-gray-850">
                        {!! $financialTimeline->appends(['active_tab' => 'financial-timeline'])->links() !!}
                    </div>
                @endif
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            (function() {
                // --- Tab State Management (Sprint 4) ---
                const tabs = document.querySelectorAll('.fin-tab-btn');
                const contents = document.querySelectorAll('.fin-tab-content');

                document.addEventListener('click', function(e) {
                    const tabBtn = e.target.closest('.fin-tab-btn');
                    if (tabBtn) {
                        e.preventDefault();
                        const target = tabBtn.getAttribute('data-tab');
                        setActiveTab(target);
                    }
                });

                function setActiveTab(targetTab) {
                    const liveTabs = document.querySelectorAll('.fin-tab-btn');
                    const liveContents = document.querySelectorAll('.fin-tab-content');

                    liveTabs.forEach(btn => {
                        if (btn.getAttribute('data-tab') === targetTab) {
                            btn.className = "fin-tab-btn py-3 text-sm font-bold text-amber-600 dark:text-amber-500 border-b-2 border-amber-600 dark:border-amber-500 focus:outline-none transition-all font-sans";
                        } else {
                            btn.className = "fin-tab-btn py-3 text-sm font-bold text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 border-b-2 border-transparent focus:outline-none transition-all font-sans";
                        }
                    });

                    liveContents.forEach(content => {
                        if (content.id === `tab-content-${targetTab}`) {
                            content.classList.remove('hidden');
                            content.classList.add('flex');
                        } else {
                            content.classList.remove('flex');
                            content.classList.add('hidden');
                        }
                    });

                    const url = new URL(window.location);
                    url.searchParams.set('active_tab', targetTab);
                    window.history.pushState({}, '', url);
                }

                // Restore active tab state from URL or defaults
                const urlParams = new URLSearchParams(window.location.search);
                let initialTab = urlParams.get('active_tab') || 'ledger-entries';
                if (!urlParams.get('active_tab')) {
                    if (urlParams.has('timeline_page')) {
                        initialTab = 'financial-timeline';
                    }
                }
                setActiveTab(initialTab);
            })();
        </script>
    @endpush
</x-admin::layouts>
