<x-admin::layouts>
    <x-slot:title>
        تعديل العرض الترويجي: {{ $promotion->name }}
    </x-slot:title>

    <div class="flex flex-col gap-4 p-6">
        <div class="flex items-center justify-between">
            <p class="text-xl font-bold text-gray-800 dark:text-white">
                تعديل العرض الترويجي: {{ $promotion->name }}
            </p>

            <a
                href="{{ route('admin.wallet.promotions.index') }}"
                class="secondary-button"
            >
                العودة للقائمة
            </a>
        </div>

        <form method="POST" action="{{ route('admin.wallet.promotions.update', $promotion->id) }}" class="mt-4">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                <!-- Name -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">اسم العرض *</label>
                    <input type="text" name="name" required class="control mt-1 w-full rounded-md border p-2" value="{{ old('name', $promotion->name) }}" />
                </div>

                <!-- Type -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">نوع العرض *</label>
                    <select name="type" required class="control mt-1 w-full rounded-md border p-2">
                        <option value="welcome_bonus" {{ $promotion->type === 'welcome_bonus' ? 'selected' : '' }}>مكافأة تسجيل ترحيبية (welcome_bonus)</option>
                        <option value="topup_bonus" {{ $promotion->type === 'topup_bonus' ? 'selected' : '' }}>بونص شحن المحفظة (topup_bonus)</option>
                        <option value="order_subtotal_cashback" {{ $promotion->type === 'order_subtotal_cashback' ? 'selected' : '' }}>كاش باك على قيمة الطلب (order_subtotal_cashback)</option>
                        <option value="order_conditional_cashback" {{ $promotion->type === 'order_conditional_cashback' ? 'selected' : '' }}>كاش باك مشروط بالمنتجات (order_conditional_cashback)</option>
                    </select>
                </div>

                <!-- Action Type -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">طريقة الحساب *</label>
                    <select name="action_type" required class="control mt-1 w-full rounded-md border p-2">
                        <option value="fixed" {{ $promotion->action_type === 'fixed' ? 'selected' : '' }}>مبلغ ثابت (Fixed Amount)</option>
                        <option value="percentage" {{ $promotion->action_type === 'percentage' ? 'selected' : '' }}>نسبة مئوية (Percentage)</option>
                    </select>
                </div>

                <!-- Reward Value -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">قيمة المكافأة / النسبة *</label>
                    <input type="number" step="0.0001" name="reward_value" required class="control mt-1 w-full rounded-md border p-2" value="{{ old('reward_value', $promotion->reward_value) }}" />
                </div>

                <!-- Status -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">حالة العرض *</label>
                    <select name="status" required class="control mt-1 w-full rounded-md border p-2">
                        <option value="draft" {{ $promotion->status === 'draft' ? 'selected' : '' }}>مسودة (Draft)</option>
                        <option value="active" {{ $promotion->status === 'active' ? 'selected' : '' }}>نشط (Active)</option>
                        <option value="inactive" {{ $promotion->status === 'inactive' ? 'selected' : '' }}>معطل (Inactive)</option>
                        <option value="archived" {{ $promotion->status === 'archived' ? 'selected' : '' }}>مؤرشف (Archived)</option>
                    </select>
                </div>

                <!-- Grant Validity Days -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">صلاحية الرصيد الترويجي (أيام)</label>
                    <input type="number" name="grant_validity_days" class="control mt-1 w-full rounded-md border p-2" value="{{ old('grant_validity_days', $promotion->grant_validity_days) }}" />
                </div>

                <!-- Min Spend Amount -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">الحد الأدنى للإنفاق/الشحن</label>
                    <input type="number" step="0.0001" name="min_spend_amount" class="control mt-1 w-full rounded-md border p-2" value="{{ old('min_spend_amount', $promotion->min_spend_amount) }}" />
                </div>

                <!-- Max Reward Amount -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">الحد الأقصى للمكافأة (سقف الكاش باك)</label>
                    <input type="number" step="0.0001" name="max_reward_amount" class="control mt-1 w-full rounded-md border p-2" value="{{ old('max_reward_amount', $promotion->max_reward_amount) }}" />
                </div>

                <!-- Total Budget -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">الميزانية الإجمالية للحملة</label>
                    <input type="number" step="0.0001" name="total_budget" class="control mt-1 w-full rounded-md border p-2" value="{{ old('total_budget', $promotion->total_budget) }}" />
                </div>

                <!-- Usage Limit -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">الحد الأقصى للاستخدام الإجمالي</label>
                    <input type="number" name="usage_limit" class="control mt-1 w-full rounded-md border p-2" value="{{ old('usage_limit', $promotion->usage_limit) }}" />
                </div>

                <!-- Usage Per Customer -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">الحد الأقصى لكل عميل</label>
                    <input type="number" name="usage_per_customer" class="control mt-1 w-full rounded-md border p-2" value="{{ old('usage_per_customer', $promotion->usage_per_customer) }}" />
                </div>

                <!-- Priority -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">الأولوية</label>
                    <input type="number" name="priority" class="control mt-1 w-full rounded-md border p-2" value="{{ old('priority', $promotion->priority) }}" />
                </div>
            </div>

            <!-- Description -->
            <div class="mt-6">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">الوصف والشروط</label>
                <textarea name="description" rows="3" class="control mt-1 w-full rounded-md border p-2">{{ old('description', $promotion->description) }}</textarea>
            </div>

            <div class="mt-8 flex items-center justify-end gap-3">
                <button type="submit" class="primary-button">
                    تحديث العرض الترويجي
                </button>
            </div>
        </form>
    </div>
</x-admin::layouts>
