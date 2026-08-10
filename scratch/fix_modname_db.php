<?php

require dirname(__DIR__) . '/vendor/autoload.php';
$app = require_once dirname(__DIR__) . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Webkul\Product\Models\ProductAttributeValue;

function sanitizeDescription($text) {
    if (empty($text)) return $text;

    $patterns = [
        '/modname\s*=\s*.*?(?=<|\n|\r|$)/iu',
        '/cols\s*=\s*\d+.*?(?=<|\n|\r|$)/iu',
        '/colspace\s*=\s*\d+.*?(?=<|\n|\r|$)/iu',
        '/rowspace\s*=\s*\d+.*?(?=<|\n|\r|$)/iu',
        '/align\s*=\s*(center|centre|مركز)(?=<|\n|\r|$)/iu',
    ];

    foreach ($patterns as $pattern) {
        $text = preg_replace($pattern, '', $text);
    }

    $text = preg_replace('/<(div|p|span)[^>]*>\s*<\/\1>/iu', '', $text);

    return trim($text);
}

$vals = ProductAttributeValue::where('text_value', 'LIKE', '%modname%')
    ->orWhere('text_value', 'LIKE', '%colspace%')
    ->orWhere('text_value', 'LIKE', '%cols=%')
    ->orWhere('text_value', 'LIKE', '%rowspace%')
    ->get();

$count = 0;
foreach ($vals as $val) {
    $cleaned = sanitizeDescription($val->text_value);
    if ($cleaned !== $val->text_value) {
        $val->text_value = $cleaned;
        $val->save();
        $count++;
    }
}

echo "Successfully cleaned {$count} product attribute records in database!\n";
