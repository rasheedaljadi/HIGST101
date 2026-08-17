<x-admin::layouts>
    <x-slot:title>
        {{ trans('delivery::app.admin.couriers.create-title') }}
    </x-slot>

    <div class="flex flex-col gap-6">
        <div class="flex items-center justify-between flex-wrap gap-4">
            <div class="flex flex-col">
                <div class="flex items-center gap-2 text-sm text-gray-500">
                    <a href="{{ route('admin.delivery.couriers.index') }}" class="hover:text-blue-600">
                        {{ trans('delivery::app.admin.couriers.title') }}
                    </a>
                    <span>/</span>
                    <span class="text-gray-800 dark:text-white font-medium">{{ trans('delivery::app.admin.couriers.create-title') }}</span>
                </div>
                <h1 class="text-2xl font-bold text-gray-800 dark:text-white mt-1">
                    {{ trans('delivery::app.admin.couriers.create-title') }}
                </h1>
            </div>
        </div>

        <div class="p-6 bg-white dark:bg-gray-900 rounded-lg border border-gray-200 dark:border-gray-800 max-w-2xl">
            <form action="{{ route('admin.delivery.couriers.store') }}" method="POST" class="flex flex-col gap-4">
                @csrf

                <div>
                    <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">
                        {{ trans('delivery::app.admin.couriers.name') }} <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="name" value="{{ old('name') }}" required class="w-full text-xs rounded border border-gray-300 dark:border-gray-700 p-2.5 bg-transparent" placeholder="أدخل اسم المندوب الكامل...">
                    @error('name')
                        <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span>
                    @enderror
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">
                        {{ trans('delivery::app.admin.couriers.email') }} <span class="text-red-500">*</span>
                    </label>
                    <input type="email" name="email" value="{{ old('email') }}" required class="w-full text-xs rounded border border-gray-300 dark:border-gray-700 p-2.5 bg-transparent" placeholder="courier@example.com">
                    @error('email')
                        <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span>
                    @enderror
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">
                        {{ trans('delivery::app.admin.couriers.password') }} <span class="text-red-500">*</span>
                    </label>
                    <input type="password" name="password" required class="w-full text-xs rounded border border-gray-300 dark:border-gray-700 p-2.5 bg-transparent" placeholder="أدخل كلمة مرور قوية (6 خانات على الأقل)...">
                    @error('password')
                        <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span>
                    @enderror
                </div>

                <div class="flex items-center justify-end gap-3 mt-4 pt-4 border-t">
                    <a href="{{ route('admin.delivery.couriers.index') }}" class="secondary-button">إلغاء</a>
                    <button type="submit" class="primary-button">{{ trans('delivery::app.admin.couriers.save-btn') }}</button>
                </div>
            </form>
        </div>
    </div>
</x-admin::layouts>
