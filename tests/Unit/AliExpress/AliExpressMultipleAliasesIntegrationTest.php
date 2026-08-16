<?php

use App\Services\AliExpress\AliExpressProductMapper;
use App\Services\AliExpress\DTO\NormalizedProduct;

it('merges multiple color alias names into a single canonical ae_color axis during mapping', function () {
    $mapper = new AliExpressProductMapper;

    // Simulate an AliExpress payload where SKU 1 uses "Color", SKU 2 uses "Color Name", and SKU 3 uses "Colour"
    $rawPayload = [
        'aliexpress_ds_product_get_response' => [
            'result' => [
                'ae_item_base_info_dto' => [
                    'subject' => 'Test Multi-Alias Product',
                    'detail' => '<p>Description</p>',
                ],
                'ae_item_sku_info_dtos' => [
                    'ae_item_sku_info_d_t_o' => [
                        [
                            'sku_id' => '111',
                            'offer_sale_price' => '10.00',
                            'sku_available_stock' => 50,
                            'ae_sku_property_dtos' => [
                                'ae_sku_property_d_t_o' => [
                                    [
                                        'sku_property_name' => 'Color',
                                        'property_value_definition_name' => 'Red',
                                    ],
                                    [
                                        'sku_property_name' => 'Size',
                                        'property_value_definition_name' => 'M',
                                    ],
                                ],
                            ],
                        ],
                        [
                            'sku_id' => '222',
                            'offer_sale_price' => '10.00',
                            'sku_available_stock' => 50,
                            'ae_sku_property_dtos' => [
                                'ae_sku_property_d_t_o' => [
                                    [
                                        'sku_property_name' => 'Color Name',
                                        'property_value_definition_name' => 'Blue',
                                    ],
                                    [
                                        'sku_property_name' => 'Size Name',
                                        'property_value_definition_name' => 'L',
                                    ],
                                ],
                            ],
                        ],
                        [
                            'sku_id' => '333',
                            'offer_sale_price' => '10.00',
                            'sku_available_stock' => 50,
                            'ae_sku_property_dtos' => [
                                'ae_sku_property_d_t_o' => [
                                    [
                                        'sku_property_name' => 'Colour',
                                        'property_value_definition_name' => 'Green',
                                    ],
                                    [
                                        'sku_property_name' => 'Sizes',
                                        'property_value_definition_name' => 'XL',
                                    ],
                                ],
                            ],
                        ],
                        [
                            'sku_id' => '444',
                            'offer_sale_price' => '10.00',
                            'sku_available_stock' => 50,
                            'ae_sku_property_dtos' => [
                                'ae_sku_property_d_t_o' => [
                                    [
                                        'sku_property_name' => 'Color_Name',
                                        'property_value_definition_name' => 'Yellow',
                                    ],
                                    [
                                        'sku_property_name' => 'Apparel Size',
                                        'property_value_definition_name' => 'XXL',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ];

    $product = $mapper->map($rawPayload, '1005009999999999');

    expect($product)->toBeInstanceOf(NormalizedProduct::class);

    // Exactly 2 axes must be produced: ae_color and ae_size (NOT 8 separate axes)
    expect($product->axes)->toHaveCount(2);

    $axesByCode = [];
    foreach ($product->axes as $axis) {
        $axesByCode[$axis->code] = $axis;
    }

    expect($axesByCode)->toHaveKey('ae_color')
        ->and($axesByCode)->toHaveKey('ae_size');

    // All color values must be merged under ae_color
    $colorAxis = $axesByCode['ae_color'];
    expect($colorAxis->name)->toBe('Color')
        ->and($colorAxis->values)->toContain('Red')
        ->and($colorAxis->values)->toContain('Blue')
        ->and($colorAxis->values)->toContain('Green')
        ->and($colorAxis->values)->toContain('Yellow')
        ->and($colorAxis->values)->toHaveCount(4);

    // All size values must be merged under ae_size
    $sizeAxis = $axesByCode['ae_size'];
    expect($sizeAxis->name)->toBe('Size')
        ->and($sizeAxis->values)->toContain('M')
        ->and($sizeAxis->values)->toContain('L')
        ->and($sizeAxis->values)->toContain('XL')
        ->and($sizeAxis->values)->toContain('XXL')
        ->and($sizeAxis->values)->toHaveCount(4);

    // Check that variants correctly store their options
    expect($product->variants)->toHaveCount(4);
    expect($product->variants[0]->optionsByAxis)->toHaveKey('Color')
        ->and($product->variants[0]->optionsByAxis['Color'])->toBe('Red')
        ->and($product->variants[0]->optionsByAxis['Size'])->toBe('M');
    expect($product->variants[1]->optionsByAxis)->toHaveKey('Color')
        ->and($product->variants[1]->optionsByAxis['Color'])->toBe('Blue')
        ->and($product->variants[1]->optionsByAxis['Size'])->toBe('L');
    expect($product->variants[2]->optionsByAxis)->toHaveKey('Color')
        ->and($product->variants[2]->optionsByAxis['Color'])->toBe('Green')
        ->and($product->variants[2]->optionsByAxis['Size'])->toBe('XL');
    expect($product->variants[3]->optionsByAxis)->toHaveKey('Color')
        ->and($product->variants[3]->optionsByAxis['Color'])->toBe('Yellow')
        ->and($product->variants[3]->optionsByAxis['Size'])->toBe('XXL');
});
