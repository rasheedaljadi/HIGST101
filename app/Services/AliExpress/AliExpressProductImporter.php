<?php

namespace App\Services\AliExpress;

use App\Exceptions\AliExpress\AliExpressImportException;
use App\Models\AliExpressProductImport;
use App\Models\AliExpressToken;
use App\Services\AliExpress\DTO\NormalizedProduct;
use App\Services\AliExpress\DTO\NormalizedVariant;
use App\Services\AliExpress\DTO\ResolvedAxes;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;
use Webkul\Attribute\Models\Attribute;
use Webkul\Attribute\Repositories\AttributeFamilyRepository;
use Webkul\Category\Models\Category;
use Webkul\Category\Repositories\CategoryRepository;
use Webkul\Core\Models\Locale;
use Webkul\Product\Helpers\Indexers\Flat;
use Webkul\Product\Helpers\Indexers\Inventory as InventoryIndexer;
use Webkul\Product\Helpers\Indexers\Price as PriceIndexer;
use Webkul\Product\Models\Product;
use Webkul\Product\Models\ProductAttributeValue;
use Webkul\Product\Repositories\ProductRepository;

/**
 * Orchestrates the synchronous import of a single AliExpress product into the
 * Bagisto catalog.
 *
 * The single public entry point is {@see self::import()}. The flow is split
 * into small private steps so each phase is individually readable and testable:
 *
 *   1. extractId()        — derive the numeric AliExpress product id.
 *   2. ensureNotDuplicate() — reject products already imported (Req 5.2).
 *   3. resolveToken()     — obtain a valid stored OAuth token (Req 3).
 *   4. fetchPayload()     — call aliexpress.ds.product.get (Req 4).
 *   5. map payload -> NormalizedProduct DTO (Req 4.4 "not found").
 *   6a. createConfigurableProduct() — configurable create + variant
 *       reconciliation: builds (but does not apply) the variants payload
 *       (subtask 7.2).
 *   6b. createSimpleProduct() — single-SKU simple create returning
 *       price/inventory for the unified 7.4 update (subtask 7.3).
 *   7. finalizeProduct() — ONE unified parent update() carrying shared fields
 *      (name/SEO/url_key/categories/status) AND the type-specific payload
 *      (variants for configurable; price + inventories for simple), then
 *      gallery image attach, then source-reference persistence (subtask 7.4).
 *
 * Atomicity (Property 7 / Req 12.3): create + unified update + image attach +
 * the success source-reference all run inside a single DB::transaction(). A
 * mid-create failure rolls the whole product back. The "failed" audit row is
 * written in the catch block AFTER the rollback so it survives.
 *
 * Ordering note: Bagisto's AbstractType::update() unconditionally re-syncs
 * images from $data['images'] (deleting any not present) and Configurable's
 * update() deletes every variant not present in $data['variants']. Therefore
 * gallery images are attached AFTER the final shared-field update (never
 * before, or they would be wiped), and the unified update always carries the
 * full variants payload so no permutation is dropped.
 *
 * All handled failure modes throw {@see AliExpressImportException}; every branch
 * logs to the "aliexpress" channel with ids/codes/counts only — never secrets.
 */
class AliExpressProductImporter
{
    /**
     * Optional progress reporter invoked at each import phase.
     * Signature: fn(string $step, int $percent, string $message): void
     *
     * @var callable|null
     */
    protected $progressCallback = null;

    public function __construct(
        protected AliExpressProductIdExtractor $extractor,
        protected AliExpressOAuthService $oauthService,
        protected AliExpressApiClient $apiClient,
        protected AliExpressProductMapper $mapper,
        protected AliExpressAttributeResolver $attributeResolver,
        protected AliExpressImageImporter $imageImporter,
        protected ProductRepository $productRepository,
        protected AttributeFamilyRepository $attributeFamilyRepository,
        protected CategoryRepository $categoryRepository,
        protected AliExpressCategorySynchronizer $categorySynchronizer,
        protected AliExpressCategoryGuesser $categoryGuesser,
        protected Flat $flatIndexer,
        protected InventoryIndexer $inventoryIndexer,
        protected PriceIndexer $priceIndexer,
        protected AliExpressFreightService $freightService,
    ) {}

    /**
     * Register a progress reporter used to stream import phase updates (e.g.
     * for a Server-Sent-Events progress bar). Returns $this for chaining.
     */
    public function onProgress(?callable $callback): static
    {
        $this->progressCallback = $callback;

        return $this;
    }

    /**
     * Emit a progress update (no-op when no callback is registered).
     */
    protected function reportProgress(string $step, int $percent, string $message): void
    {
        if ($this->progressCallback !== null) {
            ($this->progressCallback)($step, $percent, $message);
        }
    }

    /**
     * Import one AliExpress product (by id or URL) into the Bagisto catalog.
     *
     * @throws AliExpressImportException for all handled failure modes.
     */
    public function import(string $rawInput, array $options = []): AliExpressProductImport
    {
        // --- Pre-create flow (subtask 7.1) ---------------------------------
        $this->reportProgress('extract', 5, 'يتم فحص الرابط...');
        $id = $this->extractId($rawInput);

        $this->ensureNotDuplicate($id);

        $this->reportProgress('token', 12, 'يتم التحقق من الصلاحية...');
        $token = $this->resolveToken();

        // Fetch + map run BEFORE the create transaction. A failure here (API
        // ok=false per Req 4.3, or "product not found" per Req 4.4) still has to
        // leave a status=failed audit row so the failure is recorded for the
        // admin (design error-handling table / Req 12.3, 12.4). There is no DTO
        // yet, so the row is keyed by the AliExpress id.
        try {
            $this->reportProgress('fetch', 25, 'يتم قراءة معلومات المنتج...');
            $result = $this->fetchPayload($id, $token, $options);

            // Mapper throws "not found" when the payload carries no base info (Req 4.4).
            $this->reportProgress('map', 40, 'يتم تحليل بيانات المنتج...');
            $dto = $this->mapper->map($result['body'], $id);

            // Enrich with real per-locale display text (e.g. Arabic seller
            // content) by re-fetching the product in each additional store
            // language. Structure (axes/variants/images/prices) stays from the
            // primary-language body above; only display text is localized.
            $dto = $this->enrichLocalizedText($dto, $id, $token, $result['body']);
        } catch (Throwable $e) {
            $this->recordFailureForId($id, $e);

            throw $e;
        }

        Log::channel('aliexpress')->info('AliExpress product payload mapped', [
            'aliexpress_product_id' => $id,
            'is_configurable' => $dto->isConfigurable,
            'variants_count' => count($dto->variants),
            'images_count' => count($dto->imageUrls),
        ]);

        // --- Create flow (atomic) ------------------------------------------
        // create + unified shared-field update + gallery images + the success
        // source-reference all run inside one transaction so a mid-create error
        // rolls the entire product back (Property 7 / Req 12.3). The "failed"
        // audit row is written in the catch block, AFTER the rollback, so it
        // survives (Req 12.3, 12.4).
        try {
            return DB::transaction(function () use ($dto) {
                $type = $dto->isConfigurable ? 'configurable' : 'simple';

                if ($dto->isConfigurable) {
                    $this->reportProgress('variants', 55, 'يتم استيراد المتغيرات (اللون/المقاس)...');
                    $created = $this->createConfigurableProduct($dto);
                } else {
                    $this->reportProgress('create', 55, 'يتم إنشاء المنتج...');
                    $created = $this->createSimpleProduct($dto);
                }

                return $this->finalizeProduct($dto, $created, $type);
            });
        } catch (Throwable $e) {
            $this->recordFailure($dto, $e);

            if ($e instanceof AliExpressImportException) {
                throw $e;
            }

            throw new AliExpressImportException(
                'AliExpress product import failed: '.$e->getMessage(),
                ['aliexpress_product_id' => $id],
                $e
            );
        }
    }

