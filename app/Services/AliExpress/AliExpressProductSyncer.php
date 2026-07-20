<?php

namespace App\Services\AliExpress;

use App\Exceptions\AliExpress\AliExpressImportException;
use App\Models\AliExpressProductImport;
use App\Models\AliExpressToken;
use App\Services\AliExpress\DTO\NormalizedProduct;
use App\Services\AliExpress\DTO\NormalizedVariant;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;
use Webkul\Product\Models\Product;

/**
 * Handles live synchronization of AliExpress product prices and stock quantities.
 * Purely event-driven: writes to Outbox, does not write directly to catalog tables.
 */
class AliExpressProductSyncer
{
    public function __construct(
        protected AliExpressOAuthService $oauthService,
        protected AliExpressApiClient $apiClient,
        protected AliExpressProductMapper $mapper,
    ) {}

    /**
     * Synchronize price and stock for one imported product.
     *
     * @throws AliExpressImportException
     */
    public function sync(AliExpressProductImport $import, array $options = []): void
    {
        $id = $import->aliexpress_product_id;

        Log::channel('aliexpress')->info('AliExpress sync started', [
            'aliexpress_product_id' => $id,
            'product_id' => $import->product_id,
        ]);

        $lockKey = "aliexpress-sync-lock-{$id}";
        $lock = \Illuminate\Support\Facades\Cache::lock($lockKey, 600);

        if (! $lock->get()) {
            Log::channel('aliexpress')->warning('AliExpress sync skipped: another process is already syncing this product.', [
                'aliexpress_product_id' => $id,
            ]);
            return;
        }

        try {
            $token = $this->resolveToken();

            // 1. Fetch live payload from AliExpress
            try {
                $result = $this->fetchPayload($id, $token);
                $dto = $this->mapper->map($result['body'], $id);
            } catch (Throwable $e) {
                if (str_contains(strtolower($e->getMessage()), 'not found') || 
                    str_contains(strtolower($e->getMessage()), 'prohibited') || 
                    str_contains(strtolower($e->getMessage()), 'deleted')) {
                    $this->disableLocalProduct($import);
                    return;
                }
                
                $import->update([
                    'error' => Str::limit($this->getFriendlyErrorMessage($e), 1000),
                ]);
                throw $e;
            }

            // 2. Load the local product
            if ($import->product_id === null) {
                $import->update([
                    'status' => 'failed',
                    'error' => 'No local product is linked to this import.',
                ]);
                return;
            }

            $product = Product::with(['variants.inventories'])->find($import->product_id);

            if (! $product) {
                $import->update([
                    'status' => 'failed',
                    'error' => 'Local product no longer exists in the Bagisto database.',
                ]);
                return;
            }

            // Calculate live variant hashes and master SHA-256 hash
            $variantHashes = [];
            foreach ($dto->variants as $aeVariant) {
                $variantHashes[] = hash('sha256', implode('|', [
                    $dto->aliexpressProductId,
                    $aeVariant->skuId,
                    (string) $aeVariant->price,
                    implode(',', $aeVariant->optionsByAxis),
                    $aeVariant->imageUrls[0] ?? ''
                ]));
            }
            $masterHash = hash('sha256', implode(',', $variantHashes));

            // Change detection check (Version -> UpdatedAt -> Hash)
            $isVersionMatch = $import->external_product_version !== null && $import->external_product_version === $dto->externalProductVersion;
            $isUpdatedTimeMatch = $import->provider_updated_at !== null && $dto->providerUpdatedAt !== null && $import->provider_updated_at->eq($dto->providerUpdatedAt);
            $isHashMatch = $import->snapshot_hash !== null && $import->snapshot_hash === $masterHash;

            if ($isVersionMatch || $isUpdatedTimeMatch || $isHashMatch) {
                Log::channel('aliexpress')->info('AliExpress sync skipped: no changes detected (matched version/timestamp/hash).', [
                    'aliexpress_product_id' => $id,
                ]);
                return;
            }

            // 3. Detect changes and publish events to Outbox
            DB::transaction(function () use ($dto, $product, $import, $masterHash) {
                $this->detectAndPublishChanges($dto, $product);

                // 4. Update local import record
                $import->update([
                    'snapshot_hash' => $masterHash,
                    'external_product_version' => $dto->externalProductVersion,
                    'provider_updated_at' => $dto->providerUpdatedAt,
                    'payload_snapshot' => $this->payloadSnapshot($dto, $product->url_key),
                    'error' => null,
                    'status' => 'success',
                ]);
            });

            Log::channel('aliexpress')->info('AliExpress sync completed (events published)', [
                'aliexpress_product_id' => $id,
                'product_id' => $product->id,
            ]);

        } catch (Throwable $e) {
            $import->update([
                'error' => Str::limit($this->getFriendlyErrorMessage($e), 1000),
            ]);
            throw $e;
        } finally {
            $lock->release();
        }
    }

