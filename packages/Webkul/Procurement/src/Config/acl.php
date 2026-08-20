<?php

return [
    [
        'key' => 'dropshipping.procurement_v2',
        'name' => 'procurement::app.acl.procurement-v2',
        'route' => 'admin.procurement.demands.index',
        'sort' => 2,
    ],
    [
        'key' => 'dropshipping.procurement_v2.view',
        'name' => 'procurement::app.acl.view',
        'route' => 'admin.procurement.demands.index',
        'sort' => 1,
    ],
    [
        'key' => 'dropshipping.procurement_v2.batch_create',
        'name' => 'procurement::app.acl.batch-create',
        'route' => 'admin.procurement.batches.create',
        'sort' => 2,
    ],
    [
        'key' => 'dropshipping.procurement_v2.batch_approve',
        'name' => 'procurement::app.acl.batch-approve',
        'route' => 'admin.procurement.batches.approve',
        'sort' => 3,
    ],
    [
        'key' => 'dropshipping.procurement_v2.submit',
        'name' => 'procurement::app.acl.submit',
        'route' => 'admin.procurement.batches.submit',
        'sort' => 4,
    ],
    [
        'key' => 'dropshipping.procurement_v2.payment_confirm',
        'name' => 'procurement::app.acl.payment-confirm',
        'route' => 'admin.procurement.manual_payments.store',
        'sort' => 5,
    ],
    [
        'key' => 'dropshipping.procurement_v2.cost_view',
        'name' => 'procurement::app.acl.cost-view',
        'route' => 'admin.procurement.cost_variances.index',
        'sort' => 6,
    ],
    [
        'key' => 'dropshipping.procurement_v2.variance_approve',
        'name' => 'procurement::app.acl.variance-approve',
        'route' => 'admin.procurement.cost_variances.approve',
        'sort' => 7,
    ],
    [
        'key' => 'dropshipping.procurement_v2.exception_handle',
        'name' => 'procurement::app.acl.exception-handle',
        'route' => 'admin.procurement.exceptions.index',
        'sort' => 8,
    ],
    [
        'key' => 'dropshipping.procurement_v2.reports_view',
        'name' => 'procurement::app.acl.reports-view',
        'route' => 'admin.procurement.reports.index',
        'sort' => 9,
    ],
];
