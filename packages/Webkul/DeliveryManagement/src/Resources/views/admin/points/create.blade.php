<x-admin::layouts>
    <x-slot:title>
        {{ trans('delivery::app.admin.points.create-title') }}
    </x-slot>

    <div class="flex flex-col gap-6">
        <div class="flex items-center justify-between flex-wrap gap-4">
            <div class="flex flex-col">
                <div class="flex items-center gap-2 text-sm text-gray-500">
                    <a href="{{ route('admin.delivery.points.index') }}" class="hover:text-blue-600">
                        {{ trans('delivery::app.admin.points.title') }}
                    </a>
                    <span>/</span>
                    <span class="text-gray-800 dark:text-white font-medium">{{ trans('delivery::app.admin.points.create-title') }}</span>
                </div>
                <h1 class="text-2xl font-bold text-gray-800 dark:text-white mt-1">
                    {{ trans('delivery::app.admin.points.create-title') }}
                </h1>
            </div>
        </div>

        <div class="p-6 bg-white dark:bg-gray-900 rounded-lg border border-gray-200 dark:border-gray-800 max-w-3xl">
            <form action="{{ route('admin.delivery.points.store') }}" method="POST" class="flex flex-col gap-4">
                @csrf

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">
                            {{ trans('delivery::app.admin.points.code') }} <span class="text-red-500">*</span>
                        </label>
                        <input type="text" name="code" value="{{ old('code') }}" required class="w-full text-xs rounded border border-gray-300 dark:border-gray-700 p-2.5 bg-transparent" placeholder="PNT-SAN-TAHRIR">
                        @error('code')
                            <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">
                            {{ trans('delivery::app.admin.points.name') }} <span class="text-red-500">*</span>
                        </label>
                        <input type="text" name="name" value="{{ old('name') }}" required class="w-full text-xs rounded border border-gray-300 dark:border-gray-700 p-2.5 bg-transparent" placeholder="نقطة التحرير - صنعاء">
                        @error('name')
                            <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span>
                        @enderror
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">
                            {{ trans('delivery::app.admin.points.governorate') }} <span class="text-red-500">*</span>
                        </label>
                        <select name="state_code" required class="w-full text-xs rounded border border-gray-300 dark:border-gray-700 p-2.5 bg-transparent">
                            <option value="">-- اختر المحافظة --</option>
                            @foreach($governorates as $gov)
                                <option value="{{ $gov->state_code }}" {{ old('state_code') == $gov->state_code ? 'selected' : '' }}>{{ $gov->governorate_name }} ({{ $gov->state_code }})</option>
                            @endforeach
                        </select>
                        @error('state_code')
                            <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">
                            {{ trans('delivery::app.admin.points.city') }} <span class="text-red-500">*</span>
                        </label>
                        <input type="text" name="city" value="{{ old('city') }}" required class="w-full text-xs rounded border border-gray-300 dark:border-gray-700 p-2.5 bg-transparent" placeholder="صنعاء القديمة / التحرير">
                        @error('city')
                            <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span>
                        @enderror
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">
                        {{ trans('delivery::app.admin.points.address') }} <span class="text-red-500">*</span>
                    </label>
                    <textarea name="address" required class="w-full text-xs rounded border border-gray-300 dark:border-gray-700 p-2.5 bg-transparent" rows="2" placeholder="العنوان التفصيلي..."></textarea>
                    @error('address')
                        <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span>
                    @enderror
                </div>

                <div class="grid grid-cols-3 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">
                            {{ trans('delivery::app.admin.points.contact-name') }} <span class="text-red-500">*</span>
                        </label>
                        <input type="text" name="contact_name" value="{{ old('contact_name') }}" required class="w-full text-xs rounded border border-gray-300 dark:border-gray-700 p-2.5 bg-transparent">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">
                            {{ trans('delivery::app.admin.points.contact-phone') }} <span class="text-red-500">*</span>
                        </label>
                        <input type="text" name="contact_phone" value="{{ old('contact_phone') }}" required class="w-full text-xs rounded border border-gray-300 dark:border-gray-700 p-2.5 bg-transparent">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">
                            {{ trans('delivery::app.admin.points.max-capacity') }} <span class="text-red-500">*</span>
                        </label>
                        <input type="number" name="max_capacity" value="{{ old('max_capacity', 100) }}" required min="1" class="w-full text-xs rounded border border-gray-300 dark:border-gray-700 p-2.5 bg-transparent">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">
                        {{ trans('delivery::app.admin.points.status') }}
                    </label>
                    <select name="is_active" class="w-full text-xs rounded border border-gray-300 dark:border-gray-700 p-2.5 bg-transparent">
                        <option value="1">نشط</option>
                        <option value="0">معطل</option>
                    </select>
                </div>

                <div class="flex items-center justify-end gap-3 mt-4 pt-4 border-t">
                    <a href="{{ route('admin.delivery.points.index') }}" class="secondary-button">إلغاء</a>
                    <button type="submit" class="primary-button">{{ trans('delivery::app.admin.points.save-btn') }}</button>
                </div>
            </form>
        </div>
    </div>
</x-admin::layouts>
