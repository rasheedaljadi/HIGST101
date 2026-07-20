<?php

use App\Models\AliExpressProductImport;
use App\Models\AliExpressToken;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;
use Webkul\Admin\Tests\Concerns\AdminTestBench;
use Webkul\Product\Models\Product;

/*
|--------------------------------------------------------------------------
| ImportErrors — end-to-end error-path feature tests (Task 10.3)
|--------------------------------------------------------------------------
|
| Exercises the admin POST -> AliExpressProductImporter flow for the four
| handled failure / tolerance modes, asserting BOTH the user-facing flash and
| the database side effects:
|
|   1. Duplicate AliExpress id            -> rejected, references existing
|      product, no second product created                       (Req 5.2)
|   2a. No stored token                   -> "authorization required",
|       no product                                              (Req 3.2)
|   2b. Expired token, cannot refresh     -> "missing or expired",
|       no product                                              (Req 3.3)
|   3. API envelope ok=false              -> AliExpress reason surfaced,
|      no product, failed audit row recorded                    (Req 4.3, 12)
|   4. One failing image URL              -> import STILL succeeds; a single
|      bad image never aborts the gallery                       (Req 10.3)
|
| Mirrors the proven scaffolding of ImportConfigurableProductTest /
| ImportSimpleProductTest: Tests\TestCase (DatabaseTransactions) + the
| AdminTestBench trait for loginAsAdmin() so the POST clears the route's
| ['web','admin'] middleware. The AliExpress /sync business gateway and image
| URLs are faked per-test via Http::fake() (NOT in beforeEach) because each
| case needs a different gateway/image response and the first registered stub
| wins. A valid, non-expired AliExpressToken is seeded in beforeEach for the
| cases that reach the API; the token cases delete/replace it explicitly.
|
*/

uses(TestCase::class, AdminTestBench::class);

/**
 * A genuinely valid PNG (built with GD) so the image importer + Bagisto's
 * intervention image processing accept the faked download and re-encode it to
 * webp. A base64 1x1 PNG is rejected by the GD pipeline. Named uniquely to
 * avoid colliding with the fakePngBytes()/fakeSimplePngBytes() helpers the
 * sibling test files declare in the same (Pest-global) function namespace.
 */
function fakeErrorPngBytes(): string
{
    $image = imagecreatetruecolor(2, 2);
    imagefill($image, 0, 0, imagecolorallocate($image, 200, 90, 90));

    ob_start();
    imagepng($image);
    $bytes = (string) ob_get_clean();

    imagedestroy($image);

    return $bytes;
}

beforeEach(function () {
    // Seed a valid, non-expired token so the cases that reach the API pass
    // resolveToken() without a refresh. The token-specific cases override this
    // (delete / replace) at the top of their own test body.
    AliExpressToken::create([
        'account' => 'test-account',
        'access_token' => 'valid-test-access-token',
        'refresh_token' => 'valid-test-refresh-token',
        'access_token_expires_at' => now()->addDays(7),
        'refresh_token_expires_at' => now()->addDays(30),
    ]);
});

/*
|--------------------------------------------------------------------------
| Case 1 — duplicate id rejected with a reference to the existing product
|--------------------------------------------------------------------------
*/
it('rejects a duplicate AliExpress id and references the existing product without creating a second one (Req 5.2)', function () {
    $aliexpressId = '1005007777000003';

    // A prior SUCCESSFUL import already maps this AliExpress id to a Bagisto
    // product (point at a real product id when one exists; the importer's
    // duplicate message only needs the id, not the row to be loadable).
    $existingProductId = (int) (Product::query()->orderBy('id')->value('id') ?? 987654);

    AliExpressProductImport::create([
        'aliexpress_product_id' => $aliexpressId,
        'product_id' => $existingProductId,
        'type' => 'simple',
        'status' => 'success',
        'sku' => 'ae-'.$aliexpressId,
        'variants_count' => 1,
        'images_count' => 1,
    ]);

    // Nothing should hit the network — the duplicate guard runs before token
    // resolution and the API call. Fake everything anyway to surface any stray
    // request as a test failure rather than a real outbound call.
    Http::fake(['*' => Http::response(fakeErrorPngBytes(), 200)]);

    $this->loginAsAdmin();

    $response = $this->post(route('admin.dropshipping.import.store'), [
        'identifier' => $aliexpressId,
    ]);

    $response->assertRedirect(route('admin.dropshipping.import.index'));
    $response->assertSessionMissing('success');
    $response->assertSessionHas('error', fn ($message) => is_string($message)
        && str_contains($message, 'already been imported')
        && str_contains($message, '#'.$existingProductId));

    // No SECOND product was created for this AliExpress id's sku.
    expect(Product::query()->where('sku', 'ae-'.$aliexpressId)->exists())->toBeFalse();

    // Still exactly one import row for the id (the pre-existing success row);
    // the duplicate attempt did not add or mutate anything.
    $rows = AliExpressProductImport::query()
        ->where('aliexpress_product_id', $aliexpressId)
        ->get();

    expect($rows)->toHaveCount(1)
        ->and($rows->first()->status)->toBe('success')
        ->and((int) $rows->first()->product_id)->toBe($existingProductId);
});

/*
|--------------------------------------------------------------------------
| Case 2a — no stored token at all (Req 3.2)
|--------------------------------------------------------------------------
*/
it('aborts with an authorization-required error and creates no product when no token is stored (Req 3.2)', function () {
    $aliexpressId = '1005006666000004';

    // Remove the token seeded in beforeEach so latestToken() returns null.
    AliExpressToken::query()->delete();

    Http::fake(['*' => Http::response(fakeErrorPngBytes(), 200)]);

    $this->loginAsAdmin();

    $response = $this->post(route('admin.dropshipping.import.store'), [
        'identifier' => $aliexpressId,
    ]);

    $response->assertRedirect(route('admin.dropshipping.import.index'));
    $response->assertSessionMissing('success');
    $response->assertSessionHas('error', fn ($message) => is_string($message)
        && str_contains($message, 'authorization required'));

    expect(Product::query()->where('sku', 'ae-'.$aliexpressId)->exists())->toBeFalse();
});

