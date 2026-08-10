<?php

require dirname(__DIR__) . '/vendor/autoload.php';
$app = require_once dirname(__DIR__) . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Webkul\Product\Models\ProductAttributeValue;

$vals = ProductAttributeValue::where('text_value', 'LIKE', '%modname%')
    ->orWhere('text_value', 'LIKE', '%colspace%')
    ->orWhere('text_value', 'LIKE', '%cols=%')
    ->limit(10)
    ->get(['id', 'product_id', 'attribute_id', 'text_value']);

foreach ($vals as $v) {
    echo "ID: {$v->id} | Product: {$v->product_id} | Attr: {$v->attribute_id}\n";
    echo "VALUE:\n" . substr($v->text_value, 0, 200) . "\n-------------------\n";
}
