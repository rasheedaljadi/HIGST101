<?php

return [
    [
        'key' => 'wallet',
        'name' => 'إدارة المحفظة',
        'route' => 'admin.wallet.dashboard.index',
        'sort' => 4.5,
    ],
    [
        'key' => 'wallet.dashboard',
        'name' => 'الرقابة المالية',
        'route' => 'admin.wallet.dashboard.index',
        'sort' => 1,
    ],
    [
        'key' => 'wallet.accounts',
        'name' => 'محافظ العملاء',
        'route' => 'admin.wallet.accounts.index',
        'sort' => 2,
    ],
    [
        'key' => 'wallet.accounts.view',
        'name' => 'عرض المحافظ',
        'route' => 'admin.wallet.accounts.index',
        'sort' => 1,
    ],
    [
        'key' => 'wallet.accounts.adjust',
        'name' => 'تعديل الرصيد اليدوي',
        'route' => 'admin.wallet.accounts.adjust',
        'sort' => 2,
    ],
    [
        'key' => 'wallet.accounts.suspend',
        'name' => 'تجميد/تفعيل المحفظة',
        'route' => 'admin.wallet.accounts.suspend',
        'sort' => 3,
    ],
    [
        'key' => 'wallet.deposits',
        'name' => 'طلبات الشحن',
        'route' => 'admin.wallet.deposits.index',
        'sort' => 3,
    ],
    [
        'key' => 'wallet.deposits.view',
        'name' => 'عرض طلبات الشحن',
        'route' => 'admin.wallet.deposits.index',
        'sort' => 1,
    ],
    [
        'key' => 'wallet.deposits.approve',
        'name' => 'اعتماد/رفض الشحن',
        'route' => 'admin.wallet.deposits.approve',
        'sort' => 2,
    ],
    [
        'key' => 'wallet.withdrawals',
        'name' => 'طلبات السحب',
        'route' => 'admin.wallet.withdrawals.index',
        'sort' => 4,
    ],
    [
        'key' => 'wallet.withdrawals.view',
        'name' => 'عرض طلبات السحب',
        'route' => 'admin.wallet.withdrawals.index',
        'sort' => 1,
    ],
    [
        'key' => 'wallet.withdrawals.process',
        'name' => 'معالجة/رفض السحب',
        'route' => 'admin.wallet.withdrawals.complete',
        'sort' => 2,
    ],
    [
        'key' => 'wallet.reporting.view',
        'name' => 'تقارير المحفظة',
        'route' => 'admin.wallet.reports.index',
        'sort' => 5,
    ],
    [
        'key' => 'wallet.promotions',
        'name' => 'العروض الترويجية والمكافآت',
        'route' => 'admin.wallet.promotions.index',
        'sort' => 6,
    ],
    [
        'key' => 'wallet.promotions.view',
        'name' => 'عرض العروض والمكافآت',
        'route' => 'admin.wallet.promotions.index',
        'sort' => 1,
    ],
    [
        'key' => 'wallet.promotions.create',
        'name' => 'إنشاء عرض جديد',
        'route' => 'admin.wallet.promotions.create',
        'sort' => 2,
    ],
    [
        'key' => 'wallet.promotions.edit',
        'name' => 'تعديل العروض',
        'route' => 'admin.wallet.promotions.edit',
        'sort' => 3,
    ],
    [
        'key' => 'wallet.promotions.delete',
        'name' => 'أرشفة وتعطيل العروض الترويجية (Archive Only)',
        'route' => 'admin.wallet.promotions.destroy',
        'sort' => 4,
    ],
    [
        'key' => 'wallet.promotions.monitoring',
        'name' => 'شاشات الرقابة والتدقيق (الحصص، الاستخدامات، الديون، الصندوق)',
        'route' => 'admin.wallet.promotions.monitoring.index',
        'sort' => 5,
    ],
];
