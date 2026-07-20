<?php

use App\Models\AliExpressProductImport;
use App\Models\AliExpressToken;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;
use Webkul\Admin\Tests\Concerns\AdminTestBench;
use Webkul\Attribute\Models\Attribute;
use Webkul\Attribute\Models\AttributeFamily;
use Webkul\Category\Models\Category;
use Webkul\Product\Models\Product;

/*
|--------------------------------------------------------------------------
| ImportConfigurableProduct — end-to-end feature test (Task 10.1)
|--------------------------------------------------------------------------
|
| Exercises the full admin POST -> AliExpressProductImporter flow for a
| configurable AliExpress payload (Color x Size, 4 SKUs). The AliExpress
| /sync business gateway and every remote image URL are faked via
| Http::fake(); a valid non-expired AliExpressToken is seeded directly so
| OAuthService::latestToken() + isAccessTokenValid() pass without a refresh.
|
| Auth: uses Bagisto's admin test helper loginAsAdmin() (actingAs on the
| `admin` guard) so the POST clears the route's ['web','admin'] middleware,
| matching how every Webkul Admin feature test authenticates. We pull the
| helper in via the AdminTestBench trait rather than the AdminTestCase base
| class (this app-level suite binds Tests\TestCase, the project's DB-test
| convention with DatabaseTransactions against the migrated+seeded MySQL).
|
| Covers (Req 6.1, 8.1, 9.x, 10.1, 11.1, 11.3). PART 1 of two passes — this
| file currently asserts only the configurable-product + success-import-row
| smoke. PART 2 extends the deeper assertions (see the REMAINING block at the
| bottom of this file).
|
*/

uses(TestCase::class, AdminTestBench::class);

/**
 * A small but genuinely valid PNG so the image importer + Bagisto's
 * intervention image processing (GD driver) accept the faked download and
 * re-encode it to webp. Generated with GD itself so the bytes are guaranteed
 * decodable by the same library Bagisto uses.
 */
function fakePngBytes(): string
{
    $image = imagecreatetruecolor(2, 2);
    imagefill($image, 0, 0, imagecolorallocate($image, 120, 120, 120));

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
});

/**
 * Register Http fakes for the AliExpress business gateway (returning the given
 * decoded payload) plus a catch-all valid PNG for image/video downloads. Called
 * per-test (NOT in beforeEach) because Laravel's Http::fake() gives precedence
 * to the first-registered stub for a URL, so each test must register exactly
 * the gateway body it needs.
 */
function fakeAliExpressGateway(array $payload): void
{
    Http::fake([
        config('aliexpress.business_url') => Http::response($payload, 200),
        'img.example.com/*' => Http::response(fakePngBytes(), 200, [
            'Content-Type' => 'image/png',
        ]),
        '*' => Http::response(fakePngBytes(), 200, [
            'Content-Type' => 'image/png',
        ]),
    ]);
}

/**
 * Register the default configurable fixture gateway fake.
 */
function fakeDefaultConfigurableGateway(): void
{
    fakeAliExpressGateway(json_decode(
        file_get_contents(__DIR__.'/fixtures/ds_product_get_configurable.json'),
        true
    ));
}

it('imports a configurable AliExpress product end-to-end via the admin POST (Req 6.1, 11.1, 11.3)', function () {
    fakeDefaultConfigurableGateway();

    $aliexpressId = '1005009999000001';

    $this->loginAsAdmin();

    $response = $this->post(route('admin.dropshipping.import.store'), [
        'identifier' => $aliexpressId,
    ]);

    // The import succeeds and redirects back to the import page with a success
    // flash (no validation/error redirect).
    $response->assertRedirect(route('admin.dropshipping.import.index'));
    $response->assertSessionHas('success');
    $response->assertSessionMissing('error');

    // A configurable product was created with the expected ae-{id} sku.
    $product = Product::query()
        ->where('sku', 'ae-'.$aliexpressId)
        ->where('type', 'configurable')
        ->first();

    expect($product)->not->toBeNull()
        ->and($product->type)->toBe('configurable');

    // The source-reference row records a successful import bound to the product.
    $import = AliExpressProductImport::query()
        ->where('aliexpress_product_id', $aliexpressId)
        ->first();

    expect($import)->not->toBeNull()
        ->and($import->status)->toBe('success')
        ->and($import->product_id)->not->toBeNull()
        ->and((int) $import->product_id)->toBe((int) $product->id);
});

