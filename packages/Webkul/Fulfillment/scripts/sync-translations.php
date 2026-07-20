<?php

// Paths
$langDir = dirname(__DIR__) . '/src/Resources/lang';
$enFile = $langDir . '/en/app.php';
$arFile = $langDir . '/ar/app.php';

if (!file_exists($enFile)) {
    die("English translation file not found!\n");
}

$enTrans = include $enFile;

/**
 * Helper to flatten nested array to dot notation
 */
function flattenArray(array $array, string $prefix = ''): array
{
    $result = [];
    foreach ($array as $key => $value) {
        if (is_array($value)) {
            $result = array_merge($result, flattenArray($value, $prefix . $key . '.'));
        } else {
            $result[$prefix . $key] = $value;
        }
    }
    return $result;
}

// 1. Target Arabic Translation Array (custom translations)
$arTransRaw = [
    'manual_review_missing_source' => 'مراجعة يدوية مطلوبة لأمر الشراء #:po بسبب فقدان مصدر المورد.',
    'fulfillment_failed' => 'فشل تنفيذ أمر الشراء #:po. الخطأ: :error. مراجعة يدوية مطلوبة.',
    'status_check_failed' => 'فشل فحص حالة أمر الشراء #:po (المعرف الخارجي: :external). مراجعة يدوية مطلوبة.',
    'state_updated' => 'تم تحديث حالة أمر الشراء #:po من \':old\' إلى \':new\' (الحالة لدى المورد: :raw).',
    'fulfillment_completed' => 'اكتمل تنفيذ جميع أوامر الشراء.',
    'fulfillment_started' => 'بدأ التنفيذ: تم إرسال الطلبات إلى المورد.',

    'admin' => [
        'menu' => [
            'fulfillment' => 'إدارة تنفيذ الطلبات',
        ],
        'datagrid' => [
            'id' => 'معرف أمر الشراء',
            'order-id' => 'معرف الطلب',
            'provider' => 'المزود',
            'supplier-store' => 'متجر المورد',
            'cost' => 'تكلفة المورد',
            'state' => 'الحالة',
            'tracking-number' => 'رقم التتبع',
            'submitted-at' => 'تاريخ الإرسال',
            'internal-reference' => 'المرجع الداخلي',
            'external-order-id' => 'المعرف الخارجي',
            'attempts' => 'المحاولات',
            'last-error' => 'آخر خطأ',
            'view' => 'عرض التفاصيل',
            'retry' => 'إعادة المحاولة',
            'cancel' => 'إلغاء أمر الشراء',
            'refresh' => 'تحديث الحالة',
            'approve' => 'موافقة',
            'reject' => 'رفض',
        ],
        'dashboard' => [
            'success-rate' => 'معدل النجاح',
            'retry-rate' => 'معدل إعادة المحاولة',
            'avg-fulfillment-time' => 'متوسط وقت التنفيذ',
            'waiting-orders' => 'أوامر بالانتظار',
            'manual-review-orders' => 'بحاجة لمراجعة',
            'provider-health' => 'صحة المزود',
            'queue-backlog' => 'قائمة الانتظار',
            'success-rate-desc' => 'نسبة أوامر الشراء التي تم وضعها بنجاح',
            'retry-rate-desc' => 'نسبة الأوامر التي تطلبت أكثر من محاولة واحدة',
            'avg-fulfillment-desc' => 'منذ فاتورة العميل حتى وضع أمر المورد',
            'waiting-desc' => 'الأوامر قيد المعالجة أو الإرسال حالياً',
            'needs-review-desc' => 'الأوامر التي تحتاج إلى تدخل يدوي',
            'provider-health-desc' => 'معدل نجاح آخر 50 محاولة اتصال تلقائية',
            'queue-backlog-desc' => 'المهام المعلقة في الطابور الخلفي لإنشاء الأوامر',
        ],
        'states' => [
            'pending' => 'معلق',
            'submitting' => 'جاري الإرسال',
            'submitted' => 'تم وضعه',
            'shipped' => 'تم الشحن',
            'delivered' => 'تم التوصيل',
            'needs_manual_review' => 'بحاجة لمراجعة يدوية',
            'canceled' => 'ملغي',
            'awaiting_payment_to_supplier' => 'بانتظار الدفع للمورد',
        ],
        'actions' => [
            'confirm-cancel' => 'هل أنت متأكد من رغبتك في إلغاء أمر الشراء هذا؟ لا يمكن التراجع عن هذا الإجراء.',
            'confirm-override' => 'هل أنت متأكد من رغبتك في تخطي حالة أمر الشراء هذا؟',
            'reason-required' => 'السبب مطلوب (10 أحرف كحد أدنى).',
            'reason-placeholder' => 'أدخل سبب الإجراء الإداري...',
            'action-success' => 'تم تنفيذ العملية بنجاح.',
            'action-failed' => 'فشل تنفيذ العملية: :error',
            'approval-submitted' => 'تم تعليق العملية وتقديم طلب موافقة للمشرفين.',
            'approval-approved' => 'تمت الموافقة على الطلب وتنفيذه بنجاح.',
            'approval-rejected' => 'تم رفض طلب الموافقة.',
        ]
    ]
];

