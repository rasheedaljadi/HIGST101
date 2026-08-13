<x-admin::layouts>
    <x-slot:title>
        إنشاء عرض ترويجي جديد
    </x-slot:title>

    <div class="flex flex-col gap-4 p-6">
        <div class="flex items-center justify-between">
            <p class="text-xl font-bold text-gray-800 dark:text-white">
                إنشاء عرض ومكافأة محفظة جديدة
            </p>

            <a
                href="{{ route('admin.wallet.promotions.index') }}"
                class="secondary-button"
            >
                العودة للقائمة
            </a>
        </div>

        <form method="POST" action="{{ route('admin.wallet.promotions.store') }}" class="mt-4">
            @csrf

            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                <!-- Name -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">اسم العرض *</label>
                    <input type="text" name="name" required class="control mt-1 w-full rounded-md border p-2" value="{{ old('name') }}" />
                </div>

                <!-- Type -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">نوع العرض *</label>
                    <select name="type" required class="control mt-1 w-full rounded-md border p-2">
                        <option value="welcome_bonus">مكافأة تسجيل ترحيبية (welcome_bonus)</option>
                        <option value="topup_bonus">بونص شحن المحفظة (topup_bonus)</option>
                        <option value="order_subtotal_cashback">كاش باك على قيمة الطلب (order_subtotal_cashback)</option>
                        <option value="order_conditional_cashback">كاش باك مشروط بالمنتجات (order_conditional_cashback)</option>
                    </select>
                </div>

                <!-- Action Type -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">طريقة الحساب *</label>
                    <select name="action_type" required class="control mt-1 w-full rounded-md border p-2">
                        <option value="fixed">مبلغ ثابت (Fixed Amount)</option>
                        <option value="percentage">نسبة مئوية (Percentage)</option>
                    </select>
                </div>

                <!-- Reward Value -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">قيمة المكافأة / النسبة *</label>
                    <input type="number" step="0.0001" name="reward_value" required class="control mt-1 w-full rounded-md border p-2" value="{{ old('reward_value') }}" />
                </div>

                <!-- Status -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">حالة العرض *</label>
                    <select name="status" required class="control mt-1 w-full rounded-md border p-2">
                        <option value="draft">مسودة (Draft)</option>
                        <option value="active">نشط (Active)</option>
                        <option value="inactive">معطل (Inactive)</option>
                    </select>
                </div>

                <!-- Grant Validity Days -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">صلاحية الرصيد الترويجي (أيام)</label>
                    <input type="number" name="grant_validity_days" class="control mt-1 w-full rounded-md border p-2" value="{{ old('grant_validity_days', 30) }}" />
                </div>

                <!-- Min Spend Amount -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">الحد الأدنى للإنفاق/الشحن</label>
                    <input type="number" step="0.0001" name="min_spend_amount" class="control mt-1 w-full rounded-md border p-2" value="{{ old('min_spend_amount') }}" />
                </div>

                <!-- Max Reward Amount -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">الحد الأقصى للمكافأة (سقف الكاش باك)</label>
                    <input type="number" step="0.0001" name="max_reward_amount" class="control mt-1 w-full rounded-md border p-2" value="{{ old('max_reward_amount') }}" />
                </div>

                <!-- Total Budget -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">الميزانية الإجمالية للحملة</label>
                    <input type="number" step="0.0001" name="total_budget" class="control mt-1 w-full rounded-md border p-2" value="{{ old('total_budget') }}" />
                </div>

                <!-- Usage Limit -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">الحد الأقصى للاستخدام الإجمالي</label>
                    <input type="number" name="usage_limit" class="control mt-1 w-full rounded-md border p-2" value="{{ old('usage_limit') }}" />
                </div>

                <!-- Usage Per Customer -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">الحد الأقصى لكل عميل</label>
                    <input type="number" name="usage_per_customer" class="control mt-1 w-full rounded-md border p-2" value="{{ old('usage_per_customer', 1) }}" />
                </div>

                <!-- Priority -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">الأولوية</label>
                    <input type="number" name="priority" class="control mt-1 w-full rounded-md border p-2" value="{{ old('priority', 0) }}" />
                </div>

                <!-- Dates -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">تاريخ البدء</label>
                    <input type="datetime-local" name="starts_from" class="control mt-1 w-full rounded-md border p-2" />
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">تاريخ الانتهاء</label>
                    <input type="datetime-local" name="ends_till" class="control mt-1 w-full rounded-md border p-2" />
                </div>
            </div>

            <!-- Description -->
            <div class="mt-6">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">الوصف والشروط</label>
                <textarea name="description" rows="3" class="control mt-1 w-full rounded-md border p-2">{{ old('description') }}</textarea>
            </div>

            <div class="mt-8 flex items-center justify-end gap-3">
                <button type="submit" class="primary-button">
                    حفظ العرض الترويجي
                </button>
            </div>
        </form>
    </div>
</x-admin::layouts>