    /**
     * Detect modifications and publish Outbox events.
     */
    protected function detectAndPublishChanges(NormalizedProduct $dto, Product $product): void
    {
        $processedVariantIds = [];

        // 1. Process active incoming variants
        foreach ($dto->variants as $aeVariant) {
            $projection = \App\Models\ExternalVariantProjection::where('provider', 'aliexpress')
                ->where('external_sku_id', $aeVariant->skuId)
                ->first();

            if (!$projection) {
                // New variant or changed identity variant
                $this->publishOutboxEvent('VariantIdentityChanged', [
                    'event_version' => 1,
                    'change_reason' => 'scheduled_sync',
                    'product_id'    => (int) $product->id,
                    'variant_id'    => null,
                    'old_sku'       => null,
                    'new_sku'       => $aeVariant->skuId,
                    'old_options'   => [],
                    'new_options'   => $aeVariant->optionsByAxis,
                    'supplier_product_id' => $dto->aliexpressProductId,
                    'external_variant_version' => $aeVariant->version,
                    'provider_updated_at' => $dto->providerUpdatedAt ? $dto->providerUpdatedAt->toIso8601String() : null,
                    'occurred_at'   => now()->toIso8601String(),
                ], $product->id);
                continue;
            }

            $variantId = $projection->variant_product_id;
            $processedVariantIds[] = $variantId;

            // Load local variant to compare details
            $localVariant = Product::with('inventories')->find($variantId);
            if (!$localVariant) {
                continue;
            }

            // A. Compare Price
            $localPrice = (float) $localVariant->price;
            $newPrice = (float) $aeVariant->price;
            if ($localPrice !== $newPrice) {
                $pct = $localPrice > 0 ? (($newPrice - $localPrice) / $localPrice) * 100 : 0;
                $this->publishOutboxEvent('SupplierPriceChanged', [
                    'event_version' => 1,
                    'change_reason' => 'scheduled_sync',
                    'product_id'    => (int) $product->id,
                    'variant_id'    => (int) $variantId,
                    'old_price'     => $localPrice,
                    'new_price'     => $newPrice,
                    'price_change_percentage' => round($pct, 2),
                    'currency'      => $dto->currency,
                    'supplier_product_id' => $dto->aliexpressProductId,
                    'supplier_sku_id' => $aeVariant->skuId,
                    'external_variant_version' => $aeVariant->version,
                    'provider_updated_at' => $dto->providerUpdatedAt ? $dto->providerUpdatedAt->toIso8601String() : null,
                    'occurred_at'   => now()->toIso8601String(),
                ], $product->id);
            }

            // B. Compare Stock
            $localStock = (int) $localVariant->inventories->sum('qty');
            $newStock = (int) $aeVariant->stock;
            if ($localStock !== $newStock) {
                $this->publishOutboxEvent('SupplierStockChanged', [
                    'event_version' => 1,
                    'change_reason' => 'scheduled_sync',
                    'product_id'    => (int) $product->id,
                    'variant_id'    => (int) $variantId,
                    'old_stock'     => $localStock,
                    'new_stock'     => $newStock,
                    'supplier_product_id' => $dto->aliexpressProductId,
                    'supplier_sku_id' => $aeVariant->skuId,
                    'external_variant_version' => $aeVariant->version,
                    'provider_updated_at' => $dto->providerUpdatedAt ? $dto->providerUpdatedAt->toIso8601String() : null,
                    'occurred_at'   => now()->toIso8601String(),
                ], $product->id);
            }

            // C. Compare identity versions if defined
            if ($projection->external_variant_version !== null && $projection->external_variant_version !== $aeVariant->version) {
                $this->publishOutboxEvent('VariantIdentityChanged', [
                    'event_version' => 1,
                    'change_reason' => 'scheduled_sync',
                    'product_id'    => (int) $product->id,
                    'variant_id'    => (int) $variantId,
                    'old_sku'       => $projection->external_sku_id,
                    'new_sku'       => $aeVariant->skuId,
                    'old_options'   => [],
                    'new_options'   => $aeVariant->optionsByAxis,
                    'supplier_product_id' => $dto->aliexpressProductId,
                    'external_variant_version' => $aeVariant->version,
                    'provider_updated_at' => $dto->providerUpdatedAt ? $dto->providerUpdatedAt->toIso8601String() : null,
                    'occurred_at'   => now()->toIso8601String(),
                ], $product->id);
            }
        }

        // 2. Identify variants deleted by the supplier
        $allLocalVariantIds = $product->variants->pluck('id')->toArray();
        $deletedVariantIds = array_diff($allLocalVariantIds, $processedVariantIds);

        foreach ($deletedVariantIds as $deletedId) {
            $projection = \App\Models\ExternalVariantProjection::where('variant_product_id', $deletedId)->first();
            if ($projection) {
                $this->publishOutboxEvent('VariantIdentityChanged', [
                    'event_version' => 1,
                    'change_reason' => 'scheduled_sync',
                    'product_id'    => (int) $product->id,
                    'variant_id'    => (int) $deletedId,
                    'old_sku'       => $projection->external_sku_id,
                    'new_sku'       => null,
                    'old_options'   => [],
                    'new_options'   => [],
                    'supplier_product_id' => $dto->aliexpressProductId,
                    'external_variant_version' => null,
                    'provider_updated_at' => $dto->providerUpdatedAt ? $dto->providerUpdatedAt->toIso8601String() : null,
                    'occurred_at'   => now()->toIso8601String(),
                ], $product->id);
            }
        }
    }

