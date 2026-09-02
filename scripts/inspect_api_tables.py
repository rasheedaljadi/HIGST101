import remote_ssh_helper as r

client = r.get_ssh_client()

php_test = """<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\\Contracts\\Console\\Kernel::class);
$kernel->bootstrap();

use Illuminate\\Support\\Facades\\DB;
use Illuminate\\Support\\Facades\\Cache;
use Illuminate\\Support\\Facades\\Schema;

echo "=========================================================\\n";
echo "1. TABLES RELATED TO LOGS / ALIEXPRESS / API\\n";
echo "=========================================================\\n";
$tables = DB::select('SHOW TABLES');
$dbName = DB::getDatabaseName();
$col = "Tables_in_" . $dbName;

foreach ($tables as $t) {
    $tName = $t->$col;
    if (str_contains($tName, 'api') || str_contains($tName, 'log') || str_contains($tName, 'aliexpress') || str_contains($tName, 'sync')) {
        $count = DB::table($tName)->count();
        echo "Table: {$tName} (Rows: {$count})\\n";
    }
}

echo "\\n=========================================================\\n";
echo "2. CHECKING CACHE KEYS FOR RATE LIMITS & API STATS\\n";
echo "=========================================================\\n";
$circuitBreaker = Cache::get('aliexpress:api:ban_until', 0);
echo "Circuit Breaker Ban Until: " . ($circuitBreaker > time() ? date('Y-m-d H:i:s', $circuitBreaker) : 'NO BAN (HEALTHY ✅)') . "\\n";

echo "\\n=========================================================\\n";
echo "3. CHECKING STORAGE LOGS FOR ALIEXPRESS REQUESTS TODAY\\n";
echo "=========================================================\\n";
$logFiles = glob(storage_path('logs/*.log'));
foreach ($logFiles as $lf) {
    echo "Log: " . basename($lf) . " (" . filesize($lf) . " bytes)\\n";
}
"""

sftp = client.open_sftp()
with sftp.file("/home/highest-ye/htdocs/highest-ye.store/inspect_api_tables.php", "w") as f:
    f.write(php_test)
sftp.close()

code, out, err = r.run_remote_cmd(client, "cd /home/highest-ye/htdocs/highest-ye.store && php8.4 inspect_api_tables.php && rm inspect_api_tables.php")
print(f"OUT:\n{out}")

client.close()
