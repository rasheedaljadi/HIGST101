<?php

namespace Webkul\Inventory\Models;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Webkul\Inventory\Contracts\InventorySource as InventorySourceContract;
use Webkul\Inventory\Database\Factories\InventorySourceFactory;
use Webkul\Inventory\Enums\SourceType;

class InventorySource extends Model implements InventorySourceContract
{
    use HasFactory;

    protected $guarded = ['_token'];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'status' => 'boolean',
        'is_salable' => 'boolean',
        'is_delivery_source' => 'boolean',
        'source_type' => SourceType::class,
    ];

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): Factory
    {
        return InventorySourceFactory::new();
    }
}