    /**
     * Publish outbox event to DB.
     */
    protected function publishOutboxEvent(string $eventName, array $payload, int $productId): void
    {
        DB::table('domain_outbox_events')->insert([
            'event_id'       => (string) Str::uuid(),
            'event_name'     => $eventName,
            'event_version'  => 1,
            'aggregate_type' => 'Product',
            'aggregate_id'   => (string) $productId,
            'correlation_id' => (string) Str::uuid(),
            'causation_id'   => (string) Str::uuid(),
            'payload'        => json_encode($payload),
            'status'         => 'pending',
            'attempts'       => 0,
            'created_at'     => now(),
            'updated_at'     => now(),
        ]);
    }

    /**
     * Disable local product if no longer available on AliExpress.
     */
    protected function disableLocalProduct(AliExpressProductImport $import): void
    {
        Log::channel('aliexpress')->warning('AliExpress product no longer available; publishing disable events', [
            'aliexpress_product_id' => $import->aliexpress_product_id,
            'product_id' => $import->product_id,
        ]);

        if ($import->product_id !== null) {
            $product = Product::with('variants')->find($import->product_id);
            if ($product) {
                $toDisable = collect([$product])->merge($product->variants);
                foreach ($toDisable as $item) {
                    $this->publishOutboxEvent('VariantIdentityChanged', [
                        'event_version' => 1,
                        'change_reason' => 'supplier_prohibited',
                        'product_id'    => (int) $import->product_id,
                        'variant_id'    => (int) $item->id,
                        'old_sku'       => $item->sku,
                        'new_sku'       => null,
                        'old_options'   => [],
                        'new_options'   => [],
                        'supplier_product_id' => $import->aliexpress_product_id,
                        'occurred_at'   => now()->toIso8601String(),
                    ], $import->product_id);
                }
            }
        }

        $import->update([
            'status' => 'failed',
            'error' => 'Product no longer available on AliExpress (disable event queued).',
        ]);
    }

    /**
     * Resolve OAuth Token.
     */
    protected function resolveToken(): AliExpressToken
    {
        $token = $this->oauthService->latestToken();

        if ($token === null || ! $token->isAccessTokenValid()) {
            throw new AliExpressImportException('AliExpress authorization token missing or expired.');
        }

        return $token;
    }

