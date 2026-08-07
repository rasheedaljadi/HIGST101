<?php

namespace Webkul\FlashDeal\Models;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Webkul\FlashDeal\Contracts\FlashDeal as FlashDealContract;
use Webkul\FlashDeal\Database\Factories\FlashDealFactory;

class FlashDeal extends Model implements FlashDealContract
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'flash_deals';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'title',
        'status',
        'starts_at',
        'ends_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'status' => 'boolean',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
    ];

    /**
     * Get the products associated with the flash deal.
     */
    public function products(): HasMany
    {
        return $this->hasMany(FlashDealProductProxy::modelClass(), 'flash_deal_id');
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): Factory
    {
        return FlashDealFactory::new();
    }
}
