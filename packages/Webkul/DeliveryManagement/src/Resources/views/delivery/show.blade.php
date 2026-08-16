@extends('delivery::delivery.layout')

@section('title', 'تفاصيل مهمة التوصيل #' . ($assignment->order->increment_id ?? $assignment->order_id))

@section('content')
<div style="margin-bottom: 1rem;">
    <a href="{{ route('delivery.index') }}" style="color: var(--primary); text-decoration: none; font-size: 0.9rem; font-weight: 600;">
        ❮ العودة لقائمة المهام
    </a>
</div>

<div class="card">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem; border-bottom: 1px solid var(--border-color); padding-bottom: 0.75rem;">
        <div>
            <h2 style="font-size: 1.2rem; font-weight: 700;">طلب #{{ $assignment->order->increment_id ?? $assignment->order_id }}</h2>
            <div style="font-size: 0.8rem; color: var(--text-muted);">
                {{ $assignment->delivery_type === 'delivery_point' ? '📍 استلام من نقطة توزيع' : '🏠 توصيل منزلي' }}
            </div>
        </div>

        @php
            $badgeClass = match($assignment->status) {
                'ready_for_assignment' => 'badge-ready',
                'assigned' => 'badge-assigned',
                'picked_up' => 'badge-picked',
                'out_for_delivery' => 'badge-out',
                'delivered' => 'badge-delivered',
                'delivery_failed' => 'badge-failed',
                'retry_scheduled' => 'badge-retry',
                'returned_to_hayest' => 'badge-returned',
                default => 'badge-ready'
            };
            $statusText = match($assignment->status) {
                'ready_for_assignment' => 'جاهز للإسناد',
                'assigned' => 'مسند للمندوب',
                'picked_up' => 'مستلم من المستودع',
                'out_for_delivery' => 'في الطريق للعميل',
                'arrived_at_point' => 'وصل للنقطة',
                'delivered' => 'تم التسليم',
                'delivery_failed' => 'تعذر التسليم',
                'retry_scheduled' => 'معاد جدولته',
                'returned_to_hayest' => 'مرتجع للمستودع',
                default => $assignment->status
            };
        @endphp
        <span class="badge {{ $badgeClass }}">{{ $statusText }}</span>
    </div>

    <!-- Customer Information -->
    <div style="margin-bottom: 1.25rem;">
        <h3 style="font-size: 0.95rem; font-weight: 700; color: var(--text-muted); margin-bottom: 0.5rem;">معلومات العميل والتسليم</h3>
        <div style="background: #f8fafc; border-radius: 0.75rem; padding: 1rem; font-size: 0.9rem;">
            <div style="margin-bottom: 0.5rem;">
                <strong>الاسم:</strong> {{ $assignment->customer_address_snapshot['first_name'] ?? '' }} {{ $assignment->customer_address_snapshot['last_name'] ?? '' }}
            </div>
            <div style="margin-bottom: 0.5rem;">
                <strong>الهاتف:</strong>
                <a href="tel:{{ $assignment->customer_address_snapshot['phone'] ?? '' }}" style="color: var(--primary); font-weight: 700; text-decoration: none;">
                    {{ $assignment->customer_address_snapshot['phone'] ?? 'غير متوفر' }} 📞
                </a>
            </div>
            <div>
                <strong>العنوان:</strong> {{ $assignment->customer_address_snapshot['address'] ?? '' }}، {{ $assignment->customer_address_snapshot['city'] ?? '' }}
            </div>
        </div>
    </div>

    @if($assignment->delivery_type === 'delivery_point' && $assignment->delivery_point_snapshot)
        <div style="margin-bottom: 1.25rem;">
            <h3 style="font-size: 0.95rem; font-weight: 700; color: var(--text-muted); margin-bottom: 0.5rem;">نقطة الاستلام</h3>
            <div style="background: #eff6ff; border-radius: 0.75rem; padding: 1rem; font-size: 0.9rem; border: 1px solid #bfdbfe;">
                <div style="font-weight: 700; color: #1e40af; margin-bottom: 0.25rem;">
                    {{ $assignment->delivery_point_snapshot['name_ar'] ?? $assignment->delivery_point_snapshot['name'] ?? '' }}
                </div>
                <div style="color: #1e3a8a; font-size: 0.85rem;">
                    📍 {{ $assignment->delivery_point_snapshot['address'] ?? '' }}
                </div>
            </div>
        </div>
    @endif

    <!-- Payment & Collection -->
    @php
        $isCod = strtolower((string) ($assignment->order->payment?->method ?? '')) === 'cashondelivery';
    @endphp
    <div style="margin-bottom: 1.5rem;">
        <h3 style="font-size: 0.95rem; font-weight: 700; color: var(--text-muted); margin-bottom: 0.5rem;">البيانات المالية والتحصيل</h3>
        <div style="background: {{ $isCod ? '#fef3c7' : '#dcfce7' }}; border-radius: 0.75rem; padding: 1rem; border: 1px solid {{ $isCod ? '#fde68a' : '#bbf7d0' }};">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <span style="font-weight: 600; color: {{ $isCod ? '#92400e' : '#166534' }};">
                    {{ $isCod ? '💵 مطلوب تحصيل نقدي (COD):' : '✅ تم الدفع مسبقاً الإلكتروني' }}
                </span>
                @if($isCod)
                    <span style="font-size: 1.2rem; font-weight: 800; color: #92400e;">
                        {{ number_format($assignment->order->grand_total ?? 0, 0) }} YER
                    </span>
                @endif
            </div>
        </div>
    </div>

    <!-- Actions Section -->
    <div style="display: flex; flex-direction: column; gap: 0.75rem;">
        @if(in_array($assignment->status, ['assigned', 'picked_up', 'retry_scheduled']))
            @if($assignment->delivery_type === 'home_delivery')
                <button class="btn btn-primary" onclick="actionStartDelivery({{ $assignment->id }})">
                    🚀 بدء مسار التوصيل للعميل
                </button>
            @elseif($assignment->delivery_type === 'delivery_point' && $assignment->status !== 'arrived_at_point')
                <button class="btn btn-primary" onclick="actionConfirmArrival({{ $assignment->id }})">
                    📍 تأكيد وصول الشحنة لنقطة التوزيع
                </button>
            @endif
        @endif

        @if(in_array($assignment->status, ['out_for_delivery', 'arrived_at_point', 'picked_up']))
            <button class="btn btn-success" onclick="actionConfirmDelivery({{ $assignment->id }}, {{ $isCod ? ($assignment->order->grand_total ?? 0) : 'null' }})">
                ✅ تأكيد تسليم العميل {{ $isCod ? 'وتحصيل المبلغ' : '' }}
            </button>

            <button class="btn btn-danger" onclick="openFailureModal()">
                ⚠️ تسجيل تعذر التسليم
            </button>
        @endif
    </div>