    /**
     * Call the AliExpress API to get current product data.
     */
    protected function fetchPayload(string $id, AliExpressToken $token): array
    {
        $shipToCountry = config('aliexpress.import.ship_to_country', 'SA');
        $targetCurrency = $this->resolveTargetCurrency();
        $language = config('aliexpress.import.primary_language', 'en');

        $result = $this->apiClient->call('aliexpress.ds.product.get', $token->access_token, [
            'product_id' => $id,
            'ship_to_country' => $shipToCountry,
            'target_currency' => $targetCurrency,
            'target_language' => $language,
        ]);

        if ($result['ok'] === false) {
            throw new AliExpressImportException(
                "AliExpress API sync request failed: " . ($result['message'] ?? 'unknown error'),
                ['aliexpress_product_id' => $id]
            );
        }

        $envelope = $result['body']['aliexpress_ds_product_get_response'] ?? $result['body'];
        $rspCode = $envelope['rsp_code'] ?? null;
        $rspMsg = $envelope['rsp_msg'] ?? null;

        if ($rspCode !== null && (string) $rspCode !== '200') {
            throw new AliExpressImportException(
                "AliExpress API sync rejected: {$rspMsg} (code {$rspCode})",
                ['aliexpress_product_id' => $id]
            );
        }

        return $result;
    }

    /**
     * Build secret-free payload snapshot.
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

    protected function resolveTargetCurrency(): string
    {
        try {
            $base = core()->getBaseCurrencyCode();
            if (is_string($base) && $base !== '') {
                return $base;
            }
        } catch (Throwable $e) {
            // Ignore
        }
        return (string) config('aliexpress.import.target_currency', 'USD');
    }

    /**
     * Get a user-friendly Arabic error message for a given exception.
     */
    protected function getFriendlyErrorMessage(\Throwable $e): string
    {
        $message = $e->getMessage();
        $lowerMessage = strtolower($message);

        // 1. Programming / Database / Internal Errors
        if ($e instanceof \Error || 
            $e instanceof \ErrorException || 
            $e instanceof \Illuminate\Database\QueryException || 
            str_contains($lowerMessage, 'undefined property') ||
            str_contains($lowerMessage, 'call to undefined method') ||
            str_contains($lowerMessage, 'syntax error') ||
            str_contains($lowerMessage, 'sqlstate')
        ) {
            return 'فشل داخلي في النظام: حدث خطأ أثناء معالجة بيانات المنتج برمجياً. يرجى مراجعة الدعم الفني لدراسة تفاصيل الخطأ: ' . $message;
        }

        // 2. Connectivity / Timeout Errors
        if (str_contains($lowerMessage, 'timed out') || 
            str_contains($lowerMessage, 'connection timed out') || 
            str_contains($lowerMessage, 'curl error') || 
            str_contains($lowerMessage, 'timeout') ||
            str_contains($lowerMessage, 'resolving host')
        ) {
            return 'فشل الاتصال: تعذر الاتصال بخوادم AliExpress بسبب انتهاء مهلة الاتصال أو مشكلة في الشبكة. يرجى المحاولة مرة أخرى لاحقاً.';
        }

        // 3. Authentication / OAuth Credentials Errors
        if (str_contains($lowerMessage, 'unauthorized') || 
            str_contains($lowerMessage, 'token') || 
            str_contains($lowerMessage, 'oauth') || 
            str_contains($lowerMessage, 'expired') ||
            str_contains($lowerMessage, 'sign method') ||
            str_contains($lowerMessage, 'session expired')
        ) {
            return 'فشل المصادقة: رمز الوصول (Token) الخاص بـ AliExpress غير صالح أو انتهت صلاحيته. يرجى الانتقال إلى صفحة "إدارة المفاتيح" وتجديد ربط الحساب.';
        }

        // 4. Product/Supplier Availability Errors
        if (str_contains($lowerMessage, 'not found') || 
            str_contains($lowerMessage, 'deleted') || 
            str_contains($lowerMessage, '404') ||
            str_contains($lowerMessage, 'product not found')
        ) {
            return 'المنتج غير متوفر: يبدو أن المنتج قد تم حذفه من AliExpress أو أن المورد قام بإزالته.';
        }

        if (str_contains($lowerMessage, 'prohibited') || 
            str_contains($lowerMessage, 'ship_to_country_prohibited') ||
            str_contains($lowerMessage, 'cannot ship')
        ) {
            return 'شحن محظور: هذا المنتج محظور أو لا يمكن شحنه إلى الدولة المحددة حالياً في إعدادات AliExpress الخاصة بك.';
        }

        // 5. Default fallback
        return 'فشلت المزامنة بسبب خطأ غير متوقع: ' . $message;
    }
}
