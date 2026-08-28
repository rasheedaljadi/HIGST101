import remote_ssh_helper as r

client = r.get_ssh_client()

php_test = r"""<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Http;

// Try to fetch AliExpress API doc from their internal API
$urls = [
    'https://open.aliexpress.com/handler/share/apidoc/getApi.json?path=aliexpress.ds.order.create&lang=en_US',
    'https://open.aliexpress.com/handler/share/apidoc/getApi.json?path=aliexpress.ds.order.create',
    'https://open.aliexpress.com/api/doc/detail?path=aliexpress.ds.order.create',
];

foreach ($urls as $url) {
    echo "=== Fetching: {$url} ===\n";
    try {
        $resp = Http::timeout(15)->get($url);
        $body = $resp->json();
        if (!empty($body)) {
            echo json_encode($body, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n\n";
        } else {
            echo "Status: " . $resp->status() . "\n";
            echo "Body (first 500 chars): " . substr($resp->body(), 0, 500) . "\n\n";
        }
    } catch (\Throwable $e) {
        echo "Error: " . $e->getMessage() . "\n\n";
    }
}

// Also: test with the API sandbox/tools endpoint
echo "=== API Tools endpoint ===\n";
try {
    $resp = Http::timeout(15)->get('https://open.aliexpress.com/handler/share/apidoc/getApiTools.json', [
        'path' => 'aliexpress.ds.order.create',
    ]);
    $body = $resp->json();
    if (!empty($body)) {
        echo json_encode($body, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n\n";
    }
} catch (\Throwable $e) {
    echo "Error: " . $e->getMessage() . "\n\n";
}
"""

sftp = client.open_sftp()
with sftp.file("/home/highest-ye/htdocs/highest-ye.store/fetch_ae_docs.php", "w") as f:
    f.write(php_test)
sftp.close()

code, out, err = r.run_remote_cmd(client, "cd /home/highest-ye/htdocs/highest-ye.store && php8.4 fetch_ae_docs.php && rm fetch_ae_docs.php")
print(f"OUTPUT:\n{out[:5000]}")
if err:
    print(f"ERR:\n{err[:1000]}")

client.close()
