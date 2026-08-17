<x-admin::layouts>
    <x-slot:title>
        {{ trans('delivery::app.admin.couriers.edit-title', ['id' => $courier->id]) }}
    </x-slot>

    <div class="flex flex-col gap-6">
        <div class="flex items-center justify-between flex-wrap gap-4">
            <div class="flex flex-col">
                <div class="flex items-center gap-2 text-sm text-gray-500">
                    <a href="{{ route('admin.delivery.couriers.index') }}" class="hover:text-blue-600">
                        {{ trans('delivery::app.admin.couriers.title') }}
                    </a>
                    <span>/</span>
                    <span class="text-gray-800 dark:text-white font-medium">#{{ $courier->id }}</span>
                </div>
                <h1 class="text-2xl font-bold text-gray-800 dark:text-white mt-1">
                    {{ trans('delivery::app.admin.couriers.edit-title', ['id' => $courier->id]) }}
                </h1>
            </div>
        </div>

        <div class="grid grid-cols-3 gap-6 max-lg:grid-cols-1">
            {{-- Edit Form --}}
            <div class="p-6 bg-white dark:bg-gray-900 rounded-lg border border-gray-200 dark:border-gray-800">
                <form action="{{ route('admin.delivery.couriers.update', $courier->id) }}" method="POST" class="flex flex-col gap-4">
                    @csrf
                    @method('PUT')

                    <div>
                        <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">
                            {{ trans('delivery::app.admin.couriers.name') }} <span class="text-red-500">*</span>
                        </label>
                        <input type="text" name="name" value="{{ old('name', $courier->name) }}" required class="w-full text-xs rounded border border-gray-300 dark:border-gray-700 p-2.5 bg-transparent">
                        @error('name')
                            <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">
                            {{ trans('delivery::app.admin.couriers.email') }} <span class="text-red-500">*</span>
                        </label>
                        <input type="email" name="email" value="{{ old('email', $courier->email) }}" required class="w-full text-xs rounded border border-gray-300 dark:border-gray-700 p-2.5 bg-transparent">
                        @error('email')
                            <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">
                            {{ trans('delivery::app.admin.couriers.password') }} (اتركه فارغاً إن لم ترغب في التغيير)
                        </label>
                        <input type="password" name="password" class="w-full text-xs rounded border border-gray-300 dark:border-gray-700 p-2.5 bg-transparent" placeholder="كلمة مرور جديدة...">
                        @error('password')
                            <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">
                            {{ trans('delivery::app.admin.couriers.status') }}
                        </label>
                        <select name="status" class="w-full text-xs rounded border border-gray-300 dark:border-gray-700 p-2.5 bg-transparent">
                            <option value="1" {{ $courier->status ? 'selected' : '' }}>{{ trans('delivery::app.admin.couriers.active') }}</option>
                            <option value="0" {{ ! $courier->status ? 'selected' : '' }}>{{ trans('delivery::app.admin.couriers.inactive') }}</option>
                        </select>
                    </div>

                    <div class="flex items-center justify-end gap-3 mt-4 pt-4 border-t">
                        <a href="{{ route('admin.delivery.couriers.index') }}" class="secondary-button">إلغاء</a>
                        <button type="submit" class="primary-button">{{ trans('delivery::app.admin.couriers.save-btn') }}</button>
                    </div>
                </form>
            </div>

            {{-- Courier Tasks History --}}
            <div class="col-span-2 p-6 bg-white dark:bg-gray-900 rounded-lg border border-gray-200 dark:border-gray-800">
                <h2 class="text-base font-bold text-gray-800 dark:text-white mb-4 border-b pb-2">
                    المهام المسندة للمندوب ({{ $tasks->total() }} مهمة)
                </h2>

                <div class="overflow-x-auto">
                    <table class="w-full text-xs text-right">
                        <thead class="text-gray-500 bg-gray-50 dark:bg-gray-800 border-b">
                            <tr>
                                <th class="p-2.5">المهمة / الطلب</th>
                                <th class="p-2.5">المحافظة</th>
                                <th class="p-2.5">مبلغ COD</th>
                                <th class="p-2.5">الحالة</th>
                                <th class="p-2.5">التاريخ</th>
                                <th class="p-2.5 text-center">إجراء</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                            @forelse($tasks as $task)
                                <tr>
                                    <td class="p-2.5 font-bold text-blue-600">
                                        #{{ $task->id }} ({{ $task->order?->increment_id }})
                                    </td>
                                    <td class="p-2.5">{{ $task->state_code }}</td>
                                    <td class="p-2.5 font-bold text-emerald-600">
                                        {{ $task->cod_amount_yer > 0 ? number_format($task->cod_amount_yer, 2).' YER' : '-' }}
                                    </td>
                                    <td class="p-2.5">
                                        <span class="px-2 py-0.5 rounded text-[10px] font-semibold bg-gray-100 text-gray-800">
                                            {{ trans("delivery::app.admin.states.{$task->status}") }}
                                        </span>
                                    </td>
                                    <td class="p-2.5 text-gray-400">{{ core()->formatDate($task->created_at, 'Y-m-d') }}</td>
                                    <td class="p-2.5 text-center">
                                        <a href="{{ route('admin.delivery.assignments.show', $task->id) }}" class="text-blue-600 hover:underline">
                                            عرض
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="p-4 text-center text-gray-400">لا توجد مهام مسندة لهذا المندوب.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="mt-4">
                    {{ $tasks->links() }}
                </div>
            </div>
        </div>
    </div>
</x-admin::layouts>
