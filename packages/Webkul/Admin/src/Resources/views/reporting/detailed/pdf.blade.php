<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>تقرير المنتجات التفصيلي — هايست</title>
    <style>
        @page {
            size: A4 landscape;
            margin: 6mm 6mm;
        }
        body {
            font-family: "DejaVu Sans", sans-serif;
            font-size: 7pt;
            direction: rtl;
            color: #111827;
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
        .variant-row {
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
        .sku-cell {
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
                <h1 class="header-title">📊 تقرير المنتجات التفصيلي والمخزون — هايست</h1>
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
                <th style="width: 5%;" class="text-center">الحالة</th>
                <th style="width: 7%;" class="text-right">سعر البيع</th>
                <th style="width: 7%;" class="text-right">التكلفة</th>
                <th style="width: 5%;" class="text-center">المخزون</th>
                <th style="width: 5%;" class="text-center">المتغيرات</th>
                <th style="width: 10%;">المورد</th>
                <th style="width: 7%;" class="text-center">المصدر</th>
                <th style="width: 7%;" class="text-center">النوع</th>
                <th style="width: 12%;">الفئة الرئيسية</th>
                <th style="width: 20%;">اسم المنتج</th>
                <th style="width: 12%;" class="text-center">SKU</th>
                <th style="width: 4%;" class="text-center">ID</th>
            </tr>
        </thead>
        <tbody>
            @forelse($records as $product)
                <tr>
                    <td class="text-center">{{ $product->status ? 'نشط' : 'معطل' }}</td>
                    <td class="text-right font-bold">${{ number_format($product->selling_price, 2) }}</td>
                    <td class="text-right">${{ number_format($product->cost_price, 2) }}</td>
                    <td class="text-center font-bold">{{ $product->stock_quantity ?? 0 }}</td>
                    <td class="text-center">{{ $product->variants_count > 0 ? $product->variants_count : '—' }}</td>
                    <td>{{ $product->supplier }}</td>
                    <td class="text-center">{{ $product->source === 'aliexpress' ? 'AliExpress' : 'داخلي' }}</td>
                    <td class="text-center">{{ $product->type === 'configurable' ? 'بمتغيرات' : 'بسيط' }}</td>
                    <td>{{ $product->main_category }}</td>
                    <td class="font-bold">{{ $product->name }}</td>
                    <td class="sku-cell">{{ $product->sku }}</td>
                    <td class="text-center font-bold">#{{ $product->product_id }}</td>
                </tr>

                @if($includeVariants && !empty($product->variants_list) && count($product->variants_list) > 0)
                    @foreach($product->variants_list as $variant)
                        <tr class="variant-row">
                            <td class="text-center" style="color:#64748b;">{{ $variant->status ? 'نشط' : 'معطل' }}</td>
                            <td class="text-right font-bold" style="color:#1e3a8a;">${{ number_format($variant->selling_price, 2) }}</td>
                            <td class="text-right" style="color:#64748b;">${{ number_format($variant->cost_price, 2) }}</td>
                            <td class="text-center font-bold" style="color:#0f172a;">{{ $variant->stock_quantity ?? 0 }}</td>
                            <td class="text-center" style="color:#64748b;">—</td>
                            <td style="color:#64748b;">{{ $product->supplier }}</td>
                            <td class="text-center" style="color:#64748b;">{{ $product->source === 'aliexpress' ? 'AliExpress' : 'داخلي' }}</td>
                            <td class="text-center" style="color:#64748b;">متغير</td>
                            <td style="color:#64748b;">{{ $product->main_category }}</td>
                            <td style="color:#1e3a8a; padding-right: 8px;">↳ {{ $variant->name }}</td>
                            <td class="sku-cell" style="color:#1e3a8a;">{{ $variant->sku }}</td>
                            <td class="text-center" style="color:#64748b;">↳</td>
                        </tr>
                    @endforeach
                @endif
            @empty
                <tr>
                    <td colspan="12" class="text-center" style="padding: 15px;">
                        لا توجد بيانات مطابقة لخيارات الفلترة المحددة.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
