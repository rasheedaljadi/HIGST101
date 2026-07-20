<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Holds the AliExpress Open Platform credentials managed from the admin
 * "Key Management" page. A single row is used (id = 1); {@see self::current()}
 * always returns that row (creating an empty one on first access).
 *
 * The app_secret is encrypted at rest via the "encrypted" cast so it is never
 * stored in clear text. These DB values take precedence over the .env defaults
 * (see App\Providers\AppServiceProvider::applyAliExpressSettings()).
 *
 * @property string|null $app_key
 * @property string|null $app_secret
 * @property string|null $redirect_uri
 * @property string|null $authorize_url
 * @property string|null $token_url
 * @property string|null $business_url
 * @property string|null $sign_method
 */
class AliExpressSetting extends Model
{
    protected $table = 'aliexpress_settings';

    protected $fillable = [
        'app_key',
        'app_secret',
        'redirect_uri',
        'authorize_url',
        'token_url',
        'business_url',
        'sign_method',
        'shipping_margin',
        'shipping_extra_days',
        'shipping_enabled',
        'sync_enabled',
        'sync_schedule',
        'inventory_buffer',
        'price_change_limit',
        'stock_sync_enabled',
    ];

    protected $hidden = [
        'app_secret',
    ];

    protected function casts(): array
    {
        return [
            'app_secret' => 'encrypted',
            'shipping_margin' => 'decimal:4',
            'shipping_extra_days' => 'integer',
            'shipping_enabled' => 'boolean',
            'sync_enabled' => 'boolean',
            'inventory_buffer' => 'integer',
            'price_change_limit' => 'decimal:4',
            'stock_sync_enabled' => 'boolean',
        ];
    }

    /**
     * The single settings row, created empty on first access.
     */
    public static function current(): self
    {
        return static::query()->firstOrCreate(['id' => 1]);
    }

    /**
     * Whether the minimum credentials (key + secret) are configured.
     */
    public function isConfigured(): bool
    {
        return ! empty($this->app_key) && ! empty($this->app_secret);
    }
}
