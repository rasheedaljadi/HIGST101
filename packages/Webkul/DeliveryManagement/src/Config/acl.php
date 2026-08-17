<?php

return [
    [
        'key' => 'delivery_management',
        'name' => 'delivery::app.admin.acl.delivery-management',
        'route' => 'admin.delivery.dashboard.index',
        'sort' => 3,
    ],
    [
        'key' => 'delivery_management.dashboard',
        'name' => 'delivery::app.admin.acl.dashboard',
        'route' => 'admin.delivery.dashboard.index',
        'sort' => 1,
    ],
    [
        'key' => 'delivery_management.assignments',
        'name' => 'delivery::app.admin.acl.assignments',
        'route' => 'admin.delivery.assignments.index',
        'sort' => 2,
    ],
    [
        'key' => 'delivery_management.couriers',
        'name' => 'delivery::app.admin.acl.couriers',
        'route' => 'admin.delivery.couriers.index',
        'sort' => 3,
    ],
    [
        'key' => 'delivery_management.points',
        'name' => 'delivery::app.admin.acl.points',
        'route' => 'admin.delivery.points.index',
        'sort' => 4,
    ],
    [
        'key' => 'delivery_management.rules',
        'name' => 'delivery::app.admin.acl.rules',
        'route' => 'admin.delivery.rules.index',
        'sort' => 5,
    ],
    [
        'key' => 'delivery_management.failures',
        'name' => 'delivery::app.admin.acl.failures',
        'route' => 'admin.delivery.failures.index',
        'sort' => 6,
    ],
    [
        'key' => 'delivery_management.settlements',
        'name' => 'delivery::app.admin.acl.settlements',
        'route' => 'admin.delivery.settlements.index',
        'sort' => 7,
    ],
    [
        'key' => 'delivery_management.audit_logs',
        'name' => 'delivery::app.admin.acl.audit-logs',
        'route' => 'admin.delivery.audit_logs.index',
        'sort' => 8,
    ],
];
