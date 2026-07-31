<?php

return [
    [
        'key' => 'settings.offline_payment_accounts',
        'name' => 'offline_payments::app.admin.acl.title',
        'route' => 'admin.settings.offline_accounts.index',
        'sort' => 4,
    ], [
        'key' => 'settings.offline_payment_accounts.create',
        'name' => 'offline_payments::app.admin.acl.create',
        'route' => 'admin.settings.offline_accounts.create',
        'sort' => 1,
    ], [
        'key' => 'settings.offline_payment_accounts.edit',
        'name' => 'offline_payments::app.admin.acl.edit',
        'route' => 'admin.settings.offline_accounts.edit',
        'sort' => 2,
    ], [
        'key' => 'settings.offline_payment_accounts.delete',
        'name' => 'offline_payments::app.admin.acl.delete',
        'route' => 'admin.settings.offline_accounts.delete',
        'sort' => 3,
    ],
];
