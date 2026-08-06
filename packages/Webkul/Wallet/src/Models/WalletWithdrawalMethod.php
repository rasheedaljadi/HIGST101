<?php

namespace Webkul\Wallet\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Webkul\Wallet\Contracts\WalletWithdrawalMethod as WalletWithdrawalMethodContract;

class WalletWithdrawalMethod extends Model implements WalletWithdrawalMethodContract
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'wallet_withdrawal_methods';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'status',
        'sort_order',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'status' => 'boolean',
        'sort_order' => 'integer',
    ];
}