/*
|--------------------------------------------------------------------------
| PART 2 — deeper structural assertions
|--------------------------------------------------------------------------
|
| The cases below reuse the beforeEach token + Http::fake scaffolding above.
| importConfigurableProduct() drives the admin POST once and returns the
| freshly-loaded configurable parent (with the relations each test reads).
| Each test verifies one facet of how the importer actually persists data,
| grounded in the importer + AttributeResolver + ImageImporter source rather
| than assumptions.
|
*/

/**
 * Drive the admin import POST for the configurable fixture and return the
 * persisted configurable parent product, eager-loading the relations the
 * PART 2 assertions read.
 */
function importConfigurableProduct(object $test, string $aliexpressId = '1005009999000001'): Product
{
    fakeDefaultConfigurableGateway();

    $test->loginAsAdmin();

    $response = $test->post(route('admin.dropshipping.import.store'), [
        'identifier' => $aliexpressId,
    ]);

    $response->assertRedirect(route('admin.dropshipping.import.index'));
    $response->assertSessionHas('success');
    $response->assertSessionMissing('error');

    return Product::query()
        ->where('sku', 'ae-'.$aliexpressId)
        ->where('type', 'configurable')
        ->with([
            'super_attributes.options',
            'variants.attribute_values',
            'variants.inventories',
            'variants.images',
            'categories',
            'images',
        ])
        ->firstOrFail();
}

it('resolves the two axis attributes as configurable selects owning the imported option labels (Req 8.1)', function () {
    $product = importConfigurableProduct($this);

    $superCodes = $product->super_attributes->pluck('code')->all();

    // Both axes resolved to the prefixed, slugged codes (ae_ + Str::slug).
    expect($superCodes)->toContain('ae_color')
        ->and($superCodes)->toContain('ae_size')
        ->and($product->super_attributes)->toHaveCount(2);

    foreach ($product->super_attributes as $axis) {
        expect($axis->type)->toBe('select')
            ->and((bool) $axis->is_configurable)->toBeTrue();
    }

    // Each axis owns the imported option labels (Property 4 — option ownership).
    $colorLabels = Attribute::where('code', 'ae_color')->firstOrFail()
        ->options->pluck('admin_name')->all();
    $sizeLabels = Attribute::where('code', 'ae_size')->firstOrFail()
        ->options->pluck('admin_name')->all();

    expect($colorLabels)->toContain('Red')
        ->and($colorLabels)->toContain('Blue')
        ->and($sizeLabels)->toContain('S')
        ->and($sizeLabels)->toContain('M');
});

