import remote_ssh_helper as r

client = r.get_ssh_client()
sftp = client.open_sftp()

php_test = """<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\\Contracts\\Console\\Kernel::class);
$kernel->bootstrap();

try {
    echo "Bootstrap completed successfully.\\n";

    $methods = \\Webkul\\Payment\\Facades\\Payment::getPaymentMethods();
    echo "Payment methods configured: " . count($methods) . "\\n";
    foreach ($methods as $m) {
        echo "  - Class: " . get_class($m) . " | Title: " . $m->getTitle() . "\\n";
    }

    echo "\\nSupported payment methods:\\n";
    $supported = \\Webkul\\Payment\\Facades\\Payment::getSupportedPaymentMethods();
    echo json_encode($supported, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\\n";

} catch (\\Throwable $e) {
    echo "FATAL: " . $e->getMessage() . "\\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\\n";
    echo $e->getTraceAsString() . "\\n";
}
"""

with sftp.file("/home/highest-ye/htdocs/highest-ye.store/test_payment_err.php", "w") as f:
    f.write(php_test)
sftp.close()

code, out, err = r.run_remote_cmd(client, "cd /home/highest-ye/htdocs/highest-ye.store && php8.4 test_payment_err.php && rm test_payment_err.php")
print(f"CODE: {code}")
print(f"OUT:\n{out}")
print(f"ERR:\n{err}")

client.close()
