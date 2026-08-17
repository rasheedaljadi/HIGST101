<?php

return [
    /**
     * Top-level Menu Item: إدارة التسليم
     */
    [
        'key' => 'delivery_management',
        'name' => 'delivery::app.admin.menu.delivery-management',
        'route' => 'admin.delivery.dashboard.index',
        'sort' => 3,
        'icon' => 'icon-ship',
    ],

    /**
     * 1. لوحة المتابعة
     */
    [
        'key' => 'delivery_management.dashboard',
        'name' => 'delivery::app.admin.menu.dashboard',
        'route' => 'admin.delivery.dashboard.index',
        'sort' => 1,
        'icon' => '',
    ],

    /**
     * 2. طلبات التسليم
     */
    [
        'key' => 'delivery_management.assignments',
        'name' => 'delivery::app.admin.menu.assignments',
        'route' => 'admin.delivery.assignments.index',
        'sort' => 2,
        'icon' => '',
    ],

    /**
     * 3. موظفو التوصيل
     */
    [
        'key' => 'delivery_management.couriers',
        'name' => 'delivery::app.admin.menu.couriers',
        'route' => 'admin.delivery.couriers.index',
        'sort' => 3,
        'icon' => '',
    ],

    /**
     * 4. نقاط التسليم
     */
    [
        'key' => 'delivery_management.points',
        'name' => 'delivery::app.admin.menu.points',
        'route' => 'admin.delivery.points.index',
        'sort' => 4,
        'icon' => '',
    ],

    /**
     * 5. مناطق وقواعد التسليم
     */
    [
        'key' => 'delivery_management.rules',
        'name' => 'delivery::app.admin.menu.rules',
        'route' => 'admin.delivery.rules.index',
        'sort' => 5,
        'icon' => '',
    ],

    /**
     * 6. الفشل والإرجاع
     */
    [
        'key' => 'delivery_management.failures',
        'name' => 'delivery::app.admin.menu.failures',
        'route' => 'admin.delivery.failures.index',
        'sort' => 6,
        'icon' => '',
    ],

    /**
     * 7. التحصيل والتسويات
     */
    [
        'key' => 'delivery_management.settlements',
        'name' => 'delivery::app.admin.menu.settlements',
        'route' => 'admin.delivery.settlements.index',
        'sort' => 7,
        'icon' => '',
    ],

    /**
     * 8. التقارير وسجل التدقيق
     */
    [
        'key' => 'delivery_management.audit_logs',
        'name' => 'delivery::app.admin.menu.audit-logs',
        'route' => 'admin.delivery.audit_logs.index',
        'sort' => 8,
        'icon' => '',
    ],
];
