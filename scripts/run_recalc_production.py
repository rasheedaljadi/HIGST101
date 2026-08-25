import sys
import os
import paramiko

if hasattr(sys.stdout, 'reconfigure'):
    sys.stdout.reconfigure(encoding='utf-8', errors='replace')

HOST = '76.13.79.242'
USER = 'highest-ye'
PASS = 'YoK2PBV1fo82yujX2tDq'
APP_DIR = '/home/highest-ye/htdocs/highest-ye.store'

client = paramiko.SSHClient()
client.set_missing_host_key_policy(paramiko.AutoAddPolicy())
client.connect(HOST, username=USER, password=PASS, timeout=20)
sftp = client.open_sftp()

php_script = """<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\\Contracts\\Console\\Kernel::class);
$kernel->bootstrap();

use App\\Models\\AliExpressProductImport;
use App\\Models\\ExternalVariantProjection;
use App\\Services\\AliExpress\\AliExpressApiClient;
use App\\Services\\AliExpress\\AliExpressProductMapper;
use App\\Services\\Pricing\\CatalogPriceWriter;
use App\\Services\\Pricing\\DTO\\PricingContext;
use App\\Services\\Pricing\\PricingEngine;
use App\\Services\\Pricing\\PricingRuleResolver;
use App\\Services\\Pricing\\SourceOfferRecorder;
use Illuminate\\Support\\Facades\\DB;
use Spatie\\ResponseCache\\Facades\\ResponseCache;
use Webkul\\Product\\Models\\Product;

$aeId = '1005011735920938';
$import = AliExpressProductImport::where('aliexpress_product_id', $aeId)->first();
$product = Product::with(['variants.attribute_values'])->findOrFail($import->product_id);

echo "Recalculating prices for product ID: {$product->id}...\\n";

$token = App\\Models\\AliExpressToken::latest()->first();
$apiClient = app(AliExpressApiClient::class);
$mapper = app(AliExpressProductMapper::class);
$pricingEngine = app(PricingEngine::class);
$ruleResolver = app(PricingRuleResolver::class);
$sourceOfferRecorder = app(SourceOfferRecorder::class);
$catalogPriceWriter = app(CatalogPriceWriter::class);

$result = $apiClient->call('aliexpress.ds.product.get', $token->access_token, [
    'product_id' => $aeId,
    'ship_to_country' => 'SA',
    'target_currency' => 'USD',
    'target_language' => 'en',
]);

$dto = $mapper->map($result['body'], $aeId);
$rule = $ruleResolver->resolve($product->id, $product->categories->first()?->id);

foreach ($dto->variants as $aeVariant) {
    $projection = ExternalVariantProjection::where('external_sku_id', (string) $aeVariant->skuId)
        ->where('product_id', $product->id)
        ->first();

    if (!$projection) {
        echo "Missing projection for SKU {$aeVariant->skuId}\\n";
        continue;
    }

    $variantId = $projection->variant_product_id;
    $supplierCost = (float) $aeVariant->price;
    $supplierOriginalCost = $aeVariant->originalPrice !== null ? (float) $aeVariant->originalPrice : null;

    // 1. Record variant supplier offer
    $sourceOfferRecorder->record(
        variantId: $variantId,
        productId: $product->id,
        acquisitionCost: $supplierCost,
        acquisitionOriginalCost: $supplierOriginalCost,
        sourceCurrency: $dto->currency,
        sourceSkuId: $aeVariant->skuId,
        sourceProvider: 'aliexpress',
        trigger: 'import',
    );

    // 2. Calculate selling price
    $variantContext = new PricingContext(
        sourceProvider: 'aliexpress',
        currency: $dto->currency,
        acquisitionOriginalCost: $supplierOriginalCost,
        shippingCost: 0,
    );

    $calcResult = $pricingEngine->calculate($supplierCost, $rule, $variantContext);

    // 3. Write to Bagisto EAV
    $catalogPriceWriter->write(
        variantId: $variantId,
        productId: $product->id,
        result: $calcResult,
        specialPrice: $calcResult->specialPrice,
        oldAcquisitionCost: null,
        rule: $rule,
        trigger: 'recalculate',
    );
}

// Reindex product
$catalogPriceWriter->reindex($product->id);

echo "\\n=== UPDATED VARIANT PRICES ===\\n";
$product = Product::with(['variants.attribute_values'])->findOrFail($import->product_id);
foreach ($product->variants as $v) {
    $labels = DB::table('product_attribute_values')
        ->where('product_attribute_values.product_id', $v->id)
        ->join('attribute_options', 'attribute_options.id', '=', 'product_attribute_values.integer_value')
        ->join('attribute_option_translations', 'attribute_option_translations.attribute_option_id', '=', 'attribute_options.id')
        ->pluck('attribute_option_translations.label')
        ->implode(' / ');

    $proj = ExternalVariantProjection::where('variant_product_id', $v->id)->first();
    
    // Get special price from attribute_values
    $priceAttrId = DB::table('attributes')->where('code', 'price')->value('id');
    $specialPriceAttrId = DB::table('attributes')->where('code', 'special_price')->value('id');
    $costAttrId = DB::table('attributes')->where('code', 'cost')->value('id');

    $regPrice = DB::table('product_attribute_values')->where('product_id', $v->id)->where('attribute_id', $priceAttrId)->value('float_value');
    $specialPrice = DB::table('product_attribute_values')->where('product_id', $v->id)->where('attribute_id', $specialPriceAttrId)->value('float_value');
    $cost = DB::table('product_attribute_values')->where('product_id', $v->id)->where('attribute_id', $costAttrId)->value('float_value');

    echo "Variant ID: {$v->id} | Options: [{$labels}] | Regular: \${$regPrice} | Special: \${$specialPrice} | Cost: \${$cost}\\n";
}

if (class_exists(ResponseCache::class)) {
    ResponseCache::clear();
    echo "\\nResponseCache cleared.\\n";
}
"""

with sftp.open(f"{APP_DIR}/recalc_product_prices.php", 'w') as f:
    f.write(php_script)

sftp.close()

stdin, stdout, stderr = client.exec_command(f"cd {APP_DIR} && php recalc_product_prices.php && rm recalc_product_prices.php")
print(stdout.read().decode('utf-8', errors='replace'))
print(stderr.read().decode('utf-8', errors='replace'))

client.close()
