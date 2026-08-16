@extends('delivery::delivery.layout')

@section('title', 'قائمة مهام التوصيل')

@section('content')
<div class="nav-tabs">
    <a href="{{ route('delivery.index') }}" class="nav-tab {{ !request('status') ? 'active' : '' }}">الكل</a>
    <a href="{{ route('delivery.index', ['status' => 'assigned']) }}" class="nav-tab {{ request('status') === 'assigned' ? 'active' : '' }}">مسندة</a>
    <a href="{{ route('delivery.index', ['status' => 'picked_up']) }}" class="nav-tab {{ request('status') === 'picked_up' ? 'active' : '' }}">تم الاستلام</a>
    <a href="{{ route('delivery.index', ['status' => 'out_for_delivery']) }}" class="nav-tab {{ request('status') === 'out_for_delivery' ? 'active' : '' }}">في الطريق</a>
    <a href="{{ route('delivery.index', ['status' => 'delivered']) }}" class="nav-tab {{ request('status') === 'delivered' ? 'active' : '' }}">المكتملة</a>
</div>

@if($assignments->isEmpty())
    <div class="card" style="text-align: center; padding: 3rem 1.5rem; color: var(--text-muted);">
        <div style="font-size: 3rem; margin-bottom: 1rem;">📦</div>
        <h3 style="font-size: 1.1rem; color: var(--text-main); margin-bottom: 0.5rem;">لا توجد مهام تسليم حالياً</h3>
        <p style="font-size: 0.9rem;">سيتم إشعارك فور إسناد طلبات جديدة إليك.</p>
    </div>
@else
    @foreach($assignments as $assignment)
        <div class="card" onclick="window.location='{{ route('delivery.show', $assignment->id) }}'" style="cursor: pointer;">
            <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 0.75rem;">
                <div>
                    <span style="font-weight: 700; font-size: 1.05rem;">طلب #{{ $assignment->order->increment_id ?? $assignment->order_id }}</span>
                    <div style="font-size: 0.8rem; color: var(--text-muted); margin-top: 0.15rem;">
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

            <div style="background: #f8fafc; border-radius: 0.5rem; padding: 0.75rem; font-size: 0.85rem; margin-bottom: 0.75rem;">
                <div style="font-weight: 600; color: var(--text-main); margin-bottom: 0.25rem;">
                    👤 {{ $assignment->customer_address_snapshot['first_name'] ?? 'العميل' }} {{ $assignment->customer_address_snapshot['last_name'] ?? '' }}
                </div>
                <div style="color: var(--text-muted);">
                    📍 {{ $assignment->customer_address_snapshot['address'] ?? 'العنوان' }}، {{ $assignment->customer_address_snapshot['city'] ?? '' }}
                </div>
            </div>

            <div style="display: flex; justify-content: space-between; align-items: center; font-size: 0.85rem; color: var(--text-muted);">
                <span>المحاولات: {{ $assignment->attempt_count }} / {{ $assignment->max_attempts }}</span>
                <span style="font-weight: 700; color: var(--primary);">عرض التفاصيل ❯</span>
            </div>
        </div>
    @endforeach

    <div style="margin-top: 1rem;">
        {{ $assignments->links() }}
    </div>
@endif
@endsection