    /**
     * Subtask 7.4 — apply the unified parent update (shared fields, SEO,
     * url_key, category, plus the type-specific price/variants/inventories),
     * attach the gallery images, and persist the success source-reference.
     *
     * Runs inside the import() transaction. Returns the persisted
     * {@see AliExpressProductImport} row (status=success).
     *
     * @param  array<string, mixed>  $created  Return of createConfigurable/SimpleProduct().
     * @param  'configurable'|'simple'  $type
     */
    protected function finalizeProduct(NormalizedProduct $dto, array $created, string $type): AliExpressProductImport
    {
        /** @var Product $product */
        $product = $created['product'];

        $urlKey = $this->buildUniqueUrlKey($dto, $product->id);

        // The channel's default locale drives the main create/update (see the
        // long note below). Its display text comes from that locale's content.
        $primaryLocale = core()->getDefaultLocaleCodeFromDefaultChannel();
        $primaryText = $dto->textForLocale($this->matchLocaleKey($dto, $primaryLocale));

        // Single unified parent update carrying shared fields AND the
        // type-specific payload. For configurable we MUST include the full
        // variants payload (Configurable::update deletes any variant absent
        // from it); for simple we include price + inventories so the SKU's
        // price/stock are saved in the same call (Req 7.x, 9.5).
        $data = [
            'channel' => core()->getDefaultChannelCode(),
            // Use the channel's default locale (NOT app()->getLocale()).
            // Bagisto's Configurable::create() persists each generated variant's
            // `name` attribute value under core()->getDefaultLocaleCodeFromDefaultChannel().
            // saveValues() matches existing values with a case-sensitive PHP
            // comparison, but the product_attribute_values.unique_id index is
            // case-insensitive in MySQL. If these two locale strings differ only
            // by case (e.g. channel default "AR" vs app locale "ar"), the update
            // path fails to find the existing row and tries to INSERT a duplicate,
            // hitting a 1062 unique-constraint violation. Aligning on the channel
            // default locale keeps create and update writing the same rows.
            'locale' => $primaryLocale,
            'sku' => $product->sku,
            'name' => $primaryText['title'],
            'url_key' => $urlKey,
            'short_description' => $primaryText['shortDescription'],
            'description' => $primaryText['description'],
            'meta_title' => $primaryText['metaTitle'] ?? $primaryText['title'],
            'meta_keywords' => $primaryText['metaKeywords'],
            'meta_description' => $primaryText['metaDescription'],
            'status' => 1,
            'visible_individually' => 1,
            'weight' => 0,
            'tax_category_id' => '',
            'price' => $created['price'],
            'categories' => [$this->resolveProductCategoryId($dto)],
        ];

        if ($type === 'configurable') {
            $data['variants'] = $created['variants_payload'];
        } else {
            $data['inventories'] = $created['inventories'];

            // Discount: when the AliExpress SKU has an original/list price
            // higher than the sale price, store the list price as the regular
            // price and the sale price as Bagisto's special_price so the
            // storefront shows a struck-through original + discounted price.
            if (! empty($created['special_price'])) {
                $data['price'] = $created['regular_price'];
                $data['special_price'] = $created['special_price'];
            }
        }

        $this->reportProgress('content', 70, 'يتم استيراد الوصف والسعر والمخزون...');
        $this->productRepository->update($data, $product->id);

        // Store the display text for every OTHER store locale (e.g. genuine
        // Arabic seller content). These are text-only, per-locale updates: the
        // `name`/`description`/SEO attributes are value_per_locale, so writing
        // them under a different `locale` adds a parallel translation without
        // touching prices, inventories, variants, or images.
        $this->storeLocalizedText($product, $dto, $urlKey, $primaryLocale);

        // Persist per-variant special_price directly (configurable only).
        // Bagisto's Configurable::updateVariant() ignores special_price (it is
        // not in its fillable variant codes), so the unified update above can't
        // carry it. Write it straight to the variants' attribute values so the
        // discounted sale price is shown alongside the struck-through original.
        if ($type === 'configurable' && ! empty($created['variant_special_prices'])) {
            $this->applyVariantSpecialPrices($created['variant_special_prices']);
        }

        // Save aliexpress_sku_id EAV and create external_variant_projections
        if ($type === 'configurable') {
            $this->applyVariantSkuIdsAndProjections($product, $created['resolved_axes'], $dto);
        } else {
            $this->applySimpleSkuIdAndProjection($product, $dto);
        }

        // Gallery images are attached AFTER the shared-field update: that
        // update re-syncs images from its own payload and would otherwise wipe
        // them. Per-image failures are swallowed inside the importer (Req 10.3)
        // and are intentionally excluded from the atomicity guarantee.
        $product = Product::findOrFail($product->id);

        $this->reportProgress('images', 82, 'يتم استيراد الصور...');
        $this->imageImporter->attachToProduct($dto->imageUrls, $product);

        // Product videos (e.g. AliExpress mp4) are attached the same way:
        // best-effort, after the shared-field update, failures swallowed.
        if (! empty($dto->videoUrls)) {
            $this->reportProgress('videos', 90, 'يتم استيراد الفيديو...');
        }
        $this->imageImporter->attachVideosToProduct($dto->videoUrls, $product);

        // Flat-index the product (and its variants) so it appears immediately
        // in the admin products grid and storefront. The grid reads from
        // product_flat, which is otherwise only populated by the indexer
        // command / admin save flow — not by a programmatic create.
        //
        // The inventory + price indices MUST be rebuilt first: a configurable's
        // variants are only "saleable" (and therefore only shown in the
        // storefront option dropdowns) when product_inventory_indices has a
        // positive qty for them. A programmatic create does not populate these
        // index tables, so without this the color/size dropdown renders empty.
        $this->reportProgress('index', 95, 'يتم فهرسة المنتج...');
        try {
            $product = Product::with(['variants'])->findOrFail($product->id);

            $toIndex = collect([$product])->merge($product->variants)->all();

            $this->inventoryIndexer->reindexBatch($toIndex);
            $this->priceIndexer->reindexBatch($toIndex);

            foreach ($toIndex as $indexable) {
                $this->flatIndexer->refresh($indexable);
            }
        } catch (Throwable $e) {
            Log::channel('aliexpress')->warning('Indexing after import failed; run indexer:index', [
                'product_id' => $product->id,
                'message' => $e->getMessage(),
            ]);
        }

        $reference = $this->persistSuccess($dto, $product, $type, $created, $urlKey);

        Log::channel('aliexpress')->info('AliExpress product import succeeded', [
            'aliexpress_product_id' => $dto->aliexpressProductId,
            'product_id' => $product->id,
            'type' => $type,
            'sku' => $product->sku,
            'url_key' => $urlKey,
            'variants_count' => (int) $created['variants_count'],
            'images_count' => count($dto->imageUrls),
            'videos_count' => count($dto->videoUrls),
            'import_id' => $reference->id,
        ]);

        return $reference;
    }

