<x-admin::layouts>
    <x-slot:title>
        {{ trans('delivery::app.admin.rules.edit-title', ['name' => $governorateName]) }}
    </x-slot>

    @php
        $baseCurrency = core()->getBaseCurrencyCode();
    @endphp

    <div class="flex flex-col gap-6">
        {{-- Page Header & Breadcrumbs --}}
        <div class="flex items-center justify-between flex-wrap gap-4">
            <div class="flex flex-col gap-1">
                <div class="flex items-center gap-2 text-sm text-gray-500">
                    <a href="{{ route('admin.delivery.rules.index') }}" class="hover:text-blue-600 transition-colors">
                        {{ trans('delivery::app.admin.rules.title') }}
                    </a>
                    <span>/</span>
                    <span class="text-gray-800 dark:text-white font-medium">{{ $governorateName }}</span>
                </div>
                <div class="flex items-center gap-3 flex-wrap">
                    <h1 class="text-2xl font-bold text-gray-800 dark:text-white">
                        {{ trans('delivery::app.admin.rules.edit-title', ['name' => $governorateName]) }}
                    </h1>
                    <span class="px-2.5 py-1 rounded text-xs font-mono font-bold bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300">
                        {{ $rule->state_code }}
                    </span>
                    <span class="px-2.5 py-1 rounded text-xs font-semibold {{ $rule->delivery_type === 'home_delivery' ? 'bg-blue-100 text-blue-800 dark:bg-blue-950/60 dark:text-blue-300' : 'bg-purple-100 text-purple-800 dark:bg-purple-950/60 dark:text-purple-300' }}">
                        {{ $rule->delivery_type === 'home_delivery' ? '🚚 توصيل منزلي' : '📍 نقطة استلام' }}
                    </span>
                    <span class="px-2.5 py-1 rounded text-xs font-semibold {{ $rule->is_enabled ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300' : 'bg-red-100 text-red-800 dark:bg-red-950/60 dark:text-red-300' }}">
                        {{ $rule->is_enabled ? '● مفعلة' : '○ معطلة' }}
                    </span>
                </div>
            </div>

            <div class="flex items-center gap-3">
                <a href="{{ route('admin.delivery.rules.index') }}" class="secondary-button font-medium">
                    ⬅️ العودة للقواعد
                </a>
            </div>
        </div>

        {{-- ======================================================== --}}
        {{-- القسم الأول: إعدادات وتعديل قاعدة التوصيل والدفع           --}}
        {{-- ======================================================== --}}
        <div class="p-6 bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-800 shadow-sm">
            <div class="flex items-center justify-between border-b border-gray-100 dark:border-gray-800 pb-4 mb-5">
                <div class="flex items-center gap-2">
                    <span class="text-xl">⚙️</span>
                    <div>
                        <h2 class="text-base font-bold text-gray-800 dark:text-white">
                            تعديل إعدادات التوصيل والدفع لمحافظة {{ $governorateName }}
                        </h2>
                        <p class="text-xs text-gray-500">
                            حدد رسوم التوصيل، شرط الإعفاء المجاني، الحد الأدنى للطلب، وطرق الدفع المسموحة لهذه المحافظة.
                        </p>
                    </div>
                </div>
            </div>

            {{-- Base Currency Notice Banner --}}
            <div class="mb-6 p-3.5 rounded-lg bg-blue-50/90 dark:bg-blue-950/30 border border-blue-200 dark:border-blue-800/60 text-xs text-blue-900 dark:text-blue-200 flex items-start gap-2.5">
                <span class="text-base leading-none">ℹ️</span>
                <span class="leading-relaxed">
                    تُسجل جميع المبالغ والحدود بـ <strong>العملة الأساسية للنظام ({{ $baseCurrency }})</strong>، ويقوم النظام تلقائياً بتحويلها وإظهارها للعملاء بعملة واجهة المتجر المفضلة لديهم (مثل الريال اليمني YER أو الريال السعودي SAR) وفقاً لجدول أسعار الصرف.
                </span>
            </div>

            <form action="{{ route('admin.delivery.rules.update', $rule->id) }}" method="POST">
                @csrf
                @method('PUT')

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    {{-- 1. Delivery Fee --}}
                    <div class="flex flex-col gap-1.5 p-4 rounded-lg bg-gray-50/80 dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700">
                        <label class="block text-xs font-bold text-gray-700 dark:text-gray-300">
                            💵 {{ trans('delivery::app.admin.rules.delivery-fee') }} ({{ $baseCurrency }}) <span class="text-red-500">*</span>
                        </label>
                        <input
                            type="text"
                            inputmode="decimal"
                            dir="ltr"
                            name="delivery_fee"
                            value="{{ old('delivery_fee', $rule->delivery_fee) }}"
                            required
                            placeholder="0.00"
                            oninput="this.value = this.value.replace(/[٠-٩]/g, d => '٠١٢٣٤٥٦٧٨٩'.indexOf(d)).replace(/[^0-9.]/g, '')"
                            class="w-full text-sm font-semibold font-mono rounded-lg border border-gray-300 dark:border-gray-600 p-2.5 bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:border-blue-500 focus:ring-1 focus:ring-blue-500 text-right"
                        >
                        <p class="text-[11px] text-gray-500">
                            الرسوم القياسية المحتسبة على سلة المشتريات للتوصيل لهذه المحافظة.
                        </p>
                        @error('delivery_fee')
                            <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span>
                        @enderror
                    </div>

                    {{-- 2. Free Delivery Threshold --}}
                    <div class="flex flex-col gap-1.5 p-4 rounded-lg bg-emerald-50/80 dark:bg-emerald-950/20 border border-emerald-200 dark:border-emerald-800">
                        <label class="block text-xs font-bold text-emerald-900 dark:text-emerald-200 flex items-center justify-between">
                            <span>🎁 {{ trans('delivery::app.admin.rules.free-delivery-threshold') }} ({{ $baseCurrency }})</span>
                            <span class="text-[10px] font-medium text-emerald-700 dark:text-emerald-400 bg-emerald-100 dark:bg-emerald-900/60 px-2 py-0.5 rounded-full">توصيل مجاني شرطي</span>
                        </label>
                        <input
                            type="text"
                            inputmode="decimal"
                            dir="ltr"
                            name="free_delivery_threshold"
                            value="{{ old('free_delivery_threshold', $rule->free_delivery_threshold) }}"
                            placeholder="50.00 (اتركه فارغاً لتعطيله)"
                            oninput="this.value = this.value.replace(/[٠-٩]/g, d => '٠١٢٣٤٥٦٧٨٩'.indexOf(d)).replace(/[^0-9.]/g, '')"
                            class="w-full text-sm font-semibold font-mono rounded-lg border border-emerald-300 dark:border-emerald-700 p-2.5 bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 text-right"
                        >
                        <p class="text-[11px] text-emerald-700 dark:text-emerald-400 leading-relaxed">
                            {{ trans('delivery::app.admin.rules.free-delivery-threshold-hint') }}
                        </p>
                        @error('free_delivery_threshold')
                            <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span>
                        @enderror
                    </div>

                    {{-- 3. Min Order Amount (Frozen / تجميد مؤقت) --}}
                    <div class="flex flex-col gap-1.5 p-4 rounded-lg bg-gray-100/60 dark:bg-gray-800/40 border border-gray-200 dark:border-gray-700">
                        <div class="flex items-center justify-between">
                            <label class="block text-xs font-bold text-gray-500 dark:text-gray-400">
                                🛒 الحد الأدنى لقيمة الطلب ({{ $baseCurrency }})
                            </label>
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-bold bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300 border border-amber-200 dark:border-amber-700">
                                🔒 مجمد مؤقتاً
                            </span>
                        </div>
                        <input
                            type="hidden"
                            name="min_order_amount"
                            value="{{ old('min_order_amount', $rule->min_order_amount ?? '0.00') }}"
                        >
                        <input
                            type="text"
                            inputmode="decimal"
                            dir="ltr"
                            disabled
                            value="{{ old('min_order_amount', $rule->min_order_amount ?? '0.00') }}"
                            placeholder="0.00"
                            class="w-full text-sm font-semibold font-mono rounded-lg border border-gray-200 dark:border-gray-700 p-2.5 bg-gray-100 dark:bg-gray-800 text-gray-400 dark:text-gray-500 cursor-not-allowed select-none text-right"
                        >
                        <p class="text-[11px] text-gray-500 dark:text-gray-400">
                            تم تجميد هذا الحقل مؤقتاً في المرحلة الحالية ولا يُستخدم حالياً.
                        </p>
                    </div>
                </div>

                {{-- Row 2: Status & Payment Methods --}}
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-6 pt-6 border-t border-gray-100 dark:border-gray-800">
                    {{-- Allowed Payment Methods --}}
                    <div>
                        <span class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-2">
                            💳 طرق الدفع المسموحة لهذه المحافظة:
                        </span>

                        @php
                            $methods = is_string($rule->allowed_payment_methods) ? json_decode($rule->allowed_payment_methods, true) : (array) $rule->allowed_payment_methods;
                            $methods = is_array($methods) ? $methods : [];
                        @endphp

                        <div class="flex items-center gap-4 flex-wrap">
                            <label class="flex items-center gap-2.5 p-3 rounded-lg border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-800/50 cursor-pointer transition-colors">
                                <input
                                    type="checkbox"
                                    name="allowed_payment_methods[]"
                                    value="cashondelivery"
                                    {{ in_array('cashondelivery', old('allowed_payment_methods', $methods)) ? 'checked' : '' }}
                                    class="w-4 h-4 rounded text-blue-600 focus:ring-blue-500"
                                >
                                <span class="text-xs font-semibold text-gray-800 dark:text-white">
                                    💵 الدفع عند الاستلام (Cash On Delivery)
                                </span>
                            </label>

                            <label class="flex items-center gap-2.5 p-3 rounded-lg border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-800/50 cursor-pointer transition-colors">
                                <input
                                    type="checkbox"
                                    name="allowed_payment_methods[]"
                                    value="moneytransfer"
                                    {{ in_array('moneytransfer', old('allowed_payment_methods', $methods)) ? 'checked' : '' }}
                                    class="w-4 h-4 rounded text-blue-600 focus:ring-blue-500"
                                >
                                <span class="text-xs font-semibold text-gray-800 dark:text-white">
                                    🏦 حوالة بنكية / دفع إلكتروني (Money Transfer)
                                </span>
                            </label>
                        </div>
                    </div>

                    {{-- Rule Status --}}
                    <div>
                        <span class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-2">
                            ⚡ تفعيل القاعدة:
                        </span>
                        <label class="flex items-center gap-2.5 p-3 rounded-lg border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-800/50 cursor-pointer transition-colors">
                            <input
                                type="checkbox"
                                name="is_enabled"
                                value="1"
                                {{ old('is_enabled', $rule->is_enabled) ? 'checked' : '' }}
                                class="w-4 h-4 rounded text-emerald-600 focus:ring-emerald-500"
                            >
                            <span class="text-xs font-semibold text-gray-800 dark:text-white">
                                {{ trans('delivery::app.admin.rules.status') }} (تفعيل خيار التوصيل لهذه المحافظة في المتجر)
                            </span>
                        </label>
                    </div>
                </div>

                {{-- Action Buttons --}}
                <div class="flex items-center justify-end gap-3 mt-6 pt-5 border-t border-gray-100 dark:border-gray-800">
                    <a href="{{ route('admin.delivery.rules.index') }}" class="secondary-button px-5 py-2">
                        إلغاء
                    </a>
                    <button type="submit" class="primary-button px-6 py-2">
                        {{ trans('delivery::app.admin.rules.save-btn') }}
                    </button>
                </div>
            </form>
        </div>

        {{-- ======================================================== --}}
        {{-- القسم الثاني: جدول سجل التغييرات والتعديلات (Audit Trail)  --}}
        {{-- ======================================================== --}}
        <div class="bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-800 shadow-sm overflow-hidden">
            <div class="p-6 border-b border-gray-100 dark:border-gray-800 flex items-center justify-between flex-wrap gap-4">
                <div class="flex items-center gap-2">
                    <span class="text-xl">📜</span>
                    <div>
                        <h2 class="text-base font-bold text-gray-800 dark:text-white">
                            جدول سجل التغييرات والتعديلات على قاعدة المحافظة
                        </h2>
                        <p class="text-xs text-gray-500">
                            تتبع زمني دقيق لكل عملية تعديل تمت على رسوم وقواعد هذه المحافظة مع هوية المنفّذ والقيم المعدّلة.
                        </p>
                    </div>
                </div>
                <span class="px-3 py-1 rounded-full text-xs font-semibold bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300">
                    إجمالي السجلات: {{ $auditLogs->count() }}
                </span>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-right border-collapse text-xs">
                    <thead>
                        <tr class="bg-gray-50 dark:bg-gray-800/80 text-gray-700 dark:text-gray-300 border-b border-gray-200 dark:border-gray-800 whitespace-nowrap">
                            <th class="p-3.5 font-bold"># المعرف</th>
                            <th class="p-3.5 font-bold">👤 المسؤول / المشغّل</th>
                            <th class="p-3.5 font-bold">📅 تاريخ التعديل</th>
                            <th class="p-3.5 font-bold">📝 بيان الإجراء</th>
                            <th class="p-3.5 font-bold">🚚 نوع التسليم</th>
                            <th class="p-3.5 font-bold">💵 رسوم التوصيل</th>
                            <th class="p-3.5 font-bold">🎁 التوصيل المجاني</th>
                            <th class="p-3.5 font-bold">🛒 الحد الأدنى</th>
                            <th class="p-3.5 font-bold">⚡ الحالة</th>
                            <th class="p-3.5 font-bold">💳 طرق الدفع</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                        @forelse($auditLogs as $log)
                            @php
                                $vals = is_string($log->new_values) ? json_decode($log->new_values, true) : (array) $log->new_values;
                                $vals = is_array($vals) ? $vals : [];
                            @endphp
                            <tr class="hover:bg-gray-50/70 dark:hover:bg-gray-800/50 transition-colors">
                                {{-- 1. ID --}}
                                <td class="p-3.5 font-mono text-gray-400">
                                    #{{ $log->id }}
                                </td>

                                {{-- 2. Actor --}}
                                <td class="p-3.5 font-bold text-gray-900 dark:text-white whitespace-nowrap">
                                    <div class="flex items-center gap-1.5">
                                        <span class="w-6 h-6 rounded-full bg-blue-100 dark:bg-blue-900/60 text-blue-700 dark:text-blue-300 flex items-center justify-center text-[10px] font-bold">
                                            {{ mb_substr($log->user_name ?: 'S', 0, 1) }}
                                        </span>
                                        <span>{{ $log->user_name ?: 'System' }}</span>
                                    </div>
                                </td>

                                {{-- 3. Created At --}}
                                <td class="p-3.5 text-gray-500 dark:text-gray-400 whitespace-nowrap font-mono text-[11px]">
                                    {{ core()->formatDate($log->created_at, 'Y-m-d H:i:s') }}
                                </td>

                                {{-- 4. Reason --}}
                                <td class="p-3.5 text-gray-700 dark:text-gray-300 max-w-[220px]">
                                    {{ $log->reason ?: 'تحديث القاعدة' }}
                                </td>

                                {{-- 5. Delivery Type --}}
                                <td class="p-3.5 whitespace-nowrap">
                                    @if(isset($vals['delivery_type']))
                                        <span class="px-2 py-0.5 rounded text-[11px] font-semibold {{ $vals['delivery_type'] === 'home_delivery' ? 'bg-blue-50 text-blue-700 dark:bg-blue-950/60 dark:text-blue-300' : 'bg-purple-50 text-purple-700 dark:bg-purple-950/60 dark:text-purple-300' }}">
                                            {{ $vals['delivery_type'] === 'home_delivery' ? 'توصيل منزلي' : ($vals['delivery_type'] === 'pickup_point' ? 'استلام من نقطة' : $vals['delivery_type']) }}
                                        </span>
                                    @else
                                        <span class="text-gray-400">-</span>
                                    @endif
                                </td>

                                {{-- 6. Delivery Fee --}}
                                <td class="p-3.5 font-bold text-gray-900 dark:text-white whitespace-nowrap">
                                    @if(isset($vals['delivery_fee']))
                                        {{ $vals['delivery_fee'] }} {{ $baseCurrency }}
                                    @else
                                        <span class="text-gray-400 font-normal">-</span>
                                    @endif
                                </td>

                                {{-- 7. Free Delivery Threshold --}}
                                <td class="p-3.5 whitespace-nowrap">
                                    @if(array_key_exists('free_delivery_threshold', $vals))
                                        @if(!empty($vals['free_delivery_threshold']) && $vals['free_delivery_threshold'] > 0)
                                            <span class="px-2 py-0.5 rounded-full text-[11px] font-bold bg-emerald-100 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800">
                                                🎁 مجاني من {{ $vals['free_delivery_threshold'] }} {{ $baseCurrency }}
                                            </span>
                                        @else
                                            <span class="text-gray-400">غير مفعل</span>
                                        @endif
                                    @else
                                        <span class="text-gray-400">-</span>
                                    @endif
                                </td>

                                {{-- 8. Min Order Amount --}}
                                <td class="p-3.5 whitespace-nowrap">
                                    @if(isset($vals['min_order_amount']))
                                        @if($vals['min_order_amount'] > 0)
                                            <span class="font-semibold text-gray-900 dark:text-white">{{ $vals['min_order_amount'] }} {{ $baseCurrency }}</span>
                                        @else
                                            <span class="text-gray-400">لا يوجد</span>
                                        @endif
                                    @else
                                        <span class="text-gray-400">-</span>
                                    @endif
                                </td>

                                {{-- 9. Status --}}
                                <td class="p-3.5 whitespace-nowrap">
                                    @if(isset($vals['is_enabled']))
                                        <span class="px-2 py-0.5 rounded text-[11px] font-semibold {{ $vals['is_enabled'] ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300' : 'bg-red-100 text-red-800 dark:bg-red-950/60 dark:text-red-300' }}">
                                            {{ $vals['is_enabled'] ? 'مفعلة' : 'معطلة' }}
                                        </span>
                                    @else
                                        <span class="text-gray-400">-</span>
                                    @endif
                                </td>

                                {{-- 10. Payment Methods --}}
                                <td class="p-3.5">
                                    @if(isset($vals['allowed_payment_methods']))
                                        @php
                                            $methods = is_string($vals['allowed_payment_methods']) ? json_decode($vals['allowed_payment_methods'], true) : (array) $vals['allowed_payment_methods'];
                                            $methods = is_array($methods) ? $methods : [];
                                        @endphp
                                        <div class="flex items-center gap-1.5 flex-wrap">
                                            @forelse($methods as $m)
                                                <span class="px-2 py-0.5 rounded text-[10px] font-semibold whitespace-nowrap {{ $m === 'cashondelivery' ? 'bg-blue-100 text-blue-800 dark:bg-blue-950/60 dark:text-blue-300' : 'bg-amber-100 text-amber-800 dark:bg-amber-950/60 dark:text-amber-300' }}">
                                                    {{ $m === 'cashondelivery' ? 'الدفع عند الاستلام' : ($m === 'moneytransfer' ? 'حوالة بنكية' : $m) }}
                                                </span>
                                            @empty
                                                <span class="text-gray-400">-</span>
                                            @endforelse
                                        </div>
                                    @else
                                        <span class="text-gray-400">-</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="p-8 text-center text-gray-400">
                                    <div class="flex flex-col items-center justify-center gap-2">
                                        <span class="text-2xl">📋</span>
                                        <span>لم يتم تسجيل أي تعديلات يدوية سابقة على هذه القاعدة حتى الآن.</span>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-admin::layouts>
