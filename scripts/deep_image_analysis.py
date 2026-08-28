import paramiko
import json

client = paramiko.SSHClient()
client.set_missing_host_key_policy(paramiko.AutoAddPolicy())
client.connect('76.13.79.242', username='highest-ye', password='YoK2PBV1fo82yujX2tDq')

php_script = r"""<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

$images = DB::table('product_images')->get();
$totalRecords = $images->count();

$stats = [
    'total_db_records' => $totalRecords,
    'total_files_found' => 0,
    'total_files_missing' => 0,
    'by_aspect_ratio_category' => [
        'square_1_1' => ['count' => 0, 'label' => 'مربع تام أو شبه مربع (1:1) [النسبة بين 0.95 و 1.05]', 'examples' => []],
        'slight_vertical_4_5' => ['count' => 0, 'label' => 'طولي طفيف (4:5 أو 3:4) [النسبة بين 0.75 و 0.95]', 'examples' => []],
        'tall_portrait_9_16' => ['count' => 0, 'label' => 'طولي حاد / رأسي (9:16 أو 2:3) [النسبة أقل من 0.75]', 'examples' => []],
        'slight_horizontal_4_3' => ['count' => 0, 'label' => 'عرضي طفيف / كلاسيكي (4:3 أو 3:2) [النسبة بين 1.05 و 1.45]', 'examples' => []],
        'wide_landscape_16_9' => ['count' => 0, 'label' => 'عرضي عريض / شاشات (16:9 أو أعرض) [النسبة أكبر من 1.45]', 'examples' => []],
    ],
    'by_resolution' => [
        'ultra_high_1200_plus' => ['count' => 0, 'label' => 'دقة فائقة (1200px فما فوق)'],
        'high_800_1200' => ['count' => 0, 'label' => 'دقة عالية (800px إلى 1200px)'],
        'medium_500_800' => ['count' => 0, 'label' => 'دقة متوسطة (500px إلى 800px)'],
        'low_below_500' => ['count' => 0, 'label' => 'دقة منخفضة (أقل من 500px)'],
    ],
    'by_product_type' => [
        'parent_or_simple' => 0,
        'variant_products' => 0,
    ],
    'common_exact_dimensions' => [],
    'aspect_ratios_raw' => [],
];

$productParentMap = DB::table('products')->pluck('parent_id', 'id')->toArray();

foreach ($images as $img) {
    $fullPath = storage_path('app/public/' . $img->path);
    if (! file_exists($fullPath)) {
        $stats['total_files_missing']++;
        continue;
    }

    $stats['total_files_found']++;
    $info = @getimagesize($fullPath);
    if (! $info) {
        continue;
    }

    $width = $info[0];
    $height = $info[1];
    if ($height <= 0 || $width <= 0) {
        continue;
    }

    $ratio = round($width / $height, 3);
    $dimKey = "{$width}x{$height}";

    // Track dimension frequencies
    $stats['common_exact_dimensions'][$dimKey] = ($stats['common_exact_dimensions'][$dimKey] ?? 0) + 1;

    // Track product type (parent/simple vs variant)
    $parentId = $productParentMap[$img->product_id] ?? null;
    if ($parentId) {
        $stats['by_product_type']['variant_products']++;
    } else {
        $stats['by_product_type']['parent_or_simple']++;
    }

    // Resolution classification (based on max dimension)
    $maxDim = max($width, $height);
    if ($maxDim >= 1200) {
        $stats['by_resolution']['ultra_high_1200_plus']['count']++;
    } elseif ($maxDim >= 800) {
        $stats['by_resolution']['high_800_1200']['count']++;
    } elseif ($maxDim >= 500) {
        $stats['by_resolution']['medium_500_800']['count']++;
    } else {
        $stats['by_resolution']['low_below_500']['count']++;
    }

    // Aspect Ratio Classification
    if ($ratio >= 0.95 && $ratio <= 1.05) {
        $category = 'square_1_1';
    } elseif ($ratio >= 0.75 && $ratio < 0.95) {
        $category = 'slight_vertical_4_5';
    } elseif ($ratio < 0.75) {
        $category = 'tall_portrait_9_16';
    } elseif ($ratio > 1.05 && $ratio <= 1.45) {
        $category = 'slight_horizontal_4_3';
    } else {
        $category = 'wide_landscape_16_9';
    }

    $stats['by_aspect_ratio_category'][$category]['count']++;
    if (count($stats['by_aspect_ratio_category'][$category]['examples']) < 5) {
        $stats['by_aspect_ratio_category'][$category]['examples'][] = [
            'prod_id' => $img->product_id,
            'path' => $img->path,
            'width' => $width,
            'height' => $height,
            'ratio' => $ratio,
        ];
    }
}

// Sort common dimensions descending
arsort($stats['common_exact_dimensions']);
$stats['common_exact_dimensions'] = array_slice($stats['common_exact_dimensions'], 0, 15, true);

echo json_encode($stats, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
?>
"""

sftp = client.open_sftp()
with sftp.file('/home/highest-ye/htdocs/highest-ye.store/analyze_images.php', 'w') as f:
    f.write(php_script)
sftp.close()

cmd = "cd /home/highest-ye/htdocs/highest-ye.store && php analyze_images.php && rm analyze_images.php"
stdin, stdout, stderr = client.exec_command(cmd)
output = stdout.read().decode('utf-8')
err = stderr.read().decode('utf-8')

if err:
    print("STDERR:", err)

print("ANALYSIS RESULT:")
print(output)

client.close()
