<?php

return [
    [
        'key' => 'wallet',
        'name' => 'إدارة المحفظة',
        'route' => 'admin.wallet.dashboard.index',
        'sort' => 4.5,
        'icon' => 'icon-refund',
    ],
    [
        'key' => 'wallet.dashboard',
        'name' => 'الرقابة المالية',
        'route' => 'admin.wallet.dashboard.index',
        'sort' => 1,
        'icon' => '',
    ],
    [
        'key' => 'wallet.accounts',
        'name' => 'محافظ العملاء',
        'route' => 'admin.wallet.accounts.index',
        'sort' => 2,
        'icon' => '',
    ],
    [
        'key' => 'wallet.deposits',
        'name' => 'طلبات الشحن',
        'route' => 'admin.wallet.deposits.index',
        'sort' => 3,
        'icon' => '',
    ],
    [
        'key' => 'wallet.withdrawals',
        'name' => 'طلبات السحب',
        'route' => 'admin.wallet.withdrawals.index',
        'sort' => 4,
        'icon' => '',
    ],
];
