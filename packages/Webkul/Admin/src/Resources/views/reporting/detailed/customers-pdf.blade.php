<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>تقرير العملاء التفصيلي — هايست</title>
    <style>
        @page {
            size: A4 landscape;
            margin: 6mm 6mm;
        }
        body {
            font-family: "DejaVu Sans", sans-serif;
            font-size: 7pt;
            direction: rtl;
            color: #0f172a;
            background: #ffffff;
            margin: 0;
            padding: 0;
        }
        .header-table {
            width: 100%;
            border-bottom: 2px solid #0f172a;
            padding-bottom: 4px;
            margin-bottom: 6px;
            border-collapse: collapse;
        }
        .header-title {
            font-size: 12pt;
            font-weight: bold;
            color: #0f172a;
            margin: 0 0 2px 0;
        }
        .header-subtitle {
            font-size: 7pt;
            color: #475569;
            margin: 0;
        }
        .filters-box {
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            padding: 3px 5px;
            font-size: 6.5pt;
            margin-bottom: 6px;
            color: #334155;
        }
        .data-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 6.5pt;
        }
        .data-table th {
            background-color: #f1f5f9;
            color: #0f172a;
            font-weight: bold;
            border: 1px solid #94a3b8;
            padding: 4px 3px;
            text-align: right;
        }
        .data-table td {
            border: 1px solid #cbd5e1;
            padding: 3px 3px;
            text-align: right;
            color: #1e293b;
            line-height: 1.2;
        }
        .data-table tr:nth-child(even) {
            background-color: #f8fafc;
        }
        .order-row {
            background-color: #eff6ff !important;
        }
        .text-center {
            text-align: center !important;
        }
        .text-right {
            text-align: right !important;
        }
        .font-bold {
            font-weight: bold !important;
        }
        .email-cell {
            font-size: 6pt;
            direction: ltr;
            text-align: right;
        }
    </style>
</head>
<body>
    <table class="header-table">
        <tr>
            @if(!empty($logoUrl))
            <td style="border:none; padding:0; text-align:left; vertical-align:middle; width: 140px;">
                <img src="{{ $logoUrl }}" alt="هايست" style="height: 32px; max-height: 32px; width: auto; display: inline-block;">
            </td>
            @endif
            <td style="border:none; padding:0; vertical-align:middle; text-align:right;">
                <h1 class="header-title">👥 تقرير العملاء التفصيلي والمالي — هايست</h1>
                <p class="header-subtitle">تاريخ ووقت استخراج التقرير: {{ $generatedAt }}</p>
            </td>
        </tr>
    </table>

    @if(!empty($activeFilterLabels) && count($activeFilterLabels) > 0)
        <div class="filters-box">
            <strong>الفلاتر المطبقة:</strong> {{ implode(' | ', $activeFilterLabels) }}
        </div>
    @endif

    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 8%;" class="text-right">متوسط الطلب</th>
                <th style="width: 5%;" class="text-center">الهامش</th>
                <th style="width: 6%;" class="text-right">الربح</th>
                <th style="width: 6%;" class="text-right">التكلفة</th>
                <th style="width: 7%;" class="text-right">الصافي</th>
                <th style="width: 7%;" class="text-right">المبيعات</th>
                <th style="width: 6%;" class="text-center">الطلبات</th>
                <th style="width: 6%;" class="text-center">الحالة</th>
                <th style="width: 8%;" class="text-center">التصنيف</th>
                <th style="width: 8%;">الهاتف</th>
                <th style="width: 16%;">البريد الإلكتروني</th>
                <th style="width: 13%;">العميل</th>
                <th style="width: 4%;" class="text-center">ID</th>
            </tr>
        </thead>
        <tbody>
            @forelse($records as $customer)
                <tr>
                    <td class="text-right">${{ number_format($customer->avg_order_value, 2) }}</td>
                    <td class="text-center font-bold">{{ $customer->profit_margin }}%</td>
                    <td class="text-right font-bold">${{ number_format($customer->total_profit, 2) }}</td>
                    <td class="text-right">${{ number_format($customer->total_cost, 2) }}</td>
                    <td class="text-right font-bold">${{ number_format($customer->net_sales, 2) }}</td>
                    <td class="text-right">${{ number_format($customer->gross_sales, 2) }}</td>
                    <td class="text-center font-bold">{{ $customer->total_orders }} ({{ $customer->completed_orders }})</td>
                    <td class="text-center">{{ $customer->status_label }}</td>
                    <td class="text-center">{{ $customer->segment_label }}</td>
                    <td>{{ $customer->phone }}</td>
                    <td class="email-cell">{{ $customer->email }}</td>
                    <td class="font-bold">{{ $customer->name }}</td>
                    <td class="text-center font-bold">#{{ $customer->customer_id }}</td>
                </tr>

                @if($includeOrders && !empty($customer->orders_list) && count($customer->orders_list) > 0)
                    @foreach($customer->orders_list as $ord)
                        <tr class="order-row">
                            <td class="text-right" style="color:#475569;">
                                ${{ number_format((float) (($ord->base_grand_total ?? 0) - ($ord->base_grand_total_refunded ?? 0)), 2) }}
                            </td>
                            <td class="text-center font-bold" style="color:#059669;">
                                {{ $ord->calculated_margin ?? 0 }}%
                            </td>
                            <td class="text-right font-bold" style="color:#0f172a;">
                                ${{ number_format((float) ($ord->calculated_profit ?? 0), 2) }}
                            </td>
                            <td class="text-right" style="color:#b45309;">
                                ${{ number_format((float) ($ord->calculated_cost ?? 0), 2) }}
                            </td>
                            <td class="text-right font-bold" style="color:#059669;">
                                ${{ number_format((float) (($ord->base_grand_total ?? 0) - ($ord->base_grand_total_refunded ?? 0)), 2) }}
                            </td>
                            <td class="text-right" style="color:#475569;">
                                ${{ number_format((float) ($ord->base_grand_total ?? 0), 2) }}
                            </td>
                            <td class="text-center" style="color:#64748b;">1</td>
                            <td class="text-center font-bold" style="color:#2563eb;">{{ $ord->status_label ?? $ord->status ?? 'مكتمل' }}</td>
                            <td class="text-center" style="color:#64748b;">طلب متجر</td>
                            <td class="text-center" style="color:#475569;">{{ $ord->items_count ?? $ord->total_qty_ordered ?? 1 }} قطع</td>
                            <td class="email-cell" style="color:#64748b;">{{ $customer->email }}</td>
                            <td class="font-bold" style="padding-right: 8px; color:#1e3a8a;">↳ طلب #{{ $ord->increment_id ?? $ord->id }}</td>
                            <td class="text-center" style="color:#64748b;">↳</td>
                        </tr>
                    @endforeach
                @endif
            @empty
                <tr>
                    <td colspan="13" class="text-center" style="padding: 15px;">
                        لا توجد بيانات مطابقة لخيارات الفلترة المحددة.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
