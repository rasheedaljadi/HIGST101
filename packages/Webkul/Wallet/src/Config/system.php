<?php

return [
    [
        'key' => 'sales.wallet',
        'name' => 'wallet::app.admin.configuration.index.wallet.title',
        'info' => 'wallet::app.admin.configuration.index.wallet.info',
        'icon' => 'settings/payment-method.svg',
        'sort' => 11,
    ],
    [
        'key' => 'sales.wallet.settings',
        'name' => 'wallet::app.admin.configuration.index.wallet.title',
        'info' => 'wallet::app.admin.configuration.index.wallet.info',
        'icon' => 'settings/payment-method.svg',
        'sort' => 1,
        'fields' => [
            [
                'name' => 'active',
                'title' => 'wallet::app.admin.configuration.index.wallet.active',
                'type' => 'boolean',
                'default' => true,
                'channel_based' => false,
                'locale_based' => false,
            ],
            [
                'name' => 'enable_topup',
                'title' => 'wallet::app.admin.configuration.index.wallet.enable-topup',
                'type' => 'boolean',
                'default' => true,
                'channel_based' => false,
                'locale_based' => false,
            ],
            [
                'name' => 'enable_withdrawal',
                'title' => 'wallet::app.admin.configuration.index.wallet.enable-withdrawal',
                'type' => 'boolean',
                'default' => true,
                'channel_based' => false,
                'locale_based' => false,
            ],
            [
                'name' => 'min_topup_amount',
                'title' => 'wallet::app.admin.configuration.index.wallet.min-topup-amount',
                'type' => 'text',
                'default' => '10.00',
                'channel_based' => false,
                'locale_based' => false,
            ],
            [
                'name' => 'min_withdrawal_amount',
                'title' => 'wallet::app.admin.configuration.index.wallet.min-withdrawal-amount',
                'type' => 'text',
                'default' => '50.00',
                'channel_based' => false,
                'locale_based' => false,
            ],
            [
                'name' => 'max_withdrawal_amount',
                'title' => 'wallet::app.admin.configuration.index.wallet.max-withdrawal-amount',
                'type' => 'text',
                'default' => '0',
                'channel_based' => false,
                'locale_based' => false,
            ],
            [
                'name' => 'withdrawal_methods',
                'title' => 'wallet::app.admin.configuration.index.wallet.withdrawal-methods',
                'type' => 'blade',
                'path' => 'wallet::admin.configuration.withdrawal-methods',
            ],
        ],
    ],
];
