<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * Stores AliExpress OAuth tokens. Token fields are encrypted at rest via the
 * "encrypted" cast so the access/refresh tokens are never persisted in clear.
 *
 * @property string $account
 * @property string $access_token
 * @property string|null $refresh_token
 * @property Carbon|null $access_token_expires_at
 * @property Carbon|null $refresh_token_expires_at
 */
class AliExpressToken extends Model
{
    protected $table = 'aliexpress_tokens';

    protected $fillable = [
        'account',
        'account_id',
        'seller_id',
        'access_token',
        'refresh_token',
        'expires_in',
        'access_token_expires_at',
        'refresh_expires_in',
        'refresh_token_expires_at',
        'payload',
    ];

    protected $hidden = [
        'access_token',
        'refresh_token',
        'payload',
    ];

    protected function casts(): array
    {
        return [
            'access_token' => 'encrypted',
            'refresh_token' => 'encrypted',
            'payload' => 'encrypted:array',
            'access_token_expires_at' => 'datetime',
            'refresh_token_expires_at' => 'datetime',
        ];
    }

    /**
     * Whether the access token is still valid (with a small safety margin).
     */
    public function isAccessTokenValid(int $marginSeconds = 60): bool
    {
        if (empty($this->access_token)) {
            return false;
        }

        if ($this->access_token_expires_at === null) {
            return true;
        }

        return $this->access_token_expires_at->subSeconds($marginSeconds)->isFuture();
    }
}
