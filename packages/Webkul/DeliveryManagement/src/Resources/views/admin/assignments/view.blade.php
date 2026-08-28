<x-admin::layouts>
    <x-slot:title>
        {{ trans('delivery::app.admin.assignments.view-title', ['id' => $assignment->id]) }}
    </x-slot>

    @php
        $shippingAddress = $assignment->order?->addresses?->where('address_type', 'order_shipping')->first()
            ?: $assignment->order?->addresses?->where('address_type', 'order_billing')->first();

        $snapshot = is_array($assignment->customer_address_snapshot) 
            ? $assignment->customer_address_snapshot 
            : json_decode($assignment->customer_address_snapshot ?: '[]', true);

        $firstName = $shippingAddress?->first_name ?: ($snapshot['first_name'] ?? ($assignment->order?->customer_first_name ?? ''));
        $lastName = $shippingAddress?->last_name ?: ($snapshot['last_name'] ?? ($assignment->order?->customer_last_name ?? ''));
        $customerName = trim($firstName.' '.$lastName) ?: 'العميل';

        $customerEmail = $shippingAddress?->email ?: ($snapshot['email'] ?? ($assignment->order?->customer_email ?: '-'));
        $customerPhone = $shippingAddress?->phone ?: ($snapshot['phone'] ?? ($assignment->order?->customer?->phone ?: '-'));

        $stateCode = strtoupper(trim((string) ($shippingAddress?->state ?: ($snapshot['state'] ?? ''))));
        $governoratesMap = [
            'SAN' => 'صنعاء (الأمانة والمحافظة)',
            'ADE' => 'عدن',
            'TAI' => 'تعز',
            'HOD' => 'الحديدة',
            'IBB' => 'إب',
            'HAD' => 'حضرموت',
            'DHA' => 'ذمار',
            'HAJ' => 'حجة',
            'LAH' => 'لحج',
            'SAD' => 'صعدة',
            'BAW' => 'البيضاء',
            'ABY' => 'أبين',
            'SHB' => 'شبوة',
            'MAH' => 'المهرة',
            'MAR' => 'مأرب',
            'AMR' => 'عمران',
            'RAY' => 'ريمة',
            'JAW' => 'الجوف',
            'DHU' => 'الضالع',
            'MAH_ISL' => 'سقطرى',
        ];
        $governorateName = $governoratesMap[$stateCode] ?? ($shippingAddress?->state ?: ($snapshot['state'] ?? 'غير محددة'));

        $cityDistrict = $shippingAddress?->city ?: ($snapshot['city'] ?? '');
        $address1 = $shippingAddress?->address1 ?: ($snapshot['address1'] ?? ($snapshot['address'] ?? ''));
        $address2 = $shippingAddress?->address2 ?: ($snapshot['address2'] ?? '');

        $cleanPhone = preg_replace('/[^0-9]/', '', (string) $customerPhone);
    @endphp

    <div class="flex flex-col gap-6">
        {{-- Header Section --}}
        <div class="flex items-center justify-between flex-wrap gap-4">
            <div class="flex flex-col">
                <div class="flex items-center gap-2 text-sm text-gray-500">
                    <a href="{{ route('admin.delivery.assignments.index') }}" class="hover:text-blue-600">
                        {{ trans('delivery::app.admin.assignments.title') }}
                    </a>
                    <span>/</span>
                    <span class="text-gray-800 dark:text-white font-medium">#{{ $assignment->id }}</span>
                </div>
                <h1 class="text-2xl font-bold text-gray-800 dark:text-white mt-1 flex items-center gap-3">
                    مهمة تسليم #{{ $assignment->id }}
                    <span class="text-base font-normal text-gray-500">(طلب #{{ $assignment->order?->increment_id }})</span>
                    <span class="px-3 py-1 rounded-full text-xs font-semibold bg-blue-100 text-blue-800">
                        {{ trans("delivery::app.admin.states.{$assignment->status}") }}
                    </span>
                </h1>
            </div>

            {{-- Action Buttons --}}
            <div class="flex items-center gap-2">
                @if(in_array($assignment->status, ['ready_for_assignment', 'assigned', 'retry_scheduled']))
                    <button onclick="document.getElementById('assign-modal').classList.remove('hidden')" class="secondary-button">
                        {{ trans('delivery::app.admin.assignments.assign-courier') }} / النقطة
                    </button>
                @endif

                @if($assignment->status === 'assigned')
                    <button onclick="executeHandoff({{ $assignment->id }})" class="primary-button bg-purple-600 hover:bg-purple-700">
                        {{ trans('delivery::app.admin.assignments.handoff-btn') }}
                    </button>
                @endif

                @if(in_array($assignment->status, ['delivery_failed', 'retry_scheduled']))
                    <button onclick="document.getElementById('return-modal').classList.remove('hidden')" class="primary-button bg-rose-600 hover:bg-rose-700">
                        {{ trans('delivery::app.admin.assignments.return-btn') }}
                    </button>
                @endif
            </div>
        </div>

        {{-- Sequential Single-Column Layout --}}
        <div class="flex flex-col gap-6 w-full">
            {{-- Section 1: Customer Details & Full Delivery Address --}}
            <div class="p-6 bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-800 shadow-xs">
                <div class="flex items-center justify-between border-b border-gray-100 dark:border-gray-800 pb-3 mb-4">
                    <h2 class="text-base font-bold text-gray-800 dark:text-white flex items-center gap-2">
                        <span class="text-xl">👤</span>
                        <span>القسم الأول: {{ trans('delivery::app.admin.assignments.customer-info') }} وعنوان الشحن</span>
                    </h2>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 text-xs">
                    {{-- Customer Name & Email --}}
                    <div class="flex flex-col gap-3 p-4 bg-gray-50 dark:bg-gray-800/60 rounded-xl border border-gray-100 dark:border-gray-700/60">
                        <div>
                            <span class="text-gray-400 font-medium">اسم العميل:</span>
                            <p class="font-bold text-gray-900 dark:text-white text-sm mt-1">{{ $customerName }}</p>
                        </div>

                        <div>
                            <span class="text-gray-400 font-medium">البريد الإلكتروني:</span>
                            <p class="text-gray-700 dark:text-gray-300 font-mono mt-1 text-xs">{{ $customerEmail }}</p>
                        </div>
                    </div>

                    {{-- Customer Phone & Direct Contact --}}
                    <div class="flex flex-col gap-3 p-4 bg-gray-50 dark:bg-gray-800/60 rounded-xl border border-gray-100 dark:border-gray-700/60">
                        <span class="text-gray-400 font-medium">رقم الهاتف والتواصل:</span>
                        @if($customerPhone && $customerPhone !== '-')
                            <div class="flex flex-wrap items-center gap-2 mt-1">
                                <a href="tel:{{ $customerPhone }}" class="inline-flex items-center gap-1.5 font-bold text-blue-600 dark:text-blue-400 hover:underline text-sm font-mono bg-blue-50 dark:bg-blue-950/40 px-3 py-1.5 rounded-lg border border-blue-200 dark:border-blue-800">
                                    <span>📞</span>
                                    <span dir="ltr">{{ $customerPhone }}</span>
                                </a>
                                @if($cleanPhone)
                                    <a href="https://wa.me/{{ $cleanPhone }}" target="_blank" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-emerald-100 dark:bg-emerald-950 text-emerald-800 dark:text-emerald-300 text-xs font-bold hover:bg-emerald-200 transition-all">
                                        <span>💬 واتساب</span>
                                    </a>
                                @endif
                            </div>
                        @else
                            <span class="text-gray-400 mt-1">غير متوفر</span>
                        @endif
                    </div>

                    {{-- Full Shipping Address --}}
                    <div class="flex flex-col gap-2 p-4 bg-gray-50 dark:bg-gray-800/60 rounded-xl border border-gray-100 dark:border-gray-700/60">
                        <span class="text-gray-400 font-medium flex items-center gap-1">
                            <span>📍</span>
                            <span>عنوان الشحن والتسليم:</span>
                        </span>

                        <div class="flex flex-col gap-1.5 mt-1">
                            <div class="flex items-center justify-between">
                                <span class="text-gray-500">المحافظة:</span>
                                <span class="font-bold text-gray-800 dark:text-white">{{ $governorateName }}</span>
                            </div>

                            @if($cityDistrict)
                                <div class="flex items-center justify-between border-t border-gray-200/50 dark:border-gray-700/50 pt-1">
                                    <span class="text-gray-500">المدينة / المديرية:</span>
                                    <span class="font-bold text-gray-800 dark:text-white">{{ $cityDistrict }}</span>
                                </div>
                            @endif

                            @if($address1)
                                <div class="flex flex-col border-t border-gray-200/50 dark:border-gray-700/50 pt-1">
                                    <span class="text-gray-500 mb-0.5">العنوان التفصيلي (الشارع / المعلم):</span>
                                    <span class="font-medium text-gray-800 dark:text-gray-200 leading-relaxed bg-white dark:bg-gray-900 p-2 rounded-lg border border-gray-200 dark:border-gray-700">
                                        {{ $address1 }}
                                        @if($address2)
                                            <br><span class="text-gray-500 text-[11px]">{{ $address2 }}</span>
                                        @endif
                                    </span>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            {{-- Section 2: Delivery Route, Payment & Agent Details --}}
            <div class="p-6 bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-800 shadow-xs">
                <div class="flex items-center justify-between border-b border-gray-100 dark:border-gray-800 pb-3 mb-4">
                    <h2 class="text-base font-bold text-gray-800 dark:text-white flex items-center gap-2">
                        <span class="text-xl">🚚</span>
                        <span>القسم الثاني: معلومات الشحنة والتسليم والتحصيل</span>
                    </h2>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-4 gap-4 text-xs">
                    <div class="p-3.5 bg-gray-50 dark:bg-gray-800/60 rounded-xl border border-gray-100 dark:border-gray-700/60 flex flex-col gap-1">
                        <span class="text-gray-400 font-medium">نوع مسار التسليم:</span>
                        <span class="font-bold text-gray-800 dark:text-white mt-1 text-sm">
                            {{ $assignment->delivery_type === 'home_delivery' ? '🏠 توصيل منزلي' : '📍 استلام من نقطة تسليم' }}
                        </span>
                    </div>

                    <div class="p-3.5 bg-gray-50 dark:bg-gray-800/60 rounded-xl border border-gray-100 dark:border-gray-700/60 flex flex-col gap-1">
                        <span class="text-gray-400 font-medium">طريقة الدفع والتحصيل:</span>
                        <div class="mt-1">
                            @php
                                $paymentMethod = strtolower((string) ($assignment->order?->payment?->method ?? $assignment->payment_method ?? ''));
                            @endphp
                            @if(str_contains($paymentMethod, 'cod') || str_contains($paymentMethod, 'cashon'))
                                <span class="text-emerald-600 font-bold text-sm">💵 دفع عند الاستلام (COD)</span>
                            @elseif(str_contains($paymentMethod, 'wallet'))
                                <span class="text-purple-600 font-bold text-sm">👛 المحفظة الرقمية</span>
                            @else
                                <span class="text-sky-600 font-bold text-sm">💳 دفع إلكتروني مسبق</span>
                            @endif
                        </div>
                    </div>

                    <div class="p-3.5 bg-gray-50 dark:bg-gray-800/60 rounded-xl border border-gray-100 dark:border-gray-700/60 flex flex-col gap-1">
                        <span class="text-gray-400 font-medium">مبلغ التحصيل المطلوب (COD):</span>
                        <div class="mt-1">
                            @if($assignment->order && strtolower((string)$assignment->order->payment?->method) === 'cashondelivery')
                                <span class="text-emerald-600 font-bold text-base">{{ core()->formatPrice((float)$assignment->order->grand_total, $assignment->order->order_currency_code) }}</span>
                            @else
                                <span class="text-gray-500 font-medium">غير مطلوب تحصيل (مدفوع مسبقاً)</span>
                            @endif
                        </div>
                    </div>

                    <div class="p-3.5 bg-gray-50 dark:bg-gray-800/60 rounded-xl border border-gray-100 dark:border-gray-700/60 flex flex-col gap-1">
                        <span class="text-gray-400 font-medium">المندوب / جهة التوصيل:</span>
                        <div class="mt-1">
                            @if($assignment->deliveryBoy)
                                <span class="text-indigo-600 dark:text-indigo-400 font-bold text-sm">🚴 {{ $assignment->deliveryBoy->name }}</span>
                            @else
                                <span class="text-amber-600 font-semibold">⚠️ لم يسند لمندوب بعد</span>
                            @endif
                        </div>
                    </div>

                    @if($assignment->deliveryPoint)
                        <div class="col-span-1 md:col-span-4 p-3.5 bg-purple-50 dark:bg-purple-950/30 rounded-xl border border-purple-200 dark:border-purple-800/60 flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <span class="text-xl">🏢</span>
                                <div>
                                    <span class="text-purple-800 dark:text-purple-300 font-bold text-xs">نقطة الاستلام المعتمدة:</span>
                                    <p class="font-bold text-gray-900 dark:text-white text-sm mt-0.5">{{ $assignment->deliveryPoint->name }}</p>
                                </div>
                            </div>
                            <span class="text-gray-600 dark:text-gray-300 text-xs">📍 {{ $assignment->deliveryPoint->governorate }} - {{ $assignment->deliveryPoint->city }} ({{ $assignment->deliveryPoint->address }})</span>
                        </div>
                    @endif

                    @if($assignment->notes)
                        <div class="col-span-1 md:col-span-4 p-3.5 bg-amber-50 dark:bg-amber-950/30 rounded-xl border border-amber-200 dark:border-amber-800/60 flex flex-col gap-1">
                            <span class="text-amber-800 dark:text-amber-300 font-bold text-xs">ملاحظات الشحن والتسليم:</span>
                            <p class="text-gray-800 dark:text-gray-200 text-xs whitespace-pre-line leading-relaxed font-mono">{{ $assignment->notes }}</p>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Section 3: Order Items --}}
            <div class="p-6 bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-800 shadow-xs">
                <div class="flex items-center justify-between border-b border-gray-100 dark:border-gray-800 pb-3 mb-4">
                    <h2 class="text-base font-bold text-gray-800 dark:text-white flex items-center gap-2">
                        <span class="text-xl">📦</span>
                        <span>القسم الثالث: {{ trans('delivery::app.admin.assignments.order-items') }}</span>
                    </h2>
                    <span class="text-xs text-gray-500 font-medium">عدد البنود: {{ count($assignment->order?->items ?? []) }}</span>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-xs text-right">
                        <thead class="text-gray-500 bg-gray-50 dark:bg-gray-800 border-b">
                            <tr>
                                <th class="p-3">المنتج / SKU</th>
                                <th class="p-3 text-center">الكمية</th>
                                <th class="p-3 text-center">السعر</th>
                                <th class="p-3 text-left">الإجمالي</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                            @foreach($assignment->order?->items ?? [] as $item)
                                <tr>
                                    <td class="p-3 font-medium text-gray-800 dark:text-white">
                                        {{ $item->name }}
                                        <div class="text-[10px] text-gray-400 font-mono mt-0.5">{{ $item->sku }}</div>
                                    </td>
                                    <td class="p-3 font-bold text-center text-sm">{{ $item->qty_ordered }}</td>
                                    <td class="p-3 text-center">{{ number_format($item->price, 2) }} {{ $assignment->order->order_currency_code }}</td>
                                    <td class="p-3 font-bold text-left text-gray-900 dark:text-white text-sm">{{ number_format($item->total, 2) }} {{ $assignment->order->order_currency_code }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Section 4: Central Warehouse Stock Status --}}
            <div class="p-6 bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-800 shadow-xs">
                <div class="flex items-center justify-between border-b border-gray-100 dark:border-gray-800 pb-3 mb-4">
                    <h2 class="text-base font-bold text-gray-800 dark:text-white flex items-center gap-2">
                        <span class="text-xl">🏬</span>
                        <span>القسم الرابع: {{ trans('delivery::app.admin.assignments.handoff-status') }}</span>
                    </h2>
                </div>

                <div class="flex items-center gap-3 p-4 rounded-xl {{ in_array($assignment->status, ['picked_up', 'out_for_delivery', 'arrived_at_point', 'delivered']) ? 'bg-emerald-50 text-emerald-800 border border-emerald-200' : 'bg-blue-50 text-blue-800 border border-blue-200' }} text-xs">
                    <span class="text-2xl {{ in_array($assignment->status, ['picked_up', 'out_for_delivery', 'arrived_at_point', 'delivered']) ? 'icon-done' : 'icon-pending' }}"></span>
                    <div class="flex flex-col">
                        <span class="font-bold text-sm">
                            {{ in_array($assignment->status, ['picked_up', 'out_for_delivery', 'arrived_at_point', 'delivered']) ? 'تم الصرف والتسليم من المستودع المركزي للمندوب' : 'المخزون محجوز وجاهز للصرف في المستودع المركزي' }}
                        </span>
                        <span class="text-xs text-gray-600 dark:text-gray-400 mt-0.5">المستودع المصدر: hayest_central</span>
                    </div>
                </div>
            </div>

            {{-- Section 5: Delivery Attempts History (if exists) --}}
            @if($assignment->attemptLogs->isNotEmpty())
                <div class="p-6 bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-800 shadow-xs">
                    <div class="flex items-center justify-between border-b border-gray-100 dark:border-gray-800 pb-3 mb-4">
                        <h2 class="text-base font-bold text-gray-800 dark:text-white flex items-center gap-2">
                            <span class="text-xl">🔄</span>
                            <span>القسم الخامس: سجل محاولات التوصيل ({{ $assignment->attemptLogs->count() }} محاولة)</span>
                        </h2>
                        <span class="text-xs text-rose-500 font-semibold">الحد الأقصى للمحاولات: 3</span>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        @foreach($assignment->attemptLogs as $attempt)
                            <div class="p-4 rounded-xl border border-rose-100 bg-rose-50/50 dark:bg-rose-950/20 text-xs flex items-start justify-between">
                                <div class="flex flex-col">
                                    <div class="flex items-center gap-2">
                                        <span class="px-2.5 py-0.5 rounded-full bg-rose-200 text-rose-800 font-bold">محاولة #{{ $attempt->attempt_number }}</span>
                                        <span class="font-bold text-gray-800 dark:text-white text-sm">{{ $attempt->reason_code }}</span>
                                    </div>
                                    <p class="text-gray-600 dark:text-gray-300 mt-2">{{ $attempt->notes ?: 'لا توجد ملاحظات إضافية' }}</p>
                                    <span class="text-[11px] text-gray-400 mt-1">المندوب: {{ $attempt->deliveryBoy?->name ?: 'غير محدد' }}</span>
                                </div>
                                <div class="text-left text-[11px] text-gray-500">
                                    {{ core()->formatDate($attempt->created_at, 'Y-m-d H:i') }}
                                    @if($attempt->retry_scheduled_at)
                                        <div class="text-orange-600 font-bold mt-1">إعادة المحاولة: {{ core()->formatDate($attempt->retry_scheduled_at, 'Y-m-d H:i') }}</div>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- Section 6: Audit & Activity Logs --}}
            <div class="p-6 bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-800 shadow-xs">
                <div class="flex items-center justify-between border-b border-gray-100 dark:border-gray-800 pb-3 mb-4">
                    <h2 class="text-base font-bold text-gray-800 dark:text-white flex items-center gap-2">
                        <span class="text-xl">📜</span>
                        <span>القسم {{ $assignment->attemptLogs->isNotEmpty() ? 'السادس' : 'الخامس' }}: سجل التدقيق والأنشطة للطلب</span>
                    </h2>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3 text-xs">
                    @forelse($auditLogs as $log)
                        <div class="p-3.5 rounded-xl bg-gray-50 dark:bg-gray-800/70 border border-gray-100 dark:border-gray-700/60 flex flex-col gap-1">
                            <div class="flex items-center justify-between">
                                <span class="font-bold text-purple-700 dark:text-purple-400 text-xs">{{ $log->action }}</span>
                                <span class="text-[10px] text-gray-400">{{ core()->formatDate($log->created_at, 'Y-m-d H:i') }}</span>
                            </div>
                            <span class="text-xs text-gray-700 dark:text-gray-300 mt-1">{{ $log->reason ?: 'إجراء نظامي' }}</span>
                            <span class="text-[10px] text-gray-400 mt-1">بواسطة: {{ $log->user_name }}</span>
                        </div>
                    @empty
                        <span class="text-gray-400 col-span-3 text-center py-4">لا توجد سجلات تدقيق إضافية.</span>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    {{-- Assign Modal --}}
    <div id="assign-modal" class="fixed inset-0 z-50 bg-black/50 hidden flex items-center justify-center">
        <div class="bg-white dark:bg-gray-900 rounded-xl p-6 w-full max-w-md shadow-2xl border border-gray-200">
            <h3 class="text-lg font-bold text-gray-800 dark:text-white mb-4">إسناد مهمة التوصيل #{{ $assignment->id }}</h3>

            @if($assignment->delivery_type === 'home_delivery')
                <div class="mb-4">
                    <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">اختر المندوب الميداني:</label>
                    <select id="modal-courier-id" class="w-full text-xs rounded border border-gray-300 dark:border-gray-700 p-2">
                        <option value="">-- اختر المندوب --</option>
                        @foreach($couriers as $courier)
                            <option value="{{ $courier->id }}" {{ $assignment->delivery_boy_id == $courier->id ? 'selected' : '' }}>{{ $courier->name }} ({{ $courier->email }})</option>
                        @endforeach
                    </select>
                </div>
            @else
                <div class="mb-4">
                    <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">اختر نقطة الاستلام:</label>
                    <select id="modal-point-id" class="w-full text-xs rounded border border-gray-300 dark:border-gray-700 p-2">
                        <option value="">-- اختر نقطة الاستلام --</option>
                        @foreach($deliveryPoints as $point)
                            <option value="{{ $point->id }}" {{ $assignment->delivery_point_id == $point->id ? 'selected' : '' }}>{{ $point->name }} ({{ $point->city }} - {{ $point->state_code }})</option>
                        @endforeach
                    </select>
                </div>
            @endif

            <div class="flex items-center justify-end gap-2 mt-6">
                <button onclick="document.getElementById('assign-modal').classList.add('hidden')" class="secondary-button">إلغاء</button>
                <button onclick="submitAssignment({{ $assignment->id }})" class="primary-button">تأكيد الإسناد</button>
            </div>
        </div>
    </div>

    {{-- Return Modal --}}
    <div id="return-modal" class="fixed inset-0 z-50 bg-black/50 hidden flex items-center justify-center">
        <div class="bg-white dark:bg-gray-900 rounded-xl p-6 w-full max-w-md shadow-2xl border border-gray-200">
            <h3 class="text-lg font-bold text-gray-800 dark:text-white mb-4">اعتماد إرجاع الشحنة للمستودع المركزي</h3>
            <p class="text-xs text-gray-500 mb-3">سيؤدي الاعتماد إلى استعادة المخزون الفيزيائي لمستودع hayest_central ونقل حالة المهمة إلى "مرتجع للمركزي".</p>

            <div class="mb-4">
                <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">سبب الإرجاع الإلزامي:</label>
                <textarea id="modal-return-reason" class="w-full text-xs rounded border border-gray-300 dark:border-gray-700 p-2" rows="3" placeholder="اكتب سبب اعتماد الإرجاع (مثال: تعذر الوصول للعميل بعد 3 محاولات)..."></textarea>
            </div>

            <div class="flex items-center justify-end gap-2 mt-6">
                <button onclick="document.getElementById('return-modal').classList.add('hidden')" class="secondary-button">إلغاء</button>
                <button onclick="submitReturn({{ $assignment->id }})" class="primary-button bg-rose-600 hover:bg-rose-700">تأكيد الاعتماد والإرجاع</button>
            </div>
        </div>
    </div>

    <script>
        function submitAssignment(id) {
            const courierId = document.getElementById('modal-courier-id')?.value;
            const pointId = document.getElementById('modal-point-id')?.value;

            fetch(`/admin/delivery/assignments/${id}/assign`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({
                    delivery_boy_id: courierId,
                    delivery_point_id: pointId
                })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert(data.message || 'حدث خطأ أثناء الإسناد.');
                }
            })
            .catch(err => alert('فشل الاتصال بالخادم.'));
        }

        function executeHandoff(id) {
            if (!confirm('هل أنت متأكد من تسليم وصرف الشحنة من المستودع المركزي؟')) return;

            fetch(`/admin/delivery/assignments/${id}/handoff`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({})
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert(data.message || 'فشل تسليم المخزون.');
                }
            })
            .catch(err => alert('فشل الاتصال بالخادم.'));
        }

        function submitReturn(id) {
            const reason = document.getElementById('modal-return-reason')?.value;
            if (!reason) {
                alert('يرجى كتابة سبب الإرجاع.');
                return;
            }

            fetch(`/admin/delivery/assignments/${id}/return`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({ reason: reason })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert(data.message || 'فشل اعتماد الإرجاع.');
                }
            })
            .catch(err => alert('فشل الاتصال بالخادم.'));
        }
    </script>
</x-admin::layouts>
