<!DOCTYPE html>
<html lang="ar" dir="rtl" class="h-full bg-slate-900">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>لوحة إدارة وعمليات التوصيل - هايست</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;700;800;900&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; font-family: 'Tajawal', sans-serif; }
        body { background-color: #0f172a; color: #f8fafc; margin: 0; padding: 0; }
        .header-gradient { background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%); border-bottom: 1px solid #334155; }
        .card-bg { background-color: #1e293b; border: 1px solid #334155; }
        .table-row-hover:hover { background-color: #334155; }
        .badge { padding: 4px 10px; border-radius: 9999px; font-size: 12px; font-weight: 700; display: inline-flex; align-items: center; gap: 4px; }
        .badge-ready { background: #0284c7; color: #fff; }
        .badge-assigned { background: #6366f1; color: #fff; }
        .badge-picked { background: #8b5cf6; color: #fff; }
        .badge-out { background: #f59e0b; color: #fff; }
        .badge-delivered { background: #10b981; color: #fff; }
        .badge-failed { background: #ef4444; color: #fff; }
        .badge-retry { background: #ec4899; color: #fff; }
        .badge-returned { background: #64748b; color: #fff; }
        .btn { padding: 8px 16px; border-radius: 8px; font-weight: 700; cursor: pointer; border: none; transition: 0.2s; font-size: 13px; display: inline-flex; align-items: center; justify-content: center; gap: 6px; }
        .btn-primary { background: #3b82f6; color: #fff; }
        .btn-primary:hover { background: #2563eb; }
        .btn-success { background: #10b981; color: #fff; }
        .btn-success:hover { background: #059669; }
        .btn-warning { background: #f59e0b; color: #fff; }
        .btn-warning:hover { background: #d97706; }
        .btn-danger { background: #ef4444; color: #fff; }
        .btn-danger:hover { background: #dc2626; }
        .modal { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.7); z-index: 50; align-items: center; justify-content: center; padding: 16px; }
        .modal.active { display: flex; }
        .input-control { width: 100%; background: #0f172a; border: 1px solid #475569; color: #f8fafc; padding: 10px 14px; border-radius: 8px; font-size: 14px; outline: none; }
        .input-control:focus { border-color: #3b82f6; ring: 2px solid #3b82f6; }
    </style>
</head>
<body class="min-h-full">
    <!-- Header -->
    <header class="header-gradient px-6 py-4 flex items-center justify-between shadow-md">
        <div class="flex items-center gap-4">
            <div style="width: 44px; height: 44px; border-radius: 12px; background: linear-gradient(135deg, #3b82f6, #6366f1); display: flex; align-items: center; justify-content: center; font-weight: 900; font-size: 22px; color: white;">
                H
            </div>
            <div>
                <h1 style="margin: 0; font-size: 20px; font-weight: 800; color: #f8fafc;">لوحة إدارة وعمليات التوصيل</h1>
                <p style="margin: 0; font-size: 12px; color: #94a3b8;">إدارة التكليفات، التسليم المركزي، ومراقبة مسارات المناديب ونقاط التوزيع</p>
            </div>
        </div>
        <div class="flex items-center gap-3">
            <span style="font-size: 13px; color: #cbd5e1; background: #334155; padding: 6px 12px; border-radius: 8px;">
                المشرف: <strong style="color: #60a5fa;">{{ $user->name ?? 'مشرف العمليات' }}</strong>
            </span>
            <a href="{{ route('admin.session.destroy') }}" style="color: #ef4444; font-size: 13px; text-decoration: none; padding: 6px 12px; border: 1px solid #ef4444; border-radius: 8px; font-weight: 700;">تسجيل خروج</a>
        </div>
    </header>

    <!-- Main Container -->
    <main style="max-width: 1400px; margin: 0 auto; padding: 24px 16px;">
        <!-- Filter Bar -->
        <div class="card-bg" style="border-radius: 12px; padding: 16px; margin-bottom: 24px; display: flex; flex-wrap: wrap; gap: 16px; align-items: center; justify-content: space-between;">
            <div style="display: flex; gap: 12px; flex-wrap: wrap; align-items: center;">
                <label style="font-size: 13px; font-weight: 700; color: #94a3b8;">تصفية بحسب الحالة:</label>
                <select id="statusFilter" class="input-control" style="width: auto;" onchange="applyFilters()">
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

                <label style="font-size: 13px; font-weight: 700; color: #94a3b8; margin-right: 12px;">نوع التوصيل:</label>
                <select id="typeFilter" class="input-control" style="width: auto;" onchange="applyFilters()">
                    <option value="">جميع الأنواع</option>
                    <option value="home_delivery" {{ request('delivery_type') === 'home_delivery' ? 'selected' : '' }}>توصيل منزلي</option>
                    <option value="delivery_point" {{ request('delivery_type') === 'delivery_point' ? 'selected' : '' }}>نقطة استلام</option>
                </select>
            </div>
            <div>
                <span style="font-size: 13px; color: #94a3b8;">إجمالي المهام: <strong style="color: #f8fafc;">{{ $assignments->total() }}</strong></span>
            </div>
        </div>

        <!-- Assignments Table -->
        <div class="card-bg" style="border-radius: 12px; overflow: hidden; box-shadow: 0 10px 15px -3px rgba(0,0,0,0.3);">
            <div style="overflow-x: auto;">
                <table style="width: 100%; border-collapse: collapse; text-align: right; font-size: 13px;">
                    <thead>
                        <tr style="background-color: #0f172a; border-bottom: 2px solid #334155; color: #94a3b8; font-weight: 800;">
                            <th style="padding: 14px 16px;">رقم المهمة</th>
                            <th style="padding: 14px 16px;">رقم الطلب</th>
                            <th style="padding: 14px 16px;">نوع التوصيل</th>
                            <th style="padding: 14px 16px;">العميل والمحافظة</th>
                            <th style="padding: 14px 16px;">المندوب / النقطة</th>
                            <th style="padding: 14px 16px;">حالة المهمة</th>
                            <th style="padding: 14px 16px;">المحاولات</th>
                            <th style="padding: 14px 16px; text-align: center;">إجراءات العمليات</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($assignments as $assignment)
                            <tr class="table-row-hover" style="border-bottom: 1px solid #334155;">
                                <td style="padding: 14px 16px; font-weight: 700; color: #60a5fa;">#{{ $assignment->id }}</td>
                                <td style="padding: 14px 16px; font-weight: 700; color: #f8fafc;">{{ $assignment->order->increment_id ?? ('#'.$assignment->order_id) }}</td>
                                <td style="padding: 14px 16px;">
                                    @if($assignment->delivery_type === 'home_delivery')
                                        <span style="color: #38bdf8; font-weight: 700;">🏠 توصيل منزلي</span>
                                    @else
                                        <span style="color: #a78bfa; font-weight: 700;">📍 نقطة استلام</span>
                                    @endif
                                </td>
                                <td style="padding: 14px 16px;">
                                    <div style="font-weight: 700; color: #f8fafc;">
                                        {{ $assignment->customer_address_snapshot['first_name'] ?? '' }} {{ $assignment->customer_address_snapshot['last_name'] ?? '' }}
                                    </div>
                                    <div style="font-size: 11px; color: #94a3b8;">
                                        {{ $assignment->customer_address_snapshot['phone'] ?? '' }} | {{ $assignment->customer_address_snapshot['city'] ?? '' }} ({{ $assignment->customer_address_snapshot['state'] ?? '' }})
                                    </div>
                                </td>
                                <td style="padding: 14px 16px;">
                                    @if($assignment->deliveryBoy)
                                        <div style="font-weight: 700; color: #10b981;">🛵 {{ $assignment->deliveryBoy->name }}</div>
                                    @elseif($assignment->deliveryPoint)
                                        <div style="font-weight: 700; color: #8b5cf6;">🏢 {{ $assignment->deliveryPoint->name }}</div>
                                    @else
                                        <span style="color: #64748b; font-style: italic;">غير مسند</span>
                                    @endif
                                </td>
                                <td style="padding: 14px 16px;">
                                    @php
                                        $statusBadges = [
                                            'ready_for_assignment' => ['class' => 'badge-ready', 'label' => 'جاهز للإسناد'],
                                            'assigned' => ['class' => 'badge-assigned', 'label' => 'مسند'],
                                            'picked_up' => ['class' => 'badge-picked', 'label' => 'مستلم من المركزي'],
                                            'out_for_delivery' => ['class' => 'badge-out', 'label' => 'في مسار التوصيل'],
                                            'arrived_at_point' => ['class' => 'badge-picked', 'label' => 'وصل لنقطة التوزيع'],
                                            'delivered' => ['class' => 'badge-delivered', 'label' => 'تم التسليم بنجاح'],
                                            'delivery_failed' => ['class' => 'badge-failed', 'label' => 'فشل التسليم'],
                                            'retry_scheduled' => ['class' => 'badge-retry', 'label' => 'إعادة محاولة'],
                                            'returned_to_hayest' => ['class' => 'badge-returned', 'label' => 'مرتجع للمركزي'],
                                        ];
                                        $badge = $statusBadges[$assignment->status] ?? ['class' => 'badge-returned', 'label' => $assignment->status];
                                    @endphp
                                    <span class="badge {{ $badge['class'] }}">{{ $badge['label'] }}</span>
                                </td>
                                <td style="padding: 14px 16px;">
                                    <span style="font-weight: 700; color: {{ $assignment->attempt_count > 0 ? '#f59e0b' : '#94a3b8' }};">
                                        {{ $assignment->attempt_count }} / {{ $assignment->max_attempts }}
                                    </span>
                                </td>
                                <td style="padding: 14px 16px; text-align: center;">
                                    <div style="display: flex; gap: 6px; justify-content: center; flex-wrap: wrap;">
                                        @if(in_array($assignment->status, ['ready_for_assignment', 'assigned', 'retry_scheduled']))
                                            <button class="btn btn-primary" onclick="openAssignModal({{ $assignment->id }}, '{{ $assignment->delivery_type }}', '{{ $assignment->customer_address_snapshot['state'] ?? '' }}')">
                                                إسناد
                                            </button>
                                        @endif

                                        @if(in_array($assignment->status, ['assigned', 'retry_scheduled']))
                                            <button class="btn btn-warning" onclick="executeHandoff({{ $assignment->id }})">
                                                تسليم Handoff
                                            </button>
                                        @endif

                                        @if(in_array($assignment->status, ['delivery_failed', 'retry_scheduled', 'out_for_delivery', 'picked_up']))
                                            <button class="btn btn-danger" onclick="openReturnModal({{ $assignment->id }})">
                                                إرجاع للمركزي
                                            </button>
                                        @endif

                                        <a href="{{ route('delivery.show', $assignment->id) }}" class="btn" style="background: #334155; color: #f8fafc;">
                                            عرض
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" style="padding: 32px; text-align: center; color: #64748b;">لا توجد مهام مطابقة للشروط المحددة.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            @if($assignments->hasPages())
                <div style="padding: 16px; background-color: #0f172a; border-top: 1px solid #334155; display: flex; justify-content: center;">
                    {{ $assignments->links() }}
                </div>
            @endif
        </div>
    </main>

    <!-- Assign Modal -->
    <div id="assignModal" class="modal">
        <div class="card-bg" style="width: 100%; max-width: 480px; border-radius: 16px; padding: 24px;">
            <h3 style="margin-top: 0; font-size: 18px; font-weight: 800; color: #f8fafc; margin-bottom: 16px;">إسناد مهمة التوصيل</h3>
            <form id="assignForm" onsubmit="submitAssign(event)">
                <input type="hidden" id="modalAssignmentId">
                
                <div id="courierSelectGroup" style="margin-bottom: 16px;">
                    <label style="display: block; font-size: 13px; font-weight: 700; color: #94a3b8; margin-bottom: 8px;">اختر المندوب:</label>
                    <select id="modalCourierId" class="input-control">
                        <option value="">-- اختر مناديب التوصيل --</option>
                        @foreach($couriers as $courier)
                            <option value="{{ $courier->id }}">{{ $courier->name }} ({{ $courier->email }})</option>
                        @endforeach
                    </select>
                </div>

                <div id="pointSelectGroup" style="margin-bottom: 16px; display: none;">
                    <label style="display: block; font-size: 13px; font-weight: 700; color: #94a3b8; margin-bottom: 8px;">اختر نقطة الاستلام:</label>
                    <select id="modalPointId" class="input-control">
                        <option value="">-- اختر نقطة الاستلام النشطة --</option>
                        @foreach($points as $point)
                            <option value="{{ $point->id }}" data-state="{{ $point->governorate_code }}">
                                {{ $point->name }} ({{ $point->governorate_code }} - {{ $point->city }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <div style="display: flex; gap: 12px; justify-content: flex-end; margin-top: 24px;">
                    <button type="button" class="btn" style="background: #334155; color: #f8fafc;" onclick="closeModal('assignModal')">إلغاء</button>
                    <button type="submit" class="btn btn-primary">تأكيد الإسناد</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Return Modal -->
    <div id="returnModal" class="modal">
        <div class="card-bg" style="width: 100%; max-width: 480px; border-radius: 16px; padding: 24px;">
            <h3 style="margin-top: 0; font-size: 18px; font-weight: 800; color: #ef4444; margin-bottom: 8px;">اعتماد إرجاع الشحنة للمستودع المركزي</h3>
            <p style="font-size: 13px; color: #94a3b8; margin-bottom: 16px;">سيتم استعادة الكميات إلى رصيد `hayest_central` وإلغاء الطلب بشكل معتمد.</p>
            <form id="returnForm" onsubmit="submitReturn(event)">
                <input type="hidden" id="returnAssignmentId">
                <div style="margin-bottom: 16px;">
                    <label style="display: block; font-size: 13px; font-weight: 700; color: #94a3b8; margin-bottom: 8px;">سبب الإرجاع الإلزامي:</label>
                    <textarea id="returnReason" class="input-control" rows="3" required placeholder="اكتب سبب إرجاع الشحنة إلى المستودع المركزي..."></textarea>
                </div>
                <div style="display: flex; gap: 12px; justify-content: flex-end; margin-top: 24px;">
                    <button type="button" class="btn" style="background: #334155; color: #f8fafc;" onclick="closeModal('returnModal')">إلغاء</button>
                    <button type="submit" class="btn btn-danger">اعتماد الإرجاع واستعادة المخزون</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function applyFilters() {
            const status = document.getElementById('statusFilter').value;
            const type = document.getElementById('typeFilter').value;
            let url = new URL(window.location.href);
            if (status) url.searchParams.set('status', status); else url.searchParams.delete('status');
            if (type) url.searchParams.set('delivery_type', type); else url.searchParams.delete('delivery_type');
            window.location.href = url.toString();
        }

        function openAssignModal(id, type, state) {
            document.getElementById('modalAssignmentId').value = id;
            if (type === 'delivery_point') {
                document.getElementById('courierSelectGroup').style.display = 'none';
                document.getElementById('pointSelectGroup').style.display = 'block';
            } else {
                document.getElementById('courierSelectGroup').style.display = 'block';
                document.getElementById('pointSelectGroup').style.display = 'none';
            }
            document.getElementById('assignModal').classList.add('active');
        }

        function openReturnModal(id) {
            document.getElementById('returnAssignmentId').value = id;
            document.getElementById('returnReason').value = '';
            document.getElementById('returnModal').classList.add('active');
        }

        function closeModal(id) {
            document.getElementById(id).classList.remove('active');
        }

        async function submitAssign(e) {
            e.preventDefault();
            const id = document.getElementById('modalAssignmentId').value;
            const courierId = document.getElementById('modalCourierId').value;
            const pointId = document.getElementById('modalPointId').value;

            const payload = {};
            if (courierId) payload.delivery_boy_id = courierId;
            if (pointId) payload.delivery_point_id = pointId;

            const res = await fetch(`/admin/delivery/assignments/${id}/assign`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json'
                },
                body: JSON.stringify(payload)
            });
            const data = await res.json();
            if (data.success) {
                alert('تم إسناد المهمة بنجاح!');
                window.location.reload();
            } else {
                alert('خطأ: ' + (data.message || 'فشلت العملية'));
            }
        }

        async function executeHandoff(id) {
            if (!confirm('هل أنت متأكد من تنفيذ Handoff وتسليم الشحنة من المستودع المركزي؟')) return;
            const res = await fetch(`/admin/delivery/assignments/${id}/handoff`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json'
                }
            });
            const data = await res.json();
            if (data.success) {
                alert('تم تسليم الشحنة وتسجيل حركة المخزون بنجاح!');
                window.location.reload();
            } else {
                alert('خطأ: ' + (data.message || 'فشلت العملية'));
            }
        }

        async function submitReturn(e) {
            e.preventDefault();
            const id = document.getElementById('returnAssignmentId').value;
            const reason = document.getElementById('returnReason').value;

            const res = await fetch(`/admin/delivery/assignments/${id}/return`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ reason })
            });
            const data = await res.json();
            if (data.success) {
                alert('تم اعتماد الإرجاع واستعادة المخزون بنجاح!');
                window.location.reload();
            } else {
                alert('خطأ: ' + (data.message || 'فشلت العملية'));
            }
        }
    </script>
</body>
</html>
