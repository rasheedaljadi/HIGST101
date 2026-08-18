<?php

return [
    [
        'key' => 'inventory',
        'name' => 'inventory::app.admin.acl.inventory',
        'route' => 'admin.inventory.dashboard.index',
        'sort' => 3,
    ],
    [
        'key' => 'inventory.dashboard',
        'name' => 'inventory::app.admin.acl.dashboard',
        'route' => 'admin.inventory.dashboard.index',
        'sort' => 1,
    ],
    [
        'key' => 'inventory.sources',
        'name' => 'inventory::app.admin.acl.sources',
        'route' => 'admin.inventory.sources.index',
        'sort' => 2,
    ],
    [
        'key' => 'inventory.products',
        'name' => 'inventory::app.admin.acl.products',
        'route' => 'admin.inventory.products.index',
        'sort' => 3,
    ],
    [
        'key' => 'inventory.products.view',
        'name' => 'inventory::app.admin.acl.products-view',
        'route' => 'admin.inventory.products.show',
        'sort' => 1,
    ],
    [
        'key' => 'inventory.movements',
        'name' => 'inventory::app.admin.acl.movements',
        'route' => 'admin.inventory.movements.index',
        'sort' => 4,
    ],
    [
        'key' => 'inventory.transfers',
        'name' => 'inventory::app.admin.acl.transfers',
        'route' => 'admin.inventory.transfers.index',
        'sort' => 5,
    ],
    [
        'key' => 'inventory.transfers.create',
        'name' => 'inventory::app.admin.acl.transfers-create',
        'route' => 'admin.inventory.transfers.create',
        'sort' => 1,
    ],
    [
        'key' => 'inventory.transfers.view',
        'name' => 'inventory::app.admin.acl.transfers-view',
        'route' => 'admin.inventory.transfers.show',
        'sort' => 2,
    ],
    [
        'key' => 'inventory.transfers.dispatch',
        'name' => 'inventory::app.admin.acl.transfers-dispatch',
        'route' => 'admin.inventory.transfers.dispatch',
        'sort' => 3,
    ],
    [
        'key' => 'inventory.receipts',
        'name' => 'inventory::app.admin.acl.receipts',
        'route' => 'admin.inventory.receipts.index',
        'sort' => 6,
    ],
    [
        'key' => 'inventory.receipts.create',
        'name' => 'inventory::app.admin.acl.receipts-process',
        'route' => 'admin.inventory.receipts.create',
        'sort' => 1,
    ],
    [
        'key' => 'inventory.receipts.view',
        'name' => 'inventory::app.admin.acl.receipts-view',
        'route' => 'admin.inventory.receipts.show',
        'sort' => 2,
    ],
    [
        'key' => 'inventory.quarantine',
        'name' => 'inventory::app.admin.acl.quarantine',
        'route' => 'admin.inventory.quarantine.index',
        'sort' => 7,
    ],
    [
        'key' => 'inventory.quarantine.approve',
        'name' => 'inventory::app.admin.acl.quarantine-approve',
        'route' => 'admin.inventory.quarantine.release',
        'sort' => 1,
    ],
    [
        'key' => 'inventory.reports',
        'name' => 'inventory::app.admin.acl.reports',
        'route' => 'admin.inventory.reports.index',
        'sort' => 8,
    ],
    [
        'key' => 'inventory.reports.export',
        'name' => 'inventory::app.admin.acl.reports-export',
        'route' => 'admin.inventory.reports.export',
        'sort' => 1,
    ],
];
