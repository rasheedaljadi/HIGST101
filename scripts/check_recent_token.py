import remote_ssh_helper as r

client = r.get_ssh_client()

php_test = r"""<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$tokens = DB::table('aliexpress_tokens')->orderBy('id', 'desc')->take(3)->get();
foreach ($tokens as $t) {
    echo "ID: {$t->id}, Account: {$t->account}, Created: {$t->created_at}, Updated: {$t->updated_at}\n";
}
"""

sftp = client.open_sftp()
with sftp.file("/home/highest-ye/htdocs/highest-ye.store/check_recent_token.php", "w") as f:
    f.write(php_test)
sftp.close()

code, out, err = r.run_remote_cmd(client, "cd /home/highest-ye/htdocs/highest-ye.store && php8.4 check_recent_token.php && rm check_recent_token.php")
print(f"OUTPUT:\n{out}")

client.close()