    /**
     * Store display text for every store locale other than the primary one.
     *
     * Each locale gets a minimal, text-only `update()` under its own `locale`
     * code. Because name/description/short_description/meta_title/url_key are
     * value_per_locale attributes, this writes a parallel translation row per
     * field without disturbing prices, inventories, variants, or images (which
     * are non-localized or already written by the primary update). The url_key
     * is intentionally shared across locales (generated from the Latin title).
     */
    protected function storeLocalizedText(
        Product $product,
        NormalizedProduct $dto,
        string $urlKey,
        string $primaryLocale,
    ): void {
        $channel = core()->getDefaultChannelCode();
        $primaryKey = strtolower(preg_replace('/[_-].*$/', '', $primaryLocale) ?? $primaryLocale);

        foreach ($this->storeImportLocales() as $locale) {
            // Skip the locale already written by the primary update.
            if (strtolower($locale) === strtolower($primaryLocale)) {
                continue;
            }

            $localeKey = $this->matchLocaleKey($dto, $locale);

            // Avoid re-writing the same content under an equivalent language
            // (e.g. primary "en" already covers another "en_*" locale).
            if (strtolower(preg_replace('/[_-].*$/', '', $localeKey) ?? $localeKey) === $primaryKey
                && ! isset($dto->localizedText[$locale])) {
                continue;
            }

            $text = $dto->textForLocale($localeKey);

            try {
                $this->productRepository->update([
                    'channel' => $channel,
                    'locale' => $locale,
                    'sku' => $product->sku,
                    'name' => $text['title'],
                    'url_key' => $urlKey,
                    'short_description' => $text['shortDescription'],
                    'description' => $text['description'],
                    'meta_title' => $text['metaTitle'] ?? $text['title'],
                    'meta_keywords' => $text['metaKeywords'],
                    'meta_description' => $text['metaDescription'],
                ], $product->id, attributes: ['name', 'url_key', 'short_description', 'description', 'meta_title', 'meta_keywords', 'meta_description']);
            } catch (Throwable $e) {
                Log::channel('aliexpress')->warning('Storing localized product text failed; locale left to fall back', [
                    'product_id' => $product->id,
                    'locale' => $locale,
                    'message' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Resolve which localizedText key best matches a store locale: an exact
     * match first, then the language part (e.g. "ar" for "ar"/"AR"/"ar_SA"),
     * else the locale itself (textForLocale then falls back to primary).
     */
    protected function matchLocaleKey(NormalizedProduct $dto, string $locale): string
    {
        if (isset($dto->localizedText[$locale])) {
            return $locale;
        }

        $lang = strtolower(preg_replace('/[_-].*$/', '', $locale) ?? $locale);

        foreach (array_keys($dto->localizedText) as $key) {
            $keyLang = strtolower(preg_replace('/[_-].*$/', '', $key) ?? $key);

            if ($keyLang === $lang) {
                return $key;
            }
        }

        return $locale;
    }

    /**
     * Persist (or update) the success source-reference row (Req 5.1, 11.3,
     * Property 1). Keyed by the unique aliexpress_product_id so a prior failed
     * attempt for the same id is promoted to success rather than duplicated.
     *
     * @param  array<string, mixed>  $created
     */
    protected function persistSuccess(
        NormalizedProduct $dto,
        Product $product,
        string $type,
        array $created,
        string $urlKey,
    ): AliExpressProductImport {
        $shipping = $this->resolveShipping($dto);

        return AliExpressProductImport::updateOrCreate(
            ['aliexpress_product_id' => $dto->aliexpressProductId],
            [
                'product_id' => $product->id,
                'type' => $type,
                'status' => 'success',
                'sku' => $product->sku,
                'variants_count' => (int) $created['variants_count'],
                'images_count' => count($dto->imageUrls),
                'error' => null,
                'payload_snapshot' => $this->payloadSnapshot($dto, $urlKey),
                'base_shipping_cost' => $shipping['cost'] ?? null,
                'shipping_currency' => $shipping['currency'] ?? null,
                'shipping_min_days' => $shipping['min_days'] ?? null,
                'shipping_max_days' => $shipping['max_days'] ?? null,
                'shipping_company' => $shipping['company'] ?? null,
                'shipping_tracking' => $shipping['tracking'] ?? null,
                'shipping_synced_at' => $shipping !== null ? now() : null,
            ]
        );
    }

    /**
     * Best-effort: fetch and cache the product's AliExpress shipping (ship-to
     * SA / store currency) so the storefront carrier can price shipping locally
     * without ever calling the API at checkout. A failure here never aborts the
     * import — shipping simply stays unset and can be backfilled by the resync
     * command.
     *
     * @return array{cost:float, currency:string, min_days:?int, max_days:?int, company:?string, tracking:bool}|null
     */
    protected function resolveShipping(NormalizedProduct $dto): ?array
    {
        try {
            // Use the cheapest variant's SKU id as the freight reference (the
            // representative variant the parent price is based on).
            $skuId = $dto->variants[0]->skuId ?? null;

            return $this->freightService->quote($dto->aliexpressProductId, $skuId);
        } catch (Throwable $e) {
            Log::channel('aliexpress')->warning('Shipping capture failed during import; left unset', [
                'aliexpress_product_id' => $dto->aliexpressProductId,
                'message' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Persist (or update) a failed source-reference row when the import fails
     * BEFORE a DTO is available (e.g. API ok=false per Req 4.3, or "product not
     * found" per Req 4.4). Keyed by the AliExpress id so the failure is still
     * recorded for the admin (design error-handling table / Req 12.3, 12.4).
     * The stored message is the exception text only — never tokens/secrets.
     */
    protected function recordFailureForId(string $id, Throwable $e): void
    {
        try {
            AliExpressProductImport::updateOrCreate(
                ['aliexpress_product_id' => $id],
                [
                    'product_id' => null,
                    'status' => 'failed',
                    'error' => Str::limit($e->getMessage(), 1000),
                ]
            );
        } catch (Throwable $persistError) {
            // Never let audit persistence mask the real failure.
            Log::channel('aliexpress')->error('AliExpress failed-import audit row could not be written', [
                'aliexpress_product_id' => $id,
            ]);
        }

        Log::channel('aliexpress')->error('AliExpress product import failed', [
            'aliexpress_product_id' => $id,
            'message' => $e->getMessage(),
        ]);
    }

    /**
     * Persist (or update) a failed source-reference row OUTSIDE the rolled-back
     * transaction so the audit survives the rollback (Req 12.3, 12.4). The
     * stored message is the exception text only — never tokens/secrets, which
     * the importer never places in exception messages.
     */
    protected function recordFailure(NormalizedProduct $dto, Throwable $e): void
    {
        try {
            AliExpressProductImport::updateOrCreate(
                ['aliexpress_product_id' => $dto->aliexpressProductId],
                [
                    'product_id' => null,
                    'type' => $dto->isConfigurable ? 'configurable' : 'simple',
                    'status' => 'failed',
                    'error' => Str::limit($e->getMessage(), 1000),
                    'payload_snapshot' => $this->payloadSnapshot($dto, null),
                ]
            );
        } catch (Throwable $persistError) {
            // Never let audit persistence mask the real failure.
            Log::channel('aliexpress')->error('AliExpress failed-import audit row could not be written', [
                'aliexpress_product_id' => $dto->aliexpressProductId,
            ]);
        }

        Log::channel('aliexpress')->error('AliExpress product import failed', [
            'aliexpress_product_id' => $dto->aliexpressProductId,
            'message' => $e->getMessage(),
        ]);
    }

    /**
     * Build a normalized, secret-free snapshot of the DTO for audit storage.
     *
     * @return array<string, mixed>
     */
    protected function payloadSnapshot(NormalizedProduct $dto, ?string $urlKey): array
    {
        return [
            'aliexpress_product_id' => $dto->aliexpressProductId,
            'title' => $dto->title,
            'is_configurable' => $dto->isConfigurable,
            'currency' => $dto->currency,
            'url_key' => $urlKey,
            'meta_title' => $dto->metaTitle,
            'meta_keywords' => $dto->metaKeywords,
            'meta_description' => $dto->metaDescription,
            'localized_text' => $dto->localizedText,
            'images_count' => count($dto->imageUrls),
            'image_urls' => array_values($dto->imageUrls),
            'videos_count' => count($dto->videoUrls),
            'video_urls' => array_values($dto->videoUrls),
            'axes' => array_map(
                fn ($axis) => ['name' => $axis->name, 'code' => $axis->code, 'values' => $axis->values],
                array_values($dto->axes),
            ),
            'variants' => array_map(
                fn (NormalizedVariant $variant) => [
                    'sku_id' => $variant->skuId,
                    'price' => $variant->price,
                    'original_price' => $variant->originalPrice,
                    'stock' => $variant->stock,
                    'options_by_axis' => $variant->optionsByAxis,
                    'images_count' => count($variant->imageUrls),
                ],
                array_values($dto->variants),
            ),
        ];
    }

    /**
     * Build a unique, slug-valid url_key from the product title (Req 7.4, 7.5).
     *
     * Uses the exact mechanism Bagisto uses for url_key uniqueness — looking
     * the slug up through ProductRepository::findBySlug() (the same call behind
     * the admin's ProductCategoryUniqueSlug rule) — rather than guessing. On
     * collision the AliExpress product id is appended; a numeric suffix is added
     * only in the (unlikely) event that is still taken.
     */
    protected function buildUniqueUrlKey(NormalizedProduct $dto, int $productId): string
    {
        $base = Str::slug($dto->title);

        // Str::slug() can yield an empty string for non-latin-only titles (the
        // store locale is ar); fall back to a deterministic, slug-valid value.
        if ($base === '') {
            $base = 'ae-'.$dto->aliexpressProductId;
        }

        if ($this->isUrlKeyAvailable($base, $productId)) {
            return $base;
        }

        // Append the AliExpress product id to disambiguate (Req 7.5).
        $candidate = $base.'-'.$dto->aliexpressProductId;

        $suffix = 1;

        while (! $this->isUrlKeyAvailable($candidate, $productId)) {
            $candidate = $base.'-'.$dto->aliexpressProductId.'-'.$suffix++;
        }

        return $candidate;
    }

    /**
     * Is the given url_key free across the catalog (ignoring this product)?
     *
     * Reuses Bagisto's slug lookup (ProductRepository::findBySlug ->
     * findByAttributeCode('url_key', ...)) so it respects the same
     * product_attribute_values-backed uniqueness the admin enforces.
     */
    protected function isUrlKeyAvailable(string $urlKey, int $productId): bool
    {
        $existing = $this->productRepository->setSearchEngine('database')->findBySlug($urlKey);

        return $existing === null || (int) $existing->id === $productId;
    }

    /**
     * Resolve the category the imported product should be assigned to: the
     * Bagisto category mirroring the product's AliExpress category (created on
     * demand), falling back to the default/root category when it cannot be
     * resolved (Req 6.4).
     */
    protected function resolveProductCategoryId(NormalizedProduct $dto): int
    {
        if ($dto->aliexpressCategoryId !== null) {
            try {
                $categoryId = $this->categorySynchronizer->resolveCategoryId($dto->aliexpressCategoryId);

                if ($categoryId !== null) {
                    return $categoryId;
                }
            } catch (Throwable $e) {
                Log::channel('aliexpress')->warning('Category resolution failed; trying title guess', [
                    'aliexpress_category_id' => $dto->aliexpressCategoryId,
                    'message' => $e->getMessage(),
                ]);
            }
        }

        // The product's legacy AliExpress category id usually cannot be
        // resolved (the category-by-id APIs require permissions the app lacks),
        // so guess a top-level category from the product title before falling
        // back to the "Other" catch-all.
        try {
            $guessed = $this->categoryGuesser->guessCategoryId($dto->title);

            if ($guessed !== null) {
                Log::channel('aliexpress')->info('Category guessed from title', [
                    'aliexpress_product_id' => $dto->aliexpressProductId,
                    'category_id' => $guessed,
                ]);

                return $guessed;
            }
        } catch (Throwable $e) {
            Log::channel('aliexpress')->warning('Title-based category guess failed; using default', [
                'message' => $e->getMessage(),
            ]);
        }

        return $this->resolveDefaultCategoryId();
    }

    /**
     * Resolve the default category id products are assigned to (Req 6.4).
     *
     * Prefers the store's root category (parent_id IS NULL — id 1 on a stock
     * Bagisto install) resolved through the CategoryRepository rather than a
     * hard-coded id.
     *
     * @throws AliExpressImportException when no category exists.
     */
    /**
     * Resolve the default category id products are assigned to (Req 6.4).
     *
     * Prefers a real, browsable category over the tree root (assigning a
     * product to the root category id is not useful — it is not browsable).
     * Order of preference:
     *   1. The first child category under the root (a real top-level category).
     *   2. The root category as a last resort.
     *
     * @throws AliExpressImportException when no category exists.
     */
    /**
     * Resolve the default category id products are assigned to when their
     * AliExpress category cannot be matched (Req 6.4).
     *
     * Returns a dedicated "Other / أخرى" catch-all category (created on first
     * use under the store root) so uncategorized imports never land in an
     * unrelated real category or the non-browsable tree root.
     *
     * @throws AliExpressImportException when no root category exists.
     */
    protected function resolveDefaultCategoryId(): int
    {
        $root = $this->categoryRepository->getRootCategories()->first()
            ?? $this->categoryRepository->all()->first();

        if ($root === null) {
            throw new AliExpressImportException('No category is available to assign the imported product.');
        }

        return $this->resolveOtherCategoryId((int) $root->id);
    }

    /**
     * Find or create the dedicated "Other" catch-all category under the root.
     */
    protected function resolveOtherCategoryId(int $rootId): int
    {
        $slug = 'other-uncategorized';

        $existing = Category::whereTranslation('slug', $slug.'-en')
            ->orWhereTranslation('slug', $slug.'-ar')
            ->first();

        if ($existing) {
            return (int) $existing->id;
        }

        $category = new Category([
            'position' => (int) Category::where('parent_id', $rootId)->max('position') + 1,
            'status' => 1,
            'display_mode' => 'products_and_description',
        ]);

        $category->parent_id = $rootId;
        $category->save();

        $names = ['ar' => 'أخرى', 'en' => 'Other'];

        foreach (Locale::all() as $locale) {
            $isArabic = strtolower($locale->code) === 'ar';
            $name = $isArabic ? $names['ar'] : $names['en'];

            $translation = $category->translations()->create([
                'locale_id' => $locale->id,
                'name' => $name,
                'slug' => $slug.'-'.strtolower($locale->code),
                'description' => $name,
                'meta_title' => $name,
                'meta_description' => $name,
                'meta_keywords' => $name,
            ]);

            if (empty($translation->locale)) {
                $translation->forceFill(['locale' => $locale->code])->save();
            }
        }

        Log::channel('aliexpress')->info('Created default "Other" category for uncategorized imports', [
            'category_id' => $category->id,
        ]);

        return (int) $category->id;
    }

    /**
     * Step 1 — derive the numeric AliExpress product id from raw input (Req 2).
     *
     * @throws AliExpressImportException when no id can be derived.
     */
    protected function extractId(string $rawInput): string
    {
        return $this->extractor->extract($rawInput);
    }

    /**
     * Step 2 — reject a product that has already been imported (Req 5.2).
     *
     * A prior import counts as a duplicate only when it actually produced a
     * Bagisto product (successful status / non-null product_id); a previous
     * failed attempt does not block a retry.
     *
     * @throws AliExpressImportException when an existing Bagisto product is found.
     */
    protected function ensureNotDuplicate(string $id): void
    {
        $existing = AliExpressProductImport::query()
            ->forAliExpressId($id)
            ->where(function ($query) {
                $query->successful()->orWhereNotNull('product_id');
            })
            ->first();

        if ($existing !== null) {
            Log::channel('aliexpress')->info('AliExpress product already imported', [
                'aliexpress_product_id' => $id,
                'product_id' => $existing->product_id,
            ]);

            $reference = $existing->product_id !== null
                ? " as product #{$existing->product_id}"
                : '';

            throw new AliExpressImportException(
                "This AliExpress product has already been imported{$reference}.",
                [
                    'aliexpress_product_id' => $id,
                    'product_id' => $existing->product_id,
                ]
            );
        }
    }

    /**
     * Step 3 — obtain a valid stored OAuth token, refreshing if needed (Req 3).
     *
     * @throws AliExpressImportException when no token is stored or it is expired.
     */
    protected function resolveToken(): AliExpressToken
    {
        $token = $this->oauthService->latestToken();

        if ($token === null) {
            Log::channel('aliexpress')->warning('AliExpress import aborted: no token stored.');

            throw new AliExpressImportException('AliExpress authorization required.');
        }

        if (! $token->isAccessTokenValid()) {
            Log::channel('aliexpress')->warning('AliExpress import aborted: access token missing or expired.', [
                'token_id' => $token->id,
            ]);

            throw new AliExpressImportException('AliExpress access token missing or expired.');
        }

        return $token;
    }

    /**
     * Step 4 — fetch the full product payload from AliExpress (Req 4).
     *
     * @return array{ok: bool, status: int, code: string|null, message: string|null, body: array<string, mixed>}
     *
     * @throws AliExpressImportException when the API call is unsuccessful (Req 4.3).
     */
    protected function fetchPayload(string $id, AliExpressToken $token, array $options = []): array
    {
        $shipToCountry = config('aliexpress.import.ship_to_country', 'US');

        // Import in the store's base currency so the stored price always matches
        // the catalog's base currency (Bagisto then converts to the other
        // display currencies via exchange rates). Falls back to the configured
        // default when the base currency cannot be resolved.
        $targetCurrency = $this->resolveTargetCurrency();

        // Primary language drives the structural mapping (axes/variants). The
        // store's primary import language is English so axis names like
        // "Color"/"Size" stay stable and dictionary-matchable.
        $language = (string) ($options['language'] ?? config('aliexpress.import.primary_language', 'en'));

        $result = $this->apiClient->call('aliexpress.ds.product.get', $token->access_token, [
            'product_id' => $id,
            'ship_to_country' => $shipToCountry,
            'target_currency' => $targetCurrency,
            'target_language' => $language,
        ]);

        if ($result['ok'] === false) {
            Log::channel('aliexpress')->error('AliExpress product fetch failed', [
                'aliexpress_product_id' => $id,
                'code' => $result['code'],
                'message' => $result['message'],
                'status' => $result['status'],
            ]);

            $reason = $result['message'] ?? 'unknown error';
            $code = $result['code'] !== null ? " (code {$result['code']})" : '';

            throw new AliExpressImportException(
                "AliExpress API request failed: {$reason}{$code}.",
                [
                    'aliexpress_product_id' => $id,
                    'code' => $result['code'],
                ]
            );
        }

        // The gateway answers HTTP 200 / ok=true even for business-level
        // rejections, carrying the real outcome in the wrapped `rsp_code` /
        // `rsp_msg`. A successful product fetch returns rsp_code 200; anything
        // else (e.g. 482 SHIP_TO_COUNTRY_PROHIBITED) means there is no product
        // payload to map, so surface the precise reason instead of a misleading
        // "product not found".
        $envelope = $result['body']['aliexpress_ds_product_get_response'] ?? $result['body'];
        $rspCode = $envelope['rsp_code'] ?? null;
        $rspMsg = $envelope['rsp_msg'] ?? null;

        if ($rspCode !== null && (string) $rspCode !== '200') {
            Log::channel('aliexpress')->error('AliExpress product fetch rejected', [
                'aliexpress_product_id' => $id,
                'rsp_code' => $rspCode,
                'rsp_msg' => $rspMsg,
                'ship_to_country' => $shipToCountry,
            ]);

            throw new AliExpressImportException(
                $this->describeRspError((string) $rspCode, $rspMsg, $shipToCountry),
                [
                    'aliexpress_product_id' => $id,
                    'rsp_code' => $rspCode,
                    'rsp_msg' => $rspMsg,
                ]
            );
        }

        return $result;
    }

    /**
     * Enrich the DTO with real per-locale display text by re-fetching the
     * product in each additional store language (besides the primary one used
     * for structure). For each locale the store supports, AliExpress is asked
     * for that language's content; when the seller provides it (e.g. genuine
     * Arabic), it is stored for that locale, otherwise the locale silently
     * falls back to the primary text at write time.
     *
     * Only display text is taken from these extra fetches — never structure —
     * so axes/variants/images/prices remain those of the primary body.
     *
     * @param  array<string, mixed>  $primaryBody  The already-fetched primary-language body.
     */
    protected function enrichLocalizedText(
        NormalizedProduct $dto,
        string $id,
        AliExpressToken $token,
        array $primaryBody,
    ): NormalizedProduct {
        $primary = (string) config('aliexpress.import.primary_language', 'en');

        // Seed the primary locale's text from the body we already have.
        $primaryText = $this->mapper->extractText($primaryBody);
        if ($primaryText !== null) {
            $dto->localizedText[$primary] = $primaryText;
        }

        foreach ($this->storeImportLocales() as $locale) {
            $apiLang = $this->aliexpressLanguageForLocale($locale);

            // Skip the primary language (already seeded) and unmappable locales.
            if ($apiLang === null || $apiLang === $primary) {
                continue;
            }

            try {
                $result = $this->fetchPayload($id, $token, ['language' => $apiLang]);
                $text = $this->mapper->extractText($result['body']);

                if ($text !== null) {
                    $dto->localizedText[$locale] = $text;
                }
            } catch (Throwable $e) {
                // Localized enrichment is best-effort: a failure for one locale
                // must never abort the import (the locale falls back to primary).
                Log::channel('aliexpress')->warning('AliExpress localized text fetch failed; using primary language', [
                    'aliexpress_product_id' => $id,
                    'locale' => $locale,
                    'language' => $apiLang,
                    'message' => $e->getMessage(),
                ]);
            }
        }

        return $dto;
    }

    /**
     * The store locale codes products should be imported/stored in.
     *
     * @return string[]
     */
    protected function storeImportLocales(): array
    {
        try {
            $locales = core()->getAllLocales()->pluck('code')->all();

            if (! empty($locales)) {
                return array_values(array_unique(array_map('strval', $locales)));
            }
        } catch (Throwable $e) {
            // Fall through to the default pair below.
        }

        return ['en', 'ar'];
    }

    /**
     * Map a Bagisto locale code to the AliExpress `target_language` value.
     * Returns null when there is no sensible AliExpress language for it (the
     * locale then falls back to the primary import language).
     */
    protected function aliexpressLanguageForLocale(string $locale): ?string
    {
        $code = strtolower(trim($locale));

        // AliExpress ds.product.get language codes are short ("en", "ar", ...).
        // Take the language part of locales like "ar", "AR", "en_US".
        $code = preg_replace('/[_-].*$/', '', $code) ?? $code;

        $supported = (array) config('aliexpress.import.language_map', [
            'ar' => 'ar',
            'en' => 'en',
        ]);

        return $supported[$code] ?? null;
    }

    /**
     * Resolve the currency to request prices in: the store's base currency so
     * the imported price is stored in the catalog's base currency. Falls back
     * to the configured default if the base currency cannot be resolved.
     */
    protected function resolveTargetCurrency(): string
    {
        try {
            $base = core()->getBaseCurrencyCode();

            if (is_string($base) && $base !== '') {
                return $base;
            }
        } catch (Throwable $e) {
            // Ignore and fall back to the configured default below.
        }

        return (string) config('aliexpress.import.target_currency', 'USD');
    }

    /**
     * Write special_price directly onto matched variants (configurable).
     *
     * Bagisto's Configurable::updateVariant() does not persist special_price
     * (it is absent from fillableVariantAttributeCodes), so we upsert the
     * value straight into product_attribute_values. special_price is a
     * non-scoped price attribute, so its value lives in float_value with a
     * unique_id of "product_id|attribute_id" (channel/locale filtered out).
     *
     * @param  array<int, float>  $variantSpecialPrices  [variantId => salePrice]
     */
    protected function applyVariantSpecialPrices(array $variantSpecialPrices): void
    {
        $attributeId = (int) (Attribute::where('code', 'special_price')->value('id') ?? 0);

        if ($attributeId === 0) {
            return;
        }

        foreach ($variantSpecialPrices as $variantId => $salePrice) {
            try {
                $uniqueId = implode('|', array_filter([
                    null,                 // channel (special_price is not channel-scoped)
                    null,                 // locale  (special_price is not locale-scoped)
                    (int) $variantId,
                    $attributeId,
                ]));

                ProductAttributeValue::updateOrCreate(
                    [
                        'product_id' => (int) $variantId,
                        'attribute_id' => $attributeId,
                        'channel' => null,
                        'locale' => null,
                    ],
                    [
                        'float_value' => $salePrice,
                        'unique_id' => $uniqueId,
                    ]
                );
            } catch (Throwable $e) {
                Log::channel('aliexpress')->warning('Failed to set variant special_price', [
                    'variant_id' => $variantId,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Build a human-readable (Arabic) message for a business-level rsp_code
     * returned by the product gateway.
     */
    protected function describeRspError(string $rspCode, ?string $rspMsg, string $shipToCountry): string
    {
        // Known, actionable cases get a tailored message.
        if ($rspCode === '482' || $rspMsg === 'SHIP_TO_COUNTRY_PROHIBITED') {
            return "هذا المنتج غير متاح للشحن إلى الدولة المحددة ({$shipToCountry}). "
                .'جرّب تغيير دولة الشحن (ALIEXPRESS_SHIP_TO_COUNTRY) إلى دولة مدعومة لهذا المنتج.';
        }

        $reason = $rspMsg !== null && $rspMsg !== '' ? $rspMsg : 'سبب غير معروف';

        return "تعذّر جلب المنتج من AliExpress: {$reason} (الرمز {$rspCode}).";
    }

    /**
     * Subtask 7.2 — create a configurable product and reconcile its generated
     * permutations against the real AliExpress SKUs (Req 6.1, 6.3, 6.4, 9.x).
     *
     * Bagisto's Configurable type auto-generates the full cartesian product of
     * the super attributes on create. We then:
     *   1. build an option-id signature for each generated variant;
     *   2. build the same signature for each AliExpress SKU;
     *   3. match by signature — matched variants get price / stock / option
     *      values / variant images and are enabled; unmatched permutations are
     *      disabled (status=0) with zero stock (never deleted);
     *   4. compute the parent representative price = min matched price.
     *
     * The matched/unmatched data is collected into a `variants_payload` whose
     * `variants[generatedVariantId]` entries follow the exact shape Bagisto's
     * Configurable::updateVariant() expects. The payload is RETURNED (not
     * applied here); finalizeProduct() applies it together with the shared
     * parent fields in one unified update().
     *
     * @return array{product: Product, price: float, variants_count: int, variants_payload: array<int, array<string, mixed>>, resolved_axes: ResolvedAxes}
     */
    protected function createConfigurableProduct(NormalizedProduct $dto): array
    {
        $resolved = $this->attributeResolver->resolveAxes($dto->axes);

        $familyId = $this->resolveDefaultFamilyId();

        $sku = $this->buildUniqueSku($dto->aliexpressProductId);

        $product = $this->productRepository->create([
            'type' => 'configurable',
            'attribute_family_id' => $familyId,
            'sku' => $sku,
            'super_attributes' => $resolved->superAttributes,
        ]);

        // Reload with the relations needed to read each generated variant's
        // option ids. Use a fresh query (not the cacheable repository) so a
        // stale "not found" cache entry can never mask the just-created rows.
        $product = Product::with(['variants.attribute_values', 'super_attributes'])
            ->findOrFail($product->id);

        $axisNameToCode = $this->mapAxisNamesToResolvedCodes($dto, $resolved);

        // Index the AliExpress SKUs by their sorted option-id signature.
        $skusBySignature = [];

        foreach ($dto->variants as $variant) {
            $signature = $this->aliexpressVariantSignature($variant, $axisNameToCode, $resolved);

            if ($signature !== null) {
                $skusBySignature[$signature] = $variant;
            }
        }

        $variantsPayload = [];
        $matchedPrices = [];
        $matchedCount = 0;
        $variantSpecialPrices = [];

        $defaultSourceId = $this->defaultInventorySourceId();

        foreach ($product->variants as $generatedVariant) {
            $signature = $this->generatedVariantSignature($generatedVariant, $resolved);

            $matchedSku = $signature !== null ? ($skusBySignature[$signature] ?? null) : null;

            if ($matchedSku !== null) {
                $variantsPayload[$generatedVariant->id] = $this->matchedVariantPayload(
                    $generatedVariant,
                    $matchedSku,
                    $resolved,
                    $defaultSourceId,
                );

                // Bagisto's Configurable::updateVariant() only persists the
                // codes in its fillableVariantAttributeCodes list, which does
                // NOT include special_price. Capture the per-variant discount
                // here so finalizeProduct() can write it directly after the
                // update. The variant's `price` carries the original/list
                // price; special_price carries the sale price.
                if ($matchedSku->originalPrice !== null) {
                    $variantSpecialPrices[$generatedVariant->id] = (float) $matchedSku->price;
                }

                $matchedPrices[] = $matchedSku->price;
                $matchedCount++;
            } else {
                $variantsPayload[$generatedVariant->id] = $this->disabledVariantPayload(
                    $generatedVariant,
                    $defaultSourceId,
                );

                Log::channel('aliexpress')->info('AliExpress configurable permutation disabled (no matching SKU)', [
                    'aliexpress_product_id' => $dto->aliexpressProductId,
                    'variant_id' => $generatedVariant->id,
                    'signature' => $signature,
                ]);
            }
        }

        // Representative parent price = minimum matched-variant price (Req 9.6).
        $representativePrice = ! empty($matchedPrices) ? (float) min($matchedPrices) : 0.0;

        // NOTE: the reconciled variants payload is returned (not applied here).
        // finalizeProduct() (subtask 7.4) applies it together with the shared
        // parent fields in ONE unified update(). Applying it here as well would
        // attach each variant's images twice and do redundant writes; and a
        // shared-field-only update later would be impossible because
        // Configurable::update() deletes every variant absent from its payload.
        Log::channel('aliexpress')->info('AliExpress configurable product created', [
            'aliexpress_product_id' => $dto->aliexpressProductId,
            'product_id' => $product->id,
            'sku' => $sku,
            'generated_variants' => count($variantsPayload),
            'matched_variants' => $matchedCount,
            'representative_price' => $representativePrice,
        ]);

        return [
            'product' => $product,
            'price' => $representativePrice,
            'variants_count' => $matchedCount,
            'variants_payload' => $variantsPayload,
            'resolved_axes' => $resolved,
            'variant_special_prices' => $variantSpecialPrices,
        ];
    }

    /**
     * Subtask 7.3 — create a simple product for a single-SKU AliExpress payload
     * (Req 6.2, 6.3, 6.4, 9.5, 9.6).
     *
     * A simple product has exactly one NormalizedVariant ($dto->variants[0]).
     *
     * Design choice (kept consistent with how createConfigurableProduct() leaves
     * shared fields for subtask 7.4): this method ONLY creates the bare product
     * row (type/family/sku) and returns the SKU's price and inventory map. It
     * deliberately does NOT issue its own price/stock update(). Bagisto's
     * Simple::update() runs saveValues() (price) + saveInventories() (stock) as
     * part of the same call that also writes name/description/SEO/url_key/
     * categories/status. Returning the price/inventories here lets subtask 7.4
     * apply everything in ONE unified update(), avoiding two conflicting partial
     * updates and matching the configurable branch's shape so 7.4 can treat both
     * uniformly.
     *
     * The returned `inventories` array is ready to drop into the 7.4 update()
     * payload as-is (`[defaultSourceId => qty]`, the shape saveInventories()
     * expects). `variants_count` is 1 to mirror the configurable return.
     *
     * @return array{product: Product, price: float, variants_count: int, inventories: array<int, int>}
     */
    protected function createSimpleProduct(NormalizedProduct $dto): array
    {
        $familyId = $this->resolveDefaultFamilyId();

        $sku = $this->buildUniqueSku($dto->aliexpressProductId);

        $product = $this->productRepository->create([
            'type' => 'simple',
            'attribute_family_id' => $familyId,
            'sku' => $sku,
        ]);

        // Fresh Eloquent reload (not the cacheable repository) so subtask 7.4
        // operates on a guaranteed-current model rather than a possibly stale
        // cached "not found" entry for the just-created row.
        $product = Product::findOrFail($product->id);

        // The single SKU carries the price + stock (Req 9.5). Currency is USD as
        // requested from AliExpress and stored as the base currency (Req 9.6).
        $variant = $dto->variants[0];

        $defaultSourceId = $this->defaultInventorySourceId();

        Log::channel('aliexpress')->info('AliExpress simple product created', [
            'aliexpress_product_id' => $dto->aliexpressProductId,
            'product_id' => $product->id,
            'sku' => $sku,
            'price' => $variant->price,
            'stock' => $variant->stock,
        ]);

        return [
            'product' => $product,
            'price' => (float) $variant->price,
            'variants_count' => 1,
            'inventories' => [
                $defaultSourceId => $variant->stock,
            ],
            // Discount: when the SKU has a higher original/list price, expose
            // it as the regular price and the sale price as special_price.
            'regular_price' => $variant->originalPrice !== null ? (float) $variant->originalPrice : null,
            'special_price' => $variant->originalPrice !== null ? (float) $variant->price : null,
        ];
    }

    /**
     * Build the `variants[id]` payload entry for a matched permutation: real
     * price, stock, option values, and any variant-specific images. Enabled.
     *
     * @return array<string, mixed>
     */
    protected function matchedVariantPayload(
        Product $variant,
        NormalizedVariant $sku,
        ResolvedAxes $resolved,
        int $defaultSourceId,
    ): array {
        $payload = [
            'sku' => $variant->sku,
            'name' => $variant->name,
            'price' => $sku->price,
            'weight' => 0,
            'status' => 1,
            'tax_category_id' => '',
            'inventories' => [
                $defaultSourceId => $sku->stock,
            ],
        ];

        // Discount: a higher original/list price becomes the variant's regular
        // price, with the sale price stored as special_price (Bagisto shows a
        // struck-through original + discounted price).
        if ($sku->originalPrice !== null) {
            $payload['price'] = $sku->originalPrice;
            $payload['special_price'] = $sku->price;
        }

        // Re-assert the configurable option values by attribute code so each
        // variant explicitly references its numeric option ids (Req 9.4).
        foreach ($this->variantOptionIds($variant, $resolved) as $code => $optionId) {
            $payload[$code] = $optionId;
        }

        // Variant-specific images: download to UploadedFiles and let Bagisto's
        // media repository (invoked by updateVariant) re-encode and store them.
        $files = $this->imageImporter->download($sku->imageUrls);

        if (! empty($files)) {
            $payload['images'] = ['files' => $files];
        }

        return $payload;
    }

    /**
     * Build the `variants[id]` payload entry for an unmatched permutation:
     * disabled (status=0) with zero stock so it is never purchasable. Existing
     * option values set at create time are preserved (not re-sent).
     *
     * @return array<string, mixed>
     */
    protected function disabledVariantPayload(Product $variant, int $defaultSourceId): array
    {
        return [
            'sku' => $variant->sku,
            'name' => $variant->name,
            'price' => 0,
            'weight' => 0,
            'status' => 0,
            'tax_category_id' => '',
            'inventories' => [
                $defaultSourceId => 0,
            ],
        ];
    }

    /**
     * Read a generated variant's option ids keyed by super-attribute code,
     * sourced directly from its persisted attribute values (select attributes
     * store the option id in `integer_value`).
     *
     * Reads from the loaded attribute_values rather than the model's magic
     * getter, because the auto-created `ae_*` attributes are not part of the
     * default attribute family and so would not resolve through it.
     *
     * @return array<string, int> [attributeCode => optionId]
     */
    protected function variantOptionIds(Product $variant, ResolvedAxes $resolved): array
    {
        $optionIds = [];

        foreach ($resolved->attributesByCode as $code => $attribute) {
            $value = $variant->attribute_values
                ->firstWhere('attribute_id', $attribute->id);

            $optionId = $value?->integer_value;

            if ($optionId !== null) {
                $optionIds[$code] = (int) $optionId;
            }
        }

        return $optionIds;
    }

    /**
     * Build the sorted option-id signature for a generated variant.
     */
    protected function generatedVariantSignature(Product $variant, ResolvedAxes $resolved): ?string
    {
        $optionIds = array_values($this->variantOptionIds($variant, $resolved));

        if (count($optionIds) !== count($resolved->attributesByCode)) {
            return null;
        }

        return $this->signature($optionIds);
    }

    /**
     * Build the sorted option-id signature for an AliExpress SKU by translating
     * its per-axis labels into the resolved attribute option ids.
     */
    protected function aliexpressVariantSignature(
        NormalizedVariant $variant,
        array $axisNameToCode,
        ResolvedAxes $resolved,
    ): ?string {
        $optionIds = [];

        foreach ($variant->optionsByAxis as $axisName => $label) {
            $code = $axisNameToCode[$axisName] ?? null;

            if ($code === null) {
                return null;
            }

            $optionId = $this->resolveOptionId($resolved->optionIdLookup[$code] ?? [], $label);

            if ($optionId === null) {
                return null;
            }

            $optionIds[] = $optionId;
        }

        if (count($optionIds) !== count($resolved->attributesByCode)) {
            return null;
        }

        return $this->signature($optionIds);
    }

    /**
     * Normalize a set of option ids into a stable, order-independent signature.
     *
     * @param  int[]  $optionIds
     */
    protected function signature(array $optionIds): string
    {
        $optionIds = array_map('intval', $optionIds);

        sort($optionIds, SORT_NUMERIC);

        return implode('-', $optionIds);
    }

    /**
     * Resolve an option id from a code's label=>id lookup, tolerating case and
     * surrounding whitespace differences.
     *
     * @param  array<string, int>  $lookup  [optionLabel => optionId]
     */
    protected function resolveOptionId(array $lookup, string $label): ?int
    {
        if (isset($lookup[$label])) {
            return (int) $lookup[$label];
        }

        $needle = mb_strtolower(trim($label));

        foreach ($lookup as $candidate => $optionId) {
            if (mb_strtolower(trim((string) $candidate)) === $needle) {
                return (int) $optionId;
            }
        }

        return null;
    }

    /**
     * Map each axis name to the attribute code it actually resolved to.
     *
     * The resolver may substitute a `<code>_var` fallback when a core attribute
     * already owns the natural code, so we pair axes (in order) with the
     * resolved super-attribute codes (which are built in the same order).
     *
     * @return array<string, string> [axisName => attributeCode]
     */
    protected function mapAxisNamesToResolvedCodes(NormalizedProduct $dto, ResolvedAxes $resolved): array
    {
        $resolvedCodes = array_keys($resolved->superAttributes);

        $map = [];

        foreach (array_values($dto->axes) as $index => $axis) {
            if (isset($resolvedCodes[$index])) {
                $map[$axis->name] = $resolvedCodes[$index];
            }
        }

        return $map;
    }

    /**
     * Resolve the default attribute family id, preferring the family coded
     * "default" and falling back to the first family.
     *
     * @throws AliExpressImportException when no attribute family exists.
     */
    protected function resolveDefaultFamilyId(): int
    {
        $family = $this->attributeFamilyRepository->findOneByField('code', 'default')
            ?? $this->attributeFamilyRepository->all()->first();

        if ($family === null) {
            throw new AliExpressImportException('No attribute family is available to assign the imported product.');
        }

        return (int) $family->id;
    }

    /**
     * Build a unique product SKU derived from the AliExpress id ("ae-{id}"),
     * appending a numeric suffix on collision.
     */
    protected function buildUniqueSku(string $aliexpressProductId): string
    {
        $base = 'ae-'.$aliexpressProductId;

        $sku = $base;

        $suffix = 1;

        while (Product::where('sku', $sku)->exists()) {
            $sku = $base.'-'.$suffix++;
        }

        return $sku;
    }

    /**
     * Resolve the inventory source quantities should be written against,
     * preferring the default channel's first active source and falling back to
     * source #1 (the seeded default warehouse).
     */
    protected function defaultInventorySourceId(): int
    {
        $source = core()->getDefaultChannel()
            ->inventory_sources
            ->where('status', 1)
            ->first();

        return (int) ($source->id ?? 1);
    }

    /**
     * Map variant SKU IDs and populate external_variant_projections read model.
     */
    protected function applyVariantSkuIdsAndProjections(Product $product, ResolvedAxes $resolved, NormalizedProduct $dto): void
    {
        $attributeId = (int) (\Webkul\Attribute\Models\Attribute::where('code', 'aliexpress_sku_id')->value('id') ?? 0);
        if ($attributeId === 0) {
            return;
        }

        $product = Product::with(['variants.attribute_values'])->findOrFail($product->id);

        $axisNameToCode = $this->mapAxisNamesToResolvedCodes($dto, $resolved);

        $skusBySignature = [];
        foreach ($dto->variants as $aeVariant) {
            $sig = $this->aliexpressVariantSignature($aeVariant, $axisNameToCode, $resolved);
            if ($sig !== null) {
                $skusBySignature[$sig] = $aeVariant;
            }
        }

        foreach ($product->variants as $variant) {
            $sig = $this->generatedVariantSignature($variant, $resolved);
            $matchedSku = $sig !== null ? ($skusBySignature[$sig] ?? null) : null;

            if ($matchedSku !== null) {
                $uniqueId = implode('|', array_filter([
                    null,
                    null,
                    (int) $variant->id,
                    $attributeId,
                ]));

                ProductAttributeValue::updateOrCreate(
                    [
                        'product_id'   => (int) $variant->id,
                        'attribute_id' => $attributeId,
                        'channel'      => null,
                        'locale'       => null,
                    ],
                    [
                        'text_value'   => $matchedSku->skuId,
                        'unique_id'    => $uniqueId,
                    ]
                );

                \App\Models\ExternalVariantProjection::updateOrCreate(
                    [
                        'variant_product_id' => $variant->id,
                    ],
                    [
                        'product_id'               => $product->id,
                        'provider'                 => 'aliexpress',
                        'external_sku_id'          => $matchedSku->skuId,
                        'external_product_id'      => $dto->aliexpressProductId,
                        'external_variant_version' => null,
                        'projection_version'       => 1,
                        'provider_updated_at'      => null,
                    ]
                );
            }
        }
    }

    /**
     * Map simple product SKU ID and populate external_variant_projections read model.
     */
    protected function applySimpleSkuIdAndProjection(Product $product, NormalizedProduct $dto): void
    {
        $attributeId = (int) (\Webkul\Attribute\Models\Attribute::where('code', 'aliexpress_sku_id')->value('id') ?? 0);
        if ($attributeId === 0) {
            return;
        }

        $aeVariant = $dto->variants[0];

        $uniqueId = implode('|', array_filter([
            null,
            null,
            (int) $product->id,
            $attributeId,
        ]));

        ProductAttributeValue::updateOrCreate(
            [
                'product_id'   => (int) $product->id,
                'attribute_id' => $attributeId,
                'channel'      => null,
                'locale'       => null,
                    ],
                    [
                'text_value'   => $aeVariant->skuId,
                'unique_id'    => $uniqueId,
            ]
        );

        \App\Models\ExternalVariantProjection::updateOrCreate(
            [
                'variant_product_id' => $product->id,
            ],
            [
                'product_id'               => $product->id,
                'provider'                 => 'aliexpress',
                'external_sku_id'          => $aeVariant->skuId,
                'external_product_id'      => $dto->aliexpressProductId,
                'external_variant_version' => null,
                'projection_version'       => 1,
                'provider_updated_at'      => null,
            ]
        );
    }
}
