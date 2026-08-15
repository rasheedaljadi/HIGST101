<?php

namespace App\Models\AliExpress;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Webkul\Category\Models\Category;

class AliExpressKeywordWeight extends Model
{
    protected $table = 'aliexpress_keyword_weights';

    protected $fillable = [
        'keyword',
        'category_id',
        'frequency',
        'weight',
    ];

    protected $casts = [
        'category_id' => 'integer',
        'frequency' => 'integer',
        'weight' => 'float',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'category_id');
    }
}
