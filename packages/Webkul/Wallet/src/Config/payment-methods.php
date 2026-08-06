<?php

use Webkul\Wallet\Payment\WalletPayment;

return [
    'wallet' => [
        'code' => 'wallet',
        'title' => 'محفظة هايست الإلكترونية',
        'description' => 'الدفع المباشر عبر رصيد محفظة هايست المتاح',
        'class' => WalletPayment::class,
        'active' => true,
        'sort' => 1,
    ],
];
