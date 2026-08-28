<?php

namespace Webkul\Admin\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class DetailedCustomerReportExport implements FromCollection, ShouldAutoSize, WithHeadings, WithMapping, WithStyles
{
    /**
     * Create a new export instance.
     */
    public function __construct(
        protected Collection $records,
        protected bool $includeOrders = false
    ) {}

    /**
     * Return collection of records to be exported.
     */
    public function collection(): Collection
    {
        if (! $this->includeOrders) {
            return $this->records;
        }

        $flattened = collect();

        foreach ($this->records as $customer) {
            $flattened->push($customer);

            if (! empty($customer->orders_list) && count($customer->orders_list) > 0) {
                foreach ($customer->orders_list as $order) {
                    $orderRow = (object) [
                        'is_order_row' => true,
                        'customer_id' => '↳',
                        'name' => '  ↳ طلب #'.($order->increment_id ?? $order->id),
                        'email' => $customer->email ?? '-',
                        'phone' => $customer->phone ?? '-',
                        'status_label' => $order->status_label ?? $order->status ?? 'مكتمل',
                        'segment_label' => 'طلب فرعي',
                        'group_name' => $customer->group_name ?? '-',
                        'total_orders' => '1',
                        'completed_orders' => $order->status === 'completed' ? '1' : '0',
                        'canceled_orders' => $order->status === 'canceled' ? '1' : '0',
                        'refunded_orders' => $order->status === 'refunded' ? '1' : '0',
                        'gross_sales' => $order->base_grand_total ?? 0,
                        'completed_sales' => $order->status === 'completed' ? ($order->base_grand_total ?? 0) : 0,
                        'total_invoiced' => $order->base_grand_total_invoiced ?? 0,
                        'total_refunded' => $order->base_grand_total_refunded ?? 0,
                        'net_sales' => ($order->base_grand_total ?? 0) - ($order->base_grand_total_refunded ?? 0),
                        'total_cost' => $order->calculated_cost ?? 0,
                        'total_profit' => $order->calculated_profit ?? 0,
                        'profit_margin' => $order->calculated_margin ?? 0,
                        'avg_order_value' => ($order->base_grand_total ?? 0) - ($order->base_grand_total_refunded ?? 0),
                        'items_bought' => $order->items_count ?? $order->total_qty_ordered ?? 1,
                        'first_order_date' => $order->created_at ?? '-',
                        'last_order_date' => $order->created_at ?? '-',
                        'created_at' => $order->created_at ?? '-',
                    ];

                    $flattened->push($orderRow);
                }
            }
        }

        return $flattened;
    }

    /**
     * Header row.
     */
    public function headings(): array
    {
        return [
            'معرف العميل (ID)',
            'اسم العميل',
            'البريد الإلكتروني',
            'رقم الهاتف',
            'حالة الحساب',
            'تصنيف العميل',
            'مجموعة العميل',
            'إجمالي الطلبات',
            'الطلبات المكتملة',
            'الطلبات الملغاة',
            'الطلبات المرتجعة',
            'إجمالي المبيعات ($)',
            'المبيعات المكتملة ($)',
            'إجمالي المدفوعات ($)',
            'إجمالي المبالغ المستردة ($)',
            'صافي المبيعات ($)',
            'تكلفة المنتجات ($)',
            'إجمالي الربح ($)',
            'هامش الربح (%)',
            'متوسط قيمة الطلب ($)',
            'إجمالي القطع المشتراة',
            'تاريخ أول طلب',
            'تاريخ آخر طلب',
            'تاريخ التسجيل',
        ];
    }

    /**
     * Map each row into columns.
     */
    public function map($row): array
    {
        if (! empty($row->is_order_row)) {
            return [
                $row->customer_id ?? '↳',
                $row->name ?? '',
                $row->email ?? '-',
                $row->phone ?? '-',
                $row->status_label ?? 'مكتمل',
                $row->segment_label ?? 'طلب فرعي',
                $row->group_name ?? '-',
                $row->total_orders ?? '1',
                $row->completed_orders ?? '-',
                $row->canceled_orders ?? '-',
                $row->refunded_orders ?? '-',
                number_format((float) ($row->gross_sales ?? 0), 2, '.', ''),
                number_format((float) ($row->completed_sales ?? 0), 2, '.', ''),
                number_format((float) ($row->total_invoiced ?? 0), 2, '.', ''),
                number_format((float) ($row->total_refunded ?? 0), 2, '.', ''),
                number_format((float) ($row->net_sales ?? 0), 2, '.', ''),
                number_format((float) ($row->total_cost ?? 0), 2, '.', ''),
                number_format((float) ($row->total_profit ?? 0), 2, '.', ''),
                is_numeric($row->profit_margin ?? null) ? number_format((float) $row->profit_margin, 2, '.', '').'%' : '-',
                is_numeric($row->avg_order_value ?? null) ? number_format((float) $row->avg_order_value, 2, '.', '') : '-',
                $row->items_bought ?? '1',
                $row->first_order_date ?? '-',
                $row->last_order_date ?? '-',
                $row->created_at ?? '-',
            ];
        }

        $grossSales = is_numeric($row->gross_sales ?? null) ? (float) $row->gross_sales : 0;
        $completedSales = is_numeric($row->completed_sales ?? null) ? (float) $row->completed_sales : 0;
        $invoiced = is_numeric($row->total_invoiced ?? null) ? (float) $row->total_invoiced : 0;
        $refunded = is_numeric($row->total_refunded ?? null) ? (float) $row->total_refunded : 0;
        $netSales = is_numeric($row->net_sales ?? null) ? (float) $row->net_sales : 0;
        $cost = is_numeric($row->total_cost ?? null) ? (float) $row->total_cost : 0;
        $profit = is_numeric($row->total_profit ?? null) ? (float) $row->total_profit : 0;
        $margin = is_numeric($row->profit_margin ?? null) ? (float) $row->profit_margin : 0;
        $aov = is_numeric($row->avg_order_value ?? null) ? (float) $row->avg_order_value : 0;

        return [
            $row->customer_id ?? '',
            $row->name ?? '',
            $row->email ?? '',
            $row->phone ?? '—',
            $row->status_label ?? 'نشط',
            $row->segment_label ?? 'عادي',
            $row->group_name ?? 'عام',
            $row->total_orders ?? 0,
            $row->completed_orders ?? 0,
            $row->canceled_orders ?? 0,
            $row->refunded_orders ?? 0,
            number_format($grossSales, 2, '.', ''),
            number_format($completedSales, 2, '.', ''),
            number_format($invoiced, 2, '.', ''),
            number_format($refunded, 2, '.', ''),
            number_format($netSales, 2, '.', ''),
            number_format($cost, 2, '.', ''),
            number_format($profit, 2, '.', ''),
            number_format($margin, 2, '.', '').'%',
            number_format($aov, 2, '.', ''),
            $row->total_items_bought ?? 0,
            $row->first_order_date ?? '—',
            $row->last_order_date ?? '—',
            $row->created_at ?? '—',
        ];
    }

    /**
     * Styles for the spreadsheet.
     */
    public function styles(Worksheet $sheet): array
    {
        return [
            1 => [
                'font' => [
                    'bold' => true,
                    'color' => ['argb' => 'FFFFFFFF'],
                    'size' => 11,
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['argb' => 'FF1F2937'], // Dark Header
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
            ],
        ];
    }
}
