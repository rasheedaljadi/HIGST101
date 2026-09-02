<x-admin::layouts>
    <x-slot:title>
        {{ trans('procurement::app.demands.title') }}
    </x-slot>

    <div class="flex flex-col gap-6">
        <div class="flex items-center justify-between flex-wrap gap-4">
            <div class="flex flex-col">
                <div class="flex items-center gap-2 text-sm text-gray-500">
                    <span>{{ trans('procurement::app.admin.menu.procurement-v2') }}</span>
                    <span>/</span>
                    <span class="text-gray-800 dark:text-white font-medium">{{ trans('procurement::app.demands.title') }}</span>
                </div>
                <h1 class="text-2xl font-bold text-gray-800 dark:text-white mt-1">
                    {{ trans('procurement::app.demands.title') }}
                </h1>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">
                    {{ trans('procurement::app.demands.description') }}
                </p>
            </div>

            <div class="flex items-center gap-2">
                <button 
                    type="button" 
                    id="sync-stock-btn"
                    onclick="triggerDemandStockSync(this)"
                    class="secondary-button flex items-center gap-2 border-gray-300 dark:border-gray-700 hover:bg-gray-100 dark:hover:bg-gray-800 text-gray-700 dark:text-gray-200 font-semibold transition-all shadow-sm cursor-pointer" 
                    title="مزامنة وتحديث المخزون والأسعار الفورية من علي إكسبرس"
                >
                    <span id="sync-icon" class="icon-refresh text-base"></span>
                    <span id="sync-text">{{ trans('procurement::app.datagrid.sync-stock') ?: 'مزامنة المخزون' }}</span>
                </button>

                @if (bouncer()->hasPermission('dropshipping.procurement_v2.batch_create'))
                    <a href="{{ route('admin.procurement.batches.create') }}" class="primary-button flex items-center gap-2">
                        <span class="icon-plus text-lg"></span>
                        {{ trans('procurement::app.batches.create-batch') }}
                    </a>
                @endif
            </div>
        </div>

        {{-- Metrics Summary --}}
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl p-4 flex flex-col justify-between shadow-sm">
                <span class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ trans('procurement::app.demands.open-for-batching') }}</span>
                <div class="text-2xl font-bold text-amber-600 dark:text-amber-400 mt-2">{{ $counts['open_for_batching'] ?? 0 }}</div>
            </div>
            <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl p-4 flex flex-col justify-between shadow-sm">
                <span class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ trans('procurement::app.demands.batched') }}</span>
                <div class="text-2xl font-bold text-blue-600 dark:text-blue-400 mt-2">{{ $counts['batched'] ?? 0 }}</div>
            </div>
            <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl p-4 flex flex-col justify-between shadow-sm">
                <span class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ trans('procurement::app.demands.locally-covered') }}</span>
                <div class="text-2xl font-bold text-teal-600 dark:text-teal-400 mt-2">{{ $counts['locally_covered'] ?? 0 }}</div>
            </div>
            <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl p-4 flex flex-col justify-between shadow-sm">
                <span class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ trans('procurement::app.demands.fulfilled') }}</span>
                <div class="text-2xl font-bold text-emerald-600 dark:text-emerald-400 mt-2">{{ $counts['fulfilled'] ?? 0 }}</div>
            </div>
        </div>

        <!-- AliExpress Style Status Classification Tabs -->
        @php
            $currentState = request('state', request('status', 'all'));
            $tabs = [
                'all' => [
                    'label' => trans('procurement::app.demands.tab-all') ?: 'الكل',
                    'count' => $counts['all'] ?? 0,
                ],
                'open_for_batching' => [
                    'label' => trans('procurement::app.demands.tab-open-for-batching') ?: 'متاح للتجميع',
                    'count' => $counts['open_for_batching'] ?? 0,
                ],
                'batched' => [
                    'label' => trans('procurement::app.demands.tab-batched') ?: 'تم التجميع',
                    'count' => $counts['batched'] ?? 0,
                ],
            ];
        @endphp

        <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl p-4 shadow-sm flex flex-col gap-4">
            <!-- Tabs Bar (AliExpress Style) -->
            <div class="flex items-center gap-6 sm:gap-8 border-b border-gray-200 dark:border-gray-800 overflow-x-auto scrollbar-none pt-1">
                @foreach ($tabs as $key => $tab)
                    @php
                        $isActive = ($currentState === $key);
                    @endphp
                    <a 
                        href="{{ route('admin.procurement.demands.index', ['state' => $key]) }}"
                        class="relative pb-3 px-1 transition-all duration-150 flex items-center gap-1.5 whitespace-nowrap {{ $isActive ? 'text-gray-900 dark:text-white font-bold text-[15px]' : 'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white text-sm font-medium' }}"
                    >
                        <span>{{ $tab['label'] }}</span>
                        @if ($tab['count'] > 0 || $key === 'all')
                            <span class="{{ $isActive ? 'text-[#ff4747] font-bold' : 'text-gray-500 dark:text-gray-400 font-normal' }}">
                                ({{ $tab['count'] }})
                            </span>
                        @endif

                        @if ($isActive)
                            <span class="absolute bottom-0 inset-x-0 h-0.5 bg-[#ff4747] rounded-full"></span>
                        @endif
                    </a>
                @endforeach
            </div>

            <!-- DataGrid Component with State Parameter -->
            <x-admin::datagrid :src="route('admin.procurement.demands.index', ['state' => $currentState])" />
        </div>
    </div>

    @pushOnce('scripts')
        <script>
            function triggerDemandStockSync(btn) {
                if (btn.disabled) return;
                btn.disabled = true;
                const icon = document.getElementById('sync-icon');
                const text = document.getElementById('sync-text');
                
                if (icon) icon.classList.add('animate-spin');
                if (text) text.innerText = 'جاري المزامنة مع علي إكسبرس...';
                
                fetch("{{ route('admin.procurement.demands.sync_stock') }}", {
                    method: "POST",
                    headers: {
                        "X-CSRF-TOKEN": "{{ csrf_token() }}",
                        "Accept": "application/json",
                        "Content-Type": "application/json"
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        if (window.emitter) {
                            window.emitter.emit('add-flash', { type: 'success', message: data.message });
                        }
                        if (text) text.innerText = 'تمت المزامنة بنجاح!';
                        setTimeout(() => {
                            window.location.reload();
                        }, 600);
                    } else {
                        alert(data.message || 'حدث تعثر أثناء المزامنة');
                        btn.disabled = false;
                        if (icon) icon.classList.remove('animate-spin');
                        if (text) text.innerText = '{{ trans('procurement::app.datagrid.sync-stock') ?: 'مزامنة المخزون' }}';
                    }
                })
                .catch(err => {
                    console.error(err);
                    alert('حدث خطأ أثناء الاتصال بعلي إكسبرس');
                    btn.disabled = false;
                    if (icon) icon.classList.remove('animate-spin');
                    if (text) text.innerText = '{{ trans('procurement::app.datagrid.sync-stock') ?: 'مزامنة المخزون' }}';
                });
            }
        </script>
    @endPushOnce
</x-admin::layouts>
