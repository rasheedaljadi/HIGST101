<?php

namespace Webkul\Wallet\Models;

use Illuminate\Database\Eloquent\Model;
use Webkul\Wallet\Contracts\WalletReconciliation as WalletReconciliationContract;

class WalletReconciliation extends Model implements WalletReconciliationContract
{
    protected $table = 'wallet_reconciliations';

    protected $fillable = [
        'run_at',
        'total_wallets_audited',
        'discrepancies_count',
        'total_system_liability',
        'status',
        'report_summary',
    ];

    protected $casts = [
        'run_at' => 'datetime',
        'total_wallets_audited' => 'integer',
        'discrepancies_count' => 'integer',
        'total_system_liability' => 'float',
        'report_summary' => 'array',
    ];

    const STATUS_CLEAN = 'clean';

    const STATUS_DISCREPANCY_DETECTED = 'discrepancy_detected';
}
