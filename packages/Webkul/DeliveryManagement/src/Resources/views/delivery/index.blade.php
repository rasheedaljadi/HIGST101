<x-admin::layouts>
    <x-slot:title>
        {{ trans('delivery::app.admin.menu.courier-tasks') }}
    </x-slot>

    @php
        $currentStatus = request()->query('status', 'all');
    @endphp

    <div class="flex flex-col gap-6">
        {{-- Header Section --}}
        <div class="flex items-center justify-between flex-wrap gap-4">
            <div class="flex flex-col">
                <div class="flex items-center gap-2 text-sm text-gray-500">
                    <a href="{{ route('delivery.index') }}" class="hover:text-blue-600">
                        {{ trans('delivery::app.admin.menu.courier-tasks') }}
                    </a>
                    <span>/</span>
                    <span class="text-gray-800 dark:text-white font-medium">قائمة المهام اليومية</span>
                </div>
                <h1 class="text-2xl font-bold text-gray-800 dark:text-white mt-1 flex items-center gap-3">
                    <span class="icon-ship text-2xl text-blue-600"></span>
                    مهام وطلبات التوصيل الميدانية
                </h1>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">
                    متابعة واستعراض طلبات الشحن المسندة، بدء خط السير، وتأكيد التسليم الميداني للعملاء ونقاط التوزيع.
                </p>
            </div>

            <div class="flex items-center gap-3">
                <a href="{{ route('delivery.index') }}" class="secondary-button flex items-center gap-2 text-xs py-2 px-3">
                    <span class="icon-refresh text-base"></span>
                    تحديث القائمة
                </a>
            </div>
        </div>

        {{-- Status Filter Tabs Bar --}}
        <div class="flex items-center gap-2 border-b border-gray-200 dark:border-gray-800 pb-2 overflow-x-auto text-xs">
            <a href="{{ route('delivery.index') }}" class="px-3.5 py-2 rounded-lg font-semibold transition-all {{ $currentStatus === 'all' || !request('status') ? 'bg-blue-600 text-white shadow-sm' : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 border border-gray-200 dark:border-gray-700' }}">
                جميع المهام ({{ $assignments->total() }})
            </a>
            <a href="{{ route('delivery.index', ['status' => 'assigned']) }}" class="px-3.5 py-2 rounded-lg font-semibold transition-all {{ request('status') === 'assigned' ? 'bg-blue-600 text-white shadow-sm' : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 border border-gray-200 dark:border-gray-700' }}">
                مسندة للمندوب
            </a>
            <a href="{{ route('delivery.index', ['status' => 'picked_up']) }}" class="px-3.5 py-2 rounded-lg font-semibold transition-all {{ request('status') === 'picked_up' ? 'bg-purple-600 text-white shadow-sm' : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 border border-gray-200 dark:border-gray-700' }}">
                تم الاستلام من المستودع
            </a>
            <a href="{{ route('delivery.index', ['status' => 'out_for_delivery']) }}" class="px-3.5 py-2 rounded-lg font-semibold transition-all {{ request('status') === 'out_for_delivery' ? 'bg-indigo-600 text-white shadow-sm' : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 border border-gray-200 dark:border-gray-700' }}">
                في الطريق للعميل
            </a>
            <a href="{{ route('delivery.index', ['status' => 'arrived_at_point']) }}" class="px-3.5 py-2 rounded-lg font-semibold transition-all {{ request('status') === 'arrived_at_point' ? 'bg-cyan-600 text-white shadow-sm' : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 border border-gray-200 dark:border-gray-700' }}">
                وصلت لنقطة الاستلام
            </a>
            <a href="{{ route('delivery.index', ['status' => 'delivered']) }}" class="px-3.5 py-2 rounded-lg font-semibold transition-all {{ request('status') === 'delivered' ? 'bg-emerald-600 text-white shadow-sm' : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 border border-gray-200 dark:border-gray-700' }}">
                المكتملة والمسلمة
            </a>
            <a href="{{ route('delivery.index', ['status' => 'delivery_failed']) }}" class="px-3.5 py-2 rounded-lg font-semibold transition-all {{ request('status') === 'delivery_failed' ? 'bg-rose-600 text-white shadow-sm' : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 border border-gray-200 dark:border-gray-700' }}">
                تعذر التسليم
            </a>
        </div>

        {{-- Tasks Grid / Empty State --}}
        @if($assignments->isEmpty())
            <div class="p-12 text-center bg-white dark:bg-gray-900 rounded-lg border border-gray-200 dark:border-gray-800 shadow-sm flex flex-col items-center justify-center gap-3">
                <div class="w-16 h-16 rounded-full bg-blue-50 dark:bg-blue-950 flex items-center justify-center text-blue-600 text-3xl">
                    <span class="icon-ship"></span>
                </div>
                <h2 class="text-base font-bold text-gray-800 dark:text-white">لا توجد مهام تسليم مسندة حالياً</h2>
                <p class="text-xs text-gray-500 max-w-md">
                    لا توجد شحنات مطابقة لحالة التصفية المحددة. سيتم إشعارك فور إسناد طلبات شحن جديدة إلى حسابك.
                </p>
                <a href="{{ route('delivery.index') }}" class="primary-button text-xs mt-2">
                    عرض جميع المهام
                </a>
            </div>
        @else
            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-5">
                @foreach($assignments as $assignment)
                    @php
                        $isCod = strtolower((string) ($assignment->order->payment?->method ?? '')) === 'cashondelivery';
                        $statusBadge = match($assignment->status) {
                            'ready_for_assignment' => ['bg' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-950 dark:text-yellow-200 border-yellow-200', 'text' => 'جاهز للإسناد'],
                            'assigned' => ['bg' => 'bg-blue-100 text-blue-800 dark:bg-blue-950 dark:text-blue-200 border-blue-200', 'text' => 'مسند للمندوب'],
                            'picked_up' => ['bg' => 'bg-purple-100 text-purple-800 dark:bg-purple-950 dark:text-purple-200 border-purple-200', 'text' => 'مستلم من المستودع'],
                            'out_for_delivery' => ['bg' => 'bg-indigo-100 text-indigo-800 dark:bg-indigo-950 dark:text-indigo-200 border-indigo-200', 'text' => 'في الطريق للعميل'],
                            'arrived_at_point' => ['bg' => 'bg-cyan-100 text-cyan-800 dark:bg-cyan-950 dark:text-cyan-200 border-cyan-200', 'text' => 'وصل لنقطة التسليم'],
                            'delivered' => ['bg' => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-200 border-emerald-200', 'text' => 'تم التسليم بنجاح'],
                            'delivery_failed' => ['bg' => 'bg-rose-100 text-rose-800 dark:bg-rose-950 dark:text-rose-200 border-rose-200', 'text' => 'تعذر التسليم'],
                            'retry_scheduled' => ['bg' => 'bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-200 border-amber-200', 'text' => 'معاد جدولته'],
                            'returned_to_hayest' => ['bg' => 'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-200 border-gray-300', 'text' => 'مرتجع للمركزي'],
                            default => ['bg' => 'bg-gray-100 text-gray-800', 'text' => $assignment->status]
                        };
                    @endphp

                    <div class="bg-white dark:bg-gray-900 rounded-lg border border-gray-200 dark:border-gray-800 shadow-sm hover:shadow-md transition-shadow p-5 flex flex-col justify-between gap-4">
                        {{-- Top Header --}}
                        <div class="flex items-start justify-between gap-2 border-b border-gray-100 dark:border-gray-800 pb-3">
                            <div class="flex flex-col">
                                <div class="flex items-center gap-2">
                                    <span class="text-base font-bold text-gray-900 dark:text-white">
                                        طلب #{{ $assignment->order->increment_id ?? $assignment->order_id }}
                                    </span>
                                </div>
                                <span class="text-[11px] text-gray-500 dark:text-gray-400 mt-0.5">
                                    {{ $assignment->delivery_type === 'delivery_point' ? '📍 استلام من نقطة توزيع' : '🏠 توصيل منزلي مباشر' }}
                                </span>
                            </div>

                            <span class="px-2.5 py-1 rounded-md text-[11px] font-bold border {{ $statusBadge['bg'] }}">
                                {{ $statusBadge['text'] }}
                            </span>
                        </div>

                        {{-- Customer & Address Info --}}
                        <div class="p-3 bg-gray-50 dark:bg-gray-800/60 rounded-lg flex flex-col gap-2 text-xs">
                            <div class="flex items-center justify-between font-semibold text-gray-900 dark:text-white">
                                <span>👤 {{ $assignment->customer_address_snapshot['first_name'] ?? 'العميل' }} {{ $assignment->customer_address_snapshot['last_name'] ?? '' }}</span>
                                @if(!empty($assignment->customer_address_snapshot['phone']))
                                    <a href="tel:{{ $assignment->customer_address_snapshot['phone'] }}" class="text-blue-600 font-bold hover:underline">
                                        📞 {{ $assignment->customer_address_snapshot['phone'] }}
                                    </a>
                                @endif
                            </div>
                            <div class="text-gray-600 dark:text-gray-300 text-[11px]">
                                📍 {{ $assignment->customer_address_snapshot['address'] ?? '' }}، {{ $assignment->customer_address_snapshot['city'] ?? '' }}
                            </div>
                            @if($assignment->delivery_type === 'delivery_point' && $assignment->delivery_point_snapshot)
                                <div class="pt-1.5 border-t border-gray-200 dark:border-gray-700 text-blue-700 dark:text-blue-300 font-medium text-[11px]">
                                    🏢 نقطة الاستلام: {{ $assignment->delivery_point_snapshot['name_ar'] ?? $assignment->delivery_point_snapshot['name'] ?? '' }}
                                </div>
                            @endif
                        </div>

                        {{-- Financial Box --}}
                        <div class="flex items-center justify-between p-2.5 rounded-lg border {{ $isCod ? 'bg-amber-50 dark:bg-amber-950/30 border-amber-200 dark:border-amber-900/50' : 'bg-emerald-50 dark:bg-emerald-950/30 border-emerald-200 dark:border-emerald-900/50' }}">
                            <div class="flex items-center gap-1.5 text-xs font-bold {{ $isCod ? 'text-amber-800 dark:text-amber-200' : 'text-emerald-800 dark:text-emerald-200' }}">
                                <span>{{ $isCod ? '💵 تحصيل نقدي (COD):' : '✅ تم الدفع مسبقاً' }}</span>
                            </div>
                            @if($isCod)
                                <span class="text-sm font-extrabold text-amber-900 dark:text-amber-100">
                                    {{ core()->formatPrice((float)($assignment->order->grand_total ?? 0), $assignment->order->order_currency_code) }}
                                </span>
                            @endif
                        </div>

                        {{-- Footer Actions --}}
                        <div class="flex items-center justify-between pt-2 border-t border-gray-100 dark:border-gray-800 text-xs">
                            <span class="text-gray-400 text-[11px]">
                                المحاولات: {{ $assignment->attempt_count }} / {{ $assignment->max_attempts }}
                            </span>

                            <a href="{{ route('delivery.show', $assignment->id) }}" class="primary-button text-xs py-1.5 px-3 flex items-center gap-1">
                                <span>تفاصيل المهمة والإجراء</span>
                                <span>←</span>
                            </a>
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- Pagination --}}
            <div class="mt-4">
                {{ $assignments->links() }}
            </div>
        @endif
    </div>
</x-admin::layouts>
