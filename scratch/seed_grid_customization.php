<?php

use Webkul\Theme\Models\ThemeCustomization;

require dirname(__DIR__) . '/vendor/autoload.php';
$app = require_once dirname(__DIR__) . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$tc = ThemeCustomization::updateOrCreate(
    [
        'name'        => 'المنتجات',
        'type'        => 'product_carousel',
        'channel_id'  => 1,
        'theme_code'  => 'default',
    ],
    [
        'sort_order' => 5,
        'status'     => 1,
        'options'    => [
            'title'        => 'المنتجات',
            'card_style'   => 'grid',
            'display_mode' => 'grid',
            'filters'      => [
                'sort'  => 'created_at-desc',
                'limit' => 12,
            ],
        ],
    ]
);

$tc->translations()->updateOrCreate(
    ['locale' => 'ar'],
    [
        'options' => [
            'title'        => 'المنتجات',
            'card_style'   => 'grid',
            'display_mode' => 'grid',
            'filters'      => [
                'sort'  => 'created_at-desc',
                'limit' => 12,
            ],
        ],
    ]
);

echo "Grid customization created successfully with ID: " . $tc->id . "\n";
