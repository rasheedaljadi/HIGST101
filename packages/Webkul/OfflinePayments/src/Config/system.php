<?php

return [
    [
        'key' => 'sales.payment_methods.offline_payments',
        'name' => 'offline_payments::app.admin.system.title',
        'info' => 'offline_payments::app.admin.system.info',
        'sort' => 8,
        'fields' => [
            [
                'name' => 'active',
                'title' => 'offline_payments::app.admin.system.status',
                'type' => 'boolean',
                'channel_based' => true,
                'locale_based' => false,
            ], [
                'name' => 'title',
                'title' => 'offline_payments::app.admin.system.method-title',
                'type' => 'text',
                'depends' => 'active:1',
                'validation' => 'required_if:active,1',
                'channel_based' => true,
                'locale_based' => true,
            ], [
                'name' => 'description',
                'title' => 'offline_payments::app.admin.system.description',
                'type' => 'textarea',
                'depends' => 'active:1',
                'channel_based' => true,
                'locale_based' => true,
            ], [
                'name' => 'image',
                'title' => 'offline_payments::app.admin.system.logo',
                'type' => 'image',
                'depends' => 'active:1',
                'channel_based' => true,
                'locale_based' => false,
                'validation' => 'mimes:bmp,jpeg,jpg,png,webp,svg',
            ], [
                'name' => 'generate_invoice',
                'title' => 'offline_payments::app.admin.system.generate-invoice',
                'type' => 'boolean',
                'depends' => 'active:1',
                'channel_based' => true,
                'locale_based' => false,
            ], [
                'name' => 'invoice_status',
                'depends' => 'active:1',
                'title' => 'offline_payments::app.admin.system.set-invoice-status',
                'type' => 'select',
                'options' => [
                    [
                        'title' => 'offline_payments::app.admin.system.pending',
                        'value' => 'pending',
                    ], [
                        'title' => 'offline_payments::app.admin.system.paid',
                        'value' => 'paid',
                    ],
                ],
                'channel_based' => true,
                'locale_based' => false,
            ], [
                'name' => 'order_status',
                'title' => 'offline_payments::app.admin.system.set-order-status',
                'type' => 'select',
                'depends' => 'active:1',
                'options' => [
                    [
                        'title' => 'offline_payments::app.admin.system.pending',
                        'value' => 'pending',
                    ], [
                        'title' => 'offline_payments::app.admin.system.pending-payment',
                        'value' => 'pending_payment',
                    ], [
                        'title' => 'offline_payments::app.admin.system.processing',
                        'value' => 'processing',
                    ],
                ],
                'channel_based' => true,
                'locale_based' => false,
            ], [
                'name' => 'sort',
                'title' => 'offline_payments::app.admin.system.sort-order',
                'type' => 'number',
                'depends' => 'active:1',
                'validation' => 'required_if:active,1|integer|min:0',
                'channel_based' => true,
                'locale_based' => false,
            ],
        ],
    ],
];
