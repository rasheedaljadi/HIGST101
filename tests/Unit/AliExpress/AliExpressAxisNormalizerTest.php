<?php

use App\Services\AliExpress\AliExpressAxisNormalizer;

it('normalizes confirmed color aliases to ae_color', function ($input) {
    expect(AliExpressAxisNormalizer::normalizeAxisCode($input))->toBe('ae_color');
})->with([
    'Color',
    'color',
    'COLOUR',
    'Colour',
    'Colors',
    'Color Name',
    'color_name',
    'colour_name',
    'اللون',
    ' اللون ',
    'Color (Name)',
    'Color-Name',
]);

it('normalizes confirmed size aliases to ae_size', function ($input) {
    expect(AliExpressAxisNormalizer::normalizeAxisCode($input))->toBe('ae_size');
})->with([
    'Size',
    'size',
    'SIZES',
    'Sizes',
    'Apparel Size',
    'apparel_size',
    'المقاس',
]);

it('normalizes shoe sizes to ae_shoe_size', function ($input) {
    expect(AliExpressAxisNormalizer::normalizeAxisCode($input))->toBe('ae_shoe_size');
})->with([
    'Shoe Size',
    'shoe_size',
    'Shoes Size',
    'shoes_size',
    'مقاس الحذاء',
]);

it('normalizes shipping aliases to ae_ships_from', function ($input) {
    expect(AliExpressAxisNormalizer::normalizeAxisCode($input))->toBe('ae_ships_from');
})->with([
    'Ships From',
    'ships_from',
    'Shipping From',
    'shipping_from',
    'Dispatched From',
    'dispatched_from',
    'يُشحن من',
    'يشحن من',
]);

it('normalizes plug type aliases to ae_plug_type', function ($input) {
    expect(AliExpressAxisNormalizer::normalizeAxisCode($input))->toBe('ae_plug_type');
})->with([
    'Plug Type',
    'plug_type',
    'Plug',
    'plug',
    'Socket Type',
    'socket_type',
    'نوع القابس',
]);

it('does not merge compound or ambiguous names into ae_color', function ($input, $expectedCode) {
    expect(AliExpressAxisNormalizer::normalizeAxisCode($input))->toBe($expectedCode);
})->with([
    ['Color Pattern', 'ae_color_pattern'],
    ['Color Type', 'ae_color_type'],
    ['Color Classification', 'ae_color_classification'],
    ['Pattern Style', 'ae_pattern_style'],
]);

it('handles null, empty strings, and whitespace safely by returning null', function ($input) {
    expect(AliExpressAxisNormalizer::normalizeAxisCode($input))->toBeNull();
})->with([
    null,
    '',
    '   ',
    '---',
    '()',
    '[]',
]);

it('is idempotent when given already-prefixed codes', function () {
    expect(AliExpressAxisNormalizer::normalizeAxisCode('ae_color'))->toBe('ae_color')
        ->and(AliExpressAxisNormalizer::normalizeAxisCode('ae_color_name'))->toBe('ae_color')
        ->and(AliExpressAxisNormalizer::normalizeAxisCode('ae_size'))->toBe('ae_size')
        ->and(AliExpressAxisNormalizer::normalizeAxisCode('ae_custom_attr'))->toBe('ae_custom_attr');
});

it('returns canonical display names for canonical codes', function () {
    expect(AliExpressAxisNormalizer::getCanonicalDisplayName('Color Name'))->toBe('Color')
        ->and(AliExpressAxisNormalizer::getCanonicalDisplayName('colour'))->toBe('Color')
        ->and(AliExpressAxisNormalizer::getCanonicalDisplayName('Shoe Size'))->toBe('Shoe Size')
        ->and(AliExpressAxisNormalizer::getCanonicalDisplayName('Custom Axis'))->toBe('Custom Axis')
        ->and(AliExpressAxisNormalizer::getCanonicalDisplayName(null))->toBeNull();
});
