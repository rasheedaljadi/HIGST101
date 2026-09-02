import remote_ssh_helper as r

client = r.get_ssh_client()

php_analysis = """<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\\Contracts\\Console\\Kernel::class);
$kernel->bootstrap();

use Illuminate\\Support\\Facades\\DB;
use Illuminate\\Support\\Facades\\Storage;

echo "=========================================================\\n";
echo "COMPREHENSIVE PRODUCT IMAGE ANALYSIS & CLASSIFICATION\\n";
echo "=========================================================\\n";

// 1. Inspect product_images table
$dbImages = DB::table('product_images')->get();
echo "Total Records in product_images Table: " . $dbImages->count() . "\\n";

// 2. Find local product image files in storage
$storagePath = storage_path('app/public/product');
$publicStoragePath = public_path('storage/product');

$allFiles = [];
if (is_dir($storagePath)) {
    $dirIterator = new RecursiveDirectoryIterator($storagePath, RecursiveDirectoryIterator::SKIP_DOTS);
    $iterator = new RecursiveIteratorIterator($dirIterator, RecursiveIteratorIterator::LEAVES_ONLY);
    foreach ($iterator as $file) {
        if ($file->isFile()) {
            $ext = strtolower($file->getExtension());
            if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif', 'avif', 'svg'])) {
                $allFiles[] = $file->getPathname();
            }
        }
    }
}

echo "Total Local Image Files Found on Disk: " . count($allFiles) . "\\n";

// 3. Analyze local files
$classifications = [
    'square' => [],             // 0.98 <= ratio <= 1.02
    'slight_portrait' => [],    // 0.85 <= ratio < 0.98
    'moderate_portrait' => [],  // 0.65 <= ratio < 0.85
    'extreme_portrait' => [],   // ratio < 0.65
    'slight_landscape' => [],   // 1.02 < ratio <= 1.20
    'moderate_landscape' => [], // 1.20 < ratio <= 1.60
    'extreme_landscape' => [],  // ratio > 1.60
];

$formats = [];
$resolutions = [];
$fileSizes = [
    '< 50 KB' => 0,
    '50 - 150 KB' => 0,
    '150 - 350 KB' => 0,
    '350 - 800 KB' => 0,
    '800 KB - 1.5 MB' => 0,
    '> 1.5 MB' => 0,
];

$ratiosList = [];
$dimensionsList = [];

$analyzedCount = 0;
$corruptedCount = 0;

foreach ($allFiles as $filePath) {
    $size = filesize($filePath);
    $info = @getimagesize($filePath);

    if (! $info) {
        $corruptedCount++;
        continue;
    }

    $analyzedCount++;
    $width = $info[0];
    $height = $info[1];
    $mime = $info['mime'];
    $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));

    // Formats
    $formats[$ext] = ($formats[$ext] ?? 0) + 1;

    // File Size bucket
    $sizeKB = $size / 1024;
    if ($sizeKB < 50) $fileSizes['< 50 KB']++;
    elseif ($sizeKB < 150) $fileSizes['50 - 150 KB']++;
    elseif ($sizeKB < 350) $fileSizes['150 - 350 KB']++;
    elseif ($sizeKB < 800) $fileSizes['350 - 800 KB']++;
    elseif ($sizeKB < 1536) $fileSizes['800 KB - 1.5 MB']++;
    else $fileSizes['> 1.5 MB']++;

    // Aspect Ratio: Width / Height
    $ratio = $height > 0 ? round($width / $height, 3) : 1.0;
    $ratiosList[] = $ratio;

    $dimKey = "{$width}x{$height}";
    $resolutions[$dimKey] = ($resolutions[$dimKey] ?? 0) + 1;

    // Classification
    if ($ratio >= 0.98 && $ratio <= 1.02) {
        $classifications['square'][] = ['dim' => $dimKey, 'size' => $sizeKB, 'path' => $filePath];
    } elseif ($ratio >= 0.85 && $ratio < 0.98) {
        $classifications['slight_portrait'][] = ['dim' => $dimKey, 'ratio' => $ratio, 'size' => $sizeKB];
    } elseif ($ratio >= 0.65 && $ratio < 0.85) {
        $classifications['moderate_portrait'][] = ['dim' => $dimKey, 'ratio' => $ratio, 'size' => $sizeKB];
    } elseif ($ratio < 0.65) {
        $classifications['extreme_portrait'][] = ['dim' => $dimKey, 'ratio' => $ratio, 'size' => $sizeKB];
    } elseif ($ratio > 1.02 && $ratio <= 1.20) {
        $classifications['slight_landscape'][] = ['dim' => $dimKey, 'ratio' => $ratio, 'size' => $sizeKB];
    } elseif ($ratio > 1.20 && $ratio <= 1.60) {
        $classifications['moderate_landscape'][] = ['dim' => $dimKey, 'ratio' => $ratio, 'size' => $sizeKB];
    } else {
        $classifications['extreme_landscape'][] = ['dim' => $dimKey, 'ratio' => $ratio, 'size' => $sizeKB];
    }
}

echo "\\nSuccessfully Analyzed Images: {$analyzedCount}\\n";
echo "Unreadable/Corrupted: {$corruptedCount}\\n";

echo "\\n--- [ 1. CLASSIFICATION BY ASPECT RATIO & ORIENTATION ] ---\\n";
$catNames = [
    'square' => 'مربعة تماماً (Square 1:1)',
    'slight_portrait' => 'طولي طفيف (Slight Portrait 4:5 - 3:4)',
    'moderate_portrait' => 'طولي واضح / معتدل (Moderate Portrait 2:3)',
    'extreme_portrait' => 'طولي زيادة / حاد (Extreme Portrait 9:16 / Banner)',
    'slight_landscape' => 'عرضي طفيف (Slight Landscape 4:3)',
    'moderate_landscape' => 'عرضي واضح / معتدل (Moderate Landscape 3:2)',
    'extreme_landscape' => 'عرضي زيادة / حاد (Extreme Landscape 16:9 / Wide)',
];

foreach ($classifications as $key => $items) {
    $cnt = count($items);
    $pct = $analyzedCount > 0 ? round(($cnt / $analyzedCount) * 100, 2) : 0;
    printf("%-50s : %6d images (%6.2f%%)\\n", $catNames[$key], $cnt, $pct);
}

echo "\\n--- [ 2. TOP 15 RESOLUTIONS (المقاسات الأكثر شيوعاً) ] ---\\n";
arsort($resolutions);
$i = 0;
foreach ($resolutions as $dim => $count) {
    $pct = $analyzedCount > 0 ? round(($count / $analyzedCount) * 100, 2) : 0;
    printf("%2d. %-15s : %6d images (%5.2f%%)\\n", ++$i, $dim, $count, $pct);
    if ($i >= 15) break;
}

echo "\\n--- [ 3. FORMATS DISTRIBUTION (أنواع وصيغ الصور) ] ---\\n";
arsort($formats);
foreach ($formats as $fmt => $count) {
    $pct = $analyzedCount > 0 ? round(($count / $analyzedCount) * 100, 2) : 0;
    printf("%-10s : %6d images (%5.2f%%)\\n", strtoupper($fmt), $count, $pct);
}

echo "\\n--- [ 4. FILE SIZE DISTRIBUTION (أحجام الملفات بالكيلوبايت) ] ---\\n";
foreach ($fileSizes as $bucket => $count) {
    $pct = $analyzedCount > 0 ? round(($count / $analyzedCount) * 100, 2) : 0;
    printf("%-20s : %6d images (%5.2f%%)\\n", $bucket, $count, $pct);
}
"""

sftp = client.open_sftp()
with sftp.file("/home/highest-ye/htdocs/highest-ye.store/analyze_images.php", "w") as f:
    f.write(php_analysis)
sftp.close()

code, out, err = r.run_remote_cmd(client, "cd /home/highest-ye/htdocs/highest-ye.store && php8.4 analyze_images.php && rm analyze_images.php")
print(f"OUT:\n{out}")

client.close()
