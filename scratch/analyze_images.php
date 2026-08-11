<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$images = DB::table('product_images')->get();
$total = count($images);
$categories = [];
$dimensions = [];

foreach ($images as $img) {
    $fullPath = storage_path('app/public/' . $img->path);
    if (! file_exists($fullPath)) {
        $fullPath = public_path('storage/' . $img->path);
    }

    if (file_exists($fullPath)) {
        $info = @getimagesize($fullPath);
        if ($info) {
            $w = $info[0];
            $h = $info[1];
            $ratio = round($w / $h, 2);

            $dimKey = $w . 'x' . $h;
            $dimensions[$dimKey] = ($dimensions[$dimKey] ?? 0) + 1;

            $cat = 'Other (' . $ratio . ')';
            if (abs($ratio - 1.0) <= 0.03) {
                $cat = '1:1 Square (1000x1000, 800x800, etc.)';
            } elseif ($ratio >= 0.72 && $ratio <= 0.82) {
                $cat = '3:4 / 4:5 Portrait (Clothing/Fashion standard)';
            } elseif ($ratio >= 0.60 && $ratio < 0.72) {
                $cat = '2:3 Tall Portrait (Full body model)';
            } elseif ($ratio >= 1.20 && $ratio <= 1.35) {
                $cat = '4:3 / 5:4 Landscape';
            } elseif ($ratio > 1.35) {
                $cat = 'Wide Landscape (> 1.35)';
            } elseif ($ratio < 0.60) {
                $cat = 'Ultra Tall (< 0.60)';
            }

            $categories[$cat] = ($categories[$cat] ?? 0) + 1;
        }
    }
}

arsort($categories);
arsort($dimensions);

echo "========================================================\n";
echo "       HIGEST STORE - PRODUCT IMAGES DIMENSION ANALYSIS  \n";
echo "========================================================\n\n";
echo "Total Image Records Scanned: " . $total . "\n\n";

echo "--- ASPECT RATIO DISTRIBUTION ---\n";
foreach ($categories as $cat => $count) {
    $pct = round(($count / max(1, array_sum($categories))) * 100, 1);
    echo sprintf("%-45s: %4d images (%5.1f%%)\n", $cat, $count, $pct);
}

echo "\n--- TOP 15 EXACT DIMENSIONS ---\n";
$i = 0;
foreach ($dimensions as $dim => $count) {
    echo sprintf("%-20s: %4d images\n", $dim, $count);
    if (++$i >= 15) break;
}
