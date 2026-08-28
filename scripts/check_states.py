import remote_ssh_helper as r

client = r.get_ssh_client()
sftp = client.open_sftp()

php_test = r"""<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== COUNTRY STATES IN DB ===\n";
$states = DB::table('country_states')->where('country_code', 'YE')->get();
foreach ($states as $s) {
    $trans = DB::table('country_state_translations')->where('country_state_id', $s->id)->get();
    $transNames = $trans->pluck('default_name', 'locale')->toArray();
    echo "  - ID: {$s->id} | Code: {$s->code} | Default: {$s->default_name} | Translations: " . json_encode($transNames, JSON_UNESCAPED_UNICODE) . "\n";
}
"""

with sftp.file("/home/highest-ye/htdocs/highest-ye.store/test_states.php", "w") as f:
    f.write(php_test)
sftp.close()

code, out, err = r.run_remote_cmd(client, "cd /home/highest-ye/htdocs/highest-ye.store && php8.4 test_states.php && rm test_states.php")
print(f"CODE: {code}")
print(f"OUT:\n{out}")

client.close()
