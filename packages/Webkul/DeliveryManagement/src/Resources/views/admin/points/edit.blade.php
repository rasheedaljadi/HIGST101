<x-admin::layouts>
    <x-slot:title>
        {{ trans('delivery::app.admin.points.edit-title', ['name' => $point->name]) }}
    </x-slot>

    <div class="flex flex-col gap-6">
        <div class="flex items-center justify-between flex-wrap gap-4">
            <div class="flex flex-col">
                <div class="flex items-center gap-2 text-sm text-gray-500">
                    <a href="{{ route('admin.delivery.points.index') }}" class="hover:text-blue-600">
                        {{ trans('delivery::app.admin.points.title') }}
                    </a>
                    <span>/</span>
                    <span class="text-gray-800 dark:text-white font-medium">{{ $point->code }}</span>
                </div>
                <h1 class="text-2xl font-bold text-gray-800 dark:text-white mt-1">
                    {{ trans('delivery::app.admin.points.edit-title', ['name' => $point->name]) }}
                </h1>
            </div>
        </div>

        <div class="grid grid-cols-3 gap-6 max-lg:grid-cols-1">
            {{-- Edit Form --}}
            <div class="p-6 bg-white dark:bg-gray-900 rounded-lg border border-gray-200 dark:border-gray-800">
                <form action="{{ route('admin.delivery.points.update', $point->id) }}" method="POST" class="flex flex-col gap-4">
                    @csrf
                    @method('PUT')

                    <div>
                        <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">
                            {{ trans('delivery::app.admin.points.name') }} <span class="text-red-500">*</span>
                        </label>
                        <input type="text" name="name" value="{{ old('name', $point->name) }}" required class="w-full text-xs rounded border border-gray-300 dark:border-gray-700 p-2.5 bg-transparent">
                        @error('name')
                            <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">
                            {{ trans('delivery::app.admin.points.governorate') }} <span class="text-red-500">*</span>
                        </label>
                        <select name="state_code" required class="w-full text-xs rounded border border-gray-300 dark:border-gray-700 p-2.5 bg-transparent">
                            @foreach($governorates as $gov)
                                <option value="{{ $gov->state_code }}" {{ $point->state_code == $gov->state_code ? 'selected' : '' }}>{{ $gov->governorate_name }} ({{ $gov->state_code }})</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">
                            {{ trans('delivery::app.admin.points.city') }} <span class="text-red-500">*</span>
                        </label>
                        <input type="text" name="city" value="{{ old('city', $point->city) }}" required class="w-full text-xs rounded border border-gray-300 dark:border-gray-700 p-2.5 bg-transparent">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">
                            {{ trans('delivery::app.admin.points.address') }} <span class="text-red-500">*</span>
                        </label>
                        <textarea name="address" required class="w-full text-xs rounded border border-gray-300 dark:border-gray-700 p-2.5 bg-transparent" rows="2">{{ old('address', $point->address) }}</textarea>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">
                                {{ trans('delivery::app.admin.points.contact-name') }}
                            </label>
                            <input type="text" name="contact_name" value="{{ old('contact_name', $point->contact_name) }}" required class="w-full text-xs rounded border border-gray-300 dark:border-gray-700 p-2 bg-transparent">
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">
                                {{ trans('delivery::app.admin.points.contact-phone') }}
                            </label>
                            <input type="text" name="contact_phone" value="{{ old('contact_phone', $point->contact_phone) }}" required class="w-full text-xs rounded border border-gray-300 dark:border-gray-700 p-2 bg-transparent">
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">
                                {{ trans('delivery::app.admin.points.max-capacity') }}
                            </label>
                            <input type="number" name="max_capacity" value="{{ old('max_capacity', $point->max_capacity) }}" required min="1" class="w-full text-xs rounded border border-gray-300 dark:border-gray-700 p-2 bg-transparent">
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">
                                {{ trans('delivery::app.admin.points.status') }}
                            </label>
                            <select name="is_active" class="w-full text-xs rounded border border-gray-300 dark:border-gray-700 p-2 bg-transparent">
                                <option value="1" {{ $point->is_active ? 'selected' : '' }}>نشط</option>
                                <option value="0" {{ ! $point->is_active ? 'selected' : '' }}>معطل</option>
                            </select>
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-3 mt-4 pt-4 border-t">
                        <a href="{{ route('admin.delivery.points.index') }}" class="secondary-button">إلغاء</a>
                        <button type="submit" class="primary-button">{{ trans('delivery::app.admin.points.save-btn') }}</button>
                    </div>
                </form>
            </div>

            {{-- Shipments currently in Point --}}
            <div class="col-span-2 p-6 bg-white dark:bg-gray-900 rounded-lg border border-gray-200 dark:border-gray-800">
                <h2 class="text-base font-bold text-gray-800 dark:text-white mb-4 border-b pb-2">
                    الشحنات المتواجدة حالياً في النقطة ({{ $shipments->total() }} شحنة)
                </h2>

                <div class="overflow-x-auto">
                    <table class="w-full text-xs text-right">
                        <thead class="text-gray-500 bg-gray-50 dark:bg-gray-800 border-b">
                            <tr>
                                <th class="p-2.5">المهمة / الطلب</th>
                                <th class="p-2.5">العميل</th>
                                <th class="p-2.5">طريقة الدفع</th>
                                <th class="p-2.5">الحالة</th>
                                <th class="p-2.5">تاريخ الوصول</th>
                                <th class="p-2.5 text-center">إجراء</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                            @forelse($shipments as $shipment)
                                <tr>
                                    <td class="p-2.5 font-bold text-blue-600">
                                        #{{ $shipment->id }} ({{ $shipment->order?->increment_id }})
                                    </td>
                                    <td class="p-2.5 font-medium">
                                        {{ $shipment->order?->customer_first_name }} {{ $shipment->order?->customer_last_name }}
                                    </td>
                                    <td class="p-2.5">
                                        {{ str_contains($shipment->payment_method, 'cod') ? 'COD' : 'مدفوع مسبقاً' }}
                                    </td>
                                    <td class="p-2.5">
                                        <span class="px-2 py-0.5 rounded text-[10px] font-semibold bg-cyan-100 text-cyan-800">
                                            {{ trans("delivery::app.admin.states.{$shipment->status}") }}
                                        </span>
                                    </td>
                                    <td class="p-2.5 text-gray-400">{{ core()->formatDate($shipment->updated_at, 'Y-m-d H:i') }}</td>
                                    <td class="p-2.5 text-center">
                                        <a href="{{ route('admin.delivery.assignments.show', $shipment->id) }}" class="text-blue-600 hover:underline">
                                            عرض
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="p-4 text-center text-gray-400">لا توجد شحنات متواجدة داخل هذه النقطة حالياً.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="mt-4">
                    {{ $shipments->links() }}
                </div>
            </div>
        </div>
    </div>
</x-admin::layouts>