</div>

<!-- Attempt History -->
@if($assignment->attemptLogs->isNotEmpty())
    <div class="card">
        <h3 style="font-size: 0.95rem; font-weight: 700; color: var(--text-muted); margin-bottom: 0.75rem;">سجل المحاولات السابقة</h3>
        @foreach($assignment->attemptLogs as $log)
            <div style="border-bottom: 1px solid var(--border-color); padding: 0.5rem 0; font-size: 0.85rem;">
                <div style="display: flex; justify-content: space-between; margin-bottom: 0.25rem;">
                    <strong>محاولة #{{ $log->attempt_number }}</strong>
                    <span style="color: var(--text-muted);">{{ $log->attempted_at?->format('Y-m-d H:i') }}</span>
                </div>
                <div style="color: var(--danger);">
                    سبب التعذر: {{ $log->failure_reason }}
                </div>
            </div>
        @endforeach
    </div>
@endif

<!-- Failure Modal -->
<div id="failureModal" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 100; align-items: center; justify-content: center; padding: 1rem;">
    <div style="background: #ffffff; border-radius: 1rem; max-width: 480px; width: 100%; padding: 1.5rem;">
        <h3 style="font-size: 1.1rem; font-weight: 700; margin-bottom: 1rem;">تسجيل سبب تعذر التسليم</h3>
        <textarea id="failureReason" rows="3" style="width: 100%; border-radius: 0.5rem; border: 1px solid var(--border-color); padding: 0.75rem; font-size: 0.9rem; margin-bottom: 1rem;" placeholder="اكتب سبب تعذر التسليم (مثل: العميل لا يجيب، طلب التأجيل...)"></textarea>
        
        <div style="display: flex; gap: 0.5rem;">
            <button class="btn btn-danger" onclick="submitFailure({{ $assignment->id }})">تأكيد التعذر</button>
            <button class="btn btn-outline" onclick="closeFailureModal()">إلغاء</button>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script>
    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

    function actionStartDelivery(id) {
        if (!confirm('هل تريد بدء مسار التوصيل للعميل الآن؟')) return;
        fetch(`/delivery/assignments/${id}/start`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken }
        }).then(r => r.json()).then(data => {
            if (data.success) { location.reload(); } else { alert(data.message); }
        });
    }

    function actionConfirmArrival(id) {
        if (!confirm('هل تم استلام الشحنة وتأكيد وصولها لنقطة التوزيع؟')) return;
        fetch(`/delivery/assignments/${id}/arrived-point`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken }
        }).then(r => r.json()).then(data => {
            if (data.success) { location.reload(); } else { alert(data.message); }
        });
    }

    function actionConfirmDelivery(id, codAmount) {
        let msg = 'هل تم تسليم الشحنة للعميل بنجاح؟';
        if (codAmount) {
            msg = `تأكيد استلام المبلغ النقدي (${codAmount} YER) وتسليم الطلب للعميل؟`;
        }
        if (!confirm(msg)) return;

        fetch(`/delivery/assignments/${id}/delivered`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
            body: JSON.stringify({ collected_amount: codAmount })
        }).then(r => r.json()).then(data => {
            if (data.success) { location.reload(); } else { alert(data.message); }
        });
    }

    function openFailureModal() {
        document.getElementById('failureModal').style.display = 'flex';
    }

    function closeFailureModal() {
        document.getElementById('failureModal').style.display = 'none';
    }

    function submitFailure(id) {
        const reason = document.getElementById('failureReason').value.trim();
        if (!reason) {
            alert('يرجى كتابة سبب التعذر.');
            return;
        }

        fetch(`/delivery/assignments/${id}/fail`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
            body: JSON.stringify({ reason: reason, schedule_retry: true })
        }).then(r => r.json()).then(data => {
            if (data.success) { location.reload(); } else { alert(data.message); }
        });
    }
</script>
@endsection
