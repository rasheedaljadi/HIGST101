<?php

namespace App\Services\AliExpress;

use App\Models\AliExpressProductImport;
use App\Models\ExternalVariantProjection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;
use Webkul\Product\Exceptions\InsufficientProductInventoryException;
use Webkul\Product\Models\Product;

class AliExpressLiveStockValidator
{
    public function __construct(
        protected AliExpressOAuthService $oauthService,
        protected AliExpressApiClient $apiClient,
    ) {}

    /**
     * Validate live stock on AliExpress for a product/variant being added to cart or checkout.
     *
     * @param  int|Product  $product  Base or child product
     * @param  array  $cartData  Data containing quantity and selected_configurable_option
     * @return bool True if valid, throws InsufficientProductInventoryException if out of stock
     *
     * @throws InsufficientProductInventoryException
     */
    public function validateLiveStock(int|Product $product, array $cartData = []): bool
    {
        $productId = $product instanceof Product ? $product->id : (int) $product;
        $targetProductId = ! empty($cartData['selected_configurable_option'])
            ? (int) $cartData['selected_configurable_option']
            : $productId;

        $requestedQty = max(1, (int) ($cartData['quantity'] ?? 1));

        // 1. Check if this target product or variant is an AliExpress product
        $import = AliExpressProductImport::where('product_id', $productId)
            ->orWhere('product_id', $targetProductId)
            ->latest('id')
            ->first();

        $projection = ExternalVariantProjection::where('variant_product_id', $targetProductId)->first();

        if (! $import && ! $projection) {
            // Not an AliExpress imported product, pass through to local stock validation
            return true;
        }

        $aeProductId = $import?->aliexpress_product_id ?? $projection?->external_product_id;
        $supplierSkuId = $projection?->external_sku_id;

        // If external_sku_id is missing from projection, resolve from import payload snapshot variants
        if (empty($supplierSkuId) && $import && ! empty($import->payload_snapshot['variants'])) {
            $variants = $import->payload_snapshot['variants'];
            if ($targetProductId !== $productId) {
                $targetProd = Product::find($targetProductId);
                $attrValues = $targetProd ? DB::table('product_attribute_values')
                    ->join('attribute_options', 'product_attribute_values.integer_value', '=', 'attribute_options.id')
                    ->join('attribute_option_translations', 'attribute_options.id', '=', 'attribute_option_translations.attribute_option_id')
                    ->where('product_attribute_values.product_id', $targetProductId)
                    ->pluck('attribute_option_translations.label')
                    ->toArray() : [];

                foreach ($variants as $v) {
                    $vSku = (string) ($v['sku_id'] ?? '');
                    $opts = array_values($v['options_by_axis'] ?? []);
                    foreach ($opts as $opt) {
                        if (in_array($opt, $attrValues, true) || ($targetProd && str_contains($targetProd->sku, $vSku))) {
                            $supplierSkuId = $vSku;
                            break 2;
                        }
                    }
                }
            }

            if (empty($supplierSkuId) && count($variants) === 1) {
                $supplierSkuId = (string) ($variants[0]['sku_id'] ?? '');
            }
        }

        if (! $aeProductId) {
            return true;
        }

        // 2. Fast cache lookup (5 minutes TTL)
        $cacheKey = "ae_live_stock_{$aeProductId}_".($supplierSkuId ?: 'default');
        $cachedStock = Cache::get($cacheKey);

        if ($cachedStock !== null) {
            if ((int) $cachedStock < $requestedQty) {
                $this->handleOutOfStock($targetProductId, (int) $cachedStock);
            }

            return true;
        }

        // 3. Live API validation
        try {
            $token = $this->oauthService->latestToken();
            if (! $token || ! $token->isAccessTokenValid()) {
                // If token is missing/expired, fall back silently without breaking store
                Log::channel('aliexpress')->warning('Live stock check skipped: AliExpress token invalid or missing.');

                return true;
            }

            $shipToCountry = config('aliexpress.import.ship_to_country', 'SA');
            $currency = config('aliexpress.import.target_currency', 'USD');
            $language = config('aliexpress.import.primary_language', 'en');

            $result = $this->apiClient->call('aliexpress.ds.product.get', $token->access_token, [
                'product_id' => $aeProductId,
                'ship_to_country' => $shipToCountry,
                'target_currency' => $currency,
                'target_language' => $language,
            ]);

            if (! $result['ok']) {
                Log::channel('aliexpress')->warning('Live stock API call returned not ok: '.($result['message'] ?? 'unknown'));

                return true;
            }

            $body = $result['body']['aliexpress_ds_product_get_response'] ?? $result['body'];
            $aeProduct = $body['result'] ?? $body['ae_item_base_info_dto'] ?? null;
            $skuList = $body['ae_item_sku_info_dtos']['ae_item_sku_info_d_t_o']
                ?? $body['result']['ae_item_sku_info_dtos']['ae_item_sku_info_d_t_o']
                ?? [];

            if (isset($skuList['sku_id'])) {
                $skuList = [$skuList];
            }

            $resolvedStock = null;

            if (! empty($skuList)) {
                foreach ($skuList as $skuItem) {
                    $sId = (string) ($skuItem['sku_id'] ?? $skuItem['id'] ?? '');
                    if ($sId === (string) $supplierSkuId || count($skuList) === 1) {
                        $resolvedStock = isset($skuItem['sku_available_stock']) || isset($skuItem['ipm_sku_stock']) || isset($skuItem['sku_stock']) || isset($skuItem['stock'])
                            ? (int) ($skuItem['sku_available_stock'] ?? $skuItem['ipm_sku_stock'] ?? $skuItem['sku_stock'] ?? $skuItem['stock'])
                            : 0;
                        break;
                    }
                }
            }

            if ($resolvedStock === null) {
                // Fallback to total item stock if individual SKU not found
                $resolvedStock = (int) ($aeProduct['total_available_stock'] ?? $aeProduct['sku_available_stock'] ?? $aeProduct['stock'] ?? 10);
            }

            // Cache result for 5 minutes
            Cache::put($cacheKey, $resolvedStock, now()->addMinutes(5));

            // 4. If out of stock or insufficient quantity
            if ($resolvedStock < $requestedQty) {
                $this->handleOutOfStock($targetProductId, $resolvedStock);
            }

            return true;
        } catch (InsufficientProductInventoryException $e) {
            throw $e;
        } catch (Throwable $e) {
            Log::channel('aliexpress')->warning("Live stock check exception for product #{$productId}: ".$e->getMessage());

            // On unexpected API error, allow customer to proceed without blocking checkout
            return true;
        }
    }

    /**
     * Handle out-of-stock scenario by updating local inventories and throwing exception.
     *
     * @throws InsufficientProductInventoryException
     */
    protected function handleOutOfStock(int $targetProductId, int $liveStock): void
    {
        // Update local database inventory so Bagisto storefront immediately reflects out of stock
        try {
            DB::table('product_inventories')
                ->where('product_id', $targetProductId)
                ->update(['qty' => max(0, $liveStock)]);
        } catch (Throwable) {
            // Ignore DB update failures
        }

        $errorMessage = $liveStock <= 0
            ? 'عذراً، هذا الصنف (اللون/الموديل المحدد) غير متوفر حالياً لدى المورد في علي إكسبرس. يرجى اختيار خيار آخر.'
            : "عذراً، الكمية المتوفرة لدى المورد حالياً ({$liveStock}) فقط، ولا تكفي للكمية المطلوبة.";

        throw new InsufficientProductInventoryException($errorMessage);
    }
}
