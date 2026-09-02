<?php

namespace Webkul\Procurement\DataGrids;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Webkul\DataGrid\DataGrid;

class ProcurementDemandDataGrid extends DataGrid
{
    protected $primaryColumn = 'demand_id';

    public function prepareQueryBuilder(): Builder
    {
        $queryBuilder = DB::table('procurement_demands')
            ->leftJoin('orders', 'procurement_demands.order_id', '=', 'orders.id')
            ->leftJoin('order_items', 'procurement_demands.order_item_id', '=', 'order_items.id')
            ->select(
                'procurement_demands.id as demand_id',
                'orders.increment_id as order_increment_id',
                'procurement_demands.order_id',
                'procurement_demands.order_item_id',
                'procurement_demands.product_id',
                'procurement_demands.variant_product_id',
                'procurement_demands.provider',
                'procurement_demands.supplier_store_name',
                'procurement_demands.supplier_product_id',
                'procurement_demands.supplier_sku_id',
                'procurement_demands.qty_requested',
                'procurement_demands.qty_covered_by_local',
                'procurement_demands.qty_required_external',
                'procurement_demands.qty_batched',
                'procurement_demands.qty_received_good',
                'procurement_demands.state',
                'procurement_demands.source_snapshot',
                'procurement_demands.supplier_currency_code',
                'procurement_demands.created_at',
                'order_items.name as order_item_name',
                'order_items.additional as order_item_additional',
                'order_items.price as customer_selling_price',
                DB::raw('COALESCE(order_items.name, (SELECT pf.name FROM product_flat pf WHERE pf.product_id = procurement_demands.product_id LIMIT 1)) as product_name'),
                DB::raw("(
                    SELECT pav.float_value 
                    FROM product_attribute_values pav 
                    JOIN attributes a ON a.id = pav.attribute_id 
                    WHERE a.code = 'cost' 
                      AND pav.product_id = COALESCE(procurement_demands.variant_product_id, procurement_demands.product_id) 
                    LIMIT 1
                ) as system_cost"),
                DB::raw("COALESCE((
                    SELECT pav.float_value 
                    FROM product_attribute_values pav 
                    JOIN attributes a ON a.id = pav.attribute_id 
                    WHERE a.code = 'cost' 
                      AND pav.product_id = COALESCE(procurement_demands.variant_product_id, procurement_demands.product_id) 
                    LIMIT 1
                ), JSON_UNQUOTE(JSON_EXTRACT(procurement_demands.source_snapshot, '$.unit_cost')), 0) as raw_system_cost"),
                DB::raw("JSON_UNQUOTE(JSON_EXTRACT(procurement_demands.source_snapshot, '$.unit_cost')) as aliexpress_cost"),
                DB::raw("(
                    SELECT api.base_shipping_cost 
                    FROM aliexpress_product_imports api 
                    WHERE api.id = JSON_UNQUOTE(JSON_EXTRACT(procurement_demands.source_snapshot, '$.import_id'))
                       OR api.aliexpress_product_id = procurement_demands.supplier_product_id
                       OR api.product_id = procurement_demands.product_id
                       OR api.product_id = (SELECT p.parent_id FROM products p WHERE p.id = COALESCE(procurement_demands.variant_product_id, procurement_demands.product_id))
                    ORDER BY api.id DESC
                    LIMIT 1
                ) as shipping_cost"),
                DB::raw("(
                    SELECT api.shipping_company 
                    FROM aliexpress_product_imports api 
                    WHERE api.id = JSON_UNQUOTE(JSON_EXTRACT(procurement_demands.source_snapshot, '$.import_id'))
                       OR api.aliexpress_product_id = procurement_demands.supplier_product_id
                       OR api.product_id = procurement_demands.product_id
                       OR api.product_id = (SELECT p.parent_id FROM products p WHERE p.id = COALESCE(procurement_demands.variant_product_id, procurement_demands.product_id))
                    ORDER BY api.id DESC
                    LIMIT 1
                ) as shipping_company"),
                DB::raw("(
                    SELECT api.payload_snapshot 
                    FROM aliexpress_product_imports api 
                    WHERE api.id = JSON_UNQUOTE(JSON_EXTRACT(procurement_demands.source_snapshot, '$.import_id'))
                       OR api.aliexpress_product_id = procurement_demands.supplier_product_id
                       OR api.product_id = procurement_demands.product_id
                       OR api.product_id = (SELECT p.parent_id FROM products p WHERE p.id = COALESCE(procurement_demands.variant_product_id, procurement_demands.product_id))
                    ORDER BY api.id DESC
                    LIMIT 1
                ) as import_payload_snapshot")
            );

