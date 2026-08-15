<?php

namespace App\Models\AliExpress;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Webkul\Category\Models\Category;

class AliExpressCategoryMapping extends Model
{
    protected $table = 'aliexpress_category_mappings';

    protected $fillable = [
        'aliexpress_category_id',
        'target_category_id',
        'hits_count',
        'confidence_score',
        'last_learned_at',
    ];

    protected $casts = [
        'aliexpress_category_id' => 'integer',
        'target_category_id' => 'integer',
        'hits_count' => 'integer',
        'confidence_score' => 'float',
        'last_learned_at' => 'datetime',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'target_category_id');
    }
}
