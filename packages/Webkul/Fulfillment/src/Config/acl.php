<?php

return [
    [
        'key'   => 'dropshipping',
        'name'  => 'admin::app.components.layouts.sidebar.dropshipping',
        'route' => 'admin.dropshipping.fulfillment.index',
        'sort'  => 8,
    ],

    // Fulfillment Main Entry (Menu Key)
    [
        'key'   => 'dropshipping.fulfillment',
        'name'  => 'admin::app.acl.view',
        'route' => 'admin.dropshipping.fulfillment.index',
        'sort'  => 1,
    ],

    // Fulfillment View Operations
    [
        'key'   => 'dropshipping.fulfillment.view',
        'name'  => 'admin::app.acl.view',
        'route' => 'admin.dropshipping.fulfillment.view',
        'sort'  => 2,
    ],
    [
        'key'   => 'dropshipping.fulfillment.view',
        'name'  => 'admin::app.acl.view',
        'route' => 'admin.dropshipping.fulfillment.refresh',
        'sort'  => 3,
    ],
    [
        'key'   => 'dropshipping.fulfillment.view',
        'name'  => 'admin::app.acl.view',
        'route' => 'admin.dropshipping.fulfillment.clear-alert',
        'sort'  => 4,
    ],

    // Fulfillment Write Operations
    [
        'key'   => 'dropshipping.fulfillment.retry',
        'name'  => 'fulfillment::app.admin.acl.retry',
        'route' => 'admin.dropshipping.fulfillment.retry',
        'sort'  => 5,
    ],
    [
        'key'   => 'dropshipping.fulfillment.cancel',
        'name'  => 'fulfillment::app.admin.acl.cancel',
        'route' => 'admin.dropshipping.fulfillment.cancel',
        'sort'  => 6,
    ],
    [
        'key'   => 'dropshipping.fulfillment.override',
        'name'  => 'admin::app.acl.edit',
        'route' => 'admin.dropshipping.fulfillment.override',
        'sort'  => 7,
    ],
    [
        'key'   => 'dropshipping.fulfillment.edit',
        'name'  => 'admin::app.acl.edit',
        'route' => 'admin.dropshipping.fulfillment.edit',
        'sort'  => 8,
    ],
    [
        'key'   => 'dropshipping.fulfillment.approve',
        'name'  => 'fulfillment::app.admin.acl.approve',
        'route' => 'admin.dropshipping.fulfillment.approve',
        'sort'  => 9,
    ],
    [
        'key'   => 'dropshipping.fulfillment.approve',
        'name'  => 'fulfillment::app.admin.acl.approve',
        'route' => 'admin.dropshipping.fulfillment.reject',
        'sort'  => 10,
    ],

    // Import Capability Mappings (Menu Key: dropshipping.import)
    [
        'key'   => 'dropshipping.import',
        'name'  => 'admin::app.acl.view',
        'route' => 'admin.dropshipping.import.index',
        'sort'  => 11,
    ],
    [
        'key'   => 'dropshipping.import',
        'name'  => 'admin::app.acl.view',
        'route' => 'admin.dropshipping.imports.index', // Deprecated route mapping
        'sort'  => 11,
    ],
    [
        'key'   => 'dropshipping.import.create',
        'name'  => 'admin::app.acl.create',
        'route' => 'admin.dropshipping.import.store',
        'sort'  => 12,
    ],
    [
        'key'   => 'dropshipping.import.create',
        'name'  => 'admin::app.acl.create',
        'route' => 'admin.dropshipping.import.stream',
        'sort'  => 13,
    ],

    // Sync Capability Mappings (Menu Key: dropshipping.sync)
    [
        'key'   => 'dropshipping.sync',
        'name'  => 'admin::app.acl.view',
        'route' => 'admin.dropshipping.sync.index',
        'sort'  => 14,
    ],
    [
        'key'   => 'dropshipping.sync.execute',
        'name'  => 'admin::app.acl.create',
        'route' => 'admin.dropshipping.sync.run_single',
        'sort'  => 15,
    ],
    [
        'key'   => 'dropshipping.sync.execute',
        'name'  => 'admin::app.acl.create',
        'route' => 'admin.dropshipping.sync.get_all_syncable',
        'sort'  => 16,
    ],
    [
        'key'   => 'dropshipping.sync.execute',
        'name'  => 'admin::app.acl.create',
        'route' => 'admin.dropshipping.sync.outbox.replay',
        'sort'  => 17,
    ],
    [
        'key'   => 'dropshipping.sync.execute',
        'name'  => 'admin::app.acl.create',
        'route' => 'admin.dropshipping.sync.inbox.replay',
        'sort'  => 18,
    ],

    // Keys Capability Mappings (Menu Key: dropshipping.keys)
    [
        'key'   => 'dropshipping.keys',
        'name'  => 'admin::app.acl.view',
        'route' => 'admin.dropshipping.keys.index',
        'sort'  => 17,
    ],
    [
        'key'   => 'dropshipping.keys',
        'name'  => 'admin::app.acl.view',
        'route' => 'admin.dropshipping.api-keys.index', // Deprecated route mapping
        'sort'  => 17,
    ],
    [
        'key'   => 'dropshipping.keys.manage',
        'name'  => 'admin::app.acl.edit',
        'route' => 'admin.dropshipping.keys.store',
        'sort'  => 18,
    ],
    [
        'key'   => 'dropshipping.finance',
        'name'  => 'admin::app.acl.view',
        'route' => 'admin.dropshipping.finance.index',
        'sort'  => 19,
    ],
    [
        'key'   => 'dropshipping.monitoring',
        'name'  => 'admin::app.acl.view',
        'route' => 'admin.dropshipping.monitoring.index',
        'sort'  => 20,
    ],
    [
        'key'   => 'dropshipping.monitoring.reset',
        'name'  => 'admin::app.acl.edit',
        'route' => 'admin.dropshipping.monitoring.reset-circuit',
        'sort'  => 21,
    ],
];