it('generates 4 permutations, all matched + enabled with their AliExpress price and stock (Req 6.1, 9.1, 9.2, 9.3)', function () {
    $product = importConfigurableProduct($this);

    // Color{Red,Blue} x Size{S,M} = 4 real SKUs -> 4 enabled permutations,
    // 0 unmatched/disabled.
    expect($product->variants)->toHaveCount(4);

    foreach ($product->variants as $variant) {
        expect((int) $variant->status)->toBe(1);
    }

    // Map each variant to its AliExpress price/stock via its option labels so
    // the assertion does not depend on permutation ordering.
    $expected = [
        'Red|S' => ['price' => 29.99, 'stock' => 10],
        'Red|M' => ['price' => 31.99, 'stock' => 5],
        'Blue|S' => ['price' => 30.50, 'stock' => 0],
        'Blue|M' => ['price' => 32.00, 'stock' => 8],
    ];

    $colorOptions = Attribute::where('code', 'ae_color')->firstOrFail()
        ->options->pluck('admin_name', 'id')->all();
    $sizeOptions = Attribute::where('code', 'ae_size')->firstOrFail()
        ->options->pluck('admin_name', 'id')->all();

    $colorAttrId = (int) Attribute::where('code', 'ae_color')->firstOrFail()->id;
    $sizeAttrId = (int) Attribute::where('code', 'ae_size')->firstOrFail()->id;

    foreach ($product->variants as $variant) {
        $colorOptionId = (int) $variant->attribute_values->firstWhere('attribute_id', $colorAttrId)->integer_value;
        $sizeOptionId = (int) $variant->attribute_values->firstWhere('attribute_id', $sizeAttrId)->integer_value;

        $key = $colorOptions[$colorOptionId].'|'.$sizeOptions[$sizeOptionId];

        expect($expected)->toHaveKey($key);

        // Price is stored on the variant's `price` attribute value (float_value).
        expect((float) $variant->price)->toBe($expected[$key]['price']);

        // Stock is stored against the default inventory source (qty).
        $qty = (int) $variant->inventories->sum('qty');
        expect($qty)->toBe($expected[$key]['stock']);
    }

    // Representative parent price = min matched-variant price (Property 5).
    $minVariantPrice = (float) $product->variants->min(fn ($v) => (float) $v->price);
    expect($minVariantPrice)->toBe(29.99);
});

it('stores each variant Color/Size as numeric attribute_options ids, not free text (Req 8.3, 9.4)', function () {
    $product = importConfigurableProduct($this);

    $colorAttr = Attribute::where('code', 'ae_color')->firstOrFail();
    $sizeAttr = Attribute::where('code', 'ae_size')->firstOrFail();

    $colorOptionIds = $colorAttr->options->pluck('id')->map(fn ($id) => (int) $id)->all();
    $sizeOptionIds = $sizeAttr->options->pluck('id')->map(fn ($id) => (int) $id)->all();

    foreach ($product->variants as $variant) {
        $colorValue = $variant->attribute_values->firstWhere('attribute_id', (int) $colorAttr->id);
        $sizeValue = $variant->attribute_values->firstWhere('attribute_id', (int) $sizeAttr->id);

        expect($colorValue)->not->toBeNull()
            ->and($sizeValue)->not->toBeNull();

        // The select option value lives in integer_value and references an
        // existing option id owned by that attribute (Property 3).
        expect($colorValue->integer_value)->not->toBeNull()
            ->and($colorOptionIds)->toContain((int) $colorValue->integer_value);

        expect($sizeValue->integer_value)->not->toBeNull()
            ->and($sizeOptionIds)->toContain((int) $sizeValue->integer_value);
    }
});

it('creates gallery image rows on the parent and per-SKU image rows on the variants (Req 10.1)', function () {
    $product = importConfigurableProduct($this);

    // Gallery images (g1/g2/g3) are attached to the parent after the unified
    // update. Bagisto may de-dup identical bytes, so assert at least one row.
    expect($product->images->count())->toBeGreaterThanOrEqual(1);

    // Every matched variant carries a per-SKU image (red.jpg / blue.jpg), so
    // image rows exist for variants too.
    $variantImageCount = $product->variants->sum(fn ($v) => $v->images->count());
    expect($variantImageCount)->toBeGreaterThanOrEqual(1);
});

