import sys
import paramiko
import json

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
use App\\Models\\AliExpressSetting;
use App\\Models\\ExternalVariantProjection;
use App\\Models\\HigestPricingRule;
use App\\Models\\HigestSourceOffer;
use App\\Services\\AliExpress\\AliExpressApiClient;
use App\\Services\\AliExpress\\AliExpressProductMapper;
use App\\Services\\Pricing\\PricingEngine;
use App\\Services\\Pricing\\PricingRuleResolver;
use App\\Services\\Pricing\\DTO\\PricingContext;
use Illuminate\\Support\\Facades\\DB;
use Webkul\\Product\\Models\\Product;

$targetSku = 'ae-1005011735920938-variant-239-866';
$variant = Product::with(['variants', 'attribute_values'])->where('sku', $targetSku)->first();

if (!$variant) {
    echo "Variant with SKU {$targetSku} not found!\\n";
    exit(1);
}

$parent = Product::find($variant->parent_id);
$import = AliExpressProductImport::where('product_id', $parent->id)->first();
$projection = ExternalVariantProjection::where('variant_product_id', $variant->id)->first();
$sourceOffer = HigestSourceOffer::where('variant_id', $variant->id)->latest()->first();

// Option labels
$labels = DB::table('product_attribute_values')
    ->where('product_attribute_values.product_id', $variant->id)
    ->join('attribute_options', 'attribute_options.id', '=', 'product_attribute_values.integer_value')
    ->join('attribute_option_translations', 'attribute_option_translations.attribute_option_id', '=', 'attribute_options.id')
    ->pluck('attribute_option_translations.label')
    ->all();

// Product prices stored in DB
$priceAttrId = DB::table('attributes')->where('code', 'price')->value('id');
$specialPriceAttrId = DB::table('attributes')->where('code', 'special_price')->value('id');
$costAttrId = DB::table('attributes')->where('code', 'cost')->value('id');

$storedRegularPrice = DB::table('product_attribute_values')->where('product_id', $variant->id)->where('attribute_id', $priceAttrId)->value('float_value');
$storedSpecialPrice = DB::table('product_attribute_values')->where('product_id', $variant->id)->where('attribute_id', $specialPriceAttrId)->value('float_value');
$storedCost = DB::table('product_attribute_values')->where('product_id', $variant->id)->where('attribute_id', $costAttrId)->value('float_value');

// Live API Data
$token = App\\Models\\AliExpressToken::latest()->first();
$apiClient = app(AliExpressApiClient::class);
$mapper = app(AliExpressProductMapper::class);

$result = $apiClient->call('aliexpress.ds.product.get', $token->access_token, [
    'product_id' => $import->aliexpress_product_id,
    'ship_to_country' => 'SA',
    'target_currency' => 'USD',
    'target_language' => 'en',
]);

$envelope = $result['body']['aliexpress_ds_product_get_response']['result'] ?? [];
$skus = $envelope['ae_item_sku_info_dtos']['ae_item_sku_info_d_t_o'] ?? [];

$matchedApiSku = null;
foreach ($skus as $s) {
    if ((string)$s['sku_id'] === (string)$projection?->external_sku_id) {
        $matchedApiSku = $s;
        break;
    }
}

// Pricing rule
$ruleResolver = app(PricingRuleResolver::class);
$pricingEngine = app(PricingEngine::class);
$rule = $ruleResolver->resolve($parent->id, $parent->categories->first()?->id);
$settings = AliExpressSetting::current();

$data = [
    'variant_id' => $variant->id,
    'sku' => $variant->sku,
    'parent_product_id' => $parent->id,
    'parent_name' => $parent->name,
    'options' => $labels,
    'aliexpress_sku_id' => $projection?->external_sku_id,
    'aliexpress_product_id' => $import->aliexpress_product_id,
    'ali_sale_price' => $matchedApiSku['offer_sale_price'] ?? null,
    'ali_regular_price' => $matchedApiSku['sku_price'] ?? null,
    'ali_price_include_tax' => $matchedApiSku['price_include_tax'] ?? false,
    'import_shipping_cost' => $import->base_shipping_cost,
    'import_shipping_service' => $import->shipping_service_name,
    'import_is_choice' => $import->isChoice(),
    'settings_include_shipping' => $settings->include_shipping_in_price,
    'settings_exclude_choice' => $settings->exclude_choice_from_shipping_price,
    'pricing_rule' => $rule ? [
        'id' => $rule->id,
        'name' => $rule->name,
        'margin_type' => $rule->margin_type,
        'margin_value' => $rule->margin_value,
        'rounding_rule' => $rule->rounding_rule,
    ] : null,
    'stored_in_db' => [
        'cost' => $storedCost,
        'regular_price' => $storedRegularPrice,
        'special_price' => $storedSpecialPrice,
    ],
    'source_offer_recorded' => $sourceOffer ? [
        'acquisition_cost' => $sourceOffer->acquisition_cost,
        'acquisition_original_cost' => $sourceOffer->acquisition_original_cost,
        'source_currency' => $sourceOffer->source_currency,
    ] : null,
];

echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\\n";
"""

with sftp.open(f"{APP_DIR}/audit_variant_deep.php", 'w') as f:
    f.write(php_script)
sftp.close()

stdin, stdout, stderr = client.exec_command(f"cd {APP_DIR} && php audit_variant_deep.php && rm audit_variant_deep.php")
print(stdout.read().decode('utf-8', errors='replace'))
client.close()
