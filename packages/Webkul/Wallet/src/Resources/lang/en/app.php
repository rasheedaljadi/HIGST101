<?php

return [
    'admin' => [
        'dashboard' => [
            'title' => 'HIGEST Wallet - Financial Dashboard',
            'subtitle' => 'Real-time liability monitoring, liquidity tracking, and operational alerts.',
            'total-liability' => 'Total System Liability',
            'available-liquid' => 'Available Liquid Balance',
            'held-balance' => 'Held Pending Balance',
            'pending-withdrawals' => 'Pending Withdrawals',
            'requires-attention' => 'Requires Attention: Failed Operations',
            'failed-refunds' => 'Failed Refund Credits',
            'failed-refunds-desc' => 'Transactions requiring manual intervention',
            'pending-topups' => 'Pending TopUps',
            'pending-topups-desc' => 'Deposits awaiting admin verification',
            'failed-webhooks' => 'Failed Webhooks',
            'failed-webhooks-desc' => 'Gateway callback failures to retry',
        ],

        'configuration' => [
            'index' => [
                'wallet' => [
                    'title' => 'HIGEST Wallet',
                    'info' => 'Configure the HIGEST Wallet payment and balance system.',
                    'active' => 'Enable Wallet',
                    'enable-topup' => 'Enable Top-Up',
                    'enable-withdrawal' => 'Enable Withdrawal',
                    'min-topup-amount' => 'Minimum Top-Up Amount',
                    'min-withdrawal-amount' => 'Minimum Withdrawal Amount',
                    'max-withdrawal-amount' => 'Maximum Withdrawal Amount (0 = unlimited)',
                ],
            ],
        ],

        'wallet' => [
            'back' => 'Back',

            'accounts' => [
                'title' => 'Wallet Accounts',
                'detail-title' => 'Wallet Details',
                'id' => 'ID',
                'customer' => 'Customer',
                'email' => 'Email',
                'available-balance' => 'Available Balance',
                'held-balance' => 'Held Balance',
                'status' => 'Status',
                'view' => 'View',
                'adjusted' => 'Wallet balance adjusted successfully.',
                'suspended' => 'Wallet account suspended successfully.',
                'reactivated' => 'Wallet account reactivated successfully.',
                'already-suspended' => 'Wallet account is already suspended.',
                'already-active' => 'Wallet account is already active.',
            ],

            'transactions' => [
                'type' => 'Type',
                'direction' => 'Direction',
                'amount' => 'Amount',
                'balance-after' => 'Balance After',
                'description' => 'Description',
                'date' => 'Date',
            ],

            'deposits' => [
                'title' => 'Wallet Deposits (Top-Ups)',
                'customer' => 'Customer',
                'amount' => 'Amount',
                'payment-method' => 'Payment Method',
                'status' => 'Status',
                'reviewed-by' => 'Reviewed By',
                'date' => 'Date',
                'approve' => 'Approve',
                'reject' => 'Reject',
                'approved' => 'Top-Up approved and wallet credited.',
                'rejected' => 'Top-Up rejected.',
            ],

            'withdrawals' => [
                'title' => 'Withdrawal Requests',
                'customer' => 'Customer',
                'amount' => 'Amount',
                'status' => 'Status',
                'processed-by' => 'Processed By',
                'transferred-at' => 'Transferred At',
                'date' => 'Date',
                'complete' => 'Mark Complete',
                'reject' => 'Reject',
                'completed' => 'Withdrawal marked as completed.',
                'rejected' => 'Withdrawal rejected and balance released.',
                'not-pending' => 'This withdrawal request is not in pending status.',
            ],
        ],
    ],

    'shop' => [
        'checkout' => [
            'balance' => 'Your balance: :balance',
            'insufficient-balance' => 'Insufficient wallet balance. Available: :available, Required: :required. Please top up your wallet or select another payment method.',
            'wallet-unavailable' => 'Wallet payment is currently unavailable or your wallet is suspended.',
        ],

        'topup' => [
            'title' => 'Add Funds to Wallet',
            'initiated' => 'Top-Up request submitted. Please proceed with payment.',
            'under-review' => 'Payment received. Your request is under review.',
            'cancelled' => 'Top-Up cancelled.',
            'disabled' => 'Wallet top-up is currently disabled.',
        ],

        'withdrawal' => [
            'title' => 'Request Withdrawal',
            'submitted' => 'Withdrawal request submitted successfully.',
            'disabled' => 'Wallet withdrawals are currently disabled.',
        ],

        'wallet' => [
            'title' => 'My Wallet',
            'available-balance' => 'Available Balance',
            'held-balance' => 'Held (Pending Withdrawal)',
            'transactions' => 'Transactions',
            'no-transactions' => 'No transactions yet.',
            'topup' => 'Add Funds',
            'withdraw' => 'Withdraw',
        ],
    ],

    'notifications' => [
        'greeting' => 'Hello :name,',
        'view-wallet' => 'View Wallet',
        'view-withdrawals' => 'View Withdrawals',

        'topup-approved' => [
            'subject' => 'Wallet Top-Up Approved',
            'line' => 'Your wallet top-up of :amount has been approved and credited to your available balance.',
        ],
        'topup-rejected' => [
            'subject' => 'Wallet Top-Up Rejected',
            'line' => 'Your wallet top-up of :amount was rejected. Reason: :reason',
        ],
        'withdrawal-submitted' => [
            'subject' => 'Withdrawal Request Submitted',
            'line' => 'Your withdrawal request of :amount has been received and is currently under review.',
        ],
        'withdrawal-completed' => [
            'subject' => 'Withdrawal Request Completed',
            'line' => 'Your withdrawal request of :amount has been processed via bank transfer. Bank Reference: :reference',
        ],
        'withdrawal-rejected' => [
            'subject' => 'Withdrawal Request Rejected',
            'line' => 'Your withdrawal request of :amount was rejected and the held balance has been released back to your wallet. Reason: :reason',
        ],
    ],
];
