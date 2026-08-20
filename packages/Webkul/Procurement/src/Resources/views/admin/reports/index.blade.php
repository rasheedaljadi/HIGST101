<x-admin::layouts>
    <x-slot:title>
        {{ trans('procurement::app.reports.title') }}
    </x-slot>

    <div class="flex flex-col gap-6">
        <div class="flex items-center justify-between flex-wrap gap-4">
            <div class="flex flex-col">
                <div class="flex items-center gap-2 text-sm text-gray-500">
                    <span>{{ trans('procurement::app.admin.menu.procurement-v2') }}</span>
                    <span>/</span>
                    <span class="text-gray-800 dark:text-white font-medium">{{ trans('procurement::app.reports.title') }}</span>
                </div>
                <h1 class="text-2xl font-bold text-gray-800 dark:text-white mt-1">
                    {{ trans('procurement::app.reports.title') }}
                </h1>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">
                    {{ trans('procurement::app.reports.description') }}
                </p>
            </div>
        </div>

        {{-- Executive KPI Grid --}}
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl p-5 shadow-sm">
                <span class="text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ trans('procurement::app.reports.open-demands-qty') }}</span>
                <div class="text-3xl font-bold text-amber-600 dark:text-amber-400 mt-2">{{ $metrics['open_demands_qty'] }}</div>
                <span class="text-xs text-gray-400 mt-1 block">{{ $metrics['open_demands_count'] }} orders awaiting batching</span>
            </div>

            @if (bouncer()->hasPermission('dropshipping.procurement_v2.cost_view'))
                <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl p-5 shadow-sm">
                    <span class="text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ trans('procurement::app.reports.total-expected-cost') }}</span>
                    <div class="text-3xl font-bold text-gray-900 dark:text-white mt-2">${{ number_format($metrics['total_expected_cost'], 2) }}</div>
                    <span class="text-xs text-gray-400 mt-1 block">Expected USD Procurement Cost</span>
                </div>

                <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl p-5 shadow-sm">
                    <span class="text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ trans('procurement::app.reports.total-actual-cost') }}</span>
                    <div class="text-3xl font-bold text-emerald-600 dark:text-emerald-400 mt-2">${{ number_format($metrics['total_actual_cost'], 2) }}</div>
                    <span class="text-xs text-gray-400 mt-1 block">Actual USD Confirmed Paid</span>
                </div>

                <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl p-5 shadow-sm">
                    <span class="text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ trans('procurement::app.reports.net-cost-variance') }}</span>
                    <div class="text-3xl font-bold {{ $metrics['total_cost_variance'] > 0 ? 'text-rose-600' : 'text-emerald-600' }} mt-2">
                        {{ $metrics['total_cost_variance'] > 0 ? '+' : '' }}${{ number_format($metrics['total_cost_variance'], 2) }}
                    </div>
                    <span class="text-xs text-gray-400 mt-1 block">Variance Discrepancy Amount</span>
                </div>
            @endif
        </div>

        {{-- Financial & Operations Risk Cards --}}
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl p-5 shadow-sm">
                <h3 class="text-base font-bold text-gray-900 dark:text-white mb-3">{{ trans('procurement::app.reports.uncollected-cod-revenue') }}</h3>
                <div class="text-2xl font-bold text-amber-600">${{ number_format($metrics['uncollected_cod_total'], 2) }}</div>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">
                    {{ trans('procurement::app.reports.uncollected-cod-desc') }}
                </p>
            </div>

            <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl p-5 shadow-sm">
                <h3 class="text-base font-bold text-gray-900 dark:text-white mb-3">{{ trans('procurement::app.reports.delayed-platform-orders') }}</h3>
                <div class="text-2xl font-bold {{ $metrics['delayed_orders_count'] > 0 ? 'text-rose-600' : 'text-emerald-600' }}">
                    {{ $metrics['delayed_orders_count'] }}
                </div>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">
                    {{ trans('procurement::app.reports.delayed-platform-desc') }}
                </p>
            </div>
        </div>
    </div>
</x-admin::layouts>
