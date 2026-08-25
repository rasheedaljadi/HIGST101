<x-admin::layouts>
    <x-slot:title>
        {{ trans('procurement::app.platform_orders.title') }}
    </x-slot>

    <div class="flex flex-col gap-6">
        <div class="flex items-center justify-between flex-wrap gap-4">
            <div class="flex flex-col">
                <div class="flex items-center gap-2 text-sm text-gray-500">
                    <span>{{ trans('procurement::app.admin.menu.procurement-v2') }}</span>
                    <span>/</span>
                    <span class="text-gray-800 dark:text-white font-medium">{{ trans('procurement::app.platform_orders.title') }}</span>
                </div>
                <h1 class="text-2xl font-bold text-gray-800 dark:text-white mt-1">
                    {{ trans('procurement::app.platform_orders.title') }}
                </h1>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">
                    {{ trans('procurement::app.platform_orders.description') }}
                </p>
            </div>
            <div class="flex items-center gap-3">
                @if (bouncer()->hasPermission('dropshipping.procurement_v2.submit') || bouncer()->hasPermission('dropshipping.procurement_v2.view') || auth()->guard('admin')->check())
                    <form action="{{ route('admin.procurement.platform_orders.sync_all') }}" method="POST" class="inline-block">
                        @csrf
                        <button 
                            type="submit" 
                            class="primary-button flex items-center gap-2 !bg-[#0044f2] !text-white hover:!bg-[#0034b8] px-4 py-2 rounded-lg font-semibold shadow-md transition-all cursor-pointer"
                        >
                            <span class="icon-repeat text-xl text-white"></span>
                            <span class="text-white">{{ trans('procurement::app.platform_orders.sync-all') }}</span>
                        </button>
                    </form>
                @endif
            </div>
        </div>

        <!-- AliExpress Style Status Classification Tabs -->
        @php
            $currentStatus = request('status', 'all');
            $tabs = [
                'all' => [
                    'label' => trans('procurement::app.platform_orders.tab-all'),
                    'count' => $counts['all'] ?? 0,
                ],
                'wait_buyer_pay' => [
                    'label' => trans('procurement::app.platform_orders.tab-wait-buyer-pay'),
                    'count' => $counts['wait_buyer_pay'] ?? 0,
                ],
                'processing' => [
                    'label' => trans('procurement::app.platform_orders.tab-processing'),
                    'count' => $counts['processing'] ?? 0,
                ],
                'shipped' => [
                    'label' => trans('procurement::app.platform_orders.tab-shipped'),
                    'count' => $counts['shipped'] ?? 0,
                ],
                'completed' => [
                    'label' => trans('procurement::app.platform_orders.tab-completed'),
                    'count' => $counts['completed'] ?? 0,
                ],
                'cancelled' => [
                    'label' => trans('procurement::app.platform_orders.tab-cancelled'),
                    'count' => $counts['cancelled'] ?? 0,
                ],
            ];
        @endphp

        <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl p-4 shadow-sm flex flex-col gap-4">
            <!-- Tabs Bar (AliExpress Style) -->
            <div class="flex items-center gap-6 sm:gap-8 border-b border-gray-200 dark:border-gray-800 overflow-x-auto scrollbar-none pt-1">
                @foreach ($tabs as $key => $tab)
                    @php
                        $isActive = ($currentStatus === $key);
                    @endphp
                    <a 
                        href="{{ route('admin.procurement.platform_orders.index', ['status' => $key]) }}"
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

            <!-- DataGrid Component with Status Parameter -->
            <x-admin::datagrid :src="route('admin.procurement.platform_orders.index', ['status' => $currentStatus])" />
        </div>
    </div>

    @pushOnce('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                const updateCountdowns = () => {
                    const now = Math.floor(Date.now() / 1000);
                    const expiredLabel = "{{ trans('procurement::app.datagrid.countdown-expired') }}";
                    
                    document.querySelectorAll('.ali-countdown').forEach(el => {
                        const deadlineStr = el.getAttribute('data-deadline');
                        if (!deadlineStr) return;
                        
                        const deadline = Math.floor(new Date(deadlineStr).getTime() / 1000);
                        const remaining = deadline - now;
                        
                        const textSpan = el.querySelector('.countdown-timer-text');
                        if (!textSpan) return;
                        
                        if (remaining <= 0) {
                            el.outerHTML = `<span class="inline-flex items-center gap-1 px-2 py-0.5 rounded bg-rose-50 text-rose-700 border border-rose-200 dark:bg-rose-950/50 dark:text-rose-300 dark:border-rose-800 text-[10px] font-bold">⌛ ${expiredLabel}</span>`;
                        } else {
                            if (remaining >= 86400) {
                                const days = Math.floor(remaining / 86400);
                                const remSecs = remaining % 86400;
                                const hours = String(Math.floor(remSecs / 3600)).padStart(2, '0');
                                const mins = String(Math.floor((remSecs % 3600) / 60)).padStart(2, '0');
                                const secs = String(remSecs % 60).padStart(2, '0');
                                textSpan.textContent = `${days}d ${hours}:${mins}:${secs}`;
                            } else {
                                const hours = String(Math.floor(remaining / 3600)).padStart(2, '0');
                                const mins = String(Math.floor((remaining % 3600) / 60)).padStart(2, '0');
                                const secs = String(remaining % 60).padStart(2, '0');
                                textSpan.textContent = `${hours}:${mins}:${secs}`;
                            }
                        }
                    });
                };

                setInterval(updateCountdowns, 1000);
            });
        </script>
    @endPushOnce
</x-admin::layouts>
