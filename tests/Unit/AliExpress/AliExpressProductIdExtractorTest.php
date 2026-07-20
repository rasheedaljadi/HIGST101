<?php

use App\Exceptions\AliExpress\AliExpressImportException;
use App\Services\AliExpress\AliExpressProductIdExtractor;

/*
|--------------------------------------------------------------------------
| AliExpressProductIdExtractor unit tests
|--------------------------------------------------------------------------
|
| Covers Requirements 2.1, 2.2, 2.3, 2.4: deriving a numeric AliExpress
| product id from a raw id, the common product URL shapes, query-param ids,
| and rejecting non-derivable / empty input.
|
*/

beforeEach(function () {
    $this->extractor = new AliExpressProductIdExtractor;
});

it('returns a raw numeric id unchanged', function () {
    expect($this->extractor->extract('1005006789012345'))
        ->toBe('1005006789012345');
});

it('trims surrounding whitespace from a raw numeric id', function () {
    expect($this->extractor->extract("  1005006789012345  \n"))
        ->toBe('1005006789012345');
});

it('extracts the id from each supported URL shape', function (string $url, string $expected) {
    expect($this->extractor->extract($url))->toBe($expected);
})->with([
    'item/<id>.html' => ['https://www.aliexpress.com/item/1005006789012345.html', '1005006789012345'],
    'i/<id>.html' => ['https://www.aliexpress.com/i/1005006789012345.html', '1005006789012345'],
    'product/<id>' => ['https://www.aliexpress.com/product/1005006789012345', '1005006789012345'],
    'generic 6+ digit' => ['https://www.aliexpress.com/store/12345678/foo', '12345678'],
]);

it('extracts the id from the productId query parameter', function () {
    expect($this->extractor->extract('https://www.aliexpress.com/p/detail.html?productId=1005006789012345'))
        ->toBe('1005006789012345');
});

it('extracts the id from the product_id query parameter', function () {
    expect($this->extractor->extract('https://www.aliexpress.com/p/detail.html?spm=a2g0o&product_id=1005006789012345'))
        ->toBe('1005006789012345');
});

it('throws when the input is non-numeric garbage', function () {
    $this->extractor->extract('not-a-real-product');
})->throws(AliExpressImportException::class);

it('throws when the input is an empty string', function () {
    $this->extractor->extract('');
})->throws(AliExpressImportException::class);

it('throws when the input is only whitespace', function () {
    $this->extractor->extract("   \t\n");
})->throws(AliExpressImportException::class);
