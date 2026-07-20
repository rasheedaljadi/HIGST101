<?php

namespace Webkul\Fulfillment\Models;

use Illuminate\Database\Eloquent\Model;
use Webkul\Fulfillment\Contracts\ProviderAccount as ProviderAccountContract;

class ProviderAccount extends Model implements ProviderAccountContract
{
    protected $table = 'provider_accounts';

    protected $fillable = [
        'provider',
        'name',
        'app_key',
        'app_secret',
        'access_token',
        'refresh_token',
        'status',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'app_secret'    => 'encrypted',
            'access_token'  => 'encrypted',
            'refresh_token' => 'encrypted',
        ];
    }
}
