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
    [
        'key' => 'delivery',
        'name' => 'delivery::app.admin.acl.delivery-agent',
        'route' => 'delivery.index',
        'sort' => 9,
    ],
    [
        'key' => 'delivery.agent',
        'name' => 'delivery::app.admin.acl.delivery-agent',
        'route' => 'admin.courier.index',
        'sort' => 10,
    ],
    [
        'key' => 'delivery_agent',
        'name' => 'delivery::app.admin.acl.delivery-agent',
        'route' => 'admin.courier.index',
        'sort' => 11,
    ],
    [
        'key' => 'delivery_agent.all',
        'name' => 'delivery::app.admin.menu.all-tasks',
        'route' => 'admin.courier.index',
        'sort' => 1,
    ],
    [
        'key' => 'delivery_agent.assigned',
        'name' => 'delivery::app.admin.menu.assigned-tasks',
        'route' => 'admin.courier.assigned',
        'sort' => 2,
    ],
    [
        'key' => 'delivery_agent.picked_up',
        'name' => 'delivery::app.admin.menu.picked-up-tasks',
        'route' => 'admin.courier.picked_up',
        'sort' => 3,
    ],
    [
        'key' => 'delivery_agent.out_for_delivery',
        'name' => 'delivery::app.admin.menu.out-for-delivery-tasks',
        'route' => 'admin.courier.out_for_delivery',
        'sort' => 4,
    ],
    [
        'key' => 'delivery_agent.arrived_at_point',
        'name' => 'delivery::app.admin.menu.arrived-point-tasks',
        'route' => 'admin.courier.arrived_at_point',
        'sort' => 5,
    ],
    [
        'key' => 'delivery_agent.delivered',
        'name' => 'delivery::app.admin.menu.delivered-tasks',
        'route' => 'admin.courier.delivered_tasks',
        'sort' => 6,
    ],
    [
        'key' => 'delivery_agent.delivery_failed',
        'name' => 'delivery::app.admin.menu.failed-tasks',
        'route' => 'admin.courier.failed_tasks',
        'sort' => 7,
    ],
];
