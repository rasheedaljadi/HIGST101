<x-admin::layouts>
    <x-slot:title>
        إدارة وعمليات التوصيل
    </x-slot>

    <!-- Page Header & Actions -->
    <div class="flex items-center justify-between gap-4 max-sm:flex-wrap mb-5">
        <div>
            <h1 class="text-xl font-bold text-gray-800 dark:text-white">
                لوحة إدارة وعمليات التوصيل
            </h1>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                إدارة التكليفات، التسليم المركزي (Handoff)، ومراقبة مسارات المناديب ونقاط الاستلام
            </p>
        </div>

        <div class="flex items-center gap-3">
            <span class="text-xs font-semibold px-3 py-1.5 rounded-lg bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-300 border border-gray-200 dark:border-gray-700">
                المشرف: <strong class="text-blue-600 dark:text-blue-400">{{ $user->name ?? 'مشرف العمليات' }}</strong>
            </span>
        </div>
    </div>

    <!-- Filter Bar -->
    <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl p-4 mb-6 shadow-sm flex flex-wrap items-center justify-between gap-4">
        <div class="flex flex-wrap items-center gap-3">
            <label class="text-xs font-bold text-gray-600 dark:text-gray-300">تصفية بحسب الحالة:</label>
            <select id="statusFilter" class="bg-gray-50 dark:bg-gray-950 border border-gray-300 dark:border-gray-800 text-gray-800 dark:text-gray-200 text-xs rounded-lg px-3 py-2 outline-none focus:border-blue-500" onchange="applyFilters()">
                <option value="">جميع الحالات</option>
                <option value="ready_for_assignment" {{ request('status') === 'ready_for_assignment' ? 'selected' : '' }}>جاهز للإسناد (Ready)</option>
                <option value="assigned" {{ request('status') === 'assigned' ? 'selected' : '' }}>مسند (Assigned)</option>
                <option value="picked_up" {{ request('status') === 'picked_up' ? 'selected' : '' }}>مستلم من المستودع (Picked Up)</option>
                <option value="out_for_delivery" {{ request('status') === 'out_for_delivery' ? 'selected' : '' }}>خرج للتوصيل (Out for Delivery)</option>
                <option value="arrived_at_point" {{ request('status') === 'arrived_at_point' ? 'selected' : '' }}>وصل لنقطة التوزيع</option>
                <option value="delivered" {{ request('status') === 'delivered' ? 'selected' : '' }}>تم التسليم (Delivered)</option>
                <option value="delivery_failed" {{ request('status') === 'delivery_failed' ? 'selected' : '' }}>فشل التسليم (Failed)</option>
                <option value="retry_scheduled" {{ request('status') === 'retry_scheduled' ? 'selected' : '' }}>إعادة محاولة مجدولة</option>
                <option value="returned_to_hayest" {{ request('status') === 'returned_to_hayest' ? 'selected' : '' }}>مرتجع للمركزي (Returned)</option>
            </select>

            <label class="text-xs font-bold text-gray-600 dark:text-gray-300 mr-2">نوع التوصيل:</label>
            <select id="typeFilter" class="bg-gray-50 dark:bg-gray-950 border border-gray-300 dark:border-gray-800 text-gray-800 dark:text-gray-200 text-xs rounded-lg px-3 py-2 outline-none focus:border-blue-500" onchange="applyFilters()">
                <option value="">جميع الأنواع</option>
                <option value="home_delivery" {{ request('delivery_type') === 'home_delivery' ? 'selected' : '' }}>توصيل منزلي</option>
                <option value="delivery_point" {{ request('delivery_type') === 'delivery_point' ? 'selected' : '' }}>نقطة استلام</option>
            </select>
        </div>

        <div>
            <span class="text-xs text-gray-500 dark:text-gray-400">إجمالي المهام: <strong class="text-gray-800 dark:text-white font-bold">{{ $assignments->total() }}</strong></span>
        </div>
    </div>

    <!-- Assignments Table -->
    <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl overflow-hidden shadow-sm mb-6">
        <div class="overflow-x-auto">
            <table class="w-full text-right text-xs">
                <thead>
                    <tr class="bg-gray-50 dark:bg-gray-950 border-b border-gray-200 dark:border-gray-800 text-gray-600 dark:text-gray-400 font-bold">
                        <th class="p-3.5">رقم المهمة</th>
                        <th class="p-3.5">رقم الطلب</th>
                        <th class="p-3.5">نوع التوصيل</th>
                        <th class="p-3.5">العميل والمحافظة</th>
                        <th class="p-3.5">المندوب / النقطة</th>
                        <th class="p-3.5">حالة المهمة</th>
                        <th class="p-3.5">المحاولات</th>
                        <th class="p-3.5 text-center">إجراءات العمليات</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                    @forelse($assignments as $assignment)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-950 transition">
                            <td class="p-3.5 font-bold text-blue-600 dark:text-blue-400">#{{ $assignment->id }}</td>
                            <td class="p-3.5 font-bold text-gray-800 dark:text-white">{{ $assignment->order->increment_id ?? ('#'.$assignment->order_id) }}</td>
                            <td class="p-3.5 font-semibold">
                                @if($assignment->delivery_type === 'home_delivery')
                                    <span class="text-sky-600 dark:text-sky-400">🏠 توصيل منزلي</span>
                                @else
                                    <span class="text-purple-600 dark:text-purple-400">📍 نقطة استلام</span>
                                @endif
                            </td>
                            <td class="p-3.5">
                                <div class="font-bold text-gray-800 dark:text-gray-200">{{ $assignment->customer_name }}</div>
                                <div class="text-[11px] text-gray-500">{{ $assignment->governorate_code }} - {{ $assignment->delivery_address }}</div>
                            </td>
                            <td class="p-3.5">
                                @if($assignment->delivery_type === 'home_delivery')
                                    @if($assignment->courier)
                                        <span class="font-bold text-blue-600 dark:text-blue-400">🚴 {{ $assignment->courier->name }}</span>
                                    @else
                                        <span class="text-amber-500 font-semibold">⏳ بانتظار مندوب</span>
                                    @endif
                                @else
                                    @if($assignment->deliveryPoint)
                                        <span class="font-bold text-purple-600 dark:text-purple-400">🏪 {{ $assignment->deliveryPoint->name }}</span>
                                    @else
                                        <span class="text-amber-500 font-semibold">⏳ بانتظار تحديد نقطة</span>
                                    @endif
                                @endif
                            </td>
                            <td class="p-3.5">
                                @php
                                    $badgeClasses = [
                                        'ready_for_assignment' => 'bg-sky-100 text-sky-800 dark:bg-sky-950 dark:text-sky-300',
                                        'assigned' => 'bg-indigo-100 text-indigo-800 dark:bg-indigo-950 dark:text-indigo-300',
                                        'picked_up' => 'bg-purple-100 text-purple-800 dark:bg-purple-950 dark:text-purple-300',
                                        'out_for_delivery' => 'bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300',
                                        'arrived_at_point' => 'bg-purple-100 text-purple-800 dark:bg-purple-950 dark:text-purple-300',
                                        'delivered' => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300',
                                        'delivery_failed' => 'bg-rose-100 text-rose-800 dark:bg-rose-950 dark:text-rose-300',
                                        'retry_scheduled' => 'bg-pink-100 text-pink-800 dark:bg-pink-950 dark:text-pink-300',
                                        'returned_to_hayest' => 'bg-gray-200 text-gray-800 dark:bg-gray-800 dark:text-gray-300',
                                    ];
                                    $labels = [
                                        'ready_for_assignment' => 'جاهز للإسناد',
                                        'assigned' => 'تم الإسناد',
                                        'picked_up' => 'مستلم من المستودع',
                                        'out_for_delivery' => 'خرج للتوصيل',
                                        'arrived_at_point' => 'وصل لنقطة التوزيع',
                                        'delivered' => 'تم التسليم بنجاح',
                                        'delivery_failed' => 'فشل التسليم',
                                        'retry_scheduled' => 'إعادة محاولة',
                                        'returned_to_hayest' => 'مرتجع للمركزي',
                                    ];
                                @endphp
                                <span class="px-2.5 py-1 rounded-full font-bold text-[11px] inline-flex items-center gap-1 {{ $badgeClasses[$assignment->status] ?? 'bg-gray-100 text-gray-800' }}">
                                    {{ $labels[$assignment->status] ?? $assignment->status }}
                                </span>
                            </td>
                            <td class="p-3.5 font-bold text-gray-700 dark:text-gray-300">
                                {{ $assignment->attempt_count }} / {{ $assignment->max_attempts }}
                            </td>
                            <td class="p-3.5 text-center">
                                <div class="flex items-center justify-center gap-1.5 flex-wrap">
                                    @if(in_array($assignment->status, ['ready_for_assignment', 'assigned', 'retry_scheduled']))
                                        <button onclick="openAssignModal({{ $assignment->id }}, '{{ $assignment->delivery_type }}', '{{ $assignment->governorate_code }}')" class="px-3 py-1.5 rounded-lg font-bold bg-blue-600 hover:bg-blue-700 text-white transition text-xs shadow-sm">
                                            إسناد
                                        </button>
                                    @endif

                                    @if(in_array($assignment->status, ['assigned', 'retry_scheduled']))
                                        <button onclick="triggerHandoff({{ $assignment->id }})" class="px-3 py-1.5 rounded-lg font-bold bg-emerald-600 hover:bg-emerald-700 text-white transition text-xs shadow-sm">
                                            تسليم (Handoff)
                                        </button>
                                    @endif

                                    @if(in_array($assignment->status, ['delivery_failed', 'retry_scheduled', 'assigned', 'picked_up', 'out_for_delivery']))
                                        <button onclick="openReturnModal({{ $assignment->id }})" class="px-3 py-1.5 rounded-lg font-bold bg-rose-600 hover:bg-rose-700 text-white transition text-xs shadow-sm">
                                            اعتماد الإرجاع
                                        </button>
                                    @endif

                                    @if($assignment->status === 'delivered')
                                        <span class="text-emerald-600 font-bold text-xs">مكتمل ومحصّل</span>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="p-8 text-center text-gray-500 dark:text-gray-400">
                                لا توجد مهام توصيل مطابقة للفلاتر الحالية
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($assignments->hasPages())
            <div class="p-4 border-t border-gray-200 dark:border-gray-800">
                {{ $assignments->links() }}
            </div>
        @endif
    </div>

    <!-- Assign Modal -->
    <div id="assignModal" class="hidden fixed inset-0 bg-black/60 z-50 items-center justify-center p-4">
        <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl max-w-md w-full p-6 shadow-2xl">
            <h3 class="text-base font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                <span>📋</span> إسناد مهمة التوصيل
            </h3>
            
            <form id="assignForm" onsubmit="submitAssign(event)">
                <input type="hidden" id="assignAssignmentId">
                <input type="hidden" id="assignDeliveryType">

                <div id="courierField" class="mb-4">
                    <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-2">اختر مندوب التوصيل:</label>
                    <select id="courierSelect" class="w-full bg-gray-50 dark:bg-gray-950 border border-gray-300 dark:border-gray-700 text-gray-800 dark:text-gray-200 rounded-lg p-2.5 text-xs outline-none focus:border-blue-500">
                        <option value="">-- حدد المندوب --</option>
                        @foreach($couriers as $courier)
                            <option value="{{ $courier->id }}">{{ $courier->name }} ({{ $courier->email }})</option>
                        @endforeach
                    </select>
                </div>

                <div id="pointField" class="mb-4 hidden">
                    <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-2">اختر نقطة التوزيع:</label>
                    <select id="pointSelect" class="w-full bg-gray-50 dark:bg-gray-950 border border-gray-300 dark:border-gray-700 text-gray-800 dark:text-gray-200 rounded-lg p-2.5 text-xs outline-none focus:border-blue-500">
                        <option value="">-- حدد نقطة التوزيع --</option>
                        @foreach($deliveryPoints as $point)
                            <option value="{{ $point->id }}" data-gov="{{ $point->governorate_code }}">{{ $point->name }} ({{ $point->governorate_code }} - {{ $point->address }})</option>
                        @endforeach
                    </select>
                </div>

                <div class="flex items-center justify-end gap-2 mt-6">
                    <button type="button" onclick="closeModal('assignModal')" class="px-4 py-2 rounded-lg font-bold bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-700 text-xs">
                        إلغاء
                    </button>
                    <button type="submit" class="px-4 py-2 rounded-lg font-bold bg-blue-600 hover:bg-blue-700 text-white text-xs shadow-sm">
                        تأكيد الإسناد
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Return Modal -->
    <div id="returnModal" class="hidden fixed inset-0 bg-black/60 z-50 items-center justify-center p-4">
        <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl max-w-md w-full p-6 shadow-2xl">
            <h3 class="text-base font-bold text-rose-600 dark:text-rose-400 mb-4 flex items-center gap-2">
                <span>⚠️</span> اعتماد إرجاع الشحنة للمستودع المركزي
            </h3>
            <p class="text-xs text-gray-600 dark:text-gray-400 mb-4">
                سيتم استعادة كميات المنتجات الفعلية إلى مخزون <strong>hayest_central</strong> وتوثيق حركة المخزون باسمك كمشرف.
            </p>
            
            <form id="returnForm" onsubmit="submitReturn(event)">
                <input type="hidden" id="returnAssignmentId">

                <div class="mb-4">
                    <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-2">سبب الإرجاع المعتمد <span class="text-rose-500">*</span>:</label>
                    <textarea id="returnReason" required rows="3" class="w-full bg-gray-50 dark:bg-gray-950 border border-gray-300 dark:border-gray-700 text-gray-800 dark:text-gray-200 rounded-lg p-2.5 text-xs outline-none focus:border-rose-500" placeholder="مثال: استنفاد محاولات الاتصال بالعميل / إلغاء الطلب من العميل"></textarea>
                </div>

                <div class="flex items-center justify-end gap-2 mt-6">
                    <button type="button" onclick="closeModal('returnModal')" class="px-4 py-2 rounded-lg font-bold bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-700 text-xs">
                        إلغاء
                    </button>
                    <button type="submit" class="px-4 py-2 rounded-lg font-bold bg-rose-600 hover:bg-rose-700 text-white text-xs shadow-sm">
                        اعتماد الإرجاع واستعادة المخزون
                    </button>
                </div>
            </form>
        </div>
    </div>

    @pushOnce('scripts')
    <script>
        const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        function applyFilters() {
            const status = document.getElementById('statusFilter').value;
            const type = document.getElementById('typeFilter').value;
            const url = new URL(window.location.href);
            if (status) url.searchParams.set('status', status); else url.searchParams.delete('status');
            if (type) url.searchParams.set('delivery_type', type); else url.searchParams.delete('delivery_type');
            window.location.href = url.toString();
        }

        function openAssignModal(id, deliveryType, gov) {
            document.getElementById('assignAssignmentId').value = id;
            document.getElementById('assignDeliveryType').value = deliveryType;
            
            const courierField = document.getElementById('courierField');
            const pointField = document.getElementById('pointField');

            if (deliveryType === 'home_delivery') {
                courierField.classList.remove('hidden');
                pointField.classList.add('hidden');
            } else {
                pointField.classList.remove('hidden');
                courierField.classList.add('hidden');
                
                const pointSelect = document.getElementById('pointSelect');
                Array.from(pointSelect.options).forEach(opt => {
                    if (opt.value === '') return;
                    if (opt.getAttribute('data-gov') === gov) {
                        opt.style.display = 'block';
                    } else {
                        opt.style.display = 'none';
                    }
                });
            }

            const modal = document.getElementById('assignModal');
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }

        function openReturnModal(id) {
            document.getElementById('returnAssignmentId').value = id;
            document.getElementById('returnReason').value = '';
            const modal = document.getElementById('returnModal');
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }

        function closeModal(modalId) {
            const modal = document.getElementById(modalId);
            modal.classList.remove('flex');
            modal.classList.add('hidden');
        }

        async function submitAssign(e) {
            e.preventDefault();
            const id = document.getElementById('assignAssignmentId').value;
            const type = document.getElementById('assignDeliveryType').value;
            const courierId = document.getElementById('courierSelect').value;
            const pointId = document.getElementById('pointSelect').value;

            const payload = {};
            if (type === 'home_delivery') {
                if (!courierId) return alert('الرجاء اختيار مندوب التوصيل');
                payload.courier_id = courierId;
            } else {
                if (!pointId) return alert('الرجاء اختيار نقطة التوزيع');
                payload.delivery_point_id = pointId;
            }

            try {
                const res = await fetch(`/admin/delivery/assignments/${id}/assign`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': token, 'Accept': 'application/json' },
                    body: JSON.stringify(payload)
                });
                const data = await res.json();
                if (res.ok && data.success) {
                    alert(data.message || 'تم الإسناد بنجاح');
                    window.location.reload();
                } else {
                    alert(data.message || 'حدث خطأ أثناء الإسناد');
                }
            } catch (err) {
                alert('خطأ في الاتصال بالخادم');
            }
        }

        async function triggerHandoff(id) {
            if (!confirm('تأكيد تسليم الشحنة من المستودع المركزي (Handoff) وخصم المخزون؟')) return;
            try {
                const res = await fetch(`/admin/delivery/assignments/${id}/handoff`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': token, 'Accept': 'application/json' }
                });
                const data = await res.json();
                if (res.ok && data.success) {
                    alert(data.message || 'تم التسليم المركزي بنجاح');
                    window.location.reload();
                } else {
                    alert(data.message || 'فشل التسليم المركزي');
                }
            } catch (err) {
                alert('خطأ في الاتصال بالخادم');
            }
        }

        async function submitReturn(e) {
            e.preventDefault();
            const id = document.getElementById('returnAssignmentId').value;
            const reason = document.getElementById('returnReason').value;
            if (!reason) return alert('الرجاء كتابة سبب الإرجاع');

            try {
                const res = await fetch(`/admin/delivery/assignments/${id}/return`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': token, 'Accept': 'application/json' },
                    body: JSON.stringify({ reason })
                });
                const data = await res.json();
                if (res.ok && data.success) {
                    alert(data.message || 'تم اعتماد الإرجاع واستعادة المخزون بنجاح');
                    window.location.reload();
                } else {
                    alert(data.message || 'فشل اعتماد الإرجاع');
                }
            } catch (err) {
                alert('خطأ في الاتصال بالخادم');
            }
        }
    </script>
    @endpushOnce
</x-admin::layouts>
