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

class DetailedProductReportExport implements FromCollection, ShouldAutoSize, WithHeadings, WithMapping, WithStyles
{
    /**
     * Create a new export instance.
     */
    public function __construct(
        protected Collection $records,
        protected bool $includeVariants = true
    ) {}

    /**
     * Return collection of records to be exported.
     */
    public function collection(): Collection
    {
        $flattened = collect();

        foreach ($this->records as $product) {
            $flattened->push($product);

            if ($this->includeVariants && ! empty($product->variants) && count($product->variants) > 0) {
                foreach ($product->variants as $variant) {
                    $variantRow = (object) [
                        'is_variant' => true,
                        'product_id' => $variant->id ?? $variant->product_id ?? '',
                        'sku' => $variant->sku ?? '',
                        'name' => '  ↳ '.($variant->name ?? $variant->attribute_summary ?? 'Variant #'.$variant->id),
                        'main_category' => $product->main_category ?? '-',
                        'type' => 'متغير (Variant)',
                        'source' => $product->source ?? '-',
                        'supplier' => $product->supplier ?? '-',
                        'variants_count' => '-',
                        'cost_price' => $variant->cost_price ?? 0,
                        'selling_price' => $variant->selling_price ?? 0,
                        'stock_quantity' => $variant->stock_quantity ?? 0,
                        'status' => ! empty($variant->status) ? 'نشط' : 'معطل',
                    ];

                    $flattened->push($variantRow);
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
            'معرف المنتج (ID)',
            'رمز المنتج (SKU)',
            'اسم المنتج',
            'الفئة الرئيسية',
            'نوع المنتج',
            'مصدر المنتج',
            'المورد / المتجر',
            'عدد المتغيرات',
            'سعر التكلفة ($)',
            'سعر البيع ($)',
            'المخزون المتوفر',
            'حالة المنتج',
        ];
    }

    /**
     * Map each row into columns.
     */
    public function map($row): array
    {
        $cost = is_numeric($row->cost_price ?? null) ? (float) $row->cost_price : 0;
        $price = is_numeric($row->selling_price ?? null) ? (float) $row->selling_price : 0;
        $profit = is_numeric($row->unit_profit ?? null) ? (float) $row->unit_profit : ($price - $cost);
        $margin = is_numeric($row->profit_margin ?? null) ? (float) $row->profit_margin : ($price > 0 ? round(($profit / $price) * 100, 2) : 0);
        $qty = is_numeric($row->stock_quantity ?? null) ? (int) $row->stock_quantity : 0;
        $stockVal = is_numeric($row->stock_value ?? null) ? (float) $row->stock_value : ($qty * $cost);

        $typeLabel = match ($row->type ?? '') {
            'simple' => 'منتج بسيط (Simple)',
            'configurable' => 'منتج بمتغيرات (Configurable)',
            'متغير (Variant)' => 'متغير فرعي (Variant)',
            default => $row->type ?? 'عادي',
        };

        $sourceLabel = match ($row->source ?? '') {
            'aliexpress', 'imported', 'مستورد من AliExpress' => 'مستورد من AliExpress',
            'internal', 'داخلي', 'منتج داخلي' => 'منتج داخلي',
            default => $row->source ?? 'داخلي',
        };

        $statusLabel = is_string($row->status ?? null)
            ? $row->status
            : (! empty($row->status) ? 'نشط' : 'معطل');

        $isParentConfigurable = empty($row->is_variant) && (($row->type ?? '') === 'configurable' || (! empty($row->variants_count) && $row->variants_count > 0));

        $costDisplay = $isParentConfigurable ? '-' : number_format($cost, 2, '.', '');
        $priceDisplay = $isParentConfigurable ? '-' : number_format($price, 2, '.', '');
        $qtyDisplay = $isParentConfigurable ? '-' : $qty;

        return [
            $row->product_id ?? '',
            $row->sku ?? '',
            $row->name ?? '',
            $row->main_category ?? '-',
            $typeLabel,
            $sourceLabel,
            $row->supplier ?? 'المتجر الداخلي',
            $row->variants_count ?? ($row->is_variant ?? false ? '-' : '0'),
            $costDisplay,
            $priceDisplay,
            $qtyDisplay,
            $statusLabel,
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
                    'startColor' => ['argb' => 'FF1F2937'], // Dark Gray Header
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
            ],
        ];
    }
}
