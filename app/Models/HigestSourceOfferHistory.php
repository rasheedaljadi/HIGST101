<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Tracks historical shifts in source acquisition cost for trend auditing.
 *
 * @property int $id
 * @property int $source_offer_id
 * @property int $variant_id
 * @property float|null $old_acquisition_cost
 * @property float $new_acquisition_cost
 * @property float|null $old_acquisition_original_cost
 * @property float|null $new_acquisition_original_cost
 * @property string $source_currency
 * @property string $change_trigger
 * @property Carbon $created_at
 */
class HigestSourceOfferHistory extends Model
{
    public $timestamps = false;

    protected $table = 'higest_source_offer_histories';

    protected $fillable = [
        'source_offer_id',
        'variant_id',
        'old_acquisition_cost',
        'new_acquisition_cost',
        'old_acquisition_original_cost',
        'new_acquisition_original_cost',
        'source_currency',
        'change_trigger',
        'created_at',
    ];

    protected $casts = [
        'old_acquisition_cost' => 'decimal:4',
        'new_acquisition_cost' => 'decimal:4',
        'old_acquisition_original_cost' => 'decimal:4',
        'new_acquisition_original_cost' => 'decimal:4',
        'created_at' => 'datetime',
    ];

    public function offer(): BelongsTo
    {
        return $this->belongsTo(HigestSourceOffer::class, 'source_offer_id');
    }
}
