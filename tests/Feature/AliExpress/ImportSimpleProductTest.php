<?php

use App\Models\AliExpressProductImport;
use App\Models\AliExpressToken;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;
use Webkul\Admin\Tests\Concerns\AdminTestBench;
use Webkul\Attribute\Models\AttributeFamily;
use Webkul\Category\Models\Category;
use Webkul\Product\Models\Product;

/*
|--------------------------------------------------------------------------
| ImportSimpleProduct — end-to-end feature test (Task 10.2)
|--------------------------------------------------------------------------
|
| Exercises the full admin POST -> AliExpressProductImporter flow for a
| SIMPLE AliExpress payload: a single SKU with NO ae_sku_property_dtos, so
| the mapper produces no axes and exactly one variant -> isConfigurable=false
| -> the importer creates a Bagisto `simple` product.
|
| Mirrors ImportConfigurableProductTest's proven scaffolding exactly: the
| AliExpress /sync business gateway and every remote image URL are faked via
| Http::fake(); a valid non-expired AliExpressToken is seeded directly so
| OAuthService::latestToken() + isAccessTokenValid() pass without a refresh.
| Auth uses Bagisto's admin test helper loginAsAdmin() (actingAs on the
| `admin` guard) so the POST clears the route's ['web','admin'] middleware.
|
| Covers Req 6.2 (simple product type), 9.5 (single-SKU price + stock) and
| 10.1 (gallery images).
|
*/

uses(TestCase::class, AdminTestBench::class);

/**
 * A small but genuinely valid PNG so the image importer + Bagisto's
 * intervention image processing (GD driver) accept the faked download and
 * re-encode it to webp. Generated with GD itself so the bytes are guaranteed
 * decodable by the same library Bagisto uses (a base64 1x1 PNG is rejected by
 * the GD pipeline).
 */
function fakeSimplePngBytes(): string
{
    $image = imagecreatetruecolor(2, 2);
    imagefill($image, 0, 0, imagecolorallocate($image, 90, 140, 200));

    ob_start();
    imagepng($image);
    $bytes = (string) ob_get_clean();

    imagedestroy($image);

    return $bytes;
}

beforeEach(function () {
    // Seed a valid, non-expired AliExpress token so resolveToken() succeeds
    // without hitting the refresh path (access_token_expires_at in the future).
    AliExpressToken::create([
        'account' => 'test-account',
        'access_token' => 'valid-test-access-token',
        'refresh_token' => 'valid-test-refresh-token',
        'access_token_expires_at' => now()->addDays(7),
        'refresh_token_expires_at' => now()->addDays(30),
    ]);

    // Fake BOTH the AliExpress business gateway (returns the simple fixture; no
    // top-level error `code`, so the client treats it as ok=true) and every
    // image URL (tiny valid PNG, 200).
    $fixture = json_decode(
        file_get_contents(__DIR__.'/fixtures/ds_product_get_simple.json'),
        true
    );

    $businessUrl = config('aliexpress.business_url');

    Http::fake([
        $businessUrl => Http::response($fixture, 200),
        'img.example.com/*' => Http::response(fakeSimplePngBytes(), 200, [
            'Content-Type' => 'image/png',
        ]),
        // Catch-all for any other image host the importer may hit.
        '*' => Http::response(fakeSimplePngBytes(), 200, [
            'Content-Type' => 'image/png',
        ]),
    ]);
});

/**
 * Drive the admin import POST for the simple fixture and return the persisted
 * simple product, eager-loading the relations the assertions read.
 */
function importSimpleProduct(object $test, string $aliexpressId = '1005008888000002'): Product
{
    $test->loginAsAdmin();

    $response = $test->post(route('admin.dropshipping.import.store'), [
        'identifier' => $aliexpressId,
    ]);

    $response->assertRedirect(route('admin.dropshipping.import.index'));
    $response->assertSessionHas('success');
    $response->assertSessionMissing('error');

    return Product::query()
        ->where('sku', 'ae-'.$aliexpressId)
        ->where('type', 'simple')
        ->with([
            'inventories',
            'categories',
            'images',
        ])
        ->firstOrFail();
}

it('imports a single-SKU AliExpress payload as a simple product end-to-end (Req 6.2)', function () {
    $aliexpressId = '1005008888000002';

    $this->loginAsAdmin();

    $response = $this->post(route('admin.dropshipping.import.store'), [
        'identifier' => $aliexpressId,
    ]);

    // The import succeeds and redirects back to the import page with a success
    // flash (no validation/error redirect).
    $response->assertRedirect(route('admin.dropshipping.import.index'));
    $response->assertSessionHas('success');
    $response->assertSessionMissing('error');

    // A SIMPLE product was created with the expected ae-{id} sku (Req 6.2).
    $product = Product::query()
        ->where('sku', 'ae-'.$aliexpressId)
        ->first();

    expect($product)->not->toBeNull()
        ->and($product->type)->toBe('simple');
});

it('makes the simple product listing-visible (status + visible_individually) (Req 6.5)', function () {
    $product = importSimpleProduct($this);

    expect((int) $product->status)->toBe(1)
        ->and((int) $product->visible_individually)->toBe(1);
});

it('assigns a default category and the default attribute family (Req 6.3, 6.4)', function () {
    $product = importSimpleProduct($this);

    // The importer assigns the product to a real category: the AliExpress
    // category when resolvable, otherwise a browsable default (a child of the
    // root, falling back to the root). Assert at least one valid category is
    // attached and it exists in the catalog.
    $categoryIds = $product->categories->pluck('id')->map(fn ($id) => (int) $id)->all();

    expect($categoryIds)->not->toBeEmpty();

    foreach ($categoryIds as $categoryId) {
        expect(Category::whereKey($categoryId)->exists())->toBeTrue();
    }

    // Default family = the family coded "default" (fallback: first family).
    $defaultFamilyId = (int) (
        AttributeFamily::where('code', 'default')->first()
            ?? AttributeFamily::orderBy('id')->firstOrFail()
    )->id;

    expect((int) $product->attribute_family_id)->toBe($defaultFamilyId);
});

it('sets the product price and inventory quantity from the single SKU (Req 9.5)', function () {
    $product = importSimpleProduct($this);

    // Price is stored on the product's `price` attribute value (float_value),
    // exposed via the model accessor — equals the single SKU offer_sale_price.
    expect((float) $product->price)->toBe(19.99);

    // Stock is stored against the default inventory source (qty) and equals the
    // single SKU sku_available_stock.
    $qty = (int) $product->inventories->sum('qty');
    expect($qty)->toBe(25);
});

it('creates gallery image rows for the simple product (Req 10.1)', function () {
    $product = importSimpleProduct($this);

    // The two gallery urls (bottle1/bottle2) are downloaded and attached to the
    // product. Bagisto may de-dup identical bytes, so assert at least one row.
    expect($product->images->count())->toBeGreaterThanOrEqual(1);
});

it('records a successful simple import row bound to the product with counts (Req 6.2, 9.5, 11.3)', function () {
    $aliexpressId = '1005008888000002';

    $product = importSimpleProduct($this, $aliexpressId);

    $import = AliExpressProductImport::query()
        ->where('aliexpress_product_id', $aliexpressId)
        ->firstOrFail();

    expect($import->status)->toBe('success')
        ->and($import->type)->toBe('simple')
        ->and($import->product_id)->not->toBeNull()
        ->and((int) $import->product_id)->toBe((int) $product->id)
        // One purchasable SKU, two gallery image urls.
        ->and((int) $import->variants_count)->toBe(1)
        ->and((int) $import->images_count)->toBe(2);
});
