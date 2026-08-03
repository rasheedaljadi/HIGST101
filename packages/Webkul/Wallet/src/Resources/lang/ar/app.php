<?php

return [
    'admin' => [
        'dashboard' => [
            'title' => 'محفظة هايست - الرقابة المالية',
            'subtitle' => 'المراقبة الفورية للالتزامات، تتبع السيولة، والتنبيهات التشغيلية.',
            'total-liability' => 'إجمالي التزامات النظام',
            'available-liquid' => 'السيولة المتاحة',
            'held-balance' => 'الأرصدة المحجوزة والمعلقة',
            'pending-withdrawals' => 'طلبات السحب المعلقة',
            'requires-attention' => 'يتطلب الانتباه: العمليات المتعثرة',
            'failed-refunds' => 'عمليات الاسترداد الفاشلة',
            'failed-refunds-desc' => 'عمليات تحتاج لتدخل يدوي',
            'pending-topups' => 'طلبات الشحن المعلقة',
            'pending-topups-desc' => 'إيداعات بانتظار التحقق من الإدارة',
            'failed-webhooks' => 'إخفاقات الإشعارات (Webhooks)',
            'failed-webhooks-desc' => 'إشعارات بوابة الدفع بانتظار إعادة المحاولة',
        ],

        'configuration' => [
            'index' => [
                'wallet' => [
                    'title' => 'محفظة هايست',
                    'info' => 'إعداد نظام محفظة هايست للمدفوعات والأرصدة.',
                    'active' => 'تفعيل المحفظة',
                    'enable-topup' => 'تفعيل الإيداع',
                    'enable-withdrawal' => 'تفعيل السحب',
                    'min-topup-amount' => 'الحد الأدنى للإيداع',
                    'min-withdrawal-amount' => 'الحد الأدنى للسحب',
                    'max-withdrawal-amount' => 'الحد الأقصى للسحب (0 = بلا حد)',
                ],
            ],
        ],

        'wallet' => [
            'back' => 'رجوع',

            'accounts' => [
                'title' => 'حسابات المحافظ',
                'detail-title' => 'تفاصيل المحفظة',
                'id' => '#',
                'customer' => 'العميل',
                'email' => 'البريد الإلكتروني',
                'available-balance' => 'الرصيد المتاح',
                'held-balance' => 'الرصيد المحجوز',
                'status' => 'الحالة',
                'view' => 'عرض',
                'adjusted' => 'تم تعديل رصيد المحفظة بنجاح.',
                'suspended' => 'تم تعليق حساب المحفظة بنجاح.',
                'reactivated' => 'تمت إعادة تفعيل حساب المحفظة بنجاح.',
                'already-suspended' => 'حساب المحفظة معلق بالفعل.',
                'already-active' => 'حساب المحفظة نشط بالفعل.',
            ],

            'transactions' => [
                'type' => 'النوع',
                'direction' => 'الاتجاه',
                'amount' => 'المبلغ',
                'balance-after' => 'الرصيد بعد العملية',
                'description' => 'الوصف',
                'date' => 'التاريخ',
            ],

            'deposits' => [
                'title' => 'طلبات إيداع الرصيد',
                'customer' => 'العميل',
                'amount' => 'المبلغ',
                'payment-method' => 'طريقة الدفع',
                'status' => 'الحالة',
                'reviewed-by' => 'راجع بواسطة',
                'date' => 'التاريخ',
                'approve' => 'اعتماد',
                'reject' => 'رفض',
                'approved' => 'تمت الموافقة على الإيداع وإضافته للمحفظة.',
                'rejected' => 'تم رفض طلب الإيداع.',
            ],

            'withdrawals' => [
                'title' => 'طلبات السحب',
                'customer' => 'العميل',
                'amount' => 'المبلغ',
                'status' => 'الحالة',
                'processed-by' => 'نُفِّذ بواسطة',
                'transferred-at' => 'تاريخ التحويل',
                'date' => 'التاريخ',
                'complete' => 'تأكيد التنفيذ',
                'reject' => 'رفض',
                'completed' => 'تم تأكيد تنفيذ عملية السحب.',
                'rejected' => 'تم رفض طلب السحب وإعادة الرصيد.',
                'not-pending' => 'هذا الطلب لا يمكن معالجته (ليس في حالة انتظار).',
            ],
        ],
    ],

    'shop' => [
        'checkout' => [
            'balance' => 'رصيدك: :balance',
            'insufficient-balance' => 'رصيد المحفظة غير كافٍ. المتاح: :available، المطلوب: :required. يرجى إعادة شحن المحفظة أو اختيار طريقة دفع أخرى.',
            'wallet-unavailable' => 'الدفع بالمحفظة غير متاح حالياً أو أن محفظتك معطلة.',
        ],

        'topup' => [
            'title' => 'إيداع رصيد في المحفظة',
            'initiated' => 'تم تقديم طلب الإيداع. يرجى إتمام الدفع.',
            'under-review' => 'وصل الدفع. طلبك قيد المراجعة.',
            'cancelled' => 'تم إلغاء طلب الإيداع.',
            'disabled' => 'خدمة إيداع الرصيد غير متاحة حالياً.',
        ],

        'withdrawal' => [
            'title' => 'طلب سحب رصيد',
            'submitted' => 'تم تقديم طلب السحب بنجاح.',
            'disabled' => 'خدمة السحب غير متاحة حالياً.',
        ],

        'wallet' => [
            'title' => 'محفظتي',
            'available-balance' => 'الرصيد المتاح',
            'held-balance' => 'محجوز (سحب قيد المعالجة)',
            'transactions' => 'الحركات المالية',
            'no-transactions' => 'لا توجد حركات حتى الآن.',
            'topup' => 'إيداع رصيد',
            'withdraw' => 'سحب رصيد',
        ],
    ],

    'notifications' => [
        'greeting' => 'مرحباً :name،',
        'view-wallet' => 'عرض المحفظة',
        'view-withdrawals' => 'عرض طلبات السحب',

        'topup-approved' => [
            'subject' => 'تمت الموافقة على طلب إيداع المحفظة',
            'line' => 'تمت الموافقة على طلب الإيداع بمبلغ :amount وإضافته إلى رصيدك المتاح.',
        ],
        'topup-rejected' => [
            'subject' => 'تم رفض طلب إيداع المحفظة',
            'line' => 'تم رفض طلب الإيداع بمبلغ :amount. السبب: :reason',
        ],
        'withdrawal-submitted' => [
            'subject' => 'تم استلام طلب السحب بنجاح',
            'line' => 'تم استلام طلب سحب الرصيد بمبلغ :amount وهو قيد المراجعة حالياً.',
        ],
        'withdrawal-completed' => [
            'subject' => 'تم إتمام عملية السحب بنجاح',
            'line' => 'تم تحويل مبلغ السحب :amount عبر التحويل البنكي. مرجع التحويل: :reference',
        ],
        'withdrawal-rejected' => [
            'subject' => 'تم رفض طلب السحب',
            'line' => 'تم رفض طلب السحب بمبلغ :amount وإعادة الرصيد المحجوز إلى محفظتك. السبب: :reason',
        ],
    ],
];
