<?php

return [
    /**
     * Dropshipping Top-level submenu: أوامر الشراء V2
     */
    [
        'key' => 'dropshipping.procurement_v2',
        'name' => 'procurement::app.admin.menu.procurement-v2',
        'route' => 'admin.procurement.demands.index',
        'sort' => 2,
        'icon' => 'icon-sales',
    ],

    /**
     * 1. طلبات التوريد المؤهلة
     */
    [
        'key' => 'dropshipping.procurement_v2.demands',
        'name' => 'procurement::app.admin.menu.demands',
        'route' => 'admin.procurement.demands.index',
        'sort' => 1,
        'icon' => '',
    ],

    /**
     * 2. دفعات التجميع
     */
    [
        'key' => 'dropshipping.procurement_v2.batches',
        'name' => 'procurement::app.admin.menu.batches',
        'route' => 'admin.procurement.batches.index',
        'sort' => 2,
        'icon' => '',
    ],

    /**
     * 3. أوامر المورد
     */
    [
        'key' => 'dropshipping.procurement_v2.supplier_orders',
        'name' => 'procurement::app.admin.menu.supplier-orders',
        'route' => 'admin.procurement.supplier_orders.index',
        'sort' => 3,
        'icon' => '',
    ],

    /**
     * 4. أوامر علي إكسبرس
     */
    [
        'key' => 'dropshipping.procurement_v2.platform_orders',
        'name' => 'procurement::app.admin.menu.platform-orders',
        'route' => 'admin.procurement.platform_orders.index',
        'sort' => 4,
        'icon' => '',
    ],

    /**
     * 5. إقرارات الدفع اليدوي
     */
    [
        'key' => 'dropshipping.procurement_v2.manual_payments',
        'name' => 'procurement::app.admin.menu.manual-payments',
        'route' => 'admin.procurement.manual_payments.index',
        'sort' => 5,
        'icon' => '',
    ],

    /**
     * 6. فروق التكلفة والموافقات
     */
    [
        'key' => 'dropshipping.procurement_v2.cost_variances',
        'name' => 'procurement::app.admin.menu.cost-variances',
        'route' => 'admin.procurement.cost_variances.index',
        'sort' => 6,
        'icon' => '',
    ],

    /**
     * 7. المصالحات والاستثناءات
     */
    [
        'key' => 'dropshipping.procurement_v2.exceptions',
        'name' => 'procurement::app.admin.menu.exceptions',
        'route' => 'admin.procurement.exceptions.index',
        'sort' => 7,
        'icon' => '',
    ],

    /**
     * 8. التقارير
     */
    [
        'key' => 'dropshipping.procurement_v2.reports',
        'name' => 'procurement::app.admin.menu.reports',
        'route' => 'admin.procurement.reports.index',
        'sort' => 8,
        'icon' => '',
    ],
];
