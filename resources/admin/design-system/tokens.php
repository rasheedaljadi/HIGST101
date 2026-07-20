<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Design Tokens: Layout & Grid
    |--------------------------------------------------------------------------
    */
    'layout' => [
        'columns' => 12,
        'gap' => 'gap-6', // 1.5rem / 24px
        'padding' => 'pt-3 px-2 sm:px-4 lg:pt-3 lg:px-4',
        'sidebar_width' => [
            'expanded' => 'w-[286px]',
            'collapsed' => 'w-[85px]',
        ],
    ],
    'elevation' => [
        'sm' => 'shadow-sm',
        'md' => 'shadow',
        'lg' => 'shadow-md',
        'xl' => 'shadow-lg',
    ],
    'border_radius' => [
        'default' => 'rounded-lg',
        'sm' => 'rounded-md',
        'lg' => 'rounded-xl',
        'full' => 'rounded-full',
    ],
];
