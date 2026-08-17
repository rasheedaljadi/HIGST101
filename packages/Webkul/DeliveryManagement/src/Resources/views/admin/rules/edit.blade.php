<x-admin::layouts>
    <x-slot:title>
        {{ trans('delivery::app.admin.rules.edit-title', ['name' => $governorateName]) }}
    </x-slot>

    <div class="flex flex-col gap-6">
        <div class="flex items-center justify-between flex-wrap gap-4">
            <div class="flex flex-col">
                <div class="flex items-center gap-2 text-sm text-gray-500">
                    <a href="{{ route('admin.delivery.rules.index') }}" class="hover:text-blue-600">
                        {{ trans('delivery::app.admin.rules.title') }}
                    </a>
                    <span>/</span>
                    <span class="text-gray-800 dark:text-white font-medium">{{ $governorateName }}</span>
                </div>
                <h1 class="text-2xl font-bold text-gray-800 dark:text-white mt-1">
                    {{ trans('delivery::app.admin.rules.edit-title', ['name' => $governorateName]) }}
                    <span class="text-base font-normal font-mono text-gray-400">({{ $rule->state_code }} - {{ $rule->delivery_type === 'home_delivery' ? 'توصيل منزلي' : 'نقطة استلام' }})</span>
                </h1>
            </div>
        </div>

        <div class="grid grid-cols-3 gap-6 max-lg:grid-cols-1">
            {{-- Form Card --}}
            <div class="p-6 bg-white dark:bg-gray-900 rounded-lg border border-gray-200 dark:border-gray-800">
                <form action="{{ route('admin.delivery.rules.update', $rule->id) }}" method="POST" class="flex flex-col gap-4">
                    @csrf
                    @method('PUT')

                    <div>
                        <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">
                            {{ trans('delivery::app.admin.rules.delivery-fee') }} (ر.ي) <span class="text-red-500">*</span>
                        </label>
                        <input type="number" step="0.01" name="delivery_fee" value="{{ old('delivery_fee', $rule->delivery_fee) }}" required class="w-full text-xs rounded border border-gray-300 dark:border-gray-700 p-2.5 bg-transparent">
                        @error('delivery_fee')
                            <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">
                            الحد الأدنى لقيمة الطلب (ر.ي)
                        </label>
                        <input type="number" step="0.01" name="min_order_amount" value="{{ old('min_order_amount', $rule->min_order_amount) }}" class="w-full text-xs rounded border border-gray-300 dark:border-gray-700 p-2.5 bg-transparent">
                        @error('min_order_amount')
                            <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="flex flex-col gap-3 pt-2 border-t border-gray-100 dark:border-gray-800">
                        <span class="text-xs font-bold text-gray-700 dark:text-gray-300">طرق الدفع المسموحة:</span>

                        @php
                            $methods = is_string($rule->allowed_payment_methods) ? json_decode($rule->allowed_payment_methods, true) : (array) $rule->allowed_payment_methods;
                            $methods = is_array($methods) ? $methods : [];
                        @endphp

                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" name="allowed_payment_methods[]" value="cashondelivery" {{ in_array('cashondelivery', old('allowed_payment_methods', $methods)) ? 'checked' : '' }} class="rounded text-blue-600">
                            <span class="text-xs font-semibold text-gray-800 dark:text-white">الدفع عند الاستلام (COD)</span>
                        </label>

                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" name="allowed_payment_methods[]" value="moneytransfer" {{ in_array('moneytransfer', old('allowed_payment_methods', $methods)) ? 'checked' : '' }} class="rounded text-blue-600">
                            <span class="text-xs font-semibold text-gray-800 dark:text-white">الحوالة البنكية / الدفع الإلكتروني</span>
                        </label>
                    </div>

                    <div class="flex flex-col gap-3 pt-2 border-t border-gray-100 dark:border-gray-800">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" name="is_enabled" value="1" {{ old('is_enabled', $rule->is_enabled) ? 'checked' : '' }} class="rounded text-blue-600">
                            <span class="text-xs font-semibold text-gray-800 dark:text-white">{{ trans('delivery::app.admin.rules.status') }} (مفعّل)</span>
                        </label>
                    </div>

                    <div class="flex items-center justify-end gap-3 mt-4 pt-4 border-t border-gray-100 dark:border-gray-800">
                        <a href="{{ route('admin.delivery.rules.index') }}" class="secondary-button">إلغاء</a>
                        <button type="submit" class="primary-button">{{ trans('delivery::app.admin.rules.save-btn') }}</button>
                    </div>
                </form>
            </div>

            {{-- Audit Trail for Rule Changes --}}
            <div class="col-span-2 p-6 bg-white dark:bg-gray-900 rounded-lg border border-gray-200 dark:border-gray-800">
                <h2 class="text-base font-bold text-gray-800 dark:text-white mb-4 border-b pb-2">
                    سجل التغييرات والتعديلات على قاعدة المحافظة
                </h2>

                <div class="flex flex-col gap-3">
                    @forelse($auditLogs as $log)
                        <div class="p-3 rounded-lg border border-gray-100 bg-gray-50 dark:bg-gray-800 text-xs flex flex-col gap-1">
                            <div class="flex items-center justify-between">
                                <span class="font-bold text-blue-600">تم التعديل بواسطة: {{ $log->user_name ?: 'System' }}</span>
                                <span class="text-[10px] text-gray-400">{{ core()->formatDate($log->created_at, 'Y-m-d H:i:s') }}</span>
                            </div>
                            <p class="text-gray-600 dark:text-gray-300">{{ $log->reason }}</p>
                            @if(!empty($log->new_values))
                                <div class="text-[10px] font-mono text-gray-500 bg-white dark:bg-gray-900 p-2 rounded border mt-1">
                                    {{ json_encode($log->new_values, JSON_UNESCAPED_UNICODE) }}
                                </div>
                            @endif
                        </div>
                    @empty
                        <span class="text-gray-400 text-center py-4">لم يتم تسجيل أي تعديلات يدوية سابقة على هذه القاعدة.</span>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</x-admin::layouts>