/*
|--------------------------------------------------------------------------
| Case 2b — token exists but is expired and cannot refresh (Req 3.3)
|--------------------------------------------------------------------------
*/
it('aborts with a missing-or-expired error when the only token is expired and cannot refresh (Req 3.3)', function () {
    $aliexpressId = '1005006666000005';

    // Replace the seeded token with an expired one that has NO refresh token,
    // so latestToken() skips the refresh path and hands back the stale token
    // whose isAccessTokenValid() is false.
    AliExpressToken::query()->delete();

    AliExpressToken::create([
        'account' => 'expired-account',
        'access_token' => 'stale-access-token',
        'refresh_token' => null,
        'access_token_expires_at' => now()->subDay(),
        'refresh_token_expires_at' => now()->subDay(),
    ]);

    Http::fake(['*' => Http::response(fakeErrorPngBytes(), 200)]);

    $this->loginAsAdmin();

    $response = $this->post(route('admin.dropshipping.import.store'), [
        'identifier' => $aliexpressId,
    ]);

    $response->assertRedirect(route('admin.dropshipping.import.index'));
    $response->assertSessionMissing('success');
    $response->assertSessionHas('error', fn ($message) => is_string($message)
        && str_contains($message, 'missing or expired'));

    expect(Product::query()->where('sku', 'ae-'.$aliexpressId)->exists())->toBeFalse();
});

/*
|--------------------------------------------------------------------------
| Case 3 — AliExpress returns an error envelope (ok=false) (Req 4.3, 12.3/12.4)
|--------------------------------------------------------------------------
*/
it('surfaces the AliExpress error reason, creates no product, and records a failed audit row when the API responds ok=false (Req 4.3, 12.3, 12.4)', function () {
    $aliexpressId = '1005005555000006';

    $businessUrl = config('aliexpress.business_url');

    // AliExpress error envelope: an `error_response` with a non-zero code +
    // human reason. The api client treats this as ok=false.
    Http::fake([
        $businessUrl => Http::response([
            'error_response' => [
                'code' => '1001',
                'msg' => 'Invalid product id',
            ],
        ], 200),
        '*' => Http::response(fakeErrorPngBytes(), 200),
    ]);

    $this->loginAsAdmin();

    $response = $this->post(route('admin.dropshipping.import.store'), [
        'identifier' => $aliexpressId,
    ]);

    $response->assertRedirect(route('admin.dropshipping.import.index'));
    $response->assertSessionMissing('success');
    $response->assertSessionHas('error', fn ($message) => is_string($message)
        && str_contains($message, 'Invalid product id'));

    // The failure happens before product creation -> no product exists.
    expect(Product::query()->where('sku', 'ae-'.$aliexpressId)->exists())->toBeFalse();

    // A failed audit row is recorded for the id with the reason captured
    // (Req 12.3/12.4 — failures are persisted as status=failed for audit).
    $import = AliExpressProductImport::query()
        ->where('aliexpress_product_id', $aliexpressId)
        ->first();

    expect($import)->not->toBeNull()
        ->and($import->status)->toBe('failed')
        ->and($import->product_id)->toBeNull()
        ->and($import->error)->not->toBeNull()
        ->and($import->error)->toContain('Invalid product id');
});

/*
|--------------------------------------------------------------------------
| Case 4 — one failing image URL is skipped; the import still succeeds (Req 10.3)
|--------------------------------------------------------------------------
*/
it('still imports the product when a single image URL fails to download (Req 10.3)', function () {
    $aliexpressId = '1005008888000002';

    $fixture = json_decode(
        file_get_contents(__DIR__.'/fixtures/ds_product_get_simple.json'),
        true
    );

    $businessUrl = config('aliexpress.business_url');

    // The simple fixture gallery is bottle1.jpg + bottle2.jpg. Make bottle1
    // fail (500) while bottle2 (and any other host) returns a valid PNG. The
    // more specific stub is registered first so it wins over the catch-all.
    Http::fake([
        $businessUrl => Http::response($fixture, 200),
        'img.example.com/bottle1.jpg' => Http::response('', 500),
        '*' => Http::response(fakeErrorPngBytes(), 200, [
            'Content-Type' => 'image/png',
        ]),
    ]);

    $this->loginAsAdmin();

    $response = $this->post(route('admin.dropshipping.import.store'), [
        'identifier' => $aliexpressId,
    ]);

    // A single bad image must NOT abort the import: success flash, no error.
    $response->assertRedirect(route('admin.dropshipping.import.index'));
    $response->assertSessionHas('success');
    $response->assertSessionMissing('error');

    $product = Product::query()
        ->where('sku', 'ae-'.$aliexpressId)
        ->with('images')
        ->first();

    expect($product)->not->toBeNull()
        ->and($product->type)->toBe('simple');

    // The surviving image (bottle2) was still attached, proving the failed url
    // was skipped rather than aborting the gallery.
    expect($product->images->count())->toBeGreaterThanOrEqual(1);

    // The import is recorded as a success despite the skipped image.
    $import = AliExpressProductImport::query()
        ->where('aliexpress_product_id', $aliexpressId)
        ->first();

    expect($import)->not->toBeNull()
        ->and($import->status)->toBe('success');
});