        $this->addFilter('demand_id', 'procurement_demands.id');
        $this->addFilter('order_increment_id', 'orders.increment_id');
        $this->addFilter('state', 'procurement_demands.state');
        $this->addFilter('provider', 'procurement_demands.provider');
        $this->addFilter('supplier_store_name', 'procurement_demands.supplier_store_name');
        $this->addFilter('supplier_sku_id', 'procurement_demands.supplier_sku_id');

        $state = request()->get('state') ?? request()->get('status');
        if (! empty($state) && $state !== 'all') {
            $queryBuilder->where('procurement_demands.state', $state);
        }

        return $queryBuilder;
    }

    public function prepareColumns(): void
    {
        $this->addColumn([
            'index' => 'demand_id',
            'label' => 'ID',
            'type' => 'integer',
            'searchable' => true,
            'sortable' => true,
            'filterable' => true,
        ]);

        $this->addColumn([
            'index' => 'order_increment_id',
            'label' => trans('procurement::app.datagrid.order-id'),
            'type' => 'string',
            'searchable' => true,
            'sortable' => true,
            'filterable' => true,
        ]);

        $this->addColumn([
            'index' => 'product_name',
            'label' => trans('procurement::app.datagrid.product-name'),
            'type' => 'string',
            'searchable' => true,
            'sortable' => true,
            'filterable' => true,
            'closure' => function ($row) {
                $name = $row->product_name ?: $row->order_item_name ?: 'منتج بدون اسم';
                $additional = null;
                if (! empty($row->order_item_additional)) {
                    $additional = is_string($row->order_item_additional)
                        ? json_decode($row->order_item_additional, true)
                        : $row->order_item_additional;
                }

                $attrBadges = [];
                if (! empty($additional['attributes']) && is_array($additional['attributes'])) {
                    foreach ($additional['attributes'] as $attr) {
                        $attrName = $attr['attribute_name'] ?? '';
                        $optLabel = $attr['option_label'] ?? '';
                        if ($optLabel) {
                            $attrBadges[] = '<span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded text-[11px] font-medium bg-blue-50 text-blue-800 dark:bg-blue-950/40 dark:text-blue-300 border border-blue-200 dark:border-blue-800">'.($attrName ? '<span class="text-blue-600 dark:text-blue-400 font-normal">'.$attrName.':</span> ' : '').'<span class="font-bold">'.$optLabel.'</span></span>';
                        }
                    }
                }

                $attributesHtml = '';
                if (! empty($attrBadges)) {
                    $attributesHtml = '<div class="flex flex-wrap gap-1 mt-1.5">'.implode('', $attrBadges).'</div>';
                }

                $isChoice = (
                    stripos($row->shipping_company ?? '', 'selection') !== false ||
                    stripos($row->shipping_company ?? '', 'choice') !== false
                );

                $choiceBadge = $isChoice
                    ? '<span class="inline-flex items-center px-1.5 py-0.2 rounded text-[9px] font-bold bg-amber-100 text-amber-800 dark:bg-amber-900/50 dark:text-amber-300 border border-amber-300 dark:border-amber-700 shadow-sm shrink-0" title="منتج Choice - شحن مجاني">Choice</span>'
                    : '';

                return '<div class="flex flex-col max-w-[280px]"><div class="flex items-start gap-1 flex-wrap"><span class="font-semibold text-gray-900 dark:text-white text-xs leading-snug line-clamp-2" title="'.e($name).'">'.e($name).'</span>'.$choiceBadge.'</div>'.$attributesHtml.'</div>';
            },
        ]);

        $this->addColumn([
            'index' => 'supplier_store_name',
            'label' => trans('procurement::app.datagrid.supplier-store'),
            'type' => 'string',
            'searchable' => true,
            'sortable' => true,
            'filterable' => true,
        ]);

        $this->addColumn([
            'index' => 'supplier_sku_id',
            'label' => trans('procurement::app.datagrid.supplier-sku'),
            'type' => 'string',
            'searchable' => true,
            'sortable' => true,
            'filterable' => true,
        ]);

        $this->addColumn([
            'index' => 'supplier_stock',
            'label' => trans('procurement::app.datagrid.supplier-stock'),
            'type' => 'string',
            'searchable' => false,
            'sortable' => false,
            'filterable' => false,
            'closure' => function ($row) {
                $stock = null;
                $snap = null;
                if (! empty($row->import_payload_snapshot)) {
                    $snap = is_string($row->import_payload_snapshot) ? json_decode($row->import_payload_snapshot, true) : $row->import_payload_snapshot;
                }

                if (! empty($snap['variants']) && is_array($snap['variants'])) {
                    foreach ($snap['variants'] as $v) {
                        $sId = (string) ($v['sku_id'] ?? $v['id'] ?? '');
                        if ($sId == $row->supplier_sku_id || count($snap['variants']) === 1) {
                            $stock = isset($v['stock']) || isset($v['quantity']) || isset($v['sku_stock'])
                                ? (int) ($v['stock'] ?? $v['quantity'] ?? $v['sku_stock'])
                                : 0;
                            break;
                        }
                    }
                }

                if ($stock === null && ! empty($row->source_snapshot)) {
                    $sourceSnap = is_string($row->source_snapshot) ? json_decode($row->source_snapshot, true) : $row->source_snapshot;
                    if (isset($sourceSnap['stock'])) {
                        $stock = (int) $sourceSnap['stock'];
                    }
                }

                if ($stock === null) {
                    return '<span class="text-gray-400 font-mono">-</span>';
                }

                if ($stock <= 0) {
                    return '<span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-xs font-bold bg-rose-100 text-rose-800 border border-rose-200 dark:bg-rose-950/40 dark:text-rose-300 dark:border-rose-800"><i class="icon-cancel text-xs"></i> '.trans('procurement::app.datagrid.out-of-stock').'</span>';
                }

                if ($stock < 5) {
                    return '<span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-bold bg-amber-100 text-amber-800 border border-amber-200 dark:bg-amber-950/40 dark:text-amber-300 dark:border-amber-800 font-mono">'.$stock.' '.trans('procurement::app.datagrid.units').'</span>';
                }

                return '<span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-bold bg-emerald-50 text-emerald-700 border border-emerald-200 dark:bg-emerald-950/30 dark:text-emerald-300 dark:border-emerald-800 font-mono">'.$stock.' '.trans('procurement::app.datagrid.units').'</span>';
            },
        ]);

        $this->addColumn([
            'index' => 'qty_required_external',
            'label' => trans('procurement::app.datagrid.deficit-qty'),
            'type' => 'integer',
            'searchable' => false,
            'sortable' => true,
            'filterable' => true,
        ]);

        $this->addColumn([
            'index' => 'qty_batched',
            'label' => trans('procurement::app.datagrid.batched-qty'),
            'type' => 'integer',
            'searchable' => false,
            'sortable' => true,
            'filterable' => true,
        ]);

        $this->addColumn([
            'index' => 'customer_selling_price',
            'label' => trans('procurement::app.datagrid.customer-selling-price'),
            'type' => 'decimal',
            'searchable' => false,
            'sortable' => true,
            'filterable' => true,
            'closure' => fn ($row) => '<span class="font-semibold text-gray-900 dark:text-white">$'.number_format((float) ($row->customer_selling_price ?? 0), 2).'</span>',
        ]);

        $this->addColumn([
            'index' => 'system_cost',
            'label' => trans('procurement::app.datagrid.system-cost'),
            'type' => 'decimal',
            'searchable' => false,
            'sortable' => true,
            'filterable' => true,
            'closure' => function ($row) {
                $cost = (float) ($row->raw_system_cost ?? $row->aliexpress_cost ?? 0);

                return '<span class="text-gray-700 dark:text-gray-300 font-medium">$'.number_format($cost, 2).'</span>';
            },
        ]);

        $this->addColumn([
            'index' => 'cost_with_shipping',
            'label' => trans('procurement::app.datagrid.cost-with-shipping'),
            'type' => 'decimal',
            'searchable' => false,
            'sortable' => false,
            'filterable' => false,
            'closure' => function ($row) {
                $baseCost = (float) ($row->raw_system_cost ?? $row->aliexpress_cost ?? 0);
                $isChoice = (
                    stripos($row->shipping_company ?? '', 'selection') !== false ||
                    stripos($row->shipping_company ?? '', 'choice') !== false
                );
                $shippingFee = ($isChoice || $row->shipping_cost === null) ? 0.0 : (float) $row->shipping_cost;
                $totalLandedCost = $baseCost + $shippingFee;

                if ($isChoice) {
                    return '<div class="flex items-center gap-1.5"><span class="font-bold text-emerald-700 dark:text-emerald-400 font-mono">$'.number_format($totalLandedCost, 2).'</span><span class="text-[9px] px-1 py-0.2 rounded bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300 font-semibold" title="منتج Choice شحن مجاني">Choice</span></div>';
                }

                if ($row->shipping_cost !== null && (float) $row->shipping_cost > 0) {
                    return '<div class="flex flex-col"><span class="font-bold text-emerald-700 dark:text-emerald-400 font-mono">$'.number_format($totalLandedCost, 2).'</span><span class="text-[10px] text-gray-500 font-mono" dir="ltr">(+$'.number_format((float) $row->shipping_cost, 2).' شحن)</span></div>';
                }

                return '<span class="font-bold text-gray-800 dark:text-gray-200 font-mono">$'.number_format($totalLandedCost, 2).'</span>';
            },
        ]);

        $this->addColumn([
            'index' => 'aliexpress_cost',
            'label' => trans('procurement::app.datagrid.aliexpress-cost'),
            'type' => 'decimal',
            'searchable' => false,
            'sortable' => true,
            'filterable' => true,
            'closure' => function ($row) {
                $cost = (float) ($row->aliexpress_cost ?? 0);

                return '<span class="text-blue-600 dark:text-blue-400 font-semibold">$'.number_format($cost, 2).'</span>';
            },
        ]);

        $this->addColumn([
            'index' => 'state',
            'label' => trans('procurement::app.datagrid.state'),
            'type' => 'string',
            'searchable' => true,
            'sortable' => true,
            'filterable' => true,
            'closure' => function ($row) {
                $colors = [
                    'open_for_batching' => 'bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-300',
                    'batched' => 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300',
                    'ordered' => 'bg-indigo-100 text-indigo-800 dark:bg-indigo-900/30 dark:text-indigo-300',
                    'fulfilled' => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-300',
                    'locally_covered' => 'bg-teal-100 text-teal-800 dark:bg-teal-900/30 dark:text-teal-300',
                    'cancelled' => 'bg-rose-100 text-rose-800 dark:bg-rose-900/30 dark:text-rose-300',
                ];
                $color = $colors[$row->state] ?? 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300';
                $label = trans("procurement::app.states.{$row->state}") ?: $row->state;

                return "<span class=\"inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {$color}\">{$label}</span>";
            },
        ]);

        $this->addColumn([
            'index' => 'created_at',
            'label' => trans('procurement::app.datagrid.created-at'),
            'type' => 'date',
            'searchable' => false,
            'sortable' => true,
            'filterable' => true,
        ]);
    }
}