it('assigns a default category and the default attribute family (Req 6.3, 6.4)', function () {
    $product = importConfigurableProduct($this);

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

it('makes the product listing-visible (status + visible_individually) (Req 6.5)', function () {
    $product = importConfigurableProduct($this);

    expect((int) $product->status)->toBe(1)
        ->and((int) $product->visible_individually)->toBe(1);
});

it('records variants_count and images_count on the import row (Req 11.3)', function () {
    $aliexpressId = '1005009999000001';

    importConfigurableProduct($this, $aliexpressId);

    $import = AliExpressProductImport::query()
        ->where('aliexpress_product_id', $aliexpressId)
        ->firstOrFail();

    // 4 matched SKUs, 3 gallery image urls.
    expect((int) $import->variants_count)->toBe(4)
        ->and((int) $import->images_count)->toBe(3);
});

/*
|--------------------------------------------------------------------------
| Discount (special_price) on configurable variants
|--------------------------------------------------------------------------
|
| Bagisto's Configurable::updateVariant() does not persist special_price, so
| the importer writes it directly onto each matched variant's attribute value.
| This guards the regression where variants stored the original/list price as
| their price with a NULL special_price (charging customers double).
|
*/
it('stores list price as variant price and sale price as special_price for discounted variants', function () {
    $aliexpressId = '1005000000123456';

    $payload = [
        'aliexpress_ds_product_get_response' => [
            'rsp_code' => 200,
            'result' => [
                'ae_item_base_info_dto' => [
                    'subject' => 'Discounted Configurable Product',
                    'detail' => '<p>desc</p>',
                ],
                'ae_multimedia_info_dto' => [
                    'image_urls' => 'https://img.example.com/d1.jpg',
                ],
                'ae_item_sku_info_dtos' => [
                    'ae_item_sku_info_d_t_o' => [
                        [
                            'sku_id' => 'disc-red',
                            'offer_sale_price' => '5.49',
                            'sku_price' => '10.97',
                            'sku_available_stock' => 10,
                            'ae_sku_property_dtos' => [
                                'ae_sku_property_d_t_o' => [
                                    ['sku_property_name' => 'Color', 'sku_property_value' => 'Red'],
                                ],
                            ],
                        ],
                        [
                            'sku_id' => 'disc-blue',
                            'offer_sale_price' => '5.33',
                            'sku_price' => '10.67',
                            'sku_available_stock' => 8,
                            'ae_sku_property_dtos' => [
                                'ae_sku_property_d_t_o' => [
                                    ['sku_property_name' => 'Color', 'sku_property_value' => 'Blue'],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ];

    Http::fake([
        config('aliexpress.business_url') => Http::response($payload, 200),
        '*' => Http::response(fakePngBytes(), 200, ['Content-Type' => 'image/png']),
    ]);

    $this->loginAsAdmin();

    $this->post(route('admin.dropshipping.import.store'), ['identifier' => $aliexpressId])
        ->assertSessionHas('success')
        ->assertSessionMissing('error');

    $product = Product::query()
        ->where('sku', 'ae-'.$aliexpressId)
        ->where('type', 'configurable')
        ->with('variants')
        ->firstOrFail();

    $specialPriceAttributeId = (int) Attribute::where('code', 'special_price')->value('id');

    foreach ($product->variants as $variant) {
        // Read special_price straight from product_attribute_values (the
        // storefront price helpers read it there); variant models don't expose
        // it via the family-based accessor.
        $specialPrice = (float) DB::table('product_attribute_values')
            ->where('product_id', $variant->id)
            ->where('attribute_id', $specialPriceAttributeId)
            ->value('float_value');

        // Each matched variant keeps the original/list price as its price and
        // the sale price as special_price (the discount), never zero/null.
        expect((float) $variant->price)->toBeGreaterThan($specialPrice)
            ->and($specialPrice)->toBeGreaterThan(0.0);
    }

    // Spot-check the red variant's exact numbers.
    $red = $product->variants->first(fn ($v) => (float) $v->price === 10.97);
    expect($red)->not->toBeNull();

    $redSpecial = (float) DB::table('product_attribute_values')
        ->where('product_id', $red->id)
        ->where('attribute_id', $specialPriceAttributeId)
        ->value('float_value');

    expect($redSpecial)->toBe(5.49);
});
