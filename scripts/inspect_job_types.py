import remote_ssh_helper as r

client = r.get_ssh_client()
sftp = client.open_sftp()

php_test = """<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\\Contracts\\Console\\Kernel::class);
$kernel->bootstrap();

$jobs = \\DB::table('jobs')->select('queue', 'payload')->get();
$types = [];
foreach ($jobs as $j) {
    $data = json_decode($j->payload, true);
    $name = $data['displayName'] ?? 'Unknown';
    $types[$name] = ($types[$name] ?? 0) + 1;
}
arsort($types);
echo json_encode($types, JSON_PRETTY_PRINT) . PHP_EOL;
"""

with sftp.file("/home/highest-ye/htdocs/highest-ye.store/inspect_types.php", "w") as f:
    f.write(php_test)
sftp.close()

code, out, err = r.run_remote_cmd(client, "cd /home/highest-ye/htdocs/highest-ye.store && php8.4 inspect_types.php && rm inspect_types.php")
print(f"CODE: {code}")
print(f"OUT:\n{out}")

client.close()