/**
 * Helper to write a locale file based strictly on the line-by-line structure of the English file.
 */
function writeLocaleFile(string $enPath, string $targetPath, array $translations): void
{
    $flatTrans = flattenArray($translations);
    $enLines = file($enPath);
    $targetLines = [];
    $stack = [];

    foreach ($enLines as $line) {
        $trimmed = trim($line);

        // 1. Detect array nesting starts: 'key' => [ or 'key' => array(
        if (preg_match('/^[\'"]([^\'"]+)[\'"]\s*=>\s*[\[]/', $trimmed, $matches)) {
            $stack[] = $matches[1];
            $targetLines[] = $line;
            continue;
        }

        // 2. Detect array nesting ends: ], or ]
        if ($trimmed === '],' || $trimmed === ']') {
            array_pop($stack);
            $targetLines[] = $line;
            continue;
        }

        // 3. Detect key-value lines: 'key' => 'value', or "key" => "value",
        if (preg_match('/^([\'"])([^\'"]+)([\'"]\s*=>\s*)([\'"])(.*)([\'"],?\s*)$/', $trimmed, $matches)) {
            $key = $matches[2];
            $fullKeyPath = implode('.', array_merge($stack, [$key]));

            if (isset($flatTrans[$fullKeyPath])) {
                $translatedValue = $flatTrans[$fullKeyPath];
                // Properly escape single or double quotes depending on the matches[4] quote type
                if ($matches[4] === "'") {
                    $escapedValue = str_replace("'", "\'", $translatedValue);
                    $escapedValue = str_replace("\\\\'", "\'", $escapedValue); // Prevent double escaping
                } else {
                    $escapedValue = str_replace('"', '\"', $translatedValue);
                    $escapedValue = str_replace('\\\\"', '\"', $escapedValue); // Prevent double escaping
                }
                
                // Reconstruct the line preserving original indentation and formatting
                $indentation = str_repeat(' ', strlen($line) - strlen(ltrim($line)));
                $targetLines[] = $indentation . $matches[1] . $key . $matches[3] . $matches[4] . $escapedValue . $matches[6] . "\n";
            } else {
                // If translation not found, keep the line as is
                $targetLines[] = $line;
            }
            continue;
        }

        // 4. Any other lines (comments, php header, return [, etc.)
        $targetLines[] = $line;
    }

    // Ensure directory exists
    $dir = dirname($targetPath);
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }

    // Write back
    file_put_contents($targetPath, implode('', $targetLines));
}

// Save Arabic first
writeLocaleFile($enFile, $arFile, $arTransRaw);
echo "Saved Arabic translations.\n";

// Loop through all directories under Resources/lang
$directories = glob($langDir . '/*' , GLOB_ONLYDIR);
foreach ($directories as $dir) {
    $locale = basename($dir);
    if ($locale === 'en' || $locale === 'ar') {
        continue;
    }

    $targetFile = $dir . '/app.php';
    $existingTrans = [];
    if (file_exists($targetFile)) {
        $existingTrans = include $targetFile;
    }

    // Merge missing keys with existing ones
    $merged = array_replace_recursive($enTrans, $existingTrans);

    // Save with exact structure check match
    writeLocaleFile($enFile, $targetFile, $merged);
    echo "Synchronized translations for: {$locale}\n";
}

echo "All translations synchronized successfully!\n";
